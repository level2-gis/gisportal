<?php foreach ($projects as $projects_item): ?>

    <h3><?php echo $projects_item['name']; ?></h3>
    <div class="main">
        <?php echo $projects_item['name']; ?>
    </div>
    <p><a target="client" href="<?php echo site_url('../../gisapp/'.$projects_item['name']); ?>">Open project</a></p>

<?php endforeach; ?>