	<div class="col-md-12">
		<?php $attributes = array("class" => "form-horizontal", "id" => "edit");
		echo form_open('projects/edit/' . $project['id'], $attributes); ?>
			<input name="id" type="hidden" value="<?php echo $project['id']; ?>" />
			<input id="project_name" name="name" type="hidden" value="<?php echo $project['name']; ?>" />

			<ul class="nav nav-tabs">
			  <li class="active"><a href="#edit-project-meta" data-toggle="tab"><?php echo $this->lang->line('gp_properties'); ?></a></li>
			  <li><a href="#edit-project-plugins" data-toggle="tab"><?php echo $this->lang->line('gp_plugins'); ?></a></li>
			</ul>

			<div class="tab-content">

				<fieldset id="edit-project-meta" class="tab-pane active">

                    <div class="row form-group">
                        <label for="client_id" class="control-label col-md-2"><?php echo $this->lang->line('gp_client'); ?></label>
                        <div class="col-md-5">
                            <select class="form-control" disabled="true" name="client_id" id="client_id">
                                <option value="" selected="true" disabled><?php echo $this->lang->line('gp_select_client'); ?></option>
                                <?php foreach ($clients as $client_item): ?>
                                    <option <?php if ($client_item['id'] == $project['client_id']) { echo "selected='selected'"; }; ?> value="<?php echo $client_item['id']; ?>"><?php echo $client_item['display_name'] . " (" .$client_item['name'] . ")"; ?></option>							<?php endforeach; ?>
                            </select>
                            <span class="text-danger"><?php echo form_error('client_id'); ?></span>
                        </div>
                    </div>

                    <div class="row form-group">
                        <label for="project_group_id" class="control-label col-md-2"><?php echo $this->lang->line('gp_group'); ?></label>
                        <div class="col-md-4">
                            <select class="form-control" name="project_group_id" id="project_group_id" onchange="onGroupChange(<?php echo $project['project_group_id']; ?>, this);">
                                <option value="" selected="true" disabled><?php echo $this->lang->line('gp_select_group'); ?></option>
                                <?php foreach ($groups as $group_item): ?>
                                    <option <?php if ($group_item['id'] == $project['project_group_id']) { echo "selected='selected'"; }; ?> value="<?php echo $group_item['id']; ?>"><?php echo $group_item['name']; ?></option>							<?php endforeach; ?>
                            </select>
                            <span class="text-danger"><?php echo form_error('project_group_id'); ?></span>
                        </div>
                        <div class="col-md-1">
                            <a class="btn btn-primary" id="projectGroupEditBtn" onclick="onProjectGroupEditClick();">
                                <?php echo $this->lang->line('gp_edit'); ?>
                            </a>
