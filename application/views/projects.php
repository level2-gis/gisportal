<h2>
    <?php echo $projects[0]['client'] ?>
<!--    <a style="text-decoration: none" href="--><?php //echo site_url('clients/view/'.$projects[0]['client_id']); ?><!--">--><?php //echo $projects[0]['client'] ?><!--</a>-->
</h2>
<?php foreach ($projects as $projects_item):

    $project_img = "assets/img/projects/" . $projects_item['name'] . ".png";
    $img_title = $this->lang->line('gp_open_project');
    if (file_exists(FCPATH . $project_img)) {
        $project_img = base_url($project_img);
    } else {
        $img_title = "No file: ". $project_img . " (225x150)";
        $project_img = $scheme . "://dummyimage.com/225x150/e0e0e0/706e70?text=".$projects_item['name'] . ".png";
    }

    ?>

    <div class="row">
        <div class="col-md-3">
            <a target="_self" href="<?php echo site_url($this->config->item('web_client_url').$projects_item['name']); ?>">
                <img title="<?php echo $img_title; ?>" class="img-responsive" src="<?php echo $project_img; ?>" alt="">
            </a>
        </div>
        <div class="col-md-9">
            <h3 class="top"><?php echo $projects_item['display_name']; ?></h3>
            <p class="project_description"><?php echo $projects_item['crs']; ?></br><?php echo $projects_item['description']; ?></br><?php echo $projects_item['contact']; ?></p>
            <a class="btn btn-primary bottomaligned" target="_self" href="<?php echo site_url($this->config->item('web_client_url').$projects_item['name']); ?>"><?php echo $this->lang->line('gp_open_project'); ?> <span class="glyphicon glyphicon-chevron-right"></span></a>
        </div>
    </div>

    <hr>


<?php endforeach; ?>