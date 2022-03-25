<p class="help-block">
    <?php echo $navigation ?>
</p>
<?php foreach ($projects as $item):

    $img_path = "assets/img/projects/" . $item['name'] . ".png";
    $name = empty($item['display_name']) ? $item['name'] : $item['display_name'];
    $url = site_url($this->config->item('web_client_url').$item['name']);
    $edit_url = site_url('projects/edit/' . $item['id']);

    if (!file_exists(FCPATH . $img_path)) {
        $img = base_url("assets/img/no_project.png");
        $img_class = 'item_no_image';
        $desc_class = 'description_no_image';
    } else {
        $img = base_url($img_path) . '?v=' . $this->config->item('header_logo_version');
        $img_class = 'item_image';
        $desc_class = 'description';
    }
    ?>

    <div class="col-xs-12 col-sm-6 col-md-4 col-lg-4">
        <div class="thumbnail">
            <h4 class="top"><?php echo $name; ?>
                <?php if($is_admin) : ?>
                    <a target="_self" href="<?php echo $edit_url; ?>"><span class="glyphicon small glyphicon-pencil pull-right" aria-hidden="true"></span></a>
                <?php endif; ?>
            </h4>
            <a target="_self" href="<?php echo $url; ?>"><img class="<?php echo $img_class; ?>" src="<?php echo $img; ?>" alt="<?php echo $item['name']; ?>"/>
                <div class="caption post-content">
                    <p class="<?php echo $desc_class; ?>"><?php echo $item['description']; ?></p>
                </div>
            </a>
        </div>
    </div>

<?php endforeach; ?>