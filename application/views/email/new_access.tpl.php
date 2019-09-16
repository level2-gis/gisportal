<html>
<body>
    <p><?php echo lang('gp_email_new_access') . ': ' . $display_name; ?></p>
    <br><br>
    <hr>
    <footer><?php echo anchor(site_url(), $this->config->item('site_title') . ' - ' . $_SERVER['SERVER_NAME']); ?></footer>
</body>
</html>