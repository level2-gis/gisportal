<html>
<body>
	<h3><?php echo lang('gp_new_user');?></h3>
    <p><?php echo $first_name; ?>
        <br /><?php echo $last_name; ?>
        <br /><?php echo $email; ?>
        <br /><?php echo $company; ?></p>
	<p><?php echo anchor(site_url('users/edit/'.$id), lang('gp_edit')); ?></p>
    <br><br>
    <hr>
    <footer><?php echo $this->config->item('site_title') . ' - ' . $_SERVER['SERVER_NAME']; ?></footer>
</body>
</html>
