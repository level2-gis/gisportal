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
	</tr>
	</thead>
	<?php foreach ($table as $row): ?>
	<?php
		$files = "";
		if(!empty($row->files)) {
			foreach(json_decode($row->files) as $file) {
				$files .= $file . '<br>';
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
		</tr>
	<?php endforeach; ?>
</table>
<br>
<hr>
<footer><?php echo $this->config->item('site_title') . ' - ' . $_SERVER['SERVER_NAME']; ?></footer>
</body>
</html>