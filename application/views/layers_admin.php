
<div class="page-header clearfix">
    <h1 class="col-md-8"><?php echo $title; ?></h1>
    <div class="actions  pull-right">
        <a href="<?php echo site_url('layers/edit/'); ?>" class="btn btn-mini btn-success ">New Layer</a>
    </div>
</div>

<?php echo $this->session->flashdata('alert'); ?>

<div class="col-md-12">

    <table class="table table-condensed table-striped">
        <tr>
            <th>Name</th>
            <th>Display name</th>
            <th>Type</th>
            <th>Action</th>
        </tr>
        <?php foreach ($layers as $layers_item): ?>

            <tr>
                <td class="col-md-2"><?php echo $layers_item['name']; ?></td>
                <td class="col-md-2"><?php echo $layers_item['display_name']; ?></td>
                <td class="col-md-2"><?php echo $layers_item['type']; ?></td>
                <td class="col-md-2">
                    <a class="btn btn-primary" href="<?php echo site_url('layers/edit/'.$layers_item['id']); ?>">
                        <?php echo $this->lang->line('gp_edit'); ?>
                    </a>

                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>