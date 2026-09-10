<style>
	<?php if (empty($logged_in)) : ?>
	body {
		padding-top: 20px;
	}
	<?php endif; ?>

	.signup-section-title {
		border-left: 5px solid transparent;
		border-radius: 4px;
		font-size: 16px;
		font-weight: bold;
		margin: 0 0 15px;
		padding: 10px;
	}

	.signup-required-note {
		margin: -5px 0 15px;
	}

	.signup-section {
		margin-top: 20px;
	}

	.signup-section-title.callout-primary {
		background: #f5f8fc;
		border-left-color: #337ab7;
	}

	.signup-section-title.callout-info {
		background: #f4f9fb;
		border-left-color: #5bc0de;
	}

	.signup-login-link {
		margin: 15px 0 0;
	}

	.signup-optional-label {
		font-weight: normal;
	}

	.signup-name-row {
		margin-left: -7px;
		margin-right: -7px;
	}

	.signup-name-row > div {
		padding-left: 7px;
		padding-right: 7px;
	}
</style>
<div class="row">
	<div class="col-md-4 col-md-offset-4 well signup-form-container">
		<?php $attributes = array("name" => "signupform");
		if (empty($code)) {
			echo form_open("signup", $attributes);
		} else {
			echo form_open("signup?code=" . $code, $attributes);
		} ?>
		<?php if (empty($client) && empty($code)) : ?>
			<img height="50px" class="center-block" src="<?php echo base_url("assets/img/header_logo.svg") . '?v=' . $this->config->item('header_logo_version'); ?>" alt="">
		<?php endif; ?>
		<?php if (!empty($client)) : ?>
			<h3 class="text-center"><?php echo $client['display_name']; ?></h3>
		<?php endif; ?>

		<div class="signup-section">
			<h4 class="signup-section-title callout-primary"><?php echo $this->lang->line('gp_create_account'); ?></h4>

			<div class="form-group">
				<label for="email"><?php echo $this->lang->line('gp_email'); ?> <span class="text-danger">*</span></label>
				<input class="form-control" id="email" name="email" placeholder="" required type="text"
					   value="<?php echo set_value('email'); ?>"/>
				<span class="text-danger"><?php echo form_error('email'); ?></span>
			</div>

			<div class="form-group">
				<label for="username"><?php echo $this->lang->line('gp_username'); ?> <span class="text-danger">*</span></label>
				<input class="form-control" id="username" name="username" placeholder="" required type="text" value="<?php echo set_value('username'); ?>" />
				<span class="text-danger"><?php echo form_error('username'); ?></span>
			</div>

			<div class="form-group">
				<label for="password"><?php echo $this->lang->line('gp_password'); ?> <span class="text-danger">*</span></label>
				<input class="form-control" id="password" name="password" placeholder="" required type="password"/>
				<span class="text-danger"><?php echo form_error('password'); ?></span>
			</div>

			<div class="form-group">
				<label
					for="cpassword"><?php echo $this->lang->line('gp_confirm'); ?>
					&nbsp;<?php echo $this->lang->line('gp_password'); ?> <span class="text-danger">*</span></label>
				<input class="form-control" id="cpassword" name="cpassword" placeholder="" required type="password"/>
				<span class="text-danger"><?php echo form_error('cpassword'); ?></span>
			</div>

		</div>

		<div class="signup-section">
			<h4 class="signup-section-title callout-info"><?php echo $this->lang->line('gp_user_details'); ?> <span class="text-muted">(<?php echo $this->lang->line('gp_optional'); ?>)</span></h4>

			<div class="row signup-name-row">
				<div class="col-sm-6">
					<div class="form-group">
						<label class="signup-optional-label" for="fname"><?php echo $this->lang->line('gp_first_name'); ?></label>
						<input class="form-control" id="fname" name="fname" placeholder="" type="text"
							   value="<?php echo set_value('fname'); ?>"/>
						<span class="text-danger"><?php echo form_error('fname'); ?></span>
					</div>
				</div>

				<div class="col-sm-6">
					<div class="form-group">
						<label class="signup-optional-label" for="lname"><?php echo $this->lang->line('gp_last_name'); ?></label>
						<input class="form-control" id="lname" name="lname" placeholder="" type="text"
							   value="<?php echo set_value('lname'); ?>"/>
						<span class="text-danger"><?php echo form_error('lname'); ?></span>
					</div>
				</div>
			</div>

			<div class="form-group">
				<label class="signup-optional-label" for="organization"><?php echo $this->lang->line('gp_organization'); ?></label>
				<input class="form-control" id="organization" name="organization" placeholder="" type="text"
					   value="<?php echo set_value('organization'); ?>"/>
				<span class="text-danger"><?php echo form_error('organization'); ?></span>
			</div>

			<div class="form-group">
				<label class="signup-optional-label" for="phone"><?php echo rtrim(lang('create_user_phone_label'), ':'); ?></label>
				<input class="form-control" id="phone" name="phone" placeholder="" type="text"
					   value="<?php echo set_value('phone'); ?>"/>
				<span class="text-danger"><?php echo form_error('phone'); ?></span>
			</div>
		</div>

		<?php if (!empty($terms)) : ?>
			<div class="checkbox">
				<label>
					<input name="terms" placeholder="" required type="checkbox"> <?php echo $terms; ?> <span class="text-danger">*</span>
				</label>
				<span class="text-danger"><?php echo form_error('terms'); ?></span>
			</div>
		<?php endif; ?>
		<p class="text-muted signup-required-note"><span class="text-danger">*</span> <?php echo $this->lang->line('gp_required_fields'); ?></p>

		<div class="form-group">
			<button name="submit" type="submit"
					class="btn btn-primary"><?php echo $this->lang->line('gp_register'); ?></button>
			<button name="cancel" type="reset"
					class="btn btn-default"><?php echo $this->lang->line('gp_cancel'); ?></button>
		</div>
		<?php echo form_close(); ?>
		<?php echo $this->session->flashdata('message'); ?>

		<p class="text-center signup-login-link">
			<?php echo $this->lang->line('gp_already_registered'); ?> <a href="<?php echo site_url('/auth/login') ?>"><?php echo $this->lang->line('gp_login'); ?> <?php echo $this->lang->line('gp_here'); ?></a>
		</p>
	</div>
</div>
