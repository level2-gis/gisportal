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
<script type="text/javascript" src="<?php echo base_url("assets/js/gisportal_map.js?v=20200705"); ?>"></script>
