</div>
<div class="container-fluid news-band">
	<div class="container">
		<h4><?php echo lang('gp_rss_latest_news'); ?></h4>
		<table class="table-condensed">
			<?php foreach ($rss['item'] as $item):
				$pubDateInt = strtotime($item['pubDate']);
				if($pubDateInt>$rss['last_login']) {
					$item['new'] = '<span class="label label-danger text-uppercase">'. lang('gp_rss_new') . '</span>';
				} else {
					$item['new'] = '';
				}
			?>
				<tr>
					<td class="col-md-1"><?php echo $item['new']; ?></td>
					<td class="col-md-2"><?php echo set_datestr($item['pubDate'], FALSE); ?></td>
					<td class="col-md-9"><a href="<?php echo $item['link']; ?>" target="_blank"><?php echo $item['title']; ?></a></td>
				</tr>
			<?php endforeach; ?>
		</table>
	</div>
</div>
