<html>
<body>
<p>Uporabnik <?php echo html_escape($user['display_name']); ?> je naložil novo verzijo projekta <b><?php echo $file_name; ?></b>.</p>
<p><?php echo html_escape($client['display_name']); ?></p>
<br><br>
<hr>
<footer><?php echo anchor(site_url(), $this->config->item('site_title') . ' - ' . $_SERVER['SERVER_NAME']); ?></footer>
</body>
</html>
