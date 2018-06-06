<div class="page-header clearfix">
    <h1 class="col-md-8"><?php echo $title; ?></h1>
    <div class="actions  pull-right">
        <a href="<?php echo site_url('clients/edit/'); ?>" class="btn btn-mini btn-success "><?php echo $this->lang->line('gp_new_client'); ?></a>
    </div>
</div>

<?php echo $this->session->flashdata('alert'); ?>

<div class="col-md-12">

    <table data-pagination="true" data-search="true" data-toggle="table" data-show-pagination-switch="true">
        <thead>
        <tr>
            <th></th>
            <th data-sortable="true" data-field="gp_name"><?php echo $this->lang->line('gp_name'); ?></th>
            <th data-sortable="true" data-field="gp_display_name"><?php echo $this->lang->line('gp_display_name'); ?></th>
            <th data-sortable="true" data-field="gp_project_count"><?php echo $this->lang->line('gp_project_count'); ?></th>
            <th><?php echo $this->lang->line('gp_action'); ?></th>
        </tr>
        </thead>
        <?php foreach ($clients as $clients_item): ?>

            <tr>
                <td class="col-md-1">
                    <img title="<?php echo $this->lang->line('gp_view_projects'); ?>" class="img-responsive" src="<?php echo base_url("assets/img/clients/" . $clients_item['name'] . ".png"); ?>" alt="">
                </td>
                <td class="col-md-2"><?php echo $clients_item['name']; ?></td>
                <td class="col-md-2"><?php echo $clients_item['display_name']; ?></td>
                <td class="col-md-2"><?php echo $clients_item['count']; ?></td>
                <td class="col-md-2">
                    <a class="btn btn-primary" href="<?php echo site_url('clients/edit/'.$clients_item['id']); ?>">
                        <?php echo $this->lang->line('gp_edit'); ?>
                    </a>

                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>