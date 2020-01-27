	<div class="page-header clearfix">
<!--		<h1 class="col-md-8"><i class="fa fa-pencil"></i> <span>--><?php //echo $title; ?><!--</span></h1>-->
		<h1 class="col-md-8"><span><?php echo $title; ?></span></h1>
	</div>

	<?php echo $this->session->flashdata('alert'); ?>

	<div class="col-md-12">
		<?php $attributes = array("class" => "form-horizontal");
		echo form_open('clients/edit/' . $client['id'], $attributes); ?>
			<input name="id" type="hidden" value="<?php echo $client['id']; ?>" />

        <ul class="nav nav-tabs">
            <li class="active"><a href="#edit-client-meta" data-toggle="tab"><?php echo $this->lang->line('gp_properties'); ?></a></li>
            <?php if (!$creating) : ?>
                <li><a href="#edit-client-items" data-toggle="tab"><?php echo $this->lang->line('gp_items'); ?></a></li>
            <?php endif; ?>
        </ul>

        <div class="tab-content">

			<fieldset id="edit-client-meta" class="tab-pane active">
				<div class="form-group">
					<label for="name" class="control-label col-md-2"><?php echo $this->lang->line('gp_name'); ?></label>
					<div class="col-md-5">
						<input class="form-control" name="name" placeholder="" type="text" <?php if (isset($client['count']) && $client['count']>0) { echo "readonly='readonly'"; }; ?> value="<?php echo $client['name']; ?>" />
						<span class="text-danger"><?php echo form_error('name'); ?></span>
                        <p class="help-block"><?php echo $this->lang->line('gp_name_help'); ?></p>
					</div>	
				</div>	
				<div class="form-group">
					<label for="display_name" class="control-label col-md-2"><?php echo $this->lang->line('gp_display_name'); ?></label>
					<div class="col-md-5">
						<input class="form-control" name="display_name" placeholder="" type="text" value="<?php echo $client['display_name']; ?>" />
						<span class="text-danger"><?php echo form_error('display_name'); ?></span>
					</div>	
				</div>	
				<div class="form-group">
					<label for="url" class="control-label col-md-2"><?php echo $this->lang->line('gp_url'); ?></label>
					<div class="col-md-5">
						<input class="form-control" name="url" placeholder="" type="text" value="<?php echo $client['url']; ?>" />
						<span class="text-danger"><?php echo form_error('url'); ?></span>
					</div>	
				</div>	
				<div class="form-group">
					<label for="description" class="control-label col-md-2"><?php echo ucfirst($this->lang->line('gp_description')); ?></label>
					<div class="col-md-5">
						<textarea class="form-control" cols="20" rows="3" name="description" placeholder="" type="text"><?php echo $client['description']; ?></textarea>
						<span class="text-danger"><?php echo form_error('description'); ?></span>
					</div>	
				</div>
                <div class="form-group">
                    <label class="control-label col-md-2">Portal <?php echo $this->lang->line('gp_image'); ?></label>
                    <div class="col-md-5">
                        <?php echo $image; ?>
                    </div>
                </div>
				<div class="form-group">
					<label class="control-label col-md-2">Gisapp logo</label>
					<div class="col-md-5">
						<?php echo $logo; ?>
					</div>
				</div>

                <?php if (!$creating) : ?>
                    <div class="form-group">
                        <label for="register" class="control-label col-md-2"><?php echo lang('gp_user_registration_link'); ?></label>
                        <div class="col-md-5">
                            <input id="register_link" class="form-control key" name="register" placeholder="" type="text" readonly="readonly" value="<?php echo site_url($register); ?>" />
                            <p class="help-block"><?php echo lang('gp_user_registration_help'); ?></p>
                        </div>
                    </div>
                <?php endif; ?>

            </fieldset>

            <fieldset id="edit-client-items" class="tab-pane">

                <table id="table" data-pagination="true" data-search="false" data-toggle="table" data-detail-view="true" data-detail-filter="openSubGroups"
                       data-show-pagination-switch="false">
                    <thead>
                    <tr>
                        <th data-sortable="true" data-field="gp_name"><?php echo $this->lang->line('gp_name'); ?></th>
                        <th data-sortable="true" data-field="gp_display_name"><?php echo $this->lang->line('gp_display_name'); ?></th>
                        <th data-visible="false" data-field="gp_type">Type</th>
                        <th data-visible="false" data-field="gp_items">Items</th>
                        <th data-field="gp_action"><?php echo $this->lang->line('gp_action'); ?></th>
                    </tr>
                    </thead>
                    <?php foreach ($items as $group_item): ?>
                        <tr>
                            <td class="col-md-2"><?php echo $group_item['name']; ?></td>
                            <td class="col-md-2"><?php echo $group_item['display_name']; ?></td>
                            <td><?php echo $group_item['type']; ?></td>
                            <td><?php echo json_encode($group_item['items']); ?></td>
                            <td>
                                <?php if ($group_item['type'] == PROJECT_GROUP) :?>
                                    <a class="btn btn-default" href="<?php echo site_url('project_groups/edit/'.$group_item['id']); ?>"><?php echo $this->lang->line('gp_group'); ?></a>
                                <?php elseif ($group_item['type'] == SUB_GROUP) :?>
                                    <a class="btn btn-default" href="<?php echo site_url('project_groups/edit/'.$group_item['id']); ?>"><?php echo ucwords($this->lang->line('gp_sub_group')); ?></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </fieldset>

			<div id="fixed-actions">
                <hr>
                <div class="form-actions col-md-8">
					<input name="creating" type="hidden" value="<?php echo $creating; ?>">

					<input type="submit" class="btn btn-primary" value=<?php echo $this->lang->line('gp_save'); ?>>
					<input type="submit" class="btn btn-primary" name="return" value=<?php echo $this->lang->line('gp_save')."&nbsp;&&nbsp;".strtolower($this->lang->line('gp_return')); ?>>
					<a class="btn btn-default" href="<?php echo site_url('clients/'); ?>"><?php echo $this->lang->line('gp_return'); ?></a>
				
				<?php if ( $creating === false && !empty($client['id'])) : ?>
				<div class="pull-right">
                    <a class="btn btn-danger" onclick="confirmLink(GP.deleteGeneral,'Client: <?php echo $client['display_name'].' ('.$client['name'].')'; ?>','<?php echo site_url('clients/remove/'.$client['id']); ?>')"><?php echo $this->lang->line('gp_delete'); ?></a>
				</div>
				 <?php endif; ?>
				</div>
			</div>

		</form>

	</div>
</div>

    <script type="text/javascript">

        $(document).on('focus', '.key', function() {
            this.select();
        }).on('mouseup', '.key', function(e) {
            e.preventDefault();
        });


        var $table = $('#table');
        $table.bootstrapTable({
            onExpandRow: function (index, row, $detail) {
                $detail.html('<div class="col-md-8 col-md-offset-1"><table></table></div>').find('table').bootstrapTable({
                    columns: [{
                        field: 'name',
                        title: GP.name
                    }, {
                        field: 'display_name',
                        title: GP.displayName
                    }, {
                        field: 'id',
                        title: GP.action,
                        formatter: makeGroupAction
                    }],
                    data: JSON.parse(row.gp_items)
                });
            }
        });

    </script>
