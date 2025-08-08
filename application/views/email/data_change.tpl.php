<html>
<body>
<h3>Data change</h3>
<p><?php echo lang('gp_user'); ?>: <?php echo $user; ?>
	<br/><?php echo lang('gp_project'); ?>: <a href="<?php echo $projectLink; ?>"><?php echo $project; ?></a>
	<br/><?php echo lang('gp_layer'); ?>: <a href="<?php echo $layerLink; ?>"><?php echo $layer; ?></a></p>
<table border="1">
	<thead>
	<tr>
		<th>fid</th>
		<th>type</th>
		<th>data</th>
		<th>files</th>
	</tr>
	</thead>
	<?php foreach ($table as $row): ?>
	<?php
		$files = "";
		if(!empty($row->attributes->files)) {
			foreach(json_decode($row->attributes->files) as $file) {
				$files .= "<a href=" . $uploadDir . $file . ">" . $file . '</a><br>';
			}
		}
	?>
		<tr>
			<td><?php echo $row->fid; ?></td>
			<td><?php echo $row->type; ?></td>
			<td><?php if(empty($row->data)): ?></td><?php else: ?><a href="<?php echo $row->data; ?>">geojson</a></td><?php endif; ?>
			<td><?php echo $files; ?></td>
		</tr>
	<?php endforeach; ?>
</table>
<br>
<hr>
<footer><?php echo $this->config->item('site_title'); ?> - <?php echo $interface; ?> client - <?php echo $_SERVER['SERVER_NAME']; ?></footer>
</body>
</html>
