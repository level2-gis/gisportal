<div class="page-header clearfix">
    <h1 class="col-md-8"><?php echo $title; ?></h1>
    <div class="actions  pull-right">
        <a href="<?php echo site_url('clients/edit/'); ?>" class="btn btn-mini btn-success ">New Client</a>
    </div>
</div>

<?php echo $this->session->flashdata('alert'); ?>

<div class="col-md-12">

    <table class="table table-condensed table-striped">
        <tr>
            <th></th>
            <th>Name</th>
            <th>Display name</th>
            <th>Project Count</th>
            <th>Action</th>
        </tr>
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