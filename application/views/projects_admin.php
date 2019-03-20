<div class="page-header clearfix">
	<h1 class="col-md-8"><?php echo $title; ?></h1>
    <?php if ($this->ion_auth->is_admin()){ ?>
        <div class="btn-group actions  pull-right">
            <button type="button" class="btn btn-mini btn-success dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <?php echo $this->lang->line('gp_new_project'); ?> <span class="caret"></span>
            </button>
            <ul class="dropdown-menu">
                <li><a href="<?php echo site_url('projects/create/1'); ?>"><?php echo $this->lang->line('gp_new_template'); ?></a></li>
                <li><a href="<?php echo site_url('projects/create/2'); ?>"><?php echo $this->lang->line('gp_new_upload'); ?></a></li>
            </ul>
        </div>
    <?php } ?>
</div>

<?php echo $this->session->flashdata('alert'); ?>

<div class="col-md-12">

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
</div>
