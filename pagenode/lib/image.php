<?php

class Image {
	public $path = null;
	public $valid = false;
	public $width, $height, $type;

	public $backgroundColor = 0x00000000;
	
	protected $image = null;
	protected $exif = null;
	protected $exifRotated = false;

	const RESIZE_IGNORE = 1;
	const RESIZE_COVER = 2;
	const RESIZE_CONTAIN = 3;
	const RESIZE_CROP = 4;
	
	public function __construct($path) {
		$this->path = $path;
		list($this->width, $this->height, $this->type) = @getImageSize($path);

		if ($this->type === IMAGETYPE_JPEG) {
			$this->image = imageCreateFromJPEG($path);
			$this->valid = true;
		} 
		else if ($this->type === IMAGETYPE_PNG) {
			$this->image = imageCreateFromPNG($path);
			$this->valid = true;
		}
		else if ($this->type === IMAGETYPE_GIF) {
			$this->image = imageCreateFromGIF($path);
			$this->valid = true;
		}
	}
	
	public function resize($dstWidth, $dstHeight, $mode = self::RESIZE_COVER) {
		if (!$this->image) {
			return null;
		}
		
		$srcX = 0;
		$srcY = 0;
		$srcWidth = $this->width;
		$srcHeight = $this->height;

		$dstX = 0;
		$dstY = 0;
		$imageWidth = $dstWidth;
		$imageHeight = $dstHeight;

		$srcAspect = $srcWidth / $srcHeight;
		$dstAspect = $dstWidth / $dstHeight;
		$zoom = $srcAspect / $dstAspect;
		
		if ($mode === self::RESIZE_COVER) {
			if ($srcAspect > $dstAspect) {
				$srcX = ($srcWidth - $srcWidth / $zoom) / 2;
				$srcWidth /= $zoom;
			}
			else {
				$srcY = ($srcHeight - $srcHeight * $zoom) / 2;
				$srcHeight *= $zoom;
			}
		}
		else if ($mode === self::RESIZE_CROP) {
			if ($srcAspect > $dstAspect) {
				$dstHeight /= $zoom;
				$imageHeight /= $zoom;
			}
			else {
				$dstWidth *= $zoom;
				$imageWidth *= $zoom;
			}
		}
		else if ($mode === self::RESIZE_CONTAIN) {
			if ($srcAspect > $dstAspect) {
				$dstY = ($dstHeight - $dstHeight / $zoom) / 2;
				$dstHeight /= $zoom;
			}
			else {
				$dstX = ($dstWidth - $dstWidth * $zoom) / 2;
				$dstWidth *= $zoom;
			}
		}
		
		$resized = imageCreateTrueColor($imageWidth, $imageHeight);

		if ($mode === self::RESIZE_CONTAIN) {
			// Convert RGBA into GDLib ARGB with inverted alpha
			$colorARGB = 
				(($this->backgroundColor >> 8) & 0x00ffffff) | 
				((0xff - ($this->backgroundColor & 0xff)) << 24);
			imageFill($resized, 0, 0, $colorARGB);
		}

		imageCopyResampled($resized, $this->image, $dstX, $dstY, $srcX, $srcY, $dstWidth, $dstHeight, $srcWidth, $srcHeight);
		imageDestroy($this->image);

		$this->width = $dstWidth;
		$this->height = $dstHeight;
		$this->image = $resized;

		return $this;
	}

	public function sharpen() {
		if (function_exists('imageconvolution')) {
			$sharpenMatrix = [[-1,-1,-1], [-1,16,-1],  [-1,-1,-1]];
			imageConvolution($this->image, $sharpenMatrix, 8, 0);
		}

		return $this;
	}

	public function writePNG($path) {
		if (!$this->image) {
			return false;
		}

		imageSaveAlpha($this->image, true);
		return @imagePNG($this->image, $path);
	}

	public function writeJPEG($path, $quality = 90) {
		if (!$this->image) {
			return false;
		}

		return @imageJPEG($this->image, $path, $quality);
	}

	public function destroy() {
		if (!$this->image) {
			return false;
		}

		imageDestroy($this->image);
	}

	public function rotateToExifOrientation() {
		if ($this->type !== IMAGETYPE_JPEG) {
			return $this;
		}

		if (!$this->exif && function_exists('exif_read_data')) {
			$this->exif = exif_read_data($this->path);
		}

		if (!$this->exif || empty($this->exif['Orientation']) || $this->exifRotated) {
			return $this;
		}

		$this->exifRotated = true;
		$flip = false;
		$rotate = 0;

		switch ($this->exif['Orientation']) {
			case 2: $flip =  true; $rotate =   0; break;
			case 3:	$flip = false; $rotate = 180; break;
			case 4: $flip =  true; $rotate = 180; break;
			case 5: $flip =  true; $rotate = 270; break;
			case 6: $flip = false; $rotate = 270; break;
			case 7: $flip =  true; $rotate =  90; break;
			case 8: $flip = false; $rotate =  90; break;
			default: break;
		}

		if ($rotate !== 0) {
			$this->image = imageRotate($this->image, $rotate, 0);
			$this->width = imageSX($this->image);
			$this->height = imageSY($this->image);
		}
		if ($flip) {
			$mirrored = imageCreateTrueColor($this->width, $this->height);
			imageCopyResampled($mirrored, $this->image, 0, 0, $this->width, 0, $this->width, $this->height, $this->width, $this->height);
			imageDestroy($this->image);
			$this->image = $mirrored;
		}

		return $this;
	}
}
