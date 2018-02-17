<h2><?php echo $client->display_name; ?></h2>
<p><?php echo $client->description; ?></p>
<a class="btn btn-default" href="<?php echo site_url('projects/view/'.$client->id); ?>"><?php echo $this->lang->line('gp_view_projects'); ?>
    <span class="glyphicon glyphicon-menu-right"></span>
</a>
<br/><br/>

