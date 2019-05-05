<?php echo $this->session->flashdata('alert'); ?>
<?php foreach ($clients as $item):

    $img_path = "assets/img/clients/" . $item['name'] . ".png";
    $name = $item['display_name'];

    if (!file_exists(FCPATH . $img_path)) {
        $img = base_url("assets/img/no_client.png");
        $img_class = 'item_no_image';
        $desc_class = 'description_no_image';
    } else {
        $img = base_url($img_path);
        $img_class = 'item_image';
        $desc_class = 'description';
    }

    if ($open_groups) {
        $url = site_url('project_groups/view/' . $item['id']);
    } else {
        $url = site_url('projects/view/' . $item['id']);
    }

    ?>

    <div class="col-xs-12 col-sm-6 col-md-4 col-lg-4">
        <div class="thumbnail">
            <h4 class="top"><?php echo $name; ?></h4>
            <a target="_self" href="<?php echo $url; ?>"><img class="<?php echo $img_class; ?>" src="<?php echo $img; ?>" alt="<?php echo $item['name']; ?>"/>
                <div class="caption post-content">
                    <p class="<?php echo $desc_class; ?>"><?php echo $item['description']; ?></p>
                </div>
            </a>
        </div>
    </div>

<?php endforeach; ?>