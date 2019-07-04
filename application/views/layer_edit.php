	<div class="page-header clearfix">
		<h1 class="col-md-8"><span><?php echo $title; ?></span></h1>
	</div>

	<?php echo $this->session->flashdata('alert'); ?>

	<div class="col-md-12">
		<?php $attributes = array("class" => "form-horizontal");
		echo form_open('layers/edit/' . $layer['id'], $attributes); ?>
			<input name="id" type="hidden" value="<?php echo $layer['id']; ?>" />

        <ul class="nav nav-tabs">
            <li class="active"><a href="#edit-layer-meta" data-toggle="tab"><?php echo $this->lang->line('gp_properties'); ?></a></li>
            <?php if ( $creating === false && !empty($layer['id']) ) : ?>
                <li><a href="#edit-access" data-toggle="tab"><?php echo $this->lang->line('gp_groups_title'); ?></a></li>
            <?php endif; ?>
        </ul>

        <div class="tab-content">
			<fieldset id="edit-layer-meta" class="tab-pane active">
				<div class="form-group">
					<label for="name" class="control-label col-md-2"><?php echo $this->lang->line('gp_name'); ?></label>
					<div class="col-md-6">
						<input class="form-control" name="name" placeholder="" type="text" value="<?php echo $layer['name']; ?>" />
						<span class="text-danger"><?php echo form_error('name'); ?></span>
                        <p class="help-block"><?php echo $this->lang->line('gp_name_help'); ?></p>
					</div>	
				</div>	
				<div class="form-group">
					<label for="display_name" class="control-label col-md-2"><?php echo $this->lang->line('gp_display_name'); ?></label>
					<div class="col-md-6">
						<input class="form-control" name="display_name" placeholder="" type="text" value="<?php echo $layer['display_name']; ?>" />
						<span class="text-danger"><?php echo form_error('display_name'); ?></span>
					</div>	
				</div>	
				<div class="form-group">
					<label for="url" class="control-label col-md-2"><?php echo $this->lang->line('gp_type'); ?></label>
					<div class="col-md-6">
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
					<label for="definition" class="control-label col-md-2"><?php echo $this->lang->line('gp_definition'); ?></label>
					<div class="col-md-6">
						<textarea class="form-control" cols="20" rows="18" name="definition" placeholder="" type="text"><?php echo $layer['definition']; ?></textarea>
						<span class="text-danger"><?php echo form_error('definition'); ?></span>
					</div>	
				</div>

                <div class="row form-group">
                    <label for="client_id" class="control-label col-md-2"><?php echo $this->lang->line('gp_client'); ?></label>

                    <div class="col-md-6">
                        <select class="form-control" name="client_id" id="client_id">
                            <?php if (count($clients)>1) : ?>
                                <option value="" selected="true" disabled><?php echo $this->lang->line('gp_select_client'); ?></option>
                            <?php endif ?>
                            <?php foreach ($clients as $client_item): ?>
                                <option <?php if ($client_item['id'] == $layer['client_id']) {
                                    echo "selected='selected'";
                                }; ?>
                                        value="<?php echo $client_item['id']; ?>"><?php echo $client_item['display_name'] . " (" . $client_item['name'] . ")"; ?></option>                            <?php endforeach; ?>
                        </select>
                        <span class="text-danger"><?php echo form_error('client_id'); ?></span>
                    </div>
                </div>

            </fieldset>
            <?php if ( $creating === false && !empty($layer['id']) ) : ?>
            <fieldset id="edit-access" class="tab-pane">
                <div class="form-inline well">
                    <div class="form-group">
                        <select class="form-control" name="client_id" id="client_id" onchange="onClientChange(this,3);">
                            <option value="" selected="true"
                                    disabled><?php echo $this->lang->line('gp_select_client'); ?></option>
                            <?php foreach ($clients as $client_item): ?>
                                <option value="<?php echo $client_item['id']; ?>"><?php echo $client_item['display_name'] . " (" . $client_item['name'] . ")"; ?></option>                            <?php endforeach; ?>
                        </select>

                        <select class="form-control" style="vertical-align: top" multiple size="10" name="project_group_id"
                                id="project_group_id">
                            <option value="" disabled><?php echo $this->lang->line('gp_select_groups'); ?></option>
                        </select>

                        <select class="form-control" id="destination" name="destination">
                            <?php foreach ($destination as $dest): ?>
                                <option value="<?php echo $dest['id']; ?>"><?php echo $dest['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <a onclick="addLayerMulti(<?php echo $layer['id']; ?>)"
                           class="btn btn-mini btn-success "><?php echo $this->lang->line('gp_add'); ?></a>
                    </div>
                </div>

                <table data-pagination="true" data-search="false" data-toggle="table"
                       data-show-pagination-switch="false">
                    <thead>
                    <tr>
                        <th data-sortable="true" data-field="gp_group"><?php echo $this->lang->line('gp_group'); ?></th>
                        <th data-sortable="true"
                            data-field="gp_client"><?php echo $this->lang->line('gp_client'); ?></th>
                        <th data-sortable="true"
                            data-field="gp_base"><?php echo get_first($this->lang->line('gp_base_layers')); ?></th>
                        <th data-sortable="true"
                            data-field="gp_extra"><?php echo get_first($this->lang->line('gp_overlay_layers')); ?></th>
                        <th><?php echo $this->lang->line('gp_action'); ?></th>
                    </tr>
                    </thead>
                    <?php foreach ($groups as $group_item): ?>
                        <tr>
                            <td class="col-md-2"><?php echo $group_item['name']; ?></td>
                            <td class="col-md-2"><?php echo $group_item['client']; ?></td>
                            <td class="col-md-1"><?php echo set_check_icon((boolean)$group_item['is_base']); ?></td>
                            <td class="col-md-1"><?php echo set_check_icon((boolean)$group_item['is_extra']); ?></td>
                            <td>
                                <a class="btn btn-default" href="<?php echo site_url('project_groups/edit/'.$group_item['project_group_id']); ?>"><?php echo $this->lang->line('gp_group'); ?></a>
                                <a class="btn btn-danger" onclick="confirmLink(GP.deleteLayerGroup,'<?php echo $layer['name']; ?> from group: <?php echo $group_item['name']; ?>','<?php echo site_url('project_groups/remove_layer/' . $group_item['project_group_id'] . '/' . $layer['id'] . '/' .  $group_item['is_extra'] . '/' . $group_item['client_id'] . '/layers'); ?>')"><?php echo $this->lang->line('gp_remove'); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </fieldset>
            <?php endif; ?>
        </div>

			<div id="fixed-actions">
                <hr>
                <div class="form-actions col-md-8">
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
