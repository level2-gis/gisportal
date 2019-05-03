<?php foreach ($clients as $clients_item):

    $client_img = "assets/img/clients/" . $clients_item['name'] . ".png";
    $img_title = $this->lang->line('gp_open_project');
    if (file_exists(FCPATH . $client_img)) {
        $client_img = base_url($client_img);
    } else {
        $img_title = "No file: ". $client_img . " (300x200)";
        $client_img = $scheme . "://dummyimage.com/300x200/e0e0e0/706e70?text=".$clients_item['name'] . ".png";
    }

    ?>

    <div class="row">
        <div class="col-md-4">
            <?php if ($open_groups) : ?>
                <a href="<?php echo site_url('project_groups/view/'.$clients_item['id']); ?>">
            <?php else : ?>
                <a href="<?php echo site_url('projects/view/'.$clients_item['id']); ?>">
            <?php endif; ?>
                    <img title="<?php echo $img_title; ?>" class="img-responsive" src="<?php echo $client_img; ?>" alt="">
                </a>
        </div>
        <div class="col-md-8">
             <h3 class="top">
                 <?php echo $clients_item['display_name']; ?>
<!--                    <a style="text-decoration: none" href="--><?php //echo site_url('clients/view/'.$clients_item['id']); ?><!--">--><?php //echo $clients_item['display_name']; ?><!--</a>-->
            </h3>
<!--            <h4>--><?php //echo $clients_item['name']; ?><!--</h4>-->
            <p class="client_description"><?php echo $clients_item['description']; ?></p>
            <?php if ($open_groups) : ?>
                <a class="btn btn-info bottomaligned" href="<?php echo site_url('project_groups/view/'.$clients_item['id']); ?>"><?php echo $this->lang->line('gp_view_groups'); ?>
            <?php else : ?>
                <a class="btn btn-primary bottomaligned" href="<?php echo site_url('projects/view/'.$clients_item['id']); ?>"><?php echo $this->lang->line('gp_view_projects'); ?>
            <?php endif; ?>
                <span class="badge"><?php echo $clients_item['count']; ?></span>
                <span class="glyphicon glyphicon-chevron-right"></span></a>
        </div>
    </div>

    <hr>

<?php endforeach; ?>