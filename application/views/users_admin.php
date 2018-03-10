<div class="page-header clearfix">
	<h1 class="col-md-8"><?php echo $title; ?></h1>
</div>

<?php echo $this->session->flashdata('alert'); ?>

<div class="col-md-12">

	<table class="table table-condensed table-striped">
	  <tr>
		<th>Username</th>
		<th>Email</th>
		<th>Name</th>
		<th>Is Admin</th>
		<th>Action</th>
	  </tr>
	  <?php foreach ($users as $user_item): ?>

		<tr>
		  <td class="col-md-2"><?php echo $user_item['user_name']; ?></td>
		  <td class="col-md-2"><?php echo $user_item['user_email']; ?></td>
		  <td class="col-md-2"><?php echo $user_item['display_name']; ?></td>
		  <td class="col-md-2"><?php if ($user_item['admin']) { echo 'True'; } else {echo 'False'; }; ?></td>
		  <td class="col-md-2">
			<a class="btn btn-primary" href="<?php echo site_url('users/edit/'.$user_item['user_id']); ?>">
				<?php echo $this->lang->line('gp_edit'); ?>
			</a>

		  </td>
		</tr>
		<?php endforeach; ?>
	</table>
</div>
