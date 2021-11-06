<html>
<body>
<h3><?php echo sprintf(lang('email_activate_heading'), $name); ?></h3>
<p><?php echo sprintf(lang('email_activate_subheading'), anchor('auth/activate/' . $id . '/' . $activation, lang('email_activate_link'))); ?></p>
<br><br>
<hr>
<footer><?php echo $this->config->item('site_title') . ' - ' . $_SERVER['SERVER_NAME']; ?></footer>
</body>
</html>
