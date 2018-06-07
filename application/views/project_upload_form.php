
    <?php

    $style = 'none';
    if ($project['client_id']) {
        $style = 'block';
    }

    echo $this->session->flashdata('upload_msg'); ?>

    <div class="actions  pull-right" style="display: <?php echo $style; ?>" id="uploadDiv">
    <?php echo form_open_multipart('projects/upload_admin/', array("id" => "uploadForm", "onsubmit" => " return onUploadFormSubmit()")); ?>

    <input type="file" id="userfile" name="userfile" size="10"/>
    <input name="project_id" type="hidden" value="<?php echo $project['id']; ?>">
    <input class="btn btn-mini btn-success" type="submit" value=<?php echo $this->lang->line('gp_upload'); ?>>
    </div>
</form>
