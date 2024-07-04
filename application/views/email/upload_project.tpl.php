<html>
<body>
<h3>Nova verzija projekta <?php echo $file_name; ?></h3>
<p><?php echo $client['display_name']; ?></p>
<p>Uporabnik <?php echo $user['display_name']; ?></p>
<br><br>
<hr>
<footer><?php echo anchor(site_url(), $this->config->item('site_title') . ' - ' . $_SERVER['SERVER_NAME']); ?></footer>
</body>
</html>
