<html>
<body>
<p>Uporabnik <?php echo $user['display_name']; ?> je naložil novo verzijo projekta <?php echo $file_name; ?>.</p>
<p><?php echo $client['display_name']; ?></p>
<br><br>
<hr>
<footer><?php echo anchor(site_url(), $this->config->item('site_title') . ' - ' . $_SERVER['SERVER_NAME']); ?></footer>
</body>
</html>
