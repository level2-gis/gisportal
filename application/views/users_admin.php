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
		<th>Registered</th>
		<th>Count login</th>
		<th>Last login</th>
		<th>Admin</th>
		<th>Action</th>
	  </tr>
	  <?php foreach ($users as $user_item): ?>

		<tr>
		  <td class="col-md-2"><?php echo $user_item['user_name']; ?></td>
		  <td class="col-md-2"><?php echo $user_item['user_email']; ?></td>
		  <td class="col-md-2"><?php echo $user_item['display_name']; ?></td>
		  <td class="col-md-2"><?php echo date_format(date_create($user_item['registered']),'Y-m-d H:i:s'); ?></td>
		  <td class="col-md-2"><?php echo $user_item['count_login']; ?></td>
		  <td class="col-md-2"><?php echo date_format(date_create($user_item['last_login']),'Y-m-d H:i:s'); ?></td>
		  <td class="col-md-2"><?php if ($user_item['admin']) {
                                    echo "<span class='glyphicon glyphicon-ok' aria-hidden='true'></span>";
                                } else {
                                    echo "<span class='glyphicon glyphicon-minus' aria-hidden='true'></span>";
                                }; ?></td>
		  <td class="col-md-2">
			<a class="btn btn-primary" href="<?php echo site_url('users/edit/'.$user_item['user_id']); ?>">
				<?php echo $this->lang->line('gp_edit'); ?>
			</a>

		  </td>
		</tr>
		<?php endforeach; ?>
	</table>
</div>
