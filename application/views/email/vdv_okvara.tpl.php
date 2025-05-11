<html>
<body>
<h3>Nova okvara</h3>
<p><?php echo lang('gp_user'); ?>: <?php echo $user; ?>
	<br/>Povezava: <a href="<?php echo $projectLink; ?>"><?php echo $project; ?></a>
<table border="1">
	<thead>
	<tr>
		<th>FID</th>
    	<th>Naslov</th>
    	<th>Intervencija</th>
    	<th>Ocenjene izgube (m^3/dan)</th>
    	<th>Opomba</th>
    	<th>Priloge</th>
	</tr>
	</thead>
	<?php foreach ($table as $row): ?>
	<?php
		$files = "";
		$interv = "";
		if($row->attributes->intervencija == 1) {
        	$interv = "DA";
        }
		else {
        	$interv = "NE";
        }
		if(!empty($row->attributes->files)) {
			foreach(json_decode($row->attributes->files) as $file) {
				$files .= "<a href=" . $uploadDir . $file . ">" . $file . '</a><br>';
			}
		}

	?>
		<tr>
			<td><?php echo $row->fid; ?></td>
            <td><?php echo $row->attributes->naslov; ?></td>
            <td><?php echo $interv; ?></td>
            <td><?php echo $row->attributes->Ocenjeno; ?></td>
    		<td><?php echo $row->attributes->opomba; ?></td>
            <td><?php echo $files; ?></td>
		</tr>
	<?php endforeach; ?>
</table>
<br>
<hr>
<footer><?php echo $this->config->item('site_title'); ?> - <?php echo $interface; ?> client - <?php echo $_SERVER['SERVER_NAME']; ?></footer>
</body>
</html>
