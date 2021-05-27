<!DOCTYPE html>
<html lang="en">

<head>

	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<meta name="description" content="">
	<meta name="author" content="">

	<title><?php echo $title; ?> | <?php echo $this->config->item('site_title'); ?></title>
	<!--link the bootstrap css file-->
	<link rel="stylesheet" href="<?php echo base_url("assets/css/bootstrap.min.css"); ?>">
	<link rel="stylesheet" href="<?php echo base_url("assets/css/bootstrap-table.min.css"); ?>">
	<link rel="stylesheet" href="<?php echo base_url("assets/css/bootstrap-table-reorder-rows.css"); ?>">
	<link rel="stylesheet" href="<?php echo base_url('assets/css/font-awesome.min.css'); ?>">
	<link rel="stylesheet" href="<?php echo base_url("assets/css/1-col-portfolio.css?v=20190515"); ?>">
	<link rel="stylesheet" href="<?php echo base_url("assets/css/site.css?v=20200417"); ?>">

	<style>
		/*does not work on Firefox and MS browsers*/
		input[type="search"]::-webkit-search-cancel-button {
			-webkit-appearance: searchfield-cancel-button;
		}
	</style>

	<script type="text/javascript" src="<?php echo base_url("assets/js/jquery.js"); ?>"></script>
	<script type="text/javascript" src="<?php echo base_url("assets/js/jquery.tablednd.min.js"); ?>"></script>
	<script type="text/javascript" src="<?php echo base_url("assets/js/bootstrap.min.js"); ?>"></script>
	<script type="text/javascript" src="<?php echo base_url("assets/js/bootstrap-filestyle.min.js"); ?>"></script>
	<script type="text/javascript" src="<?php echo base_url("assets/js/bootstrap3-typeahead.min.js"); ?>"></script>
	<script type="text/javascript" src="<?php echo base_url("assets/js/bootbox.all.min.js"); ?>"></script>
	<script type="text/javascript" src="<?php echo base_url("assets/js/bootstrap-table.min.js"); ?>"></script>
	<script type="text/javascript"
			src="<?php echo base_url("assets/js/bootstrap-table-reorder-rows.min.js"); ?>"></script>
	<script type="text/javascript"
			src="<?php echo base_url("assets/js/locale/bootstrap-table-" . $lang . ".js"); ?>"></script>
	<script type="text/javascript" src="<?php echo base_url("assets/js/gisportal_common.js?v=20210528"); ?>"></script>
	<script type="text/javascript">

		$(function () {
			var hash = window.location.hash;
			hash && $('ul.nav a[href="' + hash + '"]').tab('show');

			$('.nav-tabs a').click(function (e) {
				$(this).tab('show');
				var scrollmem = $('body').scrollTop() || $('html').scrollTop();
				window.location.hash = this.hash;
				$('html,body').scrollTop(scrollmem);
			});
		});

		var GP = {
			name: '<?php echo lang('gp_name'); ?>',
			displayName: '<?php echo lang('gp_display_name'); ?>',
			action: '<?php echo lang('gp_action'); ?>',
			group: '<?php echo lang('gp_group'); ?>',
			menuGroup: '<?php echo lang('gp_sub_group'); ?>',
			clientRequired: '<?php echo lang('gp_client_required'); ?>',
			userRequired: '<?php echo lang('gp_user_required'); ?>',
			layerRequired: '<?php echo lang('gp_layer_required'); ?>',
			noFile: '<?php echo lang('gp_no_file'); ?>',
			onlyQgs: '<?php echo lang('gp_only_qgs'); ?>',
			differentProjects: '<?php echo lang('gp_diff_proj'); ?>',
			project: '<?php echo lang('gp_project'); ?>',
			delete: '<?php echo lang('gp_delete'); ?>',
			deleteProject: '<?php echo lang('gp_del_proj'); ?>',
			deleteProjectFiles: '<?php echo lang('gp_del_proj_files'); ?>',
			deleteRole: '<?php echo lang('gp_del_role'); ?>',
			deleteAllRoles: '<?php echo lang('gp_del_all_roles'); ?>',
			deleteGeneral: '<?php echo lang('gp_del_general'); ?>',
			deleteLayerGroup: '<?php echo lang('gp_del_layer_group'); ?>',
			stopService: '<?php echo lang('gp_stop_service'); ?>',
			publishPublicService: '<?php echo lang('gp_publish_public_service'); ?>',
			publishPrivateService: '<?php echo lang('gp_publish_private_service'); ?>',
			selectGroup: '<?php echo lang('gp_select_group'); ?>',
			selectTemplate: '<?php echo lang('gp_select_template'); ?>',
			copyTitle: '<?php echo lang('gp_copy_title'); ?>',
			copyMsg: '<?php echo lang('gp_copy_msg'); ?>',
			addGroupTitle: '<?php echo lang('gp_add_group_title'); ?>',
			addGroupMsg: '<?php echo lang('gp_add_group_msg'); ?>',
			adminFullName: '<?php echo lang('gp_admin_full_name'); ?>',
			adminAdd: '<?php echo lang('gp_admin_add'); ?>',
			adminAddMsg: '<?php echo lang('gp_admin_add_msg'); ?>',
			adminRemove: '<?php echo lang('gp_admin_remove'); ?>'
		};

		//other stuff, not language strings
		GP.settings = {};
		GP.settings.siteUrl = '<?php echo site_url(); ?>';
		GP.settings.maxSearchResults = '20';  //max items, also possible value 'all'

	</script>
</head>
<body>
