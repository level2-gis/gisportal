<div class="col-md-12">
    <div class="row form-group">
        <label for="qgis_check"
               class="control-label col-md-2"><?php echo ($this->lang->line('gp_qgis_project') . ' ' . $project['name']); ?>
            <p class="help-block"><?php echo $project["crs"]; ?></p>
        </label>
        <div class="col-md-5">
            <?php if ($qgis_check['valid']) {
                ?>
                <div class="alert alert-success">
                    <a title="<?php echo ($this->lang->line('gp_download') . ' ' . $this->lang->line('gp_qgis_project')); ?>" class="btn" href="<?php echo site_url('projects/download/' . $project['id']); ?>" role="button">
                        <span class="glyphicon glyphicon-download-alt" aria-hidden="true"></span>
                    </a>
                    <?php echo $qgis_check['name'] ?> <span class="glyphicon glyphicon-ok" aria-hidden="true"></span>
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