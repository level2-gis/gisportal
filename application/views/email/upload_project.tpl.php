<html>
<body>
<p><?php echo $client['display_name'] . ' ' . lang('gp_upload') . ' ' . lang('gp_project') . ' ' . $file_name; ?></p>
<p><?php echo $user['display_name']; ?></p>
<br><br>
<hr>
<footer><?php echo anchor(site_url(), $this->config->item('site_title') . ' - ' . $_SERVER['SERVER_NAME']); ?></footer>
</body>
</html>
