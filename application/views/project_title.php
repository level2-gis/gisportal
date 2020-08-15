<div class="page-header clearfix">
    <h1 class="col-xs-8"><?php echo $title; ?></h1>
	<div class="col-xs-4 col-md-1 actions  pull-right">
		<a href="<?php echo site_url('projects/map/' . $project['id']); ?>"
		   class="btn btn-mini btn-default"><?php echo lang('gp_map'); ?></a>
	</div>
</div>

<?php echo $this->session->flashdata('alert'); ?>
<?php echo $this->session->flashdata('upload_msg'); ?>
