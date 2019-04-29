<?php

/** @var string $title */
/** @var string $admin_navigation */
/** @var array $group */
/** @var array $clients */
/** @var array $types */
/** @var array $projects */
/** @var array $roles */
/** @var array $users */
/** @var array $base_layers */
/** @var array $extra_layers */
/** @var boolean $is_admin */
/** @var boolean $creating */

?>

<div class="page-header clearfix">
    <h1 class="col-md-8"><span><?php echo $title; ?></span></h1>
</div>

<?php echo $this->session->flashdata('alert'); ?>

<div class="col-md-12">
    <p class="help-block"><?php echo $admin_navigation; ?></p>

    <?php $attributes = array("class" => "form-horizontal");
    echo form_open('project_groups/edit/' . $group['id'], $attributes); ?>
    <input name="id" type="hidden" value="<?php echo $group['id']; ?>"/>

    <ul class="nav nav-tabs">
        <li class="active"><a href="#edit-group-meta" data-toggle="tab"><?php echo $this->lang->line('gp_properties'); ?></a></li>
        <?php if ( $group['type'] == PROJECT_GROUP) : ?>
            <li><a href="#edit-group-projects" data-toggle="tab"><?php echo $this->lang->line('gp_projects_title'); ?></a></li>
            <li><a href="#edit-group-layers" data-toggle="tab"><?php echo $this->lang->line('gp_base_layers'); ?></a></li>
            <li><a href="#edit-group-extra-layers" data-toggle="tab"><?php echo $this->lang->line('gp_overlay_layers'); ?></a></li>
            <li><a href="#edit-access" data-toggle="tab"><?php echo $this->lang->line('gp_users_title'); ?></a></li>
        <?php elseif ( $group['type'] == SUB_GROUP) : ?>
            <li><a href="#edit-group-items" data-toggle="tab"><?php echo $this->lang->line('gp_items'); ?></a></li>
        <?php endif; ?>
    </ul>

    <div class="tab-content">


        <fieldset id="edit-group-meta" class="tab-pane active">

            <div class="row form-group">
                <label for="client_id"
                       class="control-label col-md-2"><?php echo $this->lang->line('gp_client'); ?></label>

                <div class="col-md-5">
                    <select class="form-control" name="client_id" id="client_id"
                            onchange="getParentGroups(this,<?php echo $group['id']; ?>);">
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
                <label for="url" class="control-label col-md-2"><?php echo $this->lang->line('gp_parent'); ?> <?php echo $this->lang->line('gp_group'); ?></label>

                <div class="col-md-5">
                    <select class="form-control" name="parent_id" id="parent_id">
                        <option value=""></option>
                        <?php foreach ($parents as $parent): ?>
                            <option <?php if ($parent['id'] == $group['parent_id']) {
                                echo "selected='selected'";
                            }; ?> value="<?php echo $parent['id']; ?>"><?php echo $parent['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <span class="text-danger"><?php echo form_error('parent_id'); ?></span>
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

        <?php if ( $group['type'] == PROJECT_GROUP) : ?>
        <fieldset id="edit-group-projects" class="tab-pane">
            <table data-pagination="true" data-search="true" data-toggle="table" data-show-pagination-switch="true">
                <thead>
                <tr>
                    <th></th>
                    <th data-sortable="true" data-field="gp_name"><?php echo $this->lang->line('gp_name'); ?></th>
                    <th data-sortable="true" data-field="gp_display_name"><?php echo $this->lang->line('gp_display_name'); ?></th>
                    <th data-sortable="true" data-field="gp_client"><?php echo $this->lang->line('gp_client'); ?></th>
                    <th class="text-uppercase" data-sortable="true" data-field="gp_crs"><?php echo $this->lang->line('gp_crs'); ?></th>
                    <?php if ($is_admin){ ?>
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
                        <td class="col-md-1"><?php echo $project_item['crs']; ?></td>
                        <?php if ($is_admin){ ?>
                            <td class="col-md-2">
                                <a class="btn btn-default" href="<?php echo site_url('projects/edit/' . $project_item['id']); ?>">
                                    <?php echo $this->lang->line('gp_project'); ?>
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

        <fieldset id="edit-access" class="tab-pane">
            <div class="form-inline well">
                <div class="form-group">
                    <input type="search" id="user_search" class="form-control typeahead" size="30" placeholder="<?php echo $this->lang->line('gp_find_user'); ?>..."
                           autocomplete="off">
                    <select class="form-control" id="user_role" name="user_role">
                        <?php foreach ($roles as $role): ?>
                            <option value="<?php echo $role['id']; ?>"><?php echo $role['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <a onclick="addRole(<?php echo $group['id']; ?>,'project_groups')"
                       class="btn btn-mini btn-success "><?php echo $this->lang->line('gp_add'); ?></a>
                </div>
                <div class="pull-right">
                    <a id="copyBtn" class="btn btn-info" onclick="chooseGroup(<?php echo $group['client_id']; ?>,<?php echo $group['id']; ?>)"><?php echo $this->lang->line('gp_copy'); ?></a>
                    <a class="btn btn-danger"
                       onclick="confirmLink(GP.deleteAllRoles,'Users in Group: <?php echo $group['name']; ?>','<?php echo site_url('users/remove_role/' . $group['id'] . '/null/project_groups'); ?>')"><?php echo $this->lang->line('gp_remove'); ?> <?php echo $this->lang->line('gp_all'); ?></a>                </div>
            </div>

              <table data-pagination="true" data-search="false" data-toggle="table" data-show-pagination-switch="false">
                <thead>
                <tr>
                    <th data-sortable="true"
                        data-field="gp_first_name"><?php echo $this->lang->line('gp_first_name'); ?></th>
                    <th data-sortable="true"
                        data-field="gp_last_name"><?php echo $this->lang->line('gp_last_name'); ?></th>
                    <th data-sortable="true"
                        data-field="gp_username"><?php echo $this->lang->line('gp_username'); ?></th>
                    <th data-sortable="true" data-field="gp_email"><?php echo $this->lang->line('gp_email'); ?></th>
                    <th data-sortable="true" data-field="gp_role"><?php echo $this->lang->line('gp_role'); ?></th>
                    <th><?php echo $this->lang->line('gp_action'); ?></th>
                </tr>
                </thead>
                <?php foreach ($users as $user_item): ?>
                    <tr>
                        <td class="col-md-1"><?php echo $user_item['first_name']; ?></td>
                        <td class="col-md-2"><?php echo $user_item['last_name']; ?></td>
                        <td class="col-md-1"><?php echo $user_item['user_name']; ?></td>
                        <td class="col-md-1"><?php echo $user_item['user_email']; ?></td>
                        <td class="col-md-3"><a href="#"
                                                onclick="switchRole(<?php echo $group['id'] . ',' . $user_item['user_id'] . ',' . $user_item['role_id']; ?>,'project_groups')"><?php echo $user_item['role']; ?></a>
                        </td>
                        <td class="col-md-2">
                            <a class="btn btn-default" href="<?php echo site_url('users/edit/'.$user_item['user_id']); ?>"><?php echo $this->lang->line('gp_user'); ?></a>
                            <a class="btn btn-danger"
                               onclick="confirmLink(GP.deleteRole,'User: <?php echo $user_item['first_name'] . ' ' . $user_item['last_name']; ?>','<?php echo site_url('users/remove_role/' . $group['id'] . '/' . $user_item['user_id'] . '/project_groups'); ?>')"><?php echo $this->lang->line('gp_remove'); ?></a>
                        </td>
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
        <?php endif; ?>

        <fieldset id="edit-group-items" class="tab-pane">

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

<script type="text/javascript">

    $.fn.typeahead.Constructor.prototype.clear = function () { this.$element.data("active", null); };

    $('input.typeahead').typeahead({
        minLength: 2,
        autoSelect: false,
        changeInputOnMove: false,
        source:  function (query, process) {
            return $.get(GP.settings.siteUrl + '/users/search', { query: query }, function (data) {
                //console.log(data);
                data = $.parseJSON(data);
                return process(data);
            });
        }
    });

    //dobiš aktivno
    //$('input.typeahead').typeahead("getActive");

    $('input[type=search]').on('search', function () {
        // search logic here
        $('.typeahead').typeahead('clear');
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