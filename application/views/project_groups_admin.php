<div class="page-header clearfix">
    <h1 class="col-md-8"><?php echo $title; ?></h1>
    <div class="actions  pull-right">
        <a href="<?php echo site_url('project_groups/create'); ?>" class="btn btn-mini btn-success "><?php echo $this->lang->line('gp_new_group'); ?></a>
    </div>
</div>

<?php echo $this->session->flashdata('alert'); ?>

<div class="col-md-12">

    <table data-pagination="true" data-search="true" data-toggle="table" data-show-pagination-switch="true">
        <thead>
        <tr>
            <th data-sortable="true" data-field="gp_name"><?php echo $this->lang->line('gp_name'); ?></th>
            <th data-sortable="true" data-field="gp_display_name"><?php echo $this->lang->line('gp_display_name'); ?></th>
            <th data-sortable="true" data-field="gp_client"><?php echo $this->lang->line('gp_client'); ?></th>
            <th data-sortable="true" data-field="gp_projects_title"><?php echo $this->lang->line('gp_projects_title'); ?></th>
            <th data-sortable="true" data-field="gp_base_layers"><?php echo $this->lang->line('gp_base_layers'); ?></th>
            <th data-sortable="true" data-field="gp_overlay_layers"><?php echo $this->lang->line('gp_overlay_layers'); ?></th>
            <th data-sortable="true" data-field="gp_users"><?php echo $this->lang->line('gp_users_title'); ?></th>
            <th><?php echo $this->lang->line('gp_action'); ?></th>
        </tr>
        </thead>
        <?php foreach ($groups as $groups_item): ?>

            <tr>
                <td class="col-md-2"><?php echo $groups_item['name']; ?></td>
                <td class="col-md-2"><?php echo $groups_item['display_name']; ?></td>
                <td class="col-md-2"><?php echo $groups_item['client']; ?></td>
                <td class="col-md-1"><?php echo $groups_item['projects']; ?></td>
                <td class="col-md-1"><?php echo $groups_item['base_layers']; ?></td>
                <td class="col-md-1"><?php echo $groups_item['extra_layers']; ?></td>
                <td class="col-md-1"><?php echo $groups_item['users']; ?></td>
                <td class="col-md-2">
                    <a class="btn btn-primary" href="<?php echo site_url('project_groups/edit/'.$groups_item['id']); ?>">
                        <?php echo $this->lang->line('gp_edit'); ?>
                    </a>

                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>