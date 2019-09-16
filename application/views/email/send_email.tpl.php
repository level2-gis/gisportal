<html>
<body>
<?php echo $body; ?>
<br><br>
<hr>
<footer><?php echo anchor(site_url(), $this->config->item('site_title') . ' - ' . $_SERVER['SERVER_NAME']); ?></footer>
</body>
</html>