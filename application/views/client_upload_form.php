    <?php echo $this->session->flashdata('upload_msg'); ?>

    <?php echo form_open_multipart('clients/upload/'.$client_id); ?>

    <label for="exampleInputFile"><?php echo $this->lang->line('gp_add_new_file'); ?></label>
    <input type="file" name="userfile" size="20"/>
    <p class="help-block"><?php echo $this->lang->line('gp_info_demo'); ?></p>
    <input class="btn btn-primary" type="submit" value=<?php echo $this->lang->line('gp_upload'); ?>>
</form>
