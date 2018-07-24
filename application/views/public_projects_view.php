<table class="table table-hover table-condensed">
    <caption><i class="fa fa-group"></i> <?php echo $this->lang->line('gp_public_projects'); ?></caption>
    <th class="text-uppercase"><?php echo $this->lang->line('gp_client'); ?></th>
    <th class="text-uppercase"><?php echo $this->lang->line('gp_project'); ?></th>
    <th class="text-uppercase"><?php echo $this->lang->line('gp_crs'); ?></th>
    <th class="text-uppercase"><?php echo $this->lang->line('gp_contact'); ?></th>
    <th class="text-uppercase"><?php echo $this->lang->line('gp_description'); ?></th>

    <?php foreach ($projects_public as $projects_item): ?>
        <tr>
            <td><?php echo $projects_item['client']; ?></td>
            <td><a target="_self" href="<?php echo site_url($this->config->item('web_client_url').$projects_item['name']); ?>"><?php echo $projects_item['display_name']; ?></a></td>
            <td><?php echo $projects_item['crs']; ?></td>
            <td><?php echo $projects_item['contact']; ?></td>
            <td><?php echo $projects_item['description']; ?></td>
        </tr>
    <?php endforeach; ?>
</table>