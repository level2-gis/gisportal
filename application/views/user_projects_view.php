<table class="table table-hover table-condensed">
    <caption><span class="glyphicon glyphicon-lock" aria-hidden='true'></span><?php echo $user->admin ? $this->lang->line('gp_all_projects') : $this->lang->line('gp_user_projects'); ?></caption>
    <th class="text-uppercase"><?php echo $this->lang->line('gp_client'); ?></th>
    <th class="text-uppercase"><?php echo $this->lang->line('gp_project'); ?></th>
    <!--        <th class="text-uppercase">--><?php //echo $this->lang->line('gp_public'); ?><!--</th>-->
    <th class="text-uppercase"><?php echo $this->lang->line('gp_crs'); ?></th>
    <th class="text-uppercase"><?php echo $this->lang->line('gp_contact'); ?></th>
    <th class="text-uppercase"><?php echo $this->lang->line('gp_description'); ?></th>

    <?php foreach ($projects as $projects_item): ?>
        <tr>
            <td><?php echo $projects_item['client']; ?></td>
            <td><a target="_self" href="<?php echo site_url($this->config->item('web_client_url').$projects_item['name']); ?>"><?php echo $projects_item['display_name']; ?></a></td>
            <!--            --><?php
            //               $icon = $projects_item['public'] === TRUE ? "glyphicon glyphicon-ok" : "glyphicon glyphicon-remove";
            //               echo "<td><span class='".$icon."' aria-hidden='true'></span></td>";
            //            ?>
            <td><?php echo $projects_item['crs']; ?></td>
            <td><?php echo $projects_item['contact']; ?></td>
            <td><?php echo $projects_item['description']; ?></td>
        </tr>
    <?php endforeach; ?>
</table>