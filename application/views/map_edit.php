<div class="page-header clearfix">
	<h1 class="col-xs-8"><span><?php echo $title; ?></span></h1>
	<?php if ($is_admin) : ?>
		<div class="col-xs-4 col-md-1 actions  pull-right">
			<a href="<?php echo site_url($edit_url); ?>"
			   class="btn btn-mini btn-primary"><?php echo lang('gp_edit'); ?></a>
		</div>
	<?php endif; ?>
</div>
