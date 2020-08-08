<div class="page-header clearfix">
    <h1 class="col-xs-8"><?php echo $title; ?></h1>
    <div class="col-xs-4 col-md-1 actions  pull-right">
        <a href="<?php echo site_url('layers/edit/'); ?>" class="btn btn-mini btn-success "><?php echo $this->lang->line('gp_new_layer'); ?></a>
    </div>
</div>

<?php echo $this->session->flashdata('alert'); ?>

<div class="col-md-12">

    <table data-pagination="true" data-search="true" data-toggle="table" data-show-pagination-switch="true">
        <thead>
        <tr>
            <th data-sortable="true" data-field="gp_name"><?php echo $this->lang->line('gp_name'); ?></th>
            <th data-sortable="true" data-field="gp_display_name"><?php echo $this->lang->line('gp_display_name'); ?></th>
            <th data-sortable="true" data-field="gp_type"><?php echo $this->lang->line('gp_type'); ?></th>
            <th data-sortable="true" data-field="gp_client"><?php echo $this->lang->line('gp_client'); ?></th>
            <th data-sortable="true" data-align="right" data-field="gp_groups_title"><?php echo $this->lang->line('gp_groups_title'); ?></th>
            <th><?php echo $this->lang->line('gp_action'); ?></th>
        </tr>
        </thead>
        <?php foreach ($layers as $layers_item): ?>

            <tr>
                <td class="col-md-2"><?php echo $layers_item['name']; ?></td>
                <td class="col-md-2"><?php echo $layers_item['display_name']; ?></td>
                <td class="col-md-2"><?php echo $layers_item['type']; ?></td>
                <td class="col-md-2"><?php echo $layers_item['client']; ?></td>
                <td class="col-md-1"><?php echo $layers_item['groups']; ?></td>
                <td class="col-md-3">
                    <a class="btn btn-primary" href="<?php echo site_url('layers/edit/'.$layers_item['id']); ?>">
                        <?php echo $this->lang->line('gp_edit'); ?>
                    </a>
                    <a class="btn btn-info" href="<?php echo site_url('layers/edit/'.$layers_item['id'].'/1'); ?>">
                        <?php echo lang('gp_copy'); ?>
                    </a>
					<a class="btn btn-default" href="<?php echo site_url('layers/map/'.$layers_item['id'].'/1'); ?>">
						<?php echo lang('gp_map'); ?>
					</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>
