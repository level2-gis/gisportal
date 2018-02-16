<?php echo $this->session->flashdata('upload_msg'); ?>

<?php echo form_open_multipart('clients/upload/'.$client_id); ?>

<input type="file" name="userfile" size="20"/>

<br/><br/>

<input type="submit" value="upload"/>

</form>
