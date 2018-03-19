    <div class="page-header clearfix">
		<h1 class="col-md-8"><?php echo $title; ?></h1>
	</div>

	<?php echo $this->session->flashdata('alert'); ?>

	<div class="col-md-12">
		<?php $attributes = array("class" => "form-horizontal");
		echo form_open('projects/edit/' . $project['id'], $attributes); ?>
			<input name="id" type="hidden" value="<?php echo $project['id']; ?>" />

			<ul class="nav nav-tabs">
			  <li class="active"><a href="#edit-project-meta" data-toggle="tab"><?php echo $this->lang->line('gp_properties'); ?></a></li>
			  <li><a href="#edit-project-layers" data-toggle="tab"><?php echo $this->lang->line('gp_base_layers'); ?></a></li>
			  <li><a href="#edit-project-extra-layers" data-toggle="tab"><?php echo $this->lang->line('gp_overlay_layers'); ?></a></li>
			  <li><a href="#edit-project-users" data-toggle="tab"><?php echo $this->lang->line('gp_users_title'); ?></a></li>
			</ul>

			<div class="tab-content">

				<fieldset id="edit-project-meta" class="tab-pane active">

                    <div class="form-group">
                        <label for="client_id" class="control-label col-md-2"><?php echo $this->lang->line('gp_client'); ?></label>
                        <div class="col-md-5">
                            <select class="form-control" name="client_id">
                                <option value=""><?php echo $this->lang->line('gp_select_client'); ?></option>
                                <?php foreach ($clients as $client_item): ?>
                                    <option <?php if ($client_item['id'] == $project['client_id']) { echo "selected='selected'"; }; ?> value="<?php echo $client_item['id']; ?>"><?php echo $client_item['display_name'] . " (" .$client_item['name'] . ")"; ?></option>							<?php endforeach; ?>
                            </select>
                            <span class="text-danger"><?php echo form_error('client_id'); ?></span>
                        </div>
                    </div>

                    <div class="form-group">
						<label for="name" class="control-label col-md-2"><?php echo $this->lang->line('gp_name'); ?></label>
						<div class="col-md-5">
							<input class="form-control" name="name" placeholder="" type="text" value="<?php echo $project['name']; ?>" />
							<span class="text-danger"><?php echo form_error('name'); ?></span>
                            <p class="help-block"><?php echo $this->lang->line('gp_name_tip'); ?></p>
						</div>	
					</div>	

					<div class="form-group">
						<label for="qgis_check" class="control-label col-md-2"><?php echo $this->lang->line('gp_qgis_project'); ?></label>
						<div class="col-md-5">
                            <?php if ($qgis_check['valid']) {
                                ?>
                                <div class="alert alert-success">
                                    <span class="glyphicon glyphicon-ok" aria-hidden="true"></span>
                                    <?php echo $qgis_check['name']?>
                                </div>
                                <?php
                            } else {
                               ?>
                                <div class="alert alert-danger">
                                    <span class="glyphicon glyphicon-alert" aria-hidden="true"></span>
                                    <?php echo $qgis_check['name']?>
                                </div>
                                <?php
                            }?>
						</div>
					</div>

					<div class="form-group">
						<label for="display_name" class="control-label col-md-2"><?php echo $this->lang->line('gp_display_name'); ?></label>
						<div class="col-md-5">
							<input class="form-control" name="display_name" placeholder="" readonly="readonly" type="text" value="<?php echo $project['display_name']; ?>" />
							<span class="text-danger"><?php echo form_error('display_name'); ?></span>
                            <p class="help-block"><?php echo $this->lang->line('gp_display_name_tip'); ?></p>
                        </div>

					</div>	

					<div class="form-group">
						<label for="crs" class="control-label col-md-2"><?php echo $this->lang->line('gp_crs'); ?></label>
						<div class="col-md-5">
							<input class="form-control" name="crs" placeholder="" readonly="readonly" type="text" value="<?php echo $project['crs']; ?>" />
							<span class="text-danger"><?php echo form_error('crs'); ?></span>
						</div>	
					</div>

                    <div class="form-group">
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

					<div class="form-group">
						<label for="contact" class="control-label col-md-2"><?php echo ucfirst($this->lang->line('gp_contact')); ?></label>
						<div class="col-md-5">
							<input class="form-control" name="contact" placeholder="" type="text" value="<?php echo $project['contact']; ?>" />
							<span class="text-danger"><?php echo form_error('contact'); ?></span>
						</div>	
					</div>	
					<div class="form-group">
						<label for="feedback_email" class="control-label col-md-2"><?php echo $this->lang->line('gp_feedback_email'); ?></label>
						<div class="col-md-5">
							<input class="form-control" name="feedback_email" placeholder="" type="text" value="<?php echo $project['feedback_email']; ?>" />
							<span class="text-danger"><?php echo form_error('feedback_email'); ?></span>
						</div>	
					</div>
                    <div class="form-group">
                        <label for="ordr" class="control-label col-md-2"><?php echo $this->lang->line('gp_order'); ?></label>
                        <div class="col-md-5">
                            <input class="form-control" name="ordr" placeholder="" type="text" value="<?php echo $project['ordr']; ?>" />
                            <span class="text-danger"><?php echo form_error('ordr'); ?></span>
                        </div>
                    </div>
					<div class="form-group">
						<label for="description" class="control-label col-md-2"><?php echo ucfirst($this->lang->line('gp_description')); ?></label>
						<div class="col-md-5">
							<textarea class="form-control" cols="20" rows="4" name="description" placeholder="" type="text"><?php echo $project['description']; ?></textarea>
							<span class="text-danger"><?php echo form_error('description'); ?></span>
						</div>	
					</div>	

					<div class="form-group">
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
                    <div class="form-group">
                        <label class="control-label col-md-2"><?php echo $this->lang->line('gp_image'); ?></label>
                        <div class="col-md-5">
                            <?php echo $image; ?>
                        </div>
                    </div>
    			</fieldset>

				<fieldset id="edit-project-layers" class="tab-pane">
					<div class="form-group">
