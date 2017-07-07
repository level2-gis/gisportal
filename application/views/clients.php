<?php foreach ($clients as $clients_item): ?>

    <h3><?php echo $clients_item['name']; ?></h3>
    <div class="main">
        <?php echo $clients_item['url']; ?>
    </div>
    <p><a href="<?php echo site_url('projects/view/'.$clients_item['id']); ?>">View projects</a></p>

<?php endforeach; ?>