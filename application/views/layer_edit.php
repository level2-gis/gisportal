	<div class="page-header clearfix">
		<h1 class="col-md-8"><span><?php echo $title; ?></span></h1>
	</div>

	<?php echo $this->session->flashdata('alert'); ?>

	<div class="col-md-12">
		<?php $attributes = array("class" => "form-horizontal");
		echo form_open('layers/edit/' . $layer['id'], $attributes); ?>
			<input name="id" type="hidden" value="<?php echo $layer['id']; ?>" />

			<fieldset id="edit-location-meta">
				<div class="form-group">
					<label for="name" class="control-label col-md-2"><?php echo $this->lang->line('gp_name'); ?></label>
					<div class="col-md-5">
						<input class="form-control" name="name" placeholder="" type="text" value="<?php echo $layer['name']; ?>" />
						<span class="text-danger"><?php echo form_error('name'); ?></span>
                        <p class="help-block"><?php echo $this->lang->line('gp_name_help'); ?></p>
					</div>	
				</div>	
				<div class="form-group">
					<label for="display_name" class="control-label col-md-2"><?php echo $this->lang->line('gp_display_name'); ?></label>
					<div class="col-md-5">
						<input class="form-control" name="display_name" placeholder="" type="text" value="<?php echo $layer['display_name']; ?>" />
						<span class="text-danger"><?php echo form_error('display_name'); ?></span>
					</div>	
				</div>	
				<div class="form-group">
					<label for="url" class="control-label col-md-2"><?php echo $this->lang->line('gp_type'); ?></label>
					<div class="col-md-5">
                        <select class="form-control" name="type">
                            <option value="">Select Type</option>
                            <?php foreach ($types as $type): ?>
                                <option <?php if ($type == $layer['type']) { echo "selected='selected'"; }; ?> value="<?php echo $type; ?>"><?php echo $type; ?></option>
                            <?php endforeach; ?>
                        </select>
						<span class="text-danger"><?php echo form_error('type'); ?></span>
					</div>	
				</div>	
				<div class="form-group">
					<label for="description" class="control-label col-md-2"><?php echo $this->lang->line('gp_definition'); ?></label>
					<div class="col-md-5">
						<textarea class="form-control" cols="20" rows="6" name="definition" placeholder="" type="text"><?php echo $layer['definition']; ?></textarea>
						<span class="text-danger"><?php echo form_error('definition'); ?></span>
					</div>	
				</div>
            </fieldset>


			<div id="fixed-actions">
				<div class="form-actions col-md-offset-1 col-md-8">
					<input name="creating" type="hidden" value="<?php echo $creating; ?>">

					<input type="submit" class="btn btn-primary" value=<?php echo $this->lang->line('gp_save'); ?>>
					<input type="submit" class="btn btn-primary" onclick="checkValues()" name="return" value=<?php echo $this->lang->line('gp_save')."&nbsp;&&nbsp;".strtolower($this->lang->line('gp_return')); ?>>
					<a class="btn btn-default" href="<?php echo site_url('layers/'); ?>"><?php echo $this->lang->line('gp_return'); ?></a>
				
				<?php if ( $creating === false && !empty($layer['id']) ) : ?>
				<div class="pull-right">
                    <a class="btn btn-danger" onclick="confirmLink(GP.deleteGeneral,'Layer: <?php echo $layer['display_name'].' ('.$layer['name'].')'; ?>','<?php echo site_url('layers/remove/'.$layer['id']); ?>')"><?php echo $this->lang->line('gp_delete'); ?></a>
				</div>
				 <?php endif; ?>
				</div>
			</div>

		</form>

	</div>
