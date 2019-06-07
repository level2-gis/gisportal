<div class="row">
    <div class="col-md-8 col-md-offset-2 well">
        <h3><?php echo lang('reset_password_heading'); ?></h3>

        <div id="infoMessage"><?php echo $message; ?></div>

        <?php echo form_open('auth/reset_password/' . $code); ?>

        <div class="form-group">
            <label for="new"><?php echo sprintf(lang('reset_password_new_password_label'), $min_password_length); ?></label>
            <input class="form-control" name="new"
                   type="password" value="<?php echo set_value('new'); ?>"/>
            <span class="text-danger"><?php echo form_error('new'); ?></span>
        </div>

        <div class="form-group">
            <label for="new_confirm"><?php echo lang('reset_password_new_password_confirm_label', 'new_password_confirm'); ?></label>
            <input class="form-control" name="new_confirm"
                   type="password" value="<?php echo set_value('new_confirm'); ?>"/>
            <span class="text-danger"><?php echo form_error('new_confirm'); ?></span>
        </div>

        <?php echo form_input($user_id); ?>
        <?php echo form_hidden($csrf); ?>

        <div class="form-group">
            <button name="submit" type="submit"
                    class="btn btn-info"><?php echo lang('reset_password_submit_btn'); ?></button>
        </div>

        <?php echo form_close(); ?>
    </div>
</div>
