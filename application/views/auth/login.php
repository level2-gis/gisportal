<div class="row">
    <div class="col-md-4 col-md-offset-4 well">

        <?php echo form_open("auth/login"); ?>

        <img height="50px" class="center-block" src="<?php echo base_url("assets/img/header_logo.svg") . '?v=' . $this->config->item('header_logo_version'); ?>" alt="">
        <?php if(!$this->config->item('logo_contains_site_title')) : ?>
            <h3 class="text-center"><?php echo $heading; ?></h3>
		<?php else : ?>
			<p>&nbsp</p>
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

        <div class="form-group">
            <button name="submit" type="submit"
                    class="btn btn-info btn-block"><?php echo $this->lang->line('gp_login'); ?></button>
        </div>
        <?php echo form_close(); ?>
        <?php echo $this->session->flashdata('message'); ?>

        <p class="text-center">
            <?php if ($this->config->item('public_registration')) : ?>
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
