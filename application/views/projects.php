<h2><?php echo $client[0]->display_name ?> projects</h2>
<?php foreach ($projects as $projects_item): ?>

    <div class="media">
        <div class="media-left">
            <a href="#">
                <img class="media-object" src="http://placehold.it/225x150" alt="">
            </a>
        </div>
        <div class="media-body">
            <h3 class="media-heading"><?php echo $projects_item['name']; ?></h3>
            <p>Description</p>
            <a class="btn btn-primary" target="client" href="<?php echo site_url($this->config->item('web_client_url').$projects_item['name']); ?>">Open project <span class="glyphicon glyphicon-chevron-right"></span></a>
        </div>
    </div>

<?php endforeach; ?>