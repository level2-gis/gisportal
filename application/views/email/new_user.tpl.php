<html>
<body>
<h3><?php echo lang('gp_new_user'); ?></h3>

<p><b><?php echo lang('gp_name'); ?>:</b><br/>
	<?php echo $first_name; ?> <?php echo $last_name; ?></p>

<p><b><?php echo lang('gp_email'); ?>:</b><br/>
	<?php echo $email; ?></p>

<p><b><?php echo lang('gp_client'); ?>:</b><br/>
	<?php echo $client; ?></p>

<br/>

<p><?php echo anchor(site_url('users/edit/' . $id), lang('gp_edit')); ?></p>
<br/>
<hr/>
<footer><?php echo $this->config->item('site_title') . ' - ' . $_SERVER['SERVER_NAME']; ?></footer>
</body>
</html>
