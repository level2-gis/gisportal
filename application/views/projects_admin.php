<div class="page-header clearfix">
	<h1 class="col-md-8"><?php echo $title; ?></h1>
	<div class="actions  pull-right">
		<a href="<?php echo site_url('projects/edit/'); ?>" class="btn btn-mini btn-success ">New Project</a>
	</div>
</div>

<?php echo $this->session->flashdata('alert'); ?>

<div class="col-md-12">

	<table class="table table-condensed table-striped">
	  <tr>
		<th></th>
		<th>Name</th>
		<th>Display name</th>
		<th>Client</th>
		<th>Action</th>
	  </tr>
	  <?php foreach ($projects as $project_item): ?>

		<tr>
		  <td class="col-md-1">
			<img class="img-responsive" src="<?php echo base_url("assets/img/projects/" . $project_item['name'] . ".png"); ?>" alt="">
		  </td>
		  <td class="col-md-2"><?php echo $project_item['name']; ?></td>
		  <td class="col-md-2"><?php echo $project_item['display_name']; ?></td>
		  <td class="col-md-2"><?php echo $project_item['client']; ?></td>
		  <td class="col-md-2">
			<a class="btn btn-primary" href="<?php echo site_url('projects/edit/'.$project_item['id']); ?>">
				<?php echo $this->lang->line('gp_edit'); ?>
			</a>

		  </td>
		</tr>
		<?php endforeach; ?>
	</table>
</div>
