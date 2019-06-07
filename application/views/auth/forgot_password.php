<div class="row">
    <div class="col-md-8 col-md-offset-2 well">

    <h3><?php echo lang('forgot_password_heading'); ?></h3>
    <div id="infoMessage"><?php echo $message; ?></div>

    <?php if (empty($status)): ?>

        <p><?php echo sprintf(lang('forgot_password_subheading'), $identity_label); ?></p>
        <?php echo form_open("auth/forgot_password"); ?>

        <div class="form-group">
            <input class="form-control" name="identity" placeholder="<?php echo(($type == 'email') ? sprintf(lang('forgot_password_email_label'), $identity_label) : sprintf(lang('forgot_password_identity_label'), $identity_label)); ?>"
            type="text" value="<?php echo set_value('identity'); ?>"/>
            <span class="text-danger"><?php echo form_error('identity'); ?></span>
        </div>

        <div class="form-group">
            <button name="submit" type="submit"
                    class="btn btn-info"><?php echo lang('forgot_password_submit_btn'); ?></button>
        </div>

        <?php echo form_close(); ?>
    <?php endif; ?>
    </div>
</div>
