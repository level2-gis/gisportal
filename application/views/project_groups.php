<p class="help-block">
    <?php echo $navigation ?>
</p>
<?php foreach ($items as $item):

    $img_path = "assets/img/groups/" . $item['name'] . ".png";
    $name = empty($item['display_name']) ? $item['name'] : $item['display_name'];

    if (!file_exists(FCPATH . $img_path)) {
        if ($item['type'] === SUB_GROUP) {
            $img = base_url("assets/img/no_sub_group.png");
        } else {
            $img = base_url("assets/img/no_project_group.png");
        }
        $img_class = 'item_no_image';
    } else {
        $img = base_url($img_path);
        $img_class = 'item_image';
    }

    if ($item['type'] === SUB_GROUP) {
        $url = site_url('project_groups/view/'.$client_id.'/'.$item['id']);
    } else {
        $url = site_url('projects/view_group/'.$client_id.'/'.$item['id']);
    }
    ?>

    <div class="col-xs-12 col-sm-6 col-md-4 col-lg-4">
        <div class="thumbnail">
            <a target="_self" href="<?php echo $url; ?>"><img class="<?php echo $img_class; ?>" src="<?php echo $img; ?>" alt="<?php echo $item['name']; ?>"/>
                <div class="caption post-top">
                    <h4><?php echo $name; ?></h4>
                </div>
            </a>
        </div>
    </div>

<?php endforeach; ?>