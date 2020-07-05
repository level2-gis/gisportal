<div class="page-header clearfix">
	<h1 class="col-md-8"><span><?php echo $title; ?></span></h1>
	<?php if ($is_admin) : ?>
		<div class="actions  pull-right">
			<a href="<?php echo site_url($edit_url); ?>" class="btn btn-mini btn-primary"><?php echo lang('gp_edit'); ?></a>
		</div>
	<?php endif; ?>
</div>

<?php echo $this->session->flashdata('alert'); ?>
<p class="help-block"><?php echo $subtitle; ?></p>
<div id="map" class="map"></div>
<div class="help-block" id="projection"></div>
<div id="mouse-position"></div>
<script type="text/javascript">

	var olMap = new ol.Map({
		target: 'map',
		controls: ol.control.defaults().extend([
			new ol.control.FullScreen(),
			new ol.control.ScaleLine(),
			new ol.control.MousePosition({
				target: document.getElementById('mouse-position'),
				undefinedHTML: '&nbsp;',
				className: 'custom-mouse-position help-block',
				coordinateFormat: ol.coordinate.createStringXY(2)
			})
		]),
		layers: [],
		view: new ol.View({
			center: ol.proj.fromLonLat([35, 50]),
			zoom: 4
		})
	});

	initViewer();
</script>
