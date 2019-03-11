<div class="row">
    <div class="col-md-4 col-md-offset-4 well">

        <?php echo form_open("auth/login"); ?>

        <legend><?php echo $this->lang->line('gp_login'); ?></legend>
        <div class="form-group">
            <label for="identity"><?php echo $this->lang->line('gp_user'); ?></label>
            <input class="form-control" name="<?php echo $identity['name']; ?>"
                   placeholder="<?php echo $this->lang->line('gp_username_placeholder'); ?>" type="text"
                   value="<?php echo set_value($identity['name']); ?>"/>
            <span class="text-danger"><?php echo form_error($identity['name']); ?></span>
        </div>

        <div class="form-group">
            <label for="name"><?php echo $this->lang->line('gp_password'); ?></label>
            <input class="form-control" name="password" placeholder="<?php echo $this->lang->line('gp_password'); ?>"
                   type="password" value="<?php echo set_value('password'); ?>"/>
            <span class="text-danger"><?php echo form_error('password'); ?></span>
        </div>

        <div class="checkbox">
            <label><input name="remember" id="remember" type="checkbox" value="1"><?php echo rtrim($this->lang->line('login_remember_label'),':'); ?></label>
        </div>

        <div class="form-group">
            <button name="submit" type="submit"
                    class="btn btn-info"><?php echo $this->lang->line('gp_login'); ?></button>
            <button name="cancel" type="reset"
                    class="btn btn-info"><?php echo $this->lang->line('gp_cancel'); ?></button>
        </div>
        <?php echo form_close(); ?>
        <?php echo $this->session->flashdata('message'); ?>
    </div>
</div>
<div class="row">
    <div class="col-md-4 col-md-offset-4 text-center">
        <?php echo $this->lang->line('gp_new_user'); ?> <a
            href="<?php echo site_url('/signup') ?>"><?php echo $this->lang->line('gp_register'); ?> <?php echo $this->lang->line('gp_here'); ?></a>
    </div>
</div>
<div class="row">
    <div class="col-md-4 col-md-offset-4 text-center">
        <a href="forgot_password"><?php echo lang('login_forgot_password'); ?></a>
    </div>
</div>
