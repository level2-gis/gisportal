<div class="col-md-12">
    <?php echo form_open('projects/create/'.$action); ?>
    <div class="row form-group">
        <label for="client_id" class="control-label col-md-2"><?php echo $this->lang->line('gp_client'); ?></label>

        <div class="col-md-5">
            <select class="form-control" name="client_id" id="client_id" onchange="onClientChange(this,<?php echo $action; ?>);">
                <option value=""><?php echo $this->lang->line('gp_select_client'); ?></option>
                <?php foreach ($clients as $client_item): ?>
                    <option <?php if ($client_item['id'] == $project['client_id']) {
                        echo "selected='selected'";
                    }; ?>
                        value="<?php echo $client_item['id']; ?>"><?php echo $client_item['display_name'] . " (" . $client_item['name'] . ")"; ?></option>                            <?php endforeach; ?>
            </select>
            <span class="text-danger"><?php echo form_error('client_id'); ?></span>
        </div>
    </div>

    <?php if ($action==1) { ?>
    <div class="row form-group">
        <label for="display_name"
               class="control-label col-md-2"><?php echo $this->lang->line('gp_template'); ?></label>

        <div class="col-md-5">
            <div <?php if (empty($project["client_id"])) { echo "style='display:none'"; } ?> id="templateDiv" class="form-inline">
                <select class="form-control" name="template" id="template">
                    <option value=""><?php echo $this->lang->line('gp_select_template'); ?></option>
                    <?php foreach ($templates as $template_item): ?>
                        <option
                            value="<?php echo $template_item; ?>"><?php echo $template_item; ?></option>                            <?php endforeach; ?>
                </select>
                <span class="text-danger"><?php echo form_error('template'); ?></span>
            </div>
        </div>
    </div>
    <?php } ?>

    <div class="row form-group">
        <label for="display_name"
               class="control-label col-md-2"><?php echo $this->lang->line('gp_display_name'); ?></label>

        <div class="col-md-5">
            <input class="form-control" name="display_name" placeholder="" type="text"
                   value="<?php echo $project['display_name']; ?>"/>
            <span class="text-danger"><?php echo form_error('display_name'); ?></span>
        </div>
    </div>

    <div class="row form-group">
        <label for="name" class="control-label col-md-2"><?php echo $this->lang->line('gp_name'); ?></label>

        <div class="col-md-5">
            <input class="form-control" name="name" id="project_name" placeholder="" type="text" <?php if ($action==2) { echo "readonly='readonly'"; }; ?>
                   value="<?php echo $project['name']; ?>"/>
            <span class="text-danger"><?php echo form_error('name'); ?></span>

            <p class="help-block"><?php echo $this->lang->line('gp_name_tip'); ?></p>
        </div>
    </div>

    <div id="fixed-actions">
        <div class="form-actions col-md-offset-1 col-md-8">
            <input name="creating" type="hidden" value="<?php echo $creating; ?>">
            <input id="base_ids" name="base_layers_ids" type="hidden" value="{}">
            <input id="extra_ids" name="extra_layers_ids" type="hidden" value="{}">

            <input type="submit" class="btn btn-primary" onclick="checkValues()"
                   value=<?php echo $this->lang->line('gp_save'); ?>>
            <input type="submit" class="btn btn-primary" onclick="checkValues()" name="return"
                   value=<?php echo $this->lang->line('gp_save') . "&nbsp;&&nbsp;" . strtolower($this->lang->line('gp_return')); ?>>
            <a class="btn btn-default"
               href="<?php echo site_url('projects/'); ?>"><?php echo $this->lang->line('gp_return'); ?></a>
        </div>
    </div>
    </form>
</div>
