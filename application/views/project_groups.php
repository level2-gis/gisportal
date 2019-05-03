<p class="help-block">
    <?php echo $navigation ?>
</p>
<?php foreach ($items as $item):

    $project_img = "assets/img/projects/" . $item['name'] . ".png";
    $img_title = $this->lang->line('gp_open_project');

    $title = empty($item['display_name']) ? $item['name'] : $item['display_name'];
    $url_g = site_url('project_groups/view/'.$client_id.'/'.$item['id']);
    $url_p = site_url('projects/view_group/'.$client_id.'/'.$item['id']);

    if (file_exists(FCPATH . $project_img)) {
        $project_img = base_url($project_img);
    } else {
        $img_title = "No file: ". $project_img . " (225x150)";
        $project_img = $scheme . "://dummyimage.com/225x150/e0e0e0/706e70?text=".$item['name'] . ".png";
    }

    ?>

    <div class="row">
        <div class="col-md-3">
            <?php if($item['type'] === SUB_GROUP): ?>
                <a target="_self" href="<?php echo $url_g; ?>">
                    <img title="<?php echo $img_title; ?>" class="img-responsive" src="<?php echo $project_img; ?>" alt="">
                </a>
            <?php else : ?>
                <a target="_self" href="<?php echo $url_p; ?>">
                    <img title="<?php echo $img_title; ?>" class="img-responsive" src="<?php echo $project_img; ?>" alt="">
                </a>
            <?php endif; ?>
        </div>
        <div class="col-md-9">
            <h3 class="top"><?php echo $title; ?></h3>
            <p><?php echo $item['type']; ?></p>
            <?php if($item['type'] === SUB_GROUP): ?>
                <a class="btn btn-info bottomaligned" target="_self" href="<?php echo $url_g; ?>"><?php echo $this->lang->line('gp_open_group'); ?> <span class="glyphicon glyphicon-chevron-right"></span></a>
            <?php else : ?>
                <a class="btn btn-primary bottomaligned" target="_self" href="<?php echo $url_p; ?>"><?php echo $this->lang->line('gp_view_projects'); ?> <span class="glyphicon glyphicon-chevron-right"></span></a>
            <?php endif; ?>
    </div>
    </div>

    <hr>


<?php endforeach; ?>