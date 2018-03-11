	<div class="page-header clearfix">
		<h1 class="col-md-8"><?php echo $title; ?></h1>
	</div>

	<?php echo $this->session->flashdata('alert'); ?>

	<div class="col-md-12">
		<?php $attributes = array("class" => "form-horizontal");
		echo form_open('projects/edit/' . $project['id'], $attributes); ?>
			<input name="id" type="hidden" value="<?php echo $project['id']; ?>" />

			<ul class="nav nav-tabs">
			  <li class="active"><a href="#edit-project-meta" data-toggle="tab">Properties</a></li>
			  <li><a href="#edit-project-layers" data-toggle="tab">Base Layers</a></li>
			  <li><a href="#edit-project-extra-layers" data-toggle="tab">Overlay Layers</a></li>
			  <li><a href="#edit-project-users" data-toggle="tab">Users</a></li>
			</ul>

			<div class="tab-content">

				<fieldset id="edit-project-meta" class="tab-pane active">

                    <div class="form-group">
                        <label for="client_id" class="control-label col-md-3">Client</label>
                        <div class="col-md-4">
                            <select class="form-control" name="client_id">
                                <option value="">Select Client</option>
                                <?php foreach ($clients as $client_item): ?>
                                    <option <?php if ($client_item['id'] == $project['client_id']) { echo "selected='selected'"; }; ?> value="<?php echo $client_item['id']; ?>"><?php echo $client_item['display_name'] . " (" .$client_item['name'] . ")"; ?></option>							<?php endforeach; ?>
                            </select>
                            <span class="text-danger"><?php echo form_error('client_id'); ?></span>
                        </div>
                    </div>

                    <div class="form-group">
						<label for="name" class="control-label col-md-3">Name</label>
						<div class="col-md-4">
							<input class="form-control" name="name" placeholder="" type="text" value="<?php echo $project['name']; ?>" />
							<span class="text-danger"><?php echo form_error('name'); ?></span>
						</div>	
					</div>	

					<div class="form-group">
						<label for="qgis_check" class="control-label col-md-3">QGIS Project</label>
						<div class="col-md-4">
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
						<label for="display_name" class="control-label col-md-3">Display Name</label>
						<div class="col-md-4">
							<input class="form-control" name="display_name" placeholder="" readonly="readonly" type="text" value="<?php echo $project['display_name']; ?>" />
							<span class="text-danger"><?php echo form_error('display_name'); ?></span>
                            <p class="help-block">This is set from QGIS Project properties!</p>
                        </div>

					</div>	

					<div class="form-group">
						<label for="crs" class="control-label col-md-3">CRS</label>
						<div class="col-md-4">
							<input class="form-control" name="crs" placeholder="" readonly="readonly" type="text" value="<?php echo $project['crs']; ?>" />
							<span class="text-danger"><?php echo form_error('crs'); ?></span>
						</div>	
					</div>

                    <div class="form-group">
                        <label for="overview_layer_id" class="control-label col-md-3">Overview Layer</label>
                        <div class="col-md-4">
                            <select class="form-control" name="overview_layer_id">
                                <option value="">Select Layer</option>
                                <?php foreach ($base_layers as $layer_item): ?>
                                    <option <?php if ($layer_item['id'] == $project['overview_layer_id']) { echo "selected='selected'"; }; ?> value="<?php echo $layer_item['id']; ?>"><?php echo $layer_item['display_name'] . " (" .$layer_item['name'] . ")"; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <span class="text-danger"><?php echo form_error('overview_layer_id'); ?></span>
                        </div>
                    </div>

					<div class="form-group">
						<label for="contact" class="control-label col-md-3">Contact</label>
						<div class="col-md-4">
							<input class="form-control" name="contact" placeholder="" type="text" value="<?php echo $project['contact']; ?>" />
							<span class="text-danger"><?php echo form_error('contact'); ?></span>
						</div>	
					</div>	
					<div class="form-group">
						<label for="feedback_email" class="control-label col-md-3">Feedback Email</label>
						<div class="col-md-4">
							<input class="form-control" name="feedback_email" placeholder="" type="text" value="<?php echo $project['feedback_email']; ?>" />
							<span class="text-danger"><?php echo form_error('feedback_email'); ?></span>
						</div>	
					</div>
                    <div class="form-group">
                        <label for="ordr" class="control-label col-md-3">Display order</label>
                        <div class="col-md-4">
                            <input class="form-control" name="ordr" placeholder="" type="text" value="<?php echo $project['ordr']; ?>" />
                            <span class="text-danger"><?php echo form_error('ordr'); ?></span>
                        </div>
                    </div>
					<div class="form-group">
						<label for="description" class="control-label col-md-3">Description</label>
						<div class="col-md-6">
							<textarea class="form-control" cols="20" rows="4" name="description" placeholder="" type="text"><?php echo $project['description']; ?></textarea>
							<span class="text-danger"><?php echo form_error('description'); ?></span>
						</div>	
					</div>	

					<div class="form-group">
						<div class="col-md-offset-3 col-md-4">
							<div class="control">
								<input type="checkbox" name="public" value="true" <?php if ($project['public']) { echo "checked='checked'"; }; ?> /> Public
							</div>
							<div class="control">
								<input type="checkbox" name="geolocation" value="true" <?php if ($project['geolocation']) { echo "checked='checked'"; }; ?> /> Geolocation
							</div>
							<div class="control">
								<input type="checkbox" name="restrict_to_start_extent" value="true" <?php if ($project['restrict_to_start_extent']) { echo "checked='checked'"; }; ?> /> Restrict to start extent
							</div>
							<div class="control">
								<input type="checkbox" name="feedback" value="true" <?php if ($project['feedback']) { echo "checked='checked'"; }; ?> /> Feedback
							</div>
							<div class="control">
								<input type="checkbox" name="measurements" value="true" <?php if ($project['measurements']) { echo "checked='checked'"; }; ?> /> Measurements
							</div>

                            <div class="control">
                                <input type="checkbox" name="print" value="true" <?php if ($project['print']) { echo "checked='checked'"; }; ?> /> Print
                            </div>
                            <div class="control">
                                <input type="checkbox" name="zoom_back_forward" value="true" <?php if ($project['zoom_back_forward']) { echo "checked='checked'"; }; ?> /> Zoom back - forward
                            </div>
                            <div class="control">
                                <input type="checkbox" name="identify_mode" value="true" <?php if ($project['identify_mode']) { echo "checked='checked'"; }; ?> /> Identify mode
                            </div>
                            <div class="control">
                                <input type="checkbox" name="permalink" value="true" <?php if ($project['permalink']) { echo "checked='checked'"; }; ?> /> Permalink
                            </div>
						</div>
                    </div>
    			</fieldset>

				<fieldset id="edit-project-layers" class="tab-pane">
					<div class="form-group">
						<label for="base_layers_ids" class="control-label col-md-3">Base Layers</label>
						<div class="col-md-4">
							<table class="table table-condensed table-striped">
							  <tr>
								<th>Enabled</th>
								<th>Display Name</th>
								<th>Name</th>
								<th>Type</th>
							  </tr>
								<?php foreach ($base_layers as $layer_item): ?>
									<tr>
										<td>
											<input type="checkbox" name="base_layers_ids[]" value="<?php echo $layer_item['id']; ?>" <?php if ($layer_item['selected']) { echo "checked='checked'"; }; ?> />
                                        </td>
										<td><?php echo $layer_item['display_name']; ?></td>
										<td><?php echo $layer_item['name']; ?></td>
										<td><?php echo $layer_item['type']; ?></td>
									</tr>
								<?php endforeach; ?>
							</table>

							<span class="text-danger"><?php echo form_error('base_layers_ids'); ?></span>
						</div>
					</div>
				</fieldset>

                <fieldset id="edit-project-extra-layers" class="tab-pane">
                    <div class="form-group">
                        <label for="extra_layers_ids" class="control-label col-md-3">Overlay Layers</label>
                        <div class="col-md-4">
                            <table class="table table-condensed table-striped">
                                <tr>
                                    <th>Enabled</th>
                                    <th>Display Name</th>
                                    <th>Name</th>
                                    <th>Type</th>
                                </tr>
                                <?php foreach ($extra_layers as $layer_item): ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="extra_layers_ids[]" value="<?php echo $layer_item['id']; ?>" <?php if ($layer_item['selected']) { echo "checked='checked'"; }; ?> />
                                        </td>
                                        <td><?php echo $layer_item['display_name']; ?></td>
                                        <td><?php echo $layer_item['name']; ?></td>
                                        <td><?php echo $layer_item['type']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </table>

                            <span class="text-danger"><?php echo form_error('extra_layers_ids'); ?></span>
                        </div>
                    </div>
                </fieldset>

				<fieldset id="edit-project-users" class="tab-pane">
					<table class="table table-condensed table-striped">
					  <tr>
						<th>Access</th>
						<th>Username</th>
						<th>Email</th>
						<th>Name</th>
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
				<div class="form-actions col-md-offset-2 col-md-8">
					<input name="creating" type="hidden" value="<?php echo $creating; ?>">

					<input type="submit" class="btn btn-primary" value="Save">
					<input type="submit" class="btn btn-primary" name="return" value="Save &amp; Return">
					<a class="btn btn-default" href="<?php echo site_url('projects/'); ?>">Return</a>
				
				<?php if ( $creating === false ) : ?>
				<div class="pull-right">
                    <a class="btn btn-danger" href="<?php echo site_url('projects/remove/'.$project['id']); ?>">Delete</a>
                </div>
				 <?php endif; ?>
				</div>
			</div>


		</form>

	</div>
