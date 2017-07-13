<h2><?php echo $projects[0]['client'] ?> projects</h2>
<?php foreach ($projects as $projects_item): ?>

    <div class="media">
        <div class="media-left">
            <a href="<?php echo site_url($this->config->item('web_client_url').$projects_item['name']); ?>">
                <img class="media-object" src="http://placehold.it/225x150" alt="">
            </a>
        </div>
        <div class="media-body">
            <h3 class="media-heading"><?php echo $projects_item['display_name']; ?></h3>
            <p><?php echo $projects_item['crs']; ?></p>
            <div class="trunc"><?php echo $projects_item['description']; ?></div>
            <a class="btn btn-primary" target="client" href="<?php echo site_url($this->config->item('web_client_url').$projects_item['name']); ?>">Open project <span class="glyphicon glyphicon-chevron-right"></span></a>
        </div>
    </div>

<?php endforeach; ?>