<!--						<label for="base_layers_ids" class="control-label col-md-2">Base Layers</label>-->
                        <div class="row style-select">
                            <div class="col-md-offset-1 col-md-10">
                                <div class="subject-info-box-1">
                                    <label><?php echo $this->lang->line('gp_available')." ".strtolower($this->lang->line('gp_base_layers')); ?></label>
                                    <select multiple class="form-control" id="lstBase1">
                                        <?php foreach ($base_layers as $layer_item): ?>
                                            <?php if ($layer_item['idx'] == 0) {?>
                                                <option value="<?php echo $layer_item['id']; ?>"><?php echo $layer_item['full_name']; ?></option>
                                            <?php } ?>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="subject-info-arrows text-center">
                                    <br /><br />
                                    <input type='button' onclick="moveItem('#lstBase1','#lstBase2')" value='>' class="btn btn-default" /><br />
                                    <input type='button' onclick="moveItem('#lstBase2','#lstBase1')" value='<' class="btn btn-default" /><br />
                                    <input type='button' onclick="moveAllItems('#lstBase2','#lstBase1')" value='<<' class="btn btn-default" />
                                </div>

                                <div class="subject-info-box-2">
                                    <label><?php echo $this->lang->line('gp_base_layers')." ".strtolower($this->lang->line('gp_in_project')); ?></label>
                                    <select multiple class="form-control" id="lstBase2">
                                        <?php foreach ($base_layers as $layer_item): ?>
                                            <?php if ($layer_item['idx'] > 0) {?>
                                                <option value="<?php echo $layer_item['id']; ?>"><?php echo $layer_item['display_name']; ?></option>
                                            <?php } ?>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="selected-right">
                                    <button onclick="moveUp('#lstBase2')" type="button" class="btn btn-default btn-sm">
                                        <span class="glyphicon glyphicon-chevron-up"></span>
                                    </button>
                                    <button onclick="moveDown('#lstBase2')" type="button" class="btn btn-default btn-sm">
                                        <span class="glyphicon glyphicon-chevron-down"></span>
                                    </button>
                                </div>

<!--                                <div class="col-md-3 col-sm-3 col-xs-3 add-btns">-->
<!--                                    <input type="button" id="list2val" value="get values" class="btn btn-default" />-->
<!--                                </div>-->

