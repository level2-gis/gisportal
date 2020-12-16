<!-- Navigation -->
<?php if ($logged_in) : ?>
	<nav class="navbar navbar-inverse navbar-fixed-top" role="navigation">
		<div class="container">
			<!-- Brand and toggle get grouped for better mobile display -->
			<div class="navbar-header">
				<button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#navbar1">
					<span class="sr-only">Toggle navigation</span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
				</button>
				<a class="navbar-brand" href="<?php echo base_url(); ?>">
					<img height="32px"
						 src="<?php echo base_url("assets/img/header_logo.png") . '?v=' . $this->config->item('header_logo_version'); ?>"
						 alt="">
				</a>
				<?php if (!$this->config->item('logo_contains_site_title')) : ?>
					<a class="navbar-brand"
					   href="<?php echo base_url(); ?>"><?php echo $this->config->item('site_title'); ?></a>
				<?php endif; ?>
			</div>
			<!-- Collect the nav links, forms, and other content for toggling -->
			<div class="collapse navbar-collapse" id="navbar1">
				<ul class="nav navbar-nav navbar-right">
					<?php if (isset($role) && ($role === 'admin' || $role === 'power')) : ?>
						<li><a href="<?php echo site_url('/clients'); ?>"><i class="fa fa-folder"></i>
								<span><?php echo lang('gp_clients_title'); ?></span></a></li>
						<li><a href="<?php echo site_url('/project_groups'); ?>"><i class="fa fa-list"></i>
								<span><?php echo lang('gp_groups_title'); ?></span></a></li>
					<?php endif; ?>

					<li><a href="<?php echo site_url('/projects'); ?>"><i class="fa fa-file-text"></i>
							<span><?php echo lang('gp_projects_title'); ?></span></a></li>

					<?php if (isset($role) && $role === 'admin') : ?>
						<li><a href="<?php echo site_url('/layers'); ?>"><i class="fa fa-database"></i>
								<span><?php echo lang('gp_layers_title'); ?></span></a></li>
					<?php endif; ?>
					<?php if (isset($role) && ($role === 'admin' || $role === 'power')) : ?>
						<li><a href="<?php echo site_url('/users'); ?>"><i class="fa fa-group"></i>
								<span><?php echo lang('gp_users_title'); ?></span></a></li>
					<?php endif; ?>

					<li class="dropdown">
						<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true"
						   aria-expanded="false"><span class="glyphicon glyphicon-user"
													   aria-hidden="true"></span> <?php echo $this->session->userdata('user_name'); ?>
							<span class="caret"></span></a>
						<ul class="dropdown-menu">
							<li><a href="<?php echo site_url('/profile') ?>"><?php echo lang('gp_profile_title'); ?></a>
							</li>
							<li><a href="<?php echo site_url('/auth/logout') ?>"><?php echo lang('gp_log_out'); ?></a>
							</li>
						</ul>
					</li>
				</ul>
			</div>
			<!-- /.navbar-collapse -->
		</div>
		<!-- /.container -->
	</nav>
<?php endif; ?>
<div class="container body-content">
