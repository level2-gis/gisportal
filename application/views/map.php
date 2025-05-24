<?php echo $this->session->flashdata('alert'); ?>
<p class="help-block"><?php echo $subtitle; ?></p>
<div id="map" class="map"></div>
<div class="help-block" id="projection"></div>
<div id="mouse-position"></div>
<script type="text/javascript" src="<?php echo base_url("assets/js/gisportal_map.js?v=20250524"); ?>"></script>
