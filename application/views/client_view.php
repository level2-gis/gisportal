<h2><?php echo $client->display_name; ?></h2>
<p><?php echo $client->description; ?></p>
<a class="btn btn-primary" href="<?php echo site_url('projects/view/'.$client->id); ?>"><?php echo $this->lang->line('gp_view_projects'); ?>
    <span class="badge"><?php echo $client->count; ?></span>
    <span class="glyphicon glyphicon-chevron-right"></span>
</a>
<br/><br/>

