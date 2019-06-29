<div class="page-header clearfix">
    <h1 class="col-md-8"><?php echo $title; ?>
    <br><small><?php echo $subtitle; ?></small></h1>
    <div class="actions  pull-right">
        <button type="button" class="pull-right btn btn-default btn-sm" data-toggle="collapse" data-target="#list_emails"><?php echo lang('gp_users_title'); ?>
            <span class="badge"><?php echo count($emails); ?></span>
        </button>
    </div>

</div>

<?php echo $this->session->flashdata('alert'); ?>

<div class="col-md-12">

    <div id="list_emails" class="collapse">
        <p class="help-block"><?php echo implode(';',$emails); ?></p>
    </div>

    <?php $attributes = array("class" => "form-horizontal");
    echo form_open('project_groups/send_email/' . $group['id'], $attributes); ?>

    <fieldset>
        <div class="form-group">
            <label for="subject" class="control-label col-md-2"><?php echo lang('gp_email_subject'); ?></label>
            <div class="col-md-5">
                <input class="form-control" name="subject" placeholder="" type="text"/>
                <span class="text-danger"><?php echo form_error('subject'); ?></span>
            </div>
        </div>

        <div class="form-group">
            <label for="body"
                   class="control-label col-md-2"><?php echo lang('gp_email_body'); ?></label>
            <div class="col-md-5">
                <textarea class="form-control" cols="20" rows="10" name="body" placeholder="" type="text"></textarea>
                <span class="text-danger"><?php echo form_error('body'); ?></span>
            </div>
        </div>
    </fieldset>

    <div id="fixed-actions">
        <hr>
        <div class="form-actions col-md-8">
            <input type="submit" class="btn btn-primary" value=<?php echo lang('gp_send'); ?>>
        </div>
    </div>

    </form>

</div>
</div>