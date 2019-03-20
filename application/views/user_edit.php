	<div class="page-header clearfix">
		<h1 class="col-md-8"><?php echo $title; ?></h1>
	</div>

	<?php echo $this->session->flashdata('alert'); ?>

	<div class="col-md-12">
		<?php $attributes = array("class" => "form-horizontal");
		echo form_open('users/edit/' . $user['user_id'], $attributes); ?>
			<input name="user_id" type="hidden" value="<?php echo $user['user_id']; ?>" />

			<ul class="nav nav-tabs">
			  <li class="active"><a href="#edit-user-meta" data-toggle="tab"><?php echo $this->lang->line('gp_properties'); ?></a></li>
			  <li><a href="#edit-user-projects" data-toggle="tab"><?php echo $this->lang->line('gp_projects_title'); ?></a></li>
			</ul>

			<div class="tab-content">

				<fieldset id="edit-user-meta" class="tab-pane active">
					<div class="form-group">
						<label for="user_name" class="control-label col-md-2"><?php echo $this->lang->line('gp_username'); ?></label>
						<div class="col-md-5">
							<input class="form-control" name="user_name" placeholder="" readonly="readonly" type="text" value="<?php echo $user['user_name']; ?>" />
							<span class="text-danger"><?php echo form_error('user_name'); ?></span>
						</div>	
					</div>	
					<div class="form-group">
						<label for="user_email" class="control-label col-md-2"><?php echo $this->lang->line('gp_email'); ?></label>
						<div class="col-md-5">
							<input class="form-control" name="user_email" placeholder="" readonly="readonly" type="text" value="<?php echo $user['user_email']; ?>" />
							<span class="text-danger"><?php echo form_error('user_email'); ?></span>
						</div>	
					</div>	
					<div class="form-group">
						<label for="display_name" class="control-label col-md-2"><?php echo $this->lang->line('gp_name'); ?></label>
						<div class="col-md-5">
							<input class="form-control" name="display_name" placeholder="" type="text" value="<?php echo $user['display_name']; ?>" />
							<span class="text-danger"><?php echo form_error('display_name'); ?></span>
						</div>	
					</div>
                    <div class="form-group">
                        <label for="organization" class="control-label col-md-2"><?php echo $this->lang->line('gp_organization'); ?></label>
                        <div class="col-md-5">
                            <input class="form-control" name="organization" placeholder="" type="text" value="<?php echo $user['organization']; ?>" />
                            <span class="text-danger"><?php echo form_error('organization'); ?></span>
                        </div>
                    </div>
                    <div class="form-group">
						<div class="col-md-offset-2 col-md-4">
							<div class="control">
								<input type="checkbox" name="admin" value="true" <?php if ($user['admin']) { echo "checked='checked'"; }; ?> /> <?php echo $this->lang->line('gp_make_admin'); ?>
							</div>
					
						</div>
					</div>
		
				</fieldset>

				<fieldset id="edit-user-projects" class="tab-pane">
					<?php if ($user['admin']) : ?>
                        <div class="alert alert-info col-md-9" role="alert"><?php echo $this->lang->line('gp_user_is_admin'); ?></div>
                    <?php endif; ?>
                    <?php if (!$user['admin']) : ?>
					<table class="table table-condensed table-striped">
					  <tr>
						<th><?php echo $this->lang->line('gp_access'); ?></th>
						<th><?php echo $this->lang->line('gp_code'); ?></th>
						<th><?php echo $this->lang->line('gp_name'); ?></th>
						<th><?php echo $this->lang->line('gp_client'); ?></th>
					  </tr>
					  <?php foreach ($projects as $project_item): ?>

						<tr>
						   <td class="col-md-1">
							 <input type="checkbox" name="project_ids[]" value="<?php echo $project_item['id']; ?>" <?php if ($project_item['selected']) { echo "checked='checked'"; }; ?> />
						   </td>
						  <td class="col-md-2"><?php echo $project_item['name']; ?></td>
						  <td class="col-md-2"><?php echo $project_item['display_name']; ?></td>
						  <td class="col-md-2"><?php echo $project_item['client_name']; ?></td>
						</tr>
						<?php endforeach; ?>
					</table>
                    <?php endif; ?>


				</fieldset>

			</div>
			<div id="fixed-actions">
				<div class="form-actions col-md-offset-1 col-md-8">
					<input name="creating" type="hidden" value="<?php echo $creating; ?>">

					<input type="submit" class="btn btn-primary" value=<?php echo $this->lang->line('gp_save'); ?>>
					<input type="submit" class="btn btn-primary" name="return" value=<?php echo $this->lang->line('gp_save')."&nbsp;&&nbsp;".strtolower($this->lang->line('gp_return')); ?>>
					<a class="btn btn-default" href="<?php echo site_url('users/'); ?>"><?php echo $this->lang->line('gp_return'); ?></a>
				
				<?php if ( $creating === false ) : ?>
				<div class="pull-right">
					<a class="btn btn-danger" onclick="confirmLink(GP.deleteGeneral,'User: <?php echo $user['user_name'].' ('.$user['user_email'].')'; ?>','<?php echo site_url('users/remove/'.$user['user_id']); ?>')"><?php echo $this->lang->line('gp_delete'); ?></a>
                </div>
				 <?php endif; ?>
				</div>
			</div>


		</form>

	</div>
