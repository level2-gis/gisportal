<?php if (!empty($module_context)) : ?>
    <style>
        .module-cta {
            border: 2px solid #31708f;
            border-radius: 4px;
            background-color: #eaf4f9;
            padding: 10px 12px;
            margin-bottom: 15px;
        }
        .module-cta label {
            font-weight: 700;
            color: #23527c;
            margin-bottom: 0;
        }
        .module-cta input[type="checkbox"] {
            margin-top: 3px;
        }
        .module-register {
            border: 2px dashed #31708f;
            border-radius: 4px;
            padding: 12px;
            margin-bottom: 15px;
            text-align: center;
        }
        .module-register .btn {
            margin-top: 8px;
            white-space: normal;
        }
    </style>
<?php endif; ?>
<div class="row">
    <div class="col-md-4 col-md-offset-4 well">

        <?php echo form_open("auth/login"); ?>

        <img height="50px" class="center-block" src="<?php echo base_url("assets/img/header_logo.svg") . '?v=' . $this->config->item('header_logo_version'); ?>" alt="">
        <?php if(!$this->config->item('logo_contains_site_title')) : ?>
            <h3 class="text-center"><?php echo $heading; ?></h3>
		<?php else : ?>
			<p>&nbsp</p>
		<?php endif; ?>

        <?php if (!empty($module_context)) : ?>
            <div class="alert alert-info">
                <h4><strong><?php echo html_escape($module_context['module']); ?></strong></h4>
                <?php if (!empty($module_context['description'])) : ?>
                    <p><?php echo html_escape($module_context['description']); ?></p>
                <?php endif; ?>
                <!-- <p><?php echo $this->lang->line('gp_module_login_required'); ?></p> -->
            </div>
        <?php endif; ?>

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

        <?php if (!empty($module_context['request_access_url'])) : ?>
            <div class="checkbox module-cta">
                <label><input name="request_access" id="request_access" type="checkbox" value="1"
                              <?php echo set_checkbox('request_access', '1'); ?>><?php echo $this->lang->line('gp_module_request_access_option'); ?><?php echo html_escape($module_context['module']); ?></label>
            </div>
        <?php endif; ?>

        <div class="form-group">
            <button name="submit" type="submit"
                    class="btn btn-info btn-block"><?php echo $this->lang->line('gp_login'); ?></button>
        </div>
        <?php echo form_close(); ?>
        <?php echo $this->session->flashdata('message'); ?>

        <?php if (!empty($module_context['register_url'])) : ?>
            <div class="module-register">
                <strong><?php echo $this->lang->line('gp_new_user'); ?>?</strong>
                <a class="btn btn-success btn-block" href="<?php echo html_escape($module_context['register_url']); ?>">
                    <?php echo $this->lang->line('gp_module_register_for_client'); ?><?php echo html_escape($module_context['module']); ?>
                </a>
            </div>
        <?php endif; ?>

        <p class="text-center">
            <?php if ($this->config->item('public_registration') && empty($module_context['register_url'])) : ?>
                <?php echo $this->lang->line('gp_new_user'); ?>? <a
                    href="<?php echo site_url('/signup') ?>"><?php echo $this->lang->line('gp_register'); ?> <?php echo $this->lang->line('gp_here'); ?></a></br>
            <?php endif; ?>
			<a href="forgot_password"><?php echo lang('login_forgot_password'); ?></a>
			
        </p>
		<p class="text-center">
			<a href="https://site.geo-portal.si"><?php echo "Kaj je GEO-PORTAL?"; ?></a>
		</p>
    </div>
</div>
<?php if (!empty($rss)): ?>
	<div class="row">
		<div class="alert alert-warning col-md-4 col-md-offset-4" role="alert">
			<?php foreach ($rss['item'] as $item): ?>
				<?php echo '<p class="text-center"><span class="label label-danger text-uppercase">' . lang('gp_rss_new') . '</span></p>'; ?>
				<p class="text-center"><a href="<?php echo $item['link']; ?>" target="_blank"><?php echo $item['title']; ?></a>
				</p>
			<?php endforeach; ?>
		</div>
	</div>
<?php endif; ?>
