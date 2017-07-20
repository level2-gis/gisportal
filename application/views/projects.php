<h2><?php echo $projects[0]['client'] ?> projects</h2>
<?php foreach ($projects as $projects_item): ?>

    <div class="row">
        <div class="col-md-3">
            <a target="_self" href="<?php echo site_url($this->config->item('web_client_url').$projects_item['name']); ?>">
                <img height="150px" width="225px" src="<?php echo base_url("assets/img/projects/" . $projects_item['name'] . ".png"); ?>" alt="">
<!--                <img class="img-responsive" src="http://placehold.it/225x150" alt="">-->
            </a>
        </div>
        <div class="col-md-9">
            <h3 class="top"><?php echo $projects_item['display_name']; ?></h3>
            <p class="project_description"><?php echo $projects_item['crs']; ?></br><?php echo $projects_item['description']; ?></br><?php echo $projects_item['contact']; ?></p>
            <a class="btn btn-primary bottomaligned" target="_self" href="<?php echo site_url($this->config->item('web_client_url').$projects_item['name']); ?>">Open project <span class="glyphicon glyphicon-chevron-right"></span></a>
        </div>
    </div>

    <hr>


<?php endforeach; ?>