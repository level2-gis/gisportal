<div class="col-md-12">
    <div class="row form-group">
        <div class="col-md-offset-2 col-md-5">
            <div <?php if (empty($project["client_id"])) { echo "style='display:none'"; } ?> id="uploadDiv" class="form-inline">
                <?php echo form_open_multipart('#', array("id" => "uploadForm", "onsubmit" => " return onUploadFormSubmit()")); ?>

				<input type="file" accept=".qgs,.zip" id="userfile" name="userfile" class="filestyle"
					   data-buttonBefore="true"
					   data-buttonText="QGS/ZIP File">

				<input class="btn btn-mini btn-success" type="submit"
					   value=<?php echo $this->lang->line('gp_upload'); ?>>
                <input name="project_id" type="hidden" value="<?php echo $project['id']; ?>">
                <input name="project_group_id" type="hidden" value="<?php echo $project['project_group_id']; ?>">
                </form>
            </div>
        </div>
    </div>
</div>
