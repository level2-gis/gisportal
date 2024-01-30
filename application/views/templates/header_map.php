<!DOCTYPE html>
<html lang="en">

<head>

	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
	<meta name="description" content="">
	<meta name="author" content="">
	<meta name="robots" content="noindex, nofollow">

	<title><?php echo $title; ?> | <?php echo $this->config->item('site_title'); ?></title>
	<!--link the bootstrap css file-->
	<link rel="stylesheet" href="<?php echo base_url("assets/css/bootstrap.min.css"); ?>">
	<link rel="stylesheet" href="<?php echo base_url('assets/css/font-awesome.min.css'); ?>">
	<link rel="stylesheet" href="<?php echo base_url("assets/css/1-col-portfolio.css?v=20190515"); ?>">
	<link rel="stylesheet" href="<?php echo base_url("assets/css/site.css?v=20200417"); ?>">
	<link rel="stylesheet" href="<?php echo base_url("assets/map/ol.css?v=6.4.3"); ?>">
	<link rel="stylesheet" href="<?php echo base_url("assets/map/ol-layerswitcher.css?v=3.8.1"); ?>">

	<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
	<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
	<link rel="manifest" href="/site.webmanifest">
	<link rel="mask-icon" href="/safari-pinned-tab.svg" color="#5bbad5">
	<meta name="msapplication-TileColor" content="#da532c">
	<meta name="theme-color" content="#ffffff">

	<style>
		/*does not work on Firefox and MS browsers*/
		input[type="search"]::-webkit-search-cancel-button {
			-webkit-appearance: searchfield-cancel-button;
		}

		.map {
			width: 100%;
			height: 500px;
			position: relative;
		}

		.map:-webkit-full-screen {
			height: 100%;
			margin: 0;
		}

		.map:-ms-fullscreen {
			height: 100%;
		}

		.map:fullscreen {
			height: 100%;
		}

		.ol-rotate {
			top: 3em;
			right: .5em;
		}

		div.ol-overviewmap,
		div.ol-overviewmap.ol-uncollapsible {
			bottom: 42px;
			left: auto;
			right: 7px;
			top: auto;
		}

		div.ol-overviewmap div.ol-overviewmap-map {
			border: 1px solid #7b98bc;
			height: 150px;
			margin: 2px;
			width: 180px;
		}

		div.ol-overviewmap-box {
			border: 3px solid rgba(0, 60, 136, 0.7);
		}

		/*clear ol-switcher button and make it default ol style, icon added in code*/
		div.layer-switcher button {
			width: 1.375em;
			height: 1.375em;
			background-color: rgba(0, 60, 136, 0.5);
			background-image: none;
		}

		div.layer-switcher {
			top: 3em;
		}

		span.icon {
			font-family: FontAwesome;
			color: white;
		}

	</style>

	<script type="text/javascript" src="<?php echo base_url("assets/js/jquery.js"); ?>"></script>
	<script type="text/javascript" src="<?php echo base_url("assets/js/bootstrap.min.js"); ?>"></script>
	<script type="text/javascript" src="<?php echo base_url("assets/map/proj4.js?v=2.6.3"); ?>"></script>
	<script type="text/javascript" src="<?php echo base_url("assets/map/ol.js?v=6.4.3"); ?>"></script>
	<script type="text/javascript" src="<?php echo base_url("assets/map/ol-layerswitcher.js?v=3.8.1"); ?>"></script>
	<script type="text/javascript"
			src="<?php echo base_url("../gisapp/client_common/customProjections.js"); ?>"></script>
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

			//change layer switcher icon (fa-bars)
			$('.layer-switcher button').html('<span class="icon">&#xf0c9;</span>');
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
		GP.map = {};

        <?php if (!empty($center)) : ?>
        GP.map.startCenter = <?php echo $center; ?>;	//lon lat
        GP.map.startZoom = <?php echo $zoom; ?>;
        <?php else : ?>
        GP.map.startExtent = [<?php echo implode(",", $extent); ?>];	//use instead of center and zoom in project CRS
        <?php endif; ?>
        GP.map.crs = '<?php echo $crs; ?>';
        GP.map.proj4 = '<?php echo $proj4; ?>';
        GP.map.showCoords = <?php echo isset($showCoords) ? json_encode($showCoords) : json_encode(false); ?>;
        GP.map.showProjection = <?php echo isset($showProjection) ? json_encode($showProjection) : json_encode(false); ?>;
        GP.map.showLayerSwitcher = <?php echo isset($showLayerSwitcher) ? json_encode($showLayerSwitcher) : json_encode(false); ?>;
        GP.map.baselayers = function () {
            var bl = eval(<?php echo json_encode($baselayers); ?>);
            return bl;
        }
        GP.map.overview =  <?php echo isset($overview) ? json_encode($overview) : json_encode(''); ?>;
        GP.map.bookmarks = <?php echo isset($bookmarks) ? json_encode($bookmarks) : json_encode([]); ?>;
        GP.map.attribution = <?php echo isset($attribution) ? json_encode($attribution) : json_encode(''); ?>;
    </script>
</head>
<body>
