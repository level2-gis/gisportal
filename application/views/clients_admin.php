<div class="page-header clearfix">
    <h1 class="col-md-8"><?php echo $title; ?></h1>
    <?php if (empty($current_role_filter)) : ?>
        <div class="actions  pull-right">
            <a href="<?php echo site_url('clients/edit/'); ?>" class="btn btn-mini btn-success "><?php echo $this->lang->line('gp_new_client'); ?></a>
        </div>
    <?php endif; ?>
</div>

<?php echo $this->session->flashdata('alert'); ?>

<div>

    <table data-pagination="true" data-search="true" data-toggle="table" data-show-pagination-switch="true">
        <thead>
        <tr>
            <th></th>
            <th data-sortable="true" data-field="gp_name"><?php echo $this->lang->line('gp_name'); ?></th>
            <th data-sortable="true" data-field="gp_display_name"><?php echo $this->lang->line('gp_display_name'); ?></th>
            <th data-sortable="true" data-align="right" data-field="gp_groups_title"><?php echo $this->lang->line('gp_groups_title'); ?></th>
            <th data-sortable="true" data-align="right" data-field="gp_projects_title"><?php echo $this->lang->line('gp_projects_title'); ?></th>
            <th><?php echo $this->lang->line('gp_action'); ?></th>
        </tr>
        </thead>
        <?php foreach ($clients as $clients_item):

            $img_path = "assets/img/clients/" . $clients_item['name'] . ".png";
            $img_class = "img-responsive";
            $img = base_url($img_path);

            if (!file_exists(FCPATH . $img_path)) {
                $img = base_url("assets/img/no_client.png");
                $img_class .= ' item_no_image';
            }
        ?>

            <tr>
                <td class="col-md-1">
                    <img class="<?php echo $img_class; ?>" src="<?php echo $img; ?>" alt="">
                </td>
                <td class="col-md-3"><?php echo $clients_item['name']; ?></td>
                <td class="col-md-3"><?php echo $clients_item['display_name']; ?></td>
                <td class="col-md-1"><?php echo $clients_item['count_groups']; ?></td>
                <td class="col-md-1"><?php echo $clients_item['count']; ?></td>
                <td class="col-md-3">
                    <a class="btn btn-primary" href="<?php echo site_url('clients/edit/'.$clients_item['id']); ?>">
                        <?php echo $this->lang->line('gp_edit'); ?>
                    </a>

                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>