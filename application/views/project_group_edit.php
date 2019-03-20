<div class="page-header clearfix">
    <h1 class="col-md-8"><span><?php echo $title; ?></span></h1>
</div>

<?php echo $this->session->flashdata('alert'); ?>

<div class="col-md-12">
    <?php $attributes = array("class" => "form-horizontal");
    echo form_open('project_groups/edit/' . $group['id'], $attributes); ?>
    <input name="id" type="hidden" value="<?php echo $group['id']; ?>"/>

    <ul class="nav nav-tabs">
        <li class="active"><a href="#edit-group-meta" data-toggle="tab"><?php echo $this->lang->line('gp_properties'); ?></a></li>
        <li><a href="#edit-group-layers" data-toggle="tab"><?php echo $this->lang->line('gp_base_layers'); ?></a></li>
        <li><a href="#edit-group-extra-layers" data-toggle="tab"><?php echo $this->lang->line('gp_overlay_layers'); ?></a></li>
        <li><a href="#edit-group-projects" data-toggle="tab"><?php echo $this->lang->line('gp_projects_title'); ?></a></li>
        <li><a href="#edit-group-users" data-toggle="tab"><?php echo $this->lang->line('gp_users_title'); ?></a></li>
    </ul>

    <div class="tab-content">


        <fieldset id="edit-group-meta" class="tab-pane active">

            <div class="row form-group">
                <label for="client_id"
                       class="control-label col-md-2"><?php echo $this->lang->line('gp_client'); ?></label>

                <div class="col-md-5">
                    <select class="form-control" name="client_id" id="client_id"
                            onchange="onClientChange(this,<?php echo $action; ?>);">
                        <?php foreach ($clients as $client_item): ?>
                            <option <?php if ($client_item['id'] == $group['client_id']) {
                                echo "selected='selected'";
                            }; ?>
                                value="<?php echo $client_item['id']; ?>"><?php echo $client_item['display_name'] . " (" . $client_item['name'] . ")"; ?></option>                            <?php endforeach; ?>
                    </select>
                    <span class="text-danger"><?php echo form_error('client_id'); ?></span>
                </div>
            </div>

            <div class="form-group">
                <label for="name" class="control-label col-md-2"><?php echo $this->lang->line('gp_name'); ?></label>

                <div class="col-md-5">
                    <input class="form-control" name="name" placeholder="" type="text"
                           value="<?php echo $group['name']; ?>"/>
                    <span class="text-danger"><?php echo form_error('name'); ?></span>
                    <p class="help-block"><?php echo $this->lang->line('gp_name_help'); ?></p>
                </div>
            </div>
            <div class="form-group">
                <label for="display_name"
                       class="control-label col-md-2"><?php echo $this->lang->line('gp_display_name'); ?></label>

                <div class="col-md-5">
                    <input class="form-control" name="display_name" placeholder="" type="text"
                           value="<?php echo $group['display_name']; ?>"/>
                    <span class="text-danger"><?php echo form_error('display_name'); ?></span>
                </div>
            </div>
            <div class="form-group">
                <label for="url" class="control-label col-md-2"><?php echo $this->lang->line('gp_type'); ?></label>

                <div class="col-md-5">
                    <select class="form-control" name="type">
                        <?php foreach ($types as $type): ?>
                            <option <?php if ($type['id'] == $group['type']) {
                                echo "selected='selected'";
                            }; ?> value="<?php echo $type['id']; ?>"><?php echo $type['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <span class="text-danger"><?php echo form_error('type'); ?></span>
                </div>
            </div>

        </fieldset>

        <fieldset id="edit-group-projects" class="tab-pane">
            <table data-pagination="true" data-search="true" data-toggle="table" data-show-pagination-switch="true">
                <thead>
                <tr>
                    <th></th>
                    <th data-sortable="true" data-field="gp_name"><?php echo $this->lang->line('gp_name'); ?></th>
                    <th data-sortable="true" data-field="gp_display_name"><?php echo $this->lang->line('gp_display_name'); ?></th>
                    <th data-sortable="true" data-field="gp_client"><?php echo $this->lang->line('gp_client'); ?></th>
                    <th data-sortable="true" data-field="gp_group"><?php echo $this->lang->line('gp_group'); ?></th>
                    <th class="text-uppercase" data-sortable="true" data-field="gp_crs"><?php echo $this->lang->line('gp_crs'); ?></th>
                    <!--              <th data-sortable="true" data-field="gp_contact">--><?php //echo ucfirst($this->lang->line('gp_contact')); ?><!--</th>-->
                    <?php if ($this->ion_auth->is_admin()){ ?>
                        <th><?php echo $this->lang->line('gp_action'); ?></th>
                    <?php } ?>
                </tr>
                </thead>
                <?php foreach ($projects as $project_item): ?>

                    <tr>
                        <td class="col-md-1">
                            <img class="img-responsive" src="<?php echo base_url("assets/img/projects/" . $project_item['name'] . ".png"); ?>" alt="">
                        </td>
                        <td class="col-md-2"><a target="_self" href="<?php echo site_url($this->config->item('web_client_url').$project_item['name']); ?>"><?php echo $project_item['name']; ?></a></td>
                        <td class="col-md-2"><?php echo $project_item['display_name']; ?></td>
                        <td class="col-md-2"><?php echo $project_item['client']; ?></td>
                        <td class="col-md-2"><?php echo $project_item['group']; ?></td>
                        <td class="col-md-1"><?php echo $project_item['crs']; ?></td>
                        <!--          <td class="col-md-2">--><?php //echo $project_item['contact']; ?><!--</td>-->
                        <?php if ($this->ion_auth->is_admin()){ ?>
                            <td class="col-md-2">
                                <a class="btn btn-primary" href="<?php echo site_url('projects/edit/' . $project_item['id']); ?>">
                                    <?php echo $this->lang->line('gp_edit'); ?>
                                </a>
                                <?php if ($this->config->item('enable_project_publishing')) { ?>
                                    <a class="btn btn-info" href="<?php echo site_url('projects/services/' . $project_item['id']); ?>">
                                        <?php echo $this->lang->line('gp_publish'); ?>
                                    </a>
                                <?php } ?>
                            </td>
                        <?php } ?>
                    </tr>
                <?php endforeach; ?>
            </table>
        </fieldset>

        <fieldset id="edit-group-users" class="tab-pane">
            <table data-pagination="true" data-search="true" data-toggle="table" data-show-pagination-switch="true">
                <thead>
                <tr>

                    <th data-sortable="true" data-field="gp_first_name"><?php echo $this->lang->line('gp_first_name'); ?></th>
                    <th data-sortable="true" data-field="gp_last_name"><?php echo $this->lang->line('gp_last_name'); ?></th>
                    <th data-sortable="true" data-field="gp_username"><?php echo $this->lang->line('gp_username'); ?></th>
                    <th data-sortable="true" data-field="gp_email"><?php echo $this->lang->line('gp_email'); ?></th>
                    <th data-sortable="true" data-field="gp_registered"><?php echo $this->lang->line('gp_registered'); ?></th>
                    <th data-sortable="true" data-field="gp_count_login"><?php echo $this->lang->line('gp_count_login'); ?></th>
                    <th data-sortable="true" data-field="gp_last_login"><?php echo $this->lang->line('gp_last_login'); ?></th>
                    <th data-sortable="true" data-field="gp_role"><?php echo $this->lang->line('gp_role'); ?></th>

                </tr>
                </thead>
                <?php foreach ($users as $user_item): ?>

                    <tr>
                        <td class="col-md-1"><?php echo $user_item['first_name']; ?></td>
                        <td class="col-md-2"><?php echo $user_item['last_name']; ?></td>
                        <td class="col-md-1"><?php echo $user_item['user_name']; ?></td>
                        <td class="col-md-1"><?php echo $user_item['user_email']; ?></td>
                        <td class="col-md-2"><?php echo set_datestr($user_item['registered']); ?></td>
                        <td class="col-md-1"><?php echo $user_item['count_login']; ?></td>
                        <td class="col-md-2"><?php echo set_datestr($user_item['last_login']); ?></td>
                        <td class="col-md-2"><?php echo $user_item['role']; ?></td>


                    </tr>
                <?php endforeach; ?>
            </table>
        </fieldset>

        <fieldset id="edit-group-layers" class="tab-pane">
            <div class="form-group">
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

        <fieldset id="edit-group-extra-layers" class="tab-pane">
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


        <div id="fixed-actions">
            <div class="form-actions col-md-offset-1 col-md-8">
                <input name="creating" type="hidden" value="<?php echo $creating; ?>">
                <input id="base_ids" name="base_layers_ids" type="hidden" value="{}">
                <input id="extra_ids" name="extra_layers_ids" type="hidden" value="{}">

                <input type="submit" class="btn btn-primary" onclick="checkValues()" value=<?php echo $this->lang->line('gp_save'); ?>>
                <input type="submit" class="btn btn-primary" onclick="checkValues()" name="return"
                       value=<?php echo $this->lang->line('gp_save') . "&nbsp;&&nbsp;" . strtolower($this->lang->line('gp_return')); ?>>
                <a class="btn btn-default"
                   href="<?php echo site_url('project_groups/'); ?>"><?php echo $this->lang->line('gp_return'); ?></a>

                <?php if ($creating === false && !empty($group['id'])) : ?>
                    <div class="pull-right">
                        <a class="btn btn-danger" onclick="confirmLink(GP.deleteGeneral,'Group: <?php echo $group['name']; ?>','<?php echo site_url('project_groups/remove/'.$group['id']); ?>')"><?php echo $this->lang->line('gp_delete'); ?></a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        </form>

    </div>
</div>
