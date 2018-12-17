<?php $title = 'Login'; include(PN_TEMPLATES.'head.html.php') ?>

<form action="<?=PN_ABS?>admin/account/login" method="post">
	<div class="row form-row">
		<div class="four columns">&nbsp;</div>
		<div class="four columns">
			<h1>Pagenode</h1>
			<?php if ($error === 'invalidLogin') {?>
				<p class="notice warn">Invalid Email or password.</p>
			<?php } ?>

			<label>Email</label>
			<input 
				type="email" required
				name="email" class="full-width" 
				value="<?= f($_POST['email'] ?? '');?>"/>
		</div>
	</div>
	<div class="row form-row">
		<div class="four columns">&nbsp;</div>
		<div class="four columns">
			<label>Password</label>
			<input 
				type="password" required
				name="password" class="full-width"/>
		</div>
	</div>
	<div class="row form-row">
		<div class="four columns">&nbsp;</div>
		<div class="four columns">
			<label>&nbsp;</label>
			<button role="submit" class="button primary full-width">Login</button>
		</div>
	</div>
</form>

<?php include(PN_TEMPLATES.'foot.html.php') ?>
