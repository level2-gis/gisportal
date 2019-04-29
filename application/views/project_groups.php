<p class="help-block">
    <?php echo $navigation ?>
</p>
<?php foreach ($items as $item):

    $project_img = "assets/img/projects/" . $item['name'] . ".png";
    $img_title = $this->lang->line('gp_open_project');
    if (file_exists(FCPATH . $project_img)) {
        $project_img = base_url($project_img);
    } else {
        $img_title = "No file: ". $project_img . " (225x150)";
        $project_img = $scheme . "://dummyimage.com/225x150/e0e0e0/706e70?text=".$item['name'] . ".png";
    }

    ?>

    <div class="row">
        <div class="col-md-3">
            <a target="_self" href="<?php echo site_url($this->config->item('web_client_url').$item['name']); ?>">
                <img title="<?php echo $img_title; ?>" class="img-responsive" src="<?php echo $project_img; ?>" alt="">
            </a>
        </div>
        <div class="col-md-9">
            <h3 class="top"><?php echo $item['display_name']; ?></h3>
            <p><?php echo $item['type']; ?></p>
            <a class="btn btn-primary bottomaligned" target="_self" href="<?php echo site_url($this->config->item('web_client_url').$item['name']); ?>"><?php echo $this->lang->line('gp_open_project'); ?> <span class="glyphicon glyphicon-chevron-right"></span></a>
        </div>
    </div>

    <hr>


<?php endforeach; ?>