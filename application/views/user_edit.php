    <div class="page-header clearfix">
		<h1 class="col-md-8"><?php echo $title; ?></h1>
        <div class="actions  pull-right">
            <?php if ($user['admin']) : ?>
                <a onclick="confirmLink(GP.adminRemove,'<?php echo $user['display_name']; ?>','<?php echo site_url('users/set_admin/'. $user['user_id'] . '/' . (int)$user['admin']); ?>')" href="#" class="btn btn-mini btn-danger"><?php echo $role_admin; ?></a>
            <?php else: ?>
                <a onclick="confirmLink(GP.adminAdd,'<?php echo $user['display_name']; ?>','<?php echo site_url('users/set_admin/'. $user['user_id'] . '/' . (int)$user['admin']); ?>')" href="#" class="btn btn-mini btn-success"><?php echo $role_admin; ?></a>
            <?php endif; ?>
        </div>
	</div>

	<?php echo $this->session->flashdata('alert'); ?>

	<div class="col-md-12">
		<?php $attributes = array("class" => "form-horizontal");
		echo form_open('users/edit/' . $user['user_id'], $attributes); ?>
			<input name="user_id" type="hidden" value="<?php echo $user['user_id']; ?>" />

			<ul class="nav nav-tabs">
			  <li class="active"><a href="#edit-user-meta" data-toggle="tab"><?php echo $this->lang->line('gp_properties'); ?></a></li>
			  <li><a href="#edit-access" data-toggle="tab"><?php echo $this->lang->line('gp_groups_title'); ?></a></li>
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
						<label for="first_name" class="control-label col-md-2"><?php echo $this->lang->line('gp_first_name'); ?></label>
						<div class="col-md-5">
							<input class="form-control" name="first_name" placeholder="" type="text" value="<?php echo $user['first_name']; ?>" />
							<span class="text-danger"><?php echo form_error('first_name'); ?></span>
						</div>	
					</div>
                    <div class="form-group">
                        <label for="last_name" class="control-label col-md-2"><?php echo $this->lang->line('gp_last_name'); ?></label>
                        <div class="col-md-5">
                            <input class="form-control" name="last_name" placeholder="" type="text" value="<?php echo $user['last_name']; ?>" />
                            <span class="text-danger"><?php echo form_error('last_name'); ?></span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="organization" class="control-label col-md-2"><?php echo $this->lang->line('gp_organization'); ?></label>
                        <div class="col-md-5">
                            <input class="form-control" name="organization" placeholder="" type="text" value="<?php echo $user['organization']; ?>" />
                            <span class="text-danger"><?php echo form_error('organization'); ?></span>
                        </div>
                    </div>
<!--                    <div class="form-group">-->
<!--						<div class="col-md-offset-2 col-md-4">-->
<!--							<div class="control">-->
<!--								<input type="checkbox" name="admin" value="true" --><?php //if ($user['admin']) { echo "checked='checked'"; }; ?><!-- /> --><?php //echo $this->lang->line('gp_make_admin'); ?>
<!--							</div>-->
<!--					-->
<!--						</div>-->
<!--					</div>-->
		
				</fieldset>

                <fieldset id="edit-access" class="tab-pane">
                    <?php if ($user['admin']) : ?>
                        <div class="alert alert-info col-md-9" role="alert"><?php echo $this->lang->line('gp_user_is_admin'); ?></div>
                    <?php else: ?>

                        <div class="form-inline well">
                            <div class="form-group">
                                <select class="form-control" name="client_id" id="client_id" onchange="onClientChange(this,3);">
                                    <option value="" selected="true" disabled><?php echo $this->lang->line('gp_select_client'); ?></option>
                                    <?php foreach ($clients as $client_item): ?>
                                        <option value="<?php echo $client_item['id']; ?>"><?php echo $client_item['display_name'] . " (" . $client_item['name'] . ")"; ?></option>                            <?php endforeach; ?>
                                </select>

                                <select class="form-control" style="vertical-align: top" multiple name="project_group_id" id="project_group_id">
                                    <option value="" disabled><?php echo $this->lang->line('gp_select_groups'); ?></option>
                                </select>

                                <select class="form-control" id="user_role" name="user_role">
                                    <?php foreach ($roles as $role): ?>
                                        <option value="<?php echo $role['id']; ?>"><?php echo $role['name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <a onclick="addRoleMulti(<?php echo $user['user_id']; ?>)"
                                   class="btn btn-mini btn-success "><?php echo $this->lang->line('gp_add'); ?></a>
                            </div>
                            <div class="pull-right">
                                <a class="btn btn-danger"
                                   onclick="confirmLink(GP.deleteAllRoles,'Groups for user: <?php echo $user['first_name'] . ' ' . $user['last_name']; ?>','<?php echo site_url('users/remove_role/null/' . $user['user_id'] . '/users'); ?>')"><?php echo $this->lang->line('gp_remove'); ?> <?php echo $this->lang->line('gp_all'); ?></a>                </div>
                        </div>

                        <table data-pagination="true" data-search="false" data-toggle="table" data-show-pagination-switch="false">
                            <thead>
                            <tr>
                                <th data-sortable="true" data-field="gp_name"><?php echo $this->lang->line('gp_name'); ?></th>
                                <th data-sortable="true" data-field="gp_display_name"><?php echo $this->lang->line('gp_display_name'); ?></th>
                                <th data-sortable="true" data-field="gp_client"><?php echo $this->lang->line('gp_client'); ?></th>
                                <th data-sortable="true" data-field="gp_role"><?php echo $this->lang->line('gp_role'); ?></th>
                                <th><?php echo $this->lang->line('gp_action'); ?></th>
                            </tr>
                            </thead>
                            <?php foreach ($groups as $group_item): ?>
                                <tr>
                                    <td class="col-md-2"><?php echo $group_item['name']; ?></td>
                                    <td class="col-md-2"><?php echo $group_item['display_name']; ?></td>
                                    <td class="col-md-2"><?php echo $group_item['client']; ?></td>
                                    <td class="col-md-3"><a href="#" onclick="switchRole(<?php echo $group_item['project_group_id'] . ',' .  $user['user_id'] . ',' . $group_item['role_id']; ?>,'users')"><?php echo $group_item['role']; ?></a></td>
                                    <td>
                                        <a class="btn btn-danger" onclick="confirmLink(GP.deleteRole,'Group: <?php echo $group_item['name']; ?>','<?php echo site_url('users/remove_role/' . $group_item['project_group_id'] . '/' . $user['user_id'] . '/users'); ?>')"><?php echo $this->lang->line('gp_remove'); ?></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    <?php endif; ?>
                </fieldset>
			</div>
			<div id="fixed-actions">
				<hr>
                <div class="form-actions col-md-8">
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
