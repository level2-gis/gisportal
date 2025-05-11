<html>
<body>
<h3>Sprememba stanja svetilke</h3>
<p><?php echo lang('gp_user'); ?>: <?php echo $user; ?>
	<br/>Povezava: <a href="<?php echo $projectLink; ?>"><?php echo $project; ?></a>
<table border="1">
	<thead>
	<tr>
		<th>Šifra</th>
		<th>Status</th>
		<th>Opis</th>
		<th>Priloge</th>
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
		
		switch ($row->attributes->status_stanja_svetila) {
        	case 1: 
        		$stanje = 'Ne deluje';
        		break;
        	case 2:
        		$stanje = 'Sveti neustrezno';
        		break;
        	case 9:
        		$stanje = 'Popravljena in sveti';
        		break;
        	default:
        		$stanje = '';
        
        }
	?>
		<tr>
			<td><?php echo $row->attributes->sifra; ?></td>
			<td><?php echo $stanje; ?></td>
			<td><?php echo $row->attributes->opis; ?></td>
			<td><?php echo $files; ?></td>
		</tr>
	<?php endforeach; ?>
</table>
<br>
<hr>
<footer><?php echo $this->config->item('site_title'); ?> - <?php echo $interface; ?> client - <?php echo $_SERVER['SERVER_NAME']; ?></footer>
</body>
</html>