<!--                            <a class="btn btn-info"-->
<!--                               href="--><?php //echo site_url('projects/services/'); ?><!--">-->
<!--                                --><?php //echo $this->lang->line('gp_publish'); ?>
<!--                            </a>-->
                        </div>
                    </div>

                    <div class="row form-group">
                        <label for="display_name" class="control-label col-md-2"><?php echo $this->lang->line('gp_display_name'); ?></label>
                        <div class="col-md-5">
                            <input class="form-control" name="display_name" placeholder="" type="text" value="<?php echo $project['display_name']; ?>" />
                            <span class="text-danger"><?php echo form_error('display_name'); ?></span>
                        </div>
                    </div>

                    <div class="row form-group">
                        <label for="overview_layer_id" class="control-label col-md-2"><?php echo $this->lang->line('gp_overview_layer'); ?></label>
                        <div class="col-md-5">
                            <select class="form-control" name="overview_layer_id">
                                <option value=""><?php echo $this->lang->line('gp_select_layer'); ?></option>
                                <?php foreach ($base_layers as $layer_item): ?>
                                    <option <?php if ($layer_item['id'] == $project['overview_layer_id']) { echo "selected='selected'"; }; ?> value="<?php echo $layer_item['id']; ?>"><?php echo $layer_item['display_name'] . " (" .$layer_item['name'] . ")"; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <span class="text-danger"><?php echo form_error('overview_layer_id'); ?></span>
                        </div>
                    </div>

					<div class="row form-group">
						<label for="contact" class="control-label col-md-2"><?php echo ucfirst($this->lang->line('gp_contact')); ?></label>
						<div class="col-md-5">
							<input class="form-control" name="contact" placeholder="" type="text" value="<?php echo $project['contact']; ?>" />
							<span class="text-danger"><?php echo form_error('contact'); ?></span>
						</div>	
					</div>	
					<div class="row form-group">
						<label for="feedback_email" class="control-label col-md-2"><?php echo $this->lang->line('gp_feedback_email'); ?></label>
						<div class="col-md-5">
							<input class="form-control" name="feedback_email" placeholder="" type="text" value="<?php echo $project['feedback_email']; ?>" />
							<span class="text-danger"><?php echo form_error('feedback_email'); ?></span>
						</div>	
					</div>
                    <div class="row form-group">
                        <label for="ordr" class="control-label col-md-2"><?php echo $this->lang->line('gp_order'); ?></label>
                        <div class="col-md-5">
                            <input class="form-control" name="ordr" placeholder="" type="text" value="<?php echo $project['ordr']; ?>" />
                            <span class="text-danger"><?php echo form_error('ordr'); ?></span>
                        </div>
                    </div>
					<div class="row form-group">
						<label for="description" class="control-label col-md-2"><?php echo ucfirst($this->lang->line('gp_description')); ?></label>
						<div class="col-md-5">
							<textarea class="form-control" cols="20" rows="4" name="description" placeholder="" type="text"><?php echo $project['description']; ?></textarea>
							<span class="text-danger"><?php echo form_error('description'); ?></span>
						</div>	
					</div>	

					<div class="row form-group">
						<div class="col-md-offset-2 col-md-5">
							<div class="control">
								<input type="checkbox" name="public" value="true" <?php if ($project['public']) { echo "checked='checked'"; }; ?> /> <?php echo ucfirst($this->lang->line('gp_public')); ?>
							</div>
							<div class="control">
								<input type="checkbox" name="geolocation" value="true" <?php if ($project['geolocation']) { echo "checked='checked'"; }; ?> /> <?php echo $this->lang->line('gp_geolocation'); ?>
							</div>
							<div class="control">
								<input type="checkbox" name="restrict_to_start_extent" value="true" <?php if ($project['restrict_to_start_extent']) { echo "checked='checked'"; }; ?> /> <?php echo $this->lang->line('gp_restrict'); ?>
							</div>
							<div class="control">
								<input type="checkbox" name="feedback" value="true" <?php if ($project['feedback']) { echo "checked='checked'"; }; ?> /> <?php echo $this->lang->line('gp_feedback'); ?>
							</div>
							<div class="control">
								<input type="checkbox" name="measurements" value="true" <?php if ($project['measurements']) { echo "checked='checked'"; }; ?> /> <?php echo $this->lang->line('gp_measurements'); ?>
							</div>

                            <div class="control">
                                <input type="checkbox" name="print" value="true" <?php if ($project['print']) { echo "checked='checked'"; }; ?> /> <?php echo $this->lang->line('gp_print'); ?>
                            </div>
                            <div class="control">
                                <input type="checkbox" name="zoom_back_forward" value="true" <?php if ($project['zoom_back_forward']) { echo "checked='checked'"; }; ?> /> <?php echo $this->lang->line('gp_zoom_back_forward'); ?>
                            </div>
                            <div class="control">
                                <input type="checkbox" name="identify_mode" value="true" <?php if ($project['identify_mode']) { echo "checked='checked'"; }; ?> /> <?php echo $this->lang->line('gp_identify_mode'); ?>
                            </div>
                            <div class="control">
                                <input type="checkbox" name="permalink" value="true" <?php if ($project['permalink']) { echo "checked='checked'"; }; ?> /> <?php echo $this->lang->line('gp_permalink'); ?>
                            </div>
						</div>
                    </div>
                    <div class="row form-group">
                        <label class="control-label col-md-2"><?php echo $this->lang->line('gp_image'); ?></label>
                        <div class="col-md-5">
                            <?php echo $image; ?>
                        </div>
                    </div>
    			</fieldset>

                <fieldset id="edit-project-plugins" class="tab-pane">
                    <table class="table table-condensed table-striped">
                        <tr>
                            <th><?php echo $this->lang->line('gp_access'); ?></th>
                            <th><?php echo $this->lang->line('gp_name'); ?></th>
                        </tr>
                        <?php foreach ($plugins as $plugin_item): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="plugin_ids[]" value="<?php echo $plugin_item['id']; ?>" <?php if ($plugin_item['idx'] > 0) { echo "checked='checked'"; }; ?> />
                                </td>
                                <td><?php echo $plugin_item['name']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </fieldset>

			<div id="fixed-actions">
                <hr>
                <div class="form-actions col-md-8">
					<input name="creating" type="hidden" value="<?php echo $creating; ?>">

					<input type="submit" class="btn btn-primary" value=<?php echo $this->lang->line('gp_save'); ?> >
					<input type="submit" class="btn btn-primary" name="return" value=<?php echo $this->lang->line('gp_save')."&nbsp;&&nbsp;".strtolower($this->lang->line('gp_return')); ?>>
					<a class="btn btn-default" href="<?php echo site_url('projects/'); ?>"><?php echo $this->lang->line('gp_return'); ?></a>
				
				<?php if ( $creating === false && !empty($project['id'])) : ?>
				<div class="pull-right">
                    <a class="btn btn-danger" onclick="confirmLink(GP.deleteProject,'<?php echo $project['name']; ?>','<?php echo site_url('projects/remove/'.$project['id']); ?>')"><?php echo $this->lang->line('gp_delete'); ?></a>
                </div>
				 <?php endif; ?>
				</div>
			</div>


		</form>

	</div>
