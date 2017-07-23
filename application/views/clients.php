<?php foreach ($clients as $clients_item): ?>

    <div class="row">
        <div class="col-md-4">
            <a href="<?php echo site_url('projects/view/'.$clients_item['id']); ?>">
                <img title="<?php echo $this->lang->line('gp_view_projects'); ?>" class="img-responsive" src="<?php echo base_url("assets/img/clients/" . $clients_item['name'] . ".png"); ?>" alt="">
<!--                <img class="img-responsive" src="http://placehold.it/300x200" alt="">-->
            </a>
        </div>
        <div class="col-md-8">
            <h3 class="top"><?php echo $clients_item['display_name']; ?></h3>
<!--            <h4>--><?php //echo $clients_item['name']; ?><!--</h4>-->
            <p class="client_description"><?php echo $clients_item['description']; ?></p>
            <a class="btn btn-primary bottomaligned" href="<?php echo site_url('projects/view/'.$clients_item['id']); ?>"><?php echo $this->lang->line('gp_view_projects'); ?>
                <span class="badge"><?php echo $clients_item['count']; ?></span>
                <span class="glyphicon glyphicon-chevron-right"></span></a>
        </div>
    </div>

    <hr>

<?php endforeach; ?>