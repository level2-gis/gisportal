	<div class="row">
		<div class="col-md-4">
			<h3><span class="glyphicon glyphicon-user" aria-hidden="true"></span> <?php echo $user->display_name; ?></h3>
			<hr/>
			<p><?php echo $this->lang->line('gp_username'); ?>: <?php echo $user->user_name; ?></p>
			<p><?php echo $this->lang->line('gp_email'); ?>: <?php echo $user->user_email; ?></p>
			<p>...</p>
		</div>

	</div>
    </br>
    <table class="table table-hover table-condensed">
        <th>client</th>
        <th>project</th>
        <th>crs</th>
        <th>contact</th>
        <th>description</th>

        <?php foreach ($projects as $projects_item): ?>
        <tr>
            <td><?php echo $projects_item['client']; ?></td>
            <td><a target="_self" href="<?php echo site_url($this->config->item('web_client_url').$projects_item['name']); ?>"><?php echo $projects_item['display_name']; ?></a></td>
            <td><?php echo $projects_item['crs']; ?></td>
            <td><?php echo $projects_item['contact']; ?></td>
            <td><?php echo $projects_item['description']; ?></td>
        </tr>
        <?php endforeach; ?>
    </table>