<!--                                <div class="clearfix"></div>-->
                            </div>
                        </div>

					</div>
				</fieldset>

                <fieldset id="edit-project-extra-layers" class="tab-pane">
                    <div class="form-group">
                        <!--						<label for="base_layers_ids" class="control-label col-md-2">Base Layers</label>-->
                        <div class="row style-select">
                            <div class="col-md-offset-1 col-md-10">
                                <div class="subject-info-box-1">
                                    <label><?php echo $this->lang->line('gp_available')." ".strtolower($this->lang->line('gp_overlay_layers')); ?></label>
                                    <select multiple class="form-control" id="lstExtra1">
                                        <?php foreach ($extra_layers as $layer_item): ?>
                                            <?php if ($layer_item['idx'] == 0) {?>
                                                <option value="<?php echo $layer_item['id']; ?>"><?php echo $layer_item['full_name']; ?></option>
                                            <?php } ?>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="subject-info-arrows text-center">
                                    <br /><br />
                                    <input type='button' onclick="moveItem('#lstExtra1','#lstExtra2')" value='>' class="btn btn-default" /><br />
                                    <input type='button' onclick="moveItem('#lstExtra2','#lstExtra1')" value='<' class="btn btn-default" /><br />
                                    <input type='button' onclick="moveAllItems('#lstExtra2','#lstExtra1')" value='<<' class="btn btn-default" />
                                </div>

                                <div class="subject-info-box-2">
                                    <label><?php echo $this->lang->line('gp_overlay_layers')." ".strtolower($this->lang->line('gp_in_project')); ?></label>
                                    <select multiple class="form-control" id="lstExtra2">
                                        <?php foreach ($extra_layers as $layer_item): ?>
                                            <?php if ($layer_item['idx'] > 0) {?>
                                                <option value="<?php echo $layer_item['id']; ?>"><?php echo $layer_item['display_name']; ?></option>
                                            <?php } ?>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="selected-right">
                                    <button onclick="moveUp('#lstExtra2')" type="button" class="btn btn-default btn-sm">
                                        <span class="glyphicon glyphicon-chevron-up"></span>
                                    </button>
                                    <button onclick="moveDown('#lstExtra2')" type="button" class="btn btn-default btn-sm">
                                        <span class="glyphicon glyphicon-chevron-down"></span>
                                    </button>
                                </div>

                                <!--                                <div class="col-md-3 col-sm-3 col-xs-3 add-btns">-->
                                <!--                                    <input type="button" id="list2val" value="get values" class="btn btn-default" />-->
                                <!--                                </div>-->

                                <!--                                <div class="clearfix"></div>-->
                            </div>
                        </div>

                    </div>
                </fieldset>

				<fieldset id="edit-project-users" class="tab-pane">
					<table class="table table-condensed table-striped">
					  <tr>
						<th><?php echo $this->lang->line('gp_access'); ?></th>
						<th><?php echo $this->lang->line('gp_username'); ?></th>
						<th><?php echo $this->lang->line('gp_email'); ?></th>
						<th><?php echo $this->lang->line('gp_name'); ?></th>
					  </tr>
						<?php foreach ($user_projects as $user_item): ?>
							<tr>
								<td>
									<input type="checkbox" name="user_projects_ids[]" value="<?php echo $user_item['user_id']; ?>" <?php if ($user_item['selected']) { echo "checked='checked'"; }; ?> />
								</td>
								<td><?php echo $user_item['user_name']; ?></td>
								<td><?php echo $user_item['user_email']; ?></td>
								<td><?php echo $user_item['display_name']; ?></td>
							</tr>
						<?php endforeach; ?>
					</table>



				</fieldset>

			<div id="fixed-actions">
				<div class="form-actions col-md-offset-1 col-md-8">
					<input name="creating" type="hidden" value="<?php echo $creating; ?>">
					<input id="base_ids" name="base_layers_ids" type="hidden" value="{}">
					<input id="extra_ids" name="extra_layers_ids" type="hidden" value="{}">

					<input type="submit" class="btn btn-primary" onclick="checkValues()" value=<?php echo $this->lang->line('gp_save'); ?> >
					<input type="submit" class="btn btn-primary" onclick="checkValues()" name="return" value=<?php echo $this->lang->line('gp_save')."&nbsp;&&nbsp;".strtolower($this->lang->line('gp_return')); ?>>
					<a class="btn btn-default" href="<?php echo site_url('projects/'); ?>"><?php echo $this->lang->line('gp_return'); ?></a>
				
				<?php if ( $creating === false && !empty($project['id'])) : ?>
				<div class="pull-right">
                    <a class="btn btn-danger" href="<?php echo site_url('projects/remove/'.$project['id']); ?>"><?php echo $this->lang->line('gp_delete'); ?></a>
                </div>
				 <?php endif; ?>
				</div>
			</div>


		</form>

	</div>
