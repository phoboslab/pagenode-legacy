var pn = {
	abs: document.currentScript.dataset.path
};

pn.$ = function(s) {
	return Array.prototype.slice.call(document.querySelectorAll(s));
};


pn.xhr = function(method, url, params, body, callback) {
	let qs = [];
	for (let p in params) {
		qs.push(encodeURIComponent(p) + '=' + encodeURIComponent(params[p]));
	}

	let request = new XMLHttpRequest();
	request.onload = function() {
		if (callback) {
			let parsed;
			try {
				parsed = JSON.parse(request.responseText); 
			}
			catch(err) {
				return callback('parse_error', null);
			}

			if (request.status === 200) {
				callback(null, parsed);
			}
			else {
				callback(request.status, parsed);
			}
		}
	};
	
	request.onerror = function(err) {
		if (callback) {
			callback(err, null);
		}
	};
	
	request.open(method, url + (qs.length ? ('?' + qs.join('&')) : ''));
	request.send(body);
};

pn.xhr.post = function(url, params, body, callback) {
	pn.xhr('POST', url, params, body, callback);
};

pn.xhr.get = function(url, params, callback) {
	pn.xhr('GET', url, params, null, callback);
};


pn.escapeHTML = function(s) {
	return s
		.replace(/&/g, '&amp;')
		.replace(/</g, '&lt;')
		.replace(/>/g, '&gt;')		
		.replace(/"/g, '&quot;');
};


pn.getCaretCoordinates = function(el, position) {
	var properties = [
		'direction', 'boxSizing', 'width', 'height',
		'borderTopWidth', 'borderRightWidth',
		'borderBottomWidth','borderLeftWidth', 'borderStyle',
		'paddingTop','paddingRight','paddingBottom','paddingLeft',
		'fontStyle', 'fontVariant', 'fontWeight', 'fontStretch', 'fontSize',
		'fontSizeAdjust', 'lineHeight', 'fontFamily',
		'textAlign', 'textTransform', 'textIndent', 'textDecoration', 
		'letterSpacing', 'wordSpacing', 'tabSize', 'MozTabSize'
	];

	var div = document.createElement('div');
	document.body.appendChild(div);

	var style = div.style;
	var computed = window.getComputedStyle(el);
	var isInput = el.nodeName === 'INPUT';

	style.whiteSpace = 'pre-wrap';
	if (!isInput) {
		style.wordWrap = 'break-word';
	}

	style.position = 'absolute';
	style.visibility = 'hidden';
	for (var i = 0; i < properties.length; i++) {
		var name = properties[i];
		style[name] = computed[name];
	}

	div.textContent = el.value.substring(0, position);
	if (isInput) {
		div.textContent = div.textContent.replace(/\s/g, '\u00a0');
	}

	var span = document.createElement('span');
	span.textContent = el.value.substring(position) || '.';
	div.appendChild(span);

	var coordinates = {
		top: span.offsetTop + parseInt(computed['borderTopWidth']),
		left: span.offsetLeft + parseInt(computed['borderLeftWidth']),
		height: parseInt(computed['lineHeight'])
	};

	div.remove();
	return coordinates;
};


pn.enableAutoHeight = function(el) {
	var adjust = function() {
		var previousMargin = el.style.marginBottom;
		el.style.marginBottom = el.style.height;
		el.style.height = '1px';
		el.style.height = el.scrollHeight + 'px';
		el.style.marginBottom = previousMargin;	
	};

	el.addEventListener('input', adjust);
	adjust();
};


pn.manageUndoHistory = function(el) {
	var history = {
		lastChange: 0,
		lastRecord: 0,
		stack: [{
			selection: {start: el.selectionStart, end: el.selectionEnd},
			value: el.value,
			scroll: el.getBoundingClientRect().y
		}],
		pointer: 0,
		timeout: 0
	};

	var record = function() {
		if (el.value === history.stack[history.pointer].value) {
			return;
		}

		var now = Date.now();
		if (now - history.lastRecord < 1000) {
			clearTimeout(history.timeout);
			history.timeout = setTimeout(record.bind(this, el), 250);
			return;
		}

		if (history.pointer !== history.stack.length - 1) {
			history.stack.splice(history.pointer + 1);
		}

		if (history.stack.length > 1000) {
			// Remove oldest change entry, but keep the original state
			history.stack.splice(1, 1);
		}

		history.stack.push({
			selection: {start: el.selectionStart, end: el.selectionEnd},
			value: el.value,
			scroll: document.documentElement.scrollTop
		});
		history.pointer = history.stack.length - 1;
		history.lastRecord = now;
	};

	var restore = function(dir) {
		var np = history.pointer + dir;
		if (np >= history.stack.length || np < 0) {
			return;
		}

		history.pointer = np;
		var entry = history.stack[history.pointer];
		el.value = entry.value;
		el.setSelectionRange(entry.selection.start, entry.selection.end);

		var pos = pn.getCaretCoordinates(el, entry.selection.start);
		document.documentElement.scrollTop =
			pos.top + 
			el.getBoundingClientRect().y + 
			document.documentElement.scrollTop - 
			window.innerHeight / 4;
	};

	el.addEventListener('keydown', function(ev) {
		if (ev.key === 'z' && ev.ctrlKey) {
			ev.preventDefault();
			restore(-1);
		}
		else if (ev.key === 'y' && ev.ctrlKey) {
			ev.preventDefault();
			restore(+1);
		}
	});
	el.addEventListener('input', record);
	return el;
};


pn.enableTabIndent = function(el) {
	var handleTabPress = function(ev) {
		ev.preventDefault();
		var start = el.selectionStart;
		var end = el.selectionEnd;
		var text = el.value;
		var sel = text.substring(start, end);
		var re, count;

		if (ev.shiftKey) {
			re = /^\t/gm;
			var sm = sel.match(re);
			if (!sm) { return; }
			count = -sm.length;
			el.value = text.substring(0, start) + sel.replace(re, '') + text.substring(end);
		}
		else {
			re = /^/gm;
			count = sel.match(re).length;
			el.value = text.substring(0, start) + sel.replace(re, '\t') + text.substring(end);
		}

		el.selectionStart = start === end ? end + count : start;
		el.selectionEnd = end + count;

		var inputEvent = new Event('input', {bubbles: true, cancelable: true});
		el.dispatchEvent(inputEvent);
		return;
	};
	
	el.addEventListener('keydown', function(ev){
		if (ev.key === 'Tab') {
			handleTabPress(ev);
		}
	});
	return el;
};


pn.toolbarCreate = function(buttons) {
	var toolbar = document.createElement('div');
	toolbar.classList.add('input-toolbar');

	var click = function(def, ev) {
		ev.preventDefault();
		var el = toolbar._pnCurrentInputElement;
		var text = el.value;
		var start = el.selectionStart;
		var end = el.selectionEnd;
		var selected = text.substring(start, end);
		var space = '';
		var extraLength = 0;

		if (selected.length > 1 && selected.charAt(selected.length-1) === ' ') {
			selected = selected.substring(0, selected.length - 1);
			space = ' ';
			extraLength = -1;
		}

		toolbar.style.display = 'none';

		if (def.callback) {
			def.callback(selected, toolbar, replace.bind(this, start, end, space));
		}
		else {
			replace(start, end, space, (def.before||'') + selected + (def.after||''));
		}
	};

	var replace = function(start, end, space, replacement) {
		var el = toolbar._pnCurrentInputElement;
		var text = el.value;
		el.value = text.substring(0, start) + replacement + space + text.substring(end);
		el.focus();
		el.setSelectionRange(start, start + replacement.length);

		var inputEvent = new Event('input', {bubbles: true, cancelable: true});
		el.dispatchEvent(inputEvent);		
	};

	var preventHide = function() { toolbar._pnCurrentInputElement._pnPreventHide = true; }
	var enableHide = function() { toolbar._pnCurrentInputElement._pnPreventHide = false; }
	
	for (var name in buttons) {
		var def = buttons[name];
		var button = document.createElement('a');
		button.innerHTML = def.icon;
		button.title = name;
		button.addEventListener('mousedown', preventHide);
		button.addEventListener('click', click.bind(this, def));
		button.addEventListener('mouseup', enableHide);
		toolbar.appendChild(button);
	}

	document.body.appendChild(toolbar);
	return toolbar;
};


pn.toolbarAttach = function(el, toolbar) {
	if (this instanceof HTMLElement) {
		toolbar = this;
	}

	var show = function() {
		var selPos = pn.getCaretCoordinates(el, el.selectionStart);
		var elPos = el.getBoundingClientRect();
		var doc = document.documentElement;

		toolbar.style.display = 'block';
		var rect = toolbar.getBoundingClientRect();
		
		toolbar.style.left = doc.scrollLeft + elPos.x + selPos.left + 'px';
		toolbar.style.top = doc.scrollTop + elPos.y - 40  + selPos.top + 'px';
		toolbar._pnCurrentInputElement = el;
	};

	var hide = function() {
		toolbar.style.display = 'none';
	};

	el.addEventListener('blur', function(){
		if (!el._pnPreventHide) {
			hide();
		}
	});

	el.addEventListener('input', hide);

	el.addEventListener('mouseup', function(ev) {
		if (el.selectionStart !== el.selectionEnd) {
			show();
		}
		else {
			hide();
		}
	});

	return el;
};


pn.showInputOverlay = function(x, y, title, value, callback) {
	var div = document.createElement('div');
	div.classList.add('input-overlay');

	var w = 280;
	div.style.left = (x + w < window.innerWidth ? x : window.innerWidth - w) + 'px';
	div.style.top = (y - 48) + 'px';

	var label = document.createElement('label');
	label.innerText = title;

	var input = document.createElement('input');
	input.type = 'text';
	input.value = value;

	var selectFileAction = document.createElement('span');
	selectFileAction.textContent = 'Select File';
	selectFileAction.classList.add('action');
	selectFileAction.addEventListener('mouseup', function(){
		setTimeout(function(){
			pn.showAssetsSelectOverlay('*.*', callback);	
		}, 1);
		div.remove();
	});

	div.appendChild(selectFileAction);
	div.appendChild(label);
	div.appendChild(input);
	document.body.appendChild(div);

	var cancel = function() {
		document.removeEventListener('mouseup', cancel);
		div.remove();
	}

	var handleKey = function(ev) {
		if (ev.key === 'Enter') {
			var value = input.value;
			div.remove();
			callback(value);
		}
		else if (ev.key === 'Escape') {
			cancel();
		}
	};

	input.addEventListener('keyup', handleKey);
	document.addEventListener('mouseup', cancel);

	input.focus();
	input.select();
};


pn.showAssetsSelectOverlay = function(q, callback) {
	var iframe = document.createElement('iframe');
	iframe.classList.add('assets-overlay');
	iframe.src = pn.abs + 'admin/assets?q=' + encodeURIComponent(q);
	document.body.appendChild(iframe);

	var select = function(ev) {
		if (ev.data.type === 'select-path') {
			callback(ev.data.param);
			remove();
		}
	};

	var remove = function() {
		document.removeEventListener('mouseup', cancel);
		window.removeEventListener('message', select);

		iframe.remove();
	};

	var cancel = function() {
		remove();
	}

	document.addEventListener('mouseup', cancel);
	window.addEventListener('message', select);
};


pn.showAssetsUploadOverlay = function(files, callback) {
	var div = document.createElement('div');
	div.classList.add('fullscreen-overlay');
	div.classList.add('loader');

	var info = document.createElement('div');
	var hasError = false;
	info.innerHTML = 'Uploading ' 
		+ files.length 
		+ (files.length === 1 ? ' file' : ' files')
		+ '<br/>';

	var next = function(index, uploaded) {
		if (index === files.length) {
			if (!hasError) {
				div.remove();
			}
			else {
				div.addEventListener('click', function(){
					div.remove();
				});
			}
			callback(uploaded);
			return;
		}

		var file = files[index];
		info.innerHTML += '<br/>' + pn.escapeHTML(file.name) + '… ';

		var data = new FormData();
		data.append('file', file);

		pn.xhr.post(pn.abs + 'admin/assets/upload', {}, data, function(err, res) {
			if (err || !res || !res.success) {
				info.innerHTML += 
					'<span class="notice warn">' +
						pn.escapeHTML((res && res.error) || 'error') +
					'</span>';
				hasError = true;
			}
			else {
				info.innerHTML += 'done';
				uploaded.push(res.url);
			}
			next(index + 1, uploaded);
		});
	};

	div.appendChild(info);
	document.body.appendChild(div);

	next(0, []);
};

pn.dropUpload = function(el, callback) {
	var isFile = function(ev) {
		var dt = ev.dataTransfer;
		return dt.types && dt.types.indexOf('Files') !== -1;
	};
	
	var show = function(ev) {
		if (isFile(ev)) {
			ev.preventDefault();
			el.classList.add('drop-active');
			return false;
		}
	};

	var hide = function(ev) {
		el.classList.remove('drop-active');
		return false;
	};

	var drop = function(ev) {
		if (isFile(ev)) {
			el.classList.remove('drop-active');
			ev.preventDefault();
			pn.showAssetsUploadOverlay(ev.dataTransfer.files, callback);
			return false;
		}
	};

	el.addEventListener('dragover', show);
	el.addEventListener('dragend', hide);
	el.addEventListener('dragout', hide);
	el.addEventListener('dragleave', hide);
	el.addEventListener('drop', drop);
};

pn.markdownDropUpload = function(el) {
	pn.dropUpload(el, function(uploaded) {
		var text = el.value;
		var start = el.selectionStart;
		var end = el.selectionEnd;
		var selected = text.substring(start, end) || 'title';

		var replacements = [];
		for (var i = 0; i < uploaded.length; i++) {
			var url = uploaded[i];
			if (url.match(/\.(png|jpe?g|gif)$/)) {
				replacements.push('!['+selected+']('+url+')');
			}
			else {
				replacements.push('['+selected+']('+url+')');
			}
		}

		var r = replacements.join('\n');
		el.value = text.substring(0, start) + r + text.substring(end);
		el.focus();
		el.setSelectionRange(start, start + r.length);

		var inputEvent = new Event('input', {bubbles: true, cancelable: true});
		el.dispatchEvent(inputEvent);	
	});
	return el;
};

pn.setTargetUrl = function(selector, value) {
	pn.$(selector).map(function(target){
		if (target.nodeName === 'INPUT') {
			target.value = value;
		}
		else if (target.nodeName === 'IMG') {
			target.src = value;
			target.removeAttribute('width');
			target.removeAttribute('height');
		}
		return target;
	});
}

pn.formDropUpload = function(el) {
	var setUrl = function(uploaded) {
		if (uploaded.length) {
			pn.setTargetUrl(el.dataset.target, uploaded[0]);
		}
	};
	pn.dropUpload(el, setUrl);

	el.addEventListener('click', function(ev){
		var input = document.createElement('input');
		input.type = 'file';
		input.addEventListener('change', function(ev) {
			pn.showAssetsUploadOverlay(this.files, setUrl);
			input.remove();
		});
		input.click();

		ev.preventDefault();
		return false;
	});

	return el;
};

pn.formSelectFile = function(el) {
	el.addEventListener('click', function(ev){
		var q = el.dataset.q || '*.*';
		pn.showAssetsSelectOverlay(q, function(url) {
			pn.setTargetUrl(el.dataset.target, url);
		});

		ev.preventDefault();
		return false;
	});
	
	return el;
}

pn.postMessageOnClick = function(el) {
	el.addEventListener('click', function(ev){
		var el = ev.currentTarget;
		var msg = {type: el.dataset.type, param: el.dataset.param};
		window.parent.postMessage(msg, window.location.origin);
	});
	return el;
};

pn.clearValue = function(el) {
	setTimeout(function(){el.value = '';}, 1);
	return el;
};

pn.resizeItems = function(el) {
	var reflow = function(ev) {
		var containerWidth = el.clientWidth;
		var itemStyle = window.getComputedStyle(el.children[0]);
		var itemWidth = parseInt(el.dataset.itemMaxWidth);
		var itemMargin = 
			+ parseFloat(itemStyle.marginLeft) 
			+ parseFloat(itemStyle.marginRight);
		
		var itemsPerRow = Math.ceil(containerWidth / (itemWidth + itemMargin));
		var newWidth = Math.floor((containerWidth / itemsPerRow) - itemMargin);
		
		for (var i = 0; i < el.children.length; i++) {
			el.children[i].style.width = newWidth + 'px';
			el.children[i].style.height = newWidth + 'px';
		}
	};

	window.addEventListener('resize', reflow);
	reflow();
	return el;
};





pn.markdownToolbar = (function(){
	var createLink = function(s, toolbar, replace) {
		var x = parseInt(toolbar.style.left);
		var y = parseInt(toolbar.style.top);

		
		if (s.match(/^https?:\/\//)) {
			pn.showInputOverlay(x, y, 'Link Title', '', function(text){
				replace('['+text+']('+s+')');
			});
		}
		else {
			var value = 'http://';
			var existingLink = s.match(/\[(.*?)\]\((.*?)\)/);
			if (existingLink) {
				s = existingLink[1];
				value = existingLink[2];
			}

			pn.showInputOverlay(x, y, 'Link URL', value, function(url){
				replace('['+s+']('+url+')');
			});
		}
	};

	var createImage = function(s, toolbar, replace) {
		var x = parseInt(toolbar.style.left);
		var y = parseInt(toolbar.style.top);

		pn.showAssetsSelectOverlay('*.{jpg,jpeg,png,gif}', function(url){
			if (url) {
				replace('!['+s+']('+url+')');
			}
		});
	};

	return pn.toolbarCreate({
		Headline: {icon: 'H1', before: '\n# '},
		Subheadline: {icon: 'H2', before: '\n## '},
		Subline: {icon: 'H3', before: '\n### '},
		Bold: {icon: '<strong>B</strong>', before: '**', after: '**'},
		Italic: {icon: '<em>I</em>', before: '*', after: '*'},
		Link: {icon: '↪', callback: createLink},
		Image: {icon: '◩', callback: createImage},
		Code: {icon: '{}', before: '```', after: '```'}
	});
})();


pn.$('.resize-items')
	.map(pn.resizeItems);

pn.$('.auto-clear')
	.map(pn.clearValue);

pn.$('.post-message')
	.map(pn.postMessageOnClick);

pn.$('textarea')
	.map(pn.manageUndoHistory)
	.map(pn.enableTabIndent);

pn.$('textarea.auto-height')
	.map(pn.enableAutoHeight);

pn.$('.drop-upload')
	.map(pn.formDropUpload);

pn.$('.select-file')
	.map(pn.formSelectFile);

pn.$('textarea.markdown')
	.map(pn.toolbarAttach, pn.markdownToolbar)
	.map(pn.markdownDropUpload);
