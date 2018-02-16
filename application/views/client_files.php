<p><?php echo $test; ?></p>
<table class="table">
    <caption><?php echo $main; ?></caption>
    <th>NAME</th>
    <th>SIZE</th>
    <th>DATE</th>
    <?php foreach ($files as $file): ?>
        <tr>
            <td><a href="<?php echo base_url($dir.$file['name']); ?>" target="_blank"><?php echo $file['name']; ?></a></td>
            <td align="right"><?php echo byte_format($file['size']); ?></td>
            <td><?php echo unix_to_human($file['date']); ?></td>
        </tr>
    <?php endforeach; ?>
</table>