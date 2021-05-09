<!DOCTYPE HTML>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo $title; ?> | <?php echo $this->config->item('site_title'); ?></title>
	<link rel="stylesheet" href="<?php echo base_url("assets/css/pannellum.css?v=2.5.6"); ?>">
	<script type="text/javascript" src="<?php echo base_url("assets/js/pannellum.js?v=2.5.6"); ?>"></script>
	<style>
		#panorama {
			height: 400px;
		}
	</style>
</head>
<body>

<div id="panorama"></div>
<script>
	pannellum.viewer('panorama', {
		"type": "equirectangular",
		"panorama": "<?php echo $panorama; ?>",
		"autoLoad": true
	});
</script>

</body>
</html>
