<?php foreach ($clients as $clients_item): ?>

    <div class="row">
        <div class="col-md-4">
            <a href="#">
<!--                <img class="img-responsive" src="--><?php //echo base_url("assets/img/clients/" . $clients_item['name'] . ".png"); ?><!--" alt="">-->
                <img class="img-responsive" src="http://placehold.it/300x200" alt="">
            </a>
        </div>
        <div class="col-md-8">
            <h3><?php echo $clients_item['display_name']; ?></h3>
            <h4><?php echo $clients_item['name']; ?></h4>
            <p>Description.</br><?php echo $clients_item['url']; ?></p>
            <a class="btn btn-primary" href="<?php echo site_url('projects/view/'.$clients_item['id']); ?>">View Projects <span class="glyphicon glyphicon-chevron-right"></span></a>
        </div>
    </div>

    <hr>

<?php endforeach; ?>