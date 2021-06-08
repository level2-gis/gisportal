<div class="col-md-12">
    <p class="help-block"><?php echo $admin_navigation; ?></p>
    <div class="row form-group">
        <label for="qgis_check"
               class="control-label col-md-2"><?php echo ($this->lang->line('gp_qgis_project') . ' ' . $project['name']); ?>
            <p class="help-block"><?php echo $project["crs"]; ?></p>
        </label>
        <div class="col-md-8">
            <?php if ($qgis_check['valid']) {
                ?>
                <div class="alert alert-info">
					<div class="row col-md-offset-1">
						<?php echo $qgis_check['name'] ?> <span class="glyphicon glyphicon-ok"
																aria-hidden="true"></span>
					</div>
					<div class="row col-md-offset-1">
						<?php
						$info = get_file_info($qgis_check['name'], array('size', 'date'));
						echo date('Y-m-d H:i:s', $info['date']) . ', ' . byte_format($info['size'], 2);
						?>
					</div>
					<div class="row col-md-offset-1">
						<?php echo $this->lang->line('gp_download'); ?>:
						<a title="<?php echo($this->lang->line('gp_qgis_project')); ?>"
						   href="<?php echo site_url('projects/download/qgs/' . $project['id']); ?>">QGS</a>
						<a title="project.* and /data subfolder"
						   href="<?php echo site_url('projects/download/zip/' . $project['id']); ?>">ZIP</a>
						<br><a title="<?php echo $this->lang->line('gp_open_project'); ?>"
							   href="<?php echo site_url($this->config->item('web_client_url') . $project['name']); ?>">
							<?php echo $this->lang->line('gp_open_project'); ?>?
						</a>
					</div>
				</div>
				<?php
			} else {
                if ($qgis_check['name'] > '') { ?>
                    <div class="alert alert-danger">
                        <span class="glyphicon glyphicon-alert" aria-hidden="true"></span>
                        <?php echo $qgis_check['name'] ?>
                    </div>
                <?php
                }
            } ?>
        </div>
    </div>
</div>
