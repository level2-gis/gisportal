<div class="page-header clearfix">
    <h1 class="col-md-8"><span><?php echo $title; ?></span></h1>
</div>

<?php echo $this->session->flashdata('alert'); ?>

<div class="col-md-12">

    <?php foreach ($services as $service): ?>
        <div class="row form-group">
            <label class="control-label col-md-1"><?php echo strtoupper($service['name']); ?></label>
            <?php if ($service['published'] == TRUE): ?>
                <div class="col-md-3">
                        <p><?php echo date(DATE_W3C, $service['date']); ?></p>
                </div>
                <div class="col-md-1">
                    <i class="<?php echo $service['icon']; ?>"></i>
                </div>
                <div class="col-md-1">
                    <p><?php echo ucfirst($service['type']); ?></p>
                </div>
                <div class="col-md-1">
                    <p><a target="_blank" href="<?php echo base_url($service['url']); ?>">URL</a></p>
                </div>
                <div class="col-md-2">
                    <p><a target="_blank" href="<?php echo base_url($service['capabilities']); ?>">GetCapabilities</a></p>
                </div>
                <div class="col-md-2">
                    <a class="btn btn-danger" onclick="confirmLink(GP.stopService,'<?php echo strtoupper($service['name']).' '.ucfirst($service['type']).' '.$project['name']; ?>','<?php echo site_url('projects/stop_service/'.$project['id'].'/'.$service['name'].'/'.$service['type']); ?>')"><?php echo $this->lang->line('gp_stop'); ?></a>
                </div>
                <?php else: ?>
                <div class="col-md-2">
                    <a class="btn btn-default" onclick="confirmLink(GP.publishPublicService,'<?php echo strtoupper($service['name']).' '.$project['name']; ?>','<?php echo site_url('projects/publish_service/'.$project['id'].'/'.$service['name'].'/public'); ?>')"><i class="fa fa-group"></i> <?php echo $this->lang->line('gp_publish_public'); ?></a>
                </div>
                <div class="col-md-2">
                    <a class="btn btn-default" onclick="confirmLink(GP.publishPrivateService,'<?php echo strtoupper($service['name']).' '.$project['name']; ?>','<?php echo site_url('projects/publish_service/'.$project['id'].'/'.$service['name'].'/private'); ?>')"><i class="glyphicon glyphicon-lock"></i> <?php echo $this->lang->line('gp_publish_private'); ?></a>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
    <p class="help-block"><?php echo $this->lang->line('gp_services_info'); ?></p>
    <div id="fixed-actions">
        <div class="form-actions col-md-8">
            <a class="btn btn-default"
               href="<?php echo site_url('projects/'); ?>"><?php echo $this->lang->line('gp_return'); ?></a>
        </div>
    </div>

</div>
