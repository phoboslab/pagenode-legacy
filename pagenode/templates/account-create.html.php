<?php $title = 'Create User'; include(PN_TEMPLATES.'head.html.php') ?>

<form action="<?=PN_ABS?>admin/account/create" method="post">
	<div class="row form-row">
		<div class="four columns">&nbsp;</div>
		<div class="four columns">
			<h1>Pagenode</h1>
			<p>
				Create the first User Account
			</p>

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
			<?php if ($error === User::E_PASSWORD_TOO_SHORT) {?>
				<p class="notice warn">Password too short.</p>
			<?php } ?>
			<p class="hint">Minimum 8 characters</p>
		</div>
	</div>
	<div class="row form-row">
		<div class="four columns">&nbsp;</div>
		<div class="four columns">
			<label>&nbsp;</label>
			<button role="submit" class="button primary full-width">Create Account</button>
		</div>
	</div>
</form>

<?php include(PN_TEMPLATES.'foot.html.php') ?>
