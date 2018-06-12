<div class="page-header clearfix">
    <h1 class="col-md-8"><?php echo $title; ?></h1>
</div>

<?php echo $this->session->flashdata('alert'); ?>
<?php echo $this->session->flashdata('upload_msg'); ?>

<div class="form-group">
    <label for="qgis_check"
           class="control-label col-md-2"><?php echo $this->lang->line('gp_qgis_project'); ?></label>

    <div class="col-md-5">
        <?php if ($qgis_check['valid']) {
            ?>
            <div class="alert alert-success">
                <a class="btn" href="<?php echo site_url('projects/download/'.$project['id']); ?>" role="button">
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



<?php

$style = 'none';
if ($project['client_id']) {
    $style = 'block';
}?>

<div style="display: <?php echo $style; ?>" id="uploadDiv" class="form-inline">
    <?php echo form_open_multipart('projects/upload_admin/', array("id" => "uploadForm", "onsubmit" => " return onUploadFormSubmit()")); ?>

    <input type="file" id="userfile" name="userfile" class="filestyle" data-buttonBefore="true" data-buttonText="QGIS File">

    <input class="btn btn-mini btn-success" type="submit" value=<?php echo $this->lang->line('gp_upload'); ?>>
    <input name="project_id" type="hidden" value="<?php echo $project['id']; ?>">
</div>
</form>
