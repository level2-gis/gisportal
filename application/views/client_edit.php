	<div class="page-header clearfix">
		<h1 class="col-md-8"><i class="fa fa-folder-open"></i> <span><?php echo $title; ?></span></h1>
	</div>

	<?php echo $this->session->flashdata('alert'); ?>

	<div class="col-md-12">
		<?php $attributes = array("class" => "form-horizontal");
		echo form_open('clients/edit/' . $client['id'], $attributes); ?>
			<input name="id" type="hidden" value="<?php echo $client['id']; ?>" />

			<fieldset id="edit-location-meta">
				<div class="form-group">
					<label for="name" class="control-label col-md-3">Name</label>
					<div class="col-md-4">
						<input class="form-control" name="name" placeholder="" type="text" value="<?php echo $client['name']; ?>" />
						<span class="text-danger"><?php echo form_error('name'); ?></span>
					</div>	
				</div>	
				<div class="form-group">
					<label for="display_name" class="control-label col-md-3">Display name</label>
					<div class="col-md-4">
						<input class="form-control" name="display_name" placeholder="" type="text" value="<?php echo $client['display_name']; ?>" />
						<span class="text-danger"><?php echo form_error('display_name'); ?></span>
					</div>	
				</div>	
				<div class="form-group">
					<label for="url" class="control-label col-md-3">Web site</label>
					<div class="col-md-4">
						<input class="form-control" name="url" placeholder="" type="text" value="<?php echo $client['url']; ?>" />
						<span class="text-danger"><?php echo form_error('url'); ?></span>
					</div>	
				</div>	
				<div class="form-group">
					<label for="description" class="control-label col-md-3">Description</label>
					<div class="col-md-4">
						<textarea class="form-control" cols="20" rows="2" name="description" placeholder="" type="text"><?php echo $client['description']; ?></textarea>
						<span class="text-danger"><?php echo form_error('description'); ?></span>
					</div>	
				</div>
                <div class="form-group">
                    <label for="url" class="control-label col-md-3">Display order</label>
                    <div class="col-md-4">
                        <input class="form-control" name="ordr" placeholder="" type="integer" value="<?php echo $client['ordr']; ?>" />
                        <span class="text-danger"><?php echo form_error('ordr'); ?></span>
                    </div>
                </div>
            </fieldset>


			<div id="fixed-actions">
				<div class="form-actions col-md-offset-2 col-md-8">
					<input name="creating" type="hidden" value="<?php echo $creating; ?>">

					<input type="submit" class="btn btn-primary" value="Save">
					<input type="submit" class="btn btn-primary" name="return" value="Save &amp; Return">
					<a class="btn btn-default" href="<?php echo site_url('clients/'); ?>">Return</a>
				
				<?php if ( $creating === false ) : ?>
				<div class="pull-right">
                    <a class="btn btn-danger" href="<?php echo site_url('clients/remove/'.$client['id']); ?>">Delete</a>
				</div>
				 <?php endif; ?>
				</div>
			</div>

		</form>

	</div>
