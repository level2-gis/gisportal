<div class="page-header clearfix">
    <h1 class="col-md-8"><span><?php echo $title; ?></span></h1>
</div>

<?php echo $this->session->flashdata('alert'); ?>

<div class="col-md-12">
    <?php echo form_open('project_groups/create'); ?>
    <div class="row form-group">
        <label for="client_id" class="control-label col-md-2"><?php echo $this->lang->line('gp_client'); ?></label>

        <div class="col-md-5">
            <select class="form-control" name="client_id" id="client_id" onchange="getParentGroups(this,null);">
                <option value="" selected="true" disabled><?php echo $this->lang->line('gp_select_client'); ?></option>
                <?php foreach ($clients as $client_item): ?>
                    <option <?php if ($client_item['id'] == $group['client_id']) {
                        echo "selected='selected'";
                    }; ?>
                        value="<?php echo $client_item['id']; ?>"><?php echo $client_item['display_name'] . " (" . $client_item['name'] . ")"; ?></option>                            <?php endforeach; ?>
            </select>
            <span class="text-danger"><?php echo form_error('client_id'); ?></span>
        </div>
    </div>

    <div class="row form-group">
        <label for="name" class="control-label col-md-2"><?php echo $this->lang->line('gp_name'); ?></label>

        <div class="col-md-5">
            <input class="form-control" name="name" placeholder="" type="text"
                   value="<?php echo $group['name']; ?>"/>
            <span class="text-danger"><?php echo form_error('name'); ?></span>
            <p class="help-block"><?php echo $this->lang->line('gp_name_help'); ?></p>
        </div>
    </div>

    <div class="row form-group">
        <label for="display_name"
               class="control-label col-md-2"><?php echo $this->lang->line('gp_display_name'); ?></label>

        <div class="col-md-5">
            <input class="form-control" name="display_name" placeholder="" type="text"
                   value="<?php echo $group['display_name']; ?>"/>
            <span class="text-danger"><?php echo form_error('display_name'); ?></span>
        </div>
    </div>
    <div class="row form-group">
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
    <div class="row form-group">
        <label for="url" class="control-label col-md-2"><?php echo $this->lang->line('gp_type'); ?></label>

        <div class="col-md-5">
            <select class="form-control" name="type">
                <?php foreach ($types as $type): ?>
                    <option value="<?php echo $type['id']; ?>"><?php echo $type['name']; ?></option>
                <?php endforeach; ?>
            </select>
            <span class="text-danger"><?php echo form_error('type'); ?></span>
        </div>
    </div>

    <div id="fixed-actions">
        <hr>
        <div class="form-actions col-md-8">
            <input name="creating" type="hidden" value="<?php echo $creating; ?>">
            <input id="base_ids" name="base_layers_ids" type="hidden" value="{}">
            <input id="extra_ids" name="extra_layers_ids" type="hidden" value="{}">

            <input type="submit" class="btn btn-primary" onclick="checkValues()"
                   value=<?php echo $this->lang->line('gp_save'); ?>>
            <input type="submit" class="btn btn-primary" onclick="checkValues()" name="return"
                   value=<?php echo $this->lang->line('gp_save') . "&nbsp;&&nbsp;" . strtolower($this->lang->line('gp_return')); ?>>
            <a class="btn btn-default"
               href="<?php echo site_url('project_groups/'); ?>"><?php echo $this->lang->line('gp_return'); ?></a>
        </div>
    </div>
    </form>
</div>
