<h4><?php echo $modules_heading; ?></h4>
<table class="table table-hover table-condensed">
	<thead>
	<tr>
		<th><?php echo lang('gp_module'); ?></th>
		<th><?php echo lang('gp_client'); ?></th>
		<th><?php echo lang('gp_role'); ?></th>
		<th><?php echo lang('gp_valid_to'); ?></th>
	</tr>
	</thead>
	<tbody>
	<?php foreach ($modules as $item) : ?>
		<tr>
			<td>
				<a href="<?php echo site_url(array('modules', $item['name'], $item['start'], $item['client_name'])); ?>">
					<?php echo html_escape(empty($item['display_name']) ? $item['name'] : $item['display_name']); ?>
				</a>
			</td>
			<td><?php echo html_escape($item['client_display_name']); ?></td>
			<td><?php echo html_escape(empty($item['role_display_name']) ? $item['role_name'] : $item['role_display_name']); ?></td>
			<td><?php echo html_escape($item['validto']); ?></td>
		</tr>
	<?php endforeach; ?>
	</tbody>
</table>
