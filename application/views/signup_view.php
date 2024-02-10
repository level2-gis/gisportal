<div class="row">
	<div class="col-md-4 col-md-offset-4 well">
		<?php $attributes = array("name" => "signupform");
		if (empty($code)) {
			echo form_open("signup", $attributes);
		} else {
			echo form_open("signup?code=" . $code, $attributes);
		} ?>
		<?php if (!empty($client)) : ?>
			<h3 class="text-center"><?php echo $client['display_name']; ?></h3>
		<?php endif; ?>
		<legend><?php echo $title; ?></legend>

		<div class="form-group">
			<label for="name"><?php echo $this->lang->line('gp_first_name'); ?></label>
			<input class="form-control" name="fname" placeholder="" type="text"
				   value="<?php echo set_value('fname'); ?>"/>
			<span class="text-danger"><?php echo form_error('fname'); ?></span>
		</div>

		<div class="form-group">
			<label for="name"><?php echo $this->lang->line('gp_last_name'); ?></label>
			<input class="form-control" name="lname" placeholder="" type="text"
				   value="<?php echo set_value('lname'); ?>"/>
			<span class="text-danger"><?php echo form_error('lname'); ?></span>
		</div>

		<div class="form-group">
			<label for="email"><?php echo $this->lang->line('gp_email'); ?></label>
			<input class="form-control" name="email" placeholder="" type="text"
				   value="<?php echo set_value('email'); ?>"/>
			<span class="text-danger"><?php echo form_error('email'); ?></span>
		</div>

            <div class="form-group">
                <label for="username"><?php echo $this->lang->line('gp_username'); ?></label>
                <input class="form-control" name="username" placeholder="" type="text" value="<?php echo set_value('username'); ?>" />
				<span class="text-danger"><?php echo form_error('username'); ?></span>
			</div>

		<div class="form-group">
			<label for="organization"><?php echo $this->lang->line('gp_organization'); ?></label>
			<input class="form-control" name="organization" placeholder="" type="text"
				   value="<?php echo set_value('organization'); ?>"/>
			<span class="text-danger"><?php echo form_error('organization'); ?></span>
		</div>

		<div class="form-group">
			<label for="phone"><?php echo lang('create_user_phone_label'); ?></label>
			<input class="form-control" name="phone" placeholder="" type="text"
				   value="<?php echo set_value('phone'); ?>"/>
			<span class="text-danger"><?php echo form_error('phone'); ?></span>
		</div>

		<div class="form-group">
			<label for="subject"><?php echo $this->lang->line('gp_password'); ?></label>
			<input class="form-control" name="password" placeholder="" type="password"/>
			<span class="text-danger"><?php echo form_error('password'); ?></span>
		</div>

		<div class="form-group">
			<label
				for="subject"><?php echo $this->lang->line('gp_confirm'); ?>
				&nbsp;<?php echo $this->lang->line('gp_password'); ?></label>
			<input class="form-control" name="cpassword" placeholder="" type="password"/>
			<span class="text-danger"><?php echo form_error('cpassword'); ?></span>
		</div>

		<?php if (!empty($terms)) : ?>
			<div class="checkbox">
				<label>
					<input name="terms" placeholder="" type="checkbox"> <?php echo $terms; ?>
				</label>
				<span class="text-danger"><?php echo form_error('terms'); ?></span>
			</div>
		<?php endif; ?>

		<div class="form-group">
			<button name="submit" type="submit"
					class="btn btn-info"><?php echo $this->lang->line('gp_register'); ?></button>
			<button name="cancel" type="reset"
					class="btn btn-info"><?php echo $this->lang->line('gp_cancel'); ?></button>
		</div>
		<?php echo form_close(); ?>
		<?php echo $this->session->flashdata('message'); ?>
	</div>
</div>
<div class="row">
		<div class="col-md-4 col-md-offset-4 text-center">
            <?php echo $this->lang->line('gp_already_registered'); ?> <a href="<?php echo site_url('/auth/login') ?>"><?php echo $this->lang->line('gp_login'); ?> <?php echo $this->lang->line('gp_here'); ?></a>
		</div>
	</div>
