	<div class="row">
		<div class="col-md-8">
			<h3><span class="glyphicon glyphicon-user" aria-hidden="true"></span> <?php echo $user->display_name; ?></h3>
			<hr/>
            <table class="table table-hover table-condensed">
                <tbody>
                <tr>
                    <th><?php echo $this->lang->line('gp_username'); ?></th>
                    <td><?php echo $user->user_name; ?></td>
                </tr>
                <tr>
                    <th><?php echo $this->lang->line('gp_email'); ?></th>
                    <td><?php echo $user->user_email; ?></td>
                </tr>
                <tr>
                    <th><?php echo $this->lang->line('gp_registered'); ?></th>
                    <td><?php echo $user->registered; ?></td>
                </tr>
                <tr>
                    <th><?php echo $this->lang->line('gp_last_login'); ?></th>
                    <td><?php echo $user->last_login; ?></td>
                </tr>
                <tr>
                    <th><?php echo $this->lang->line('gp_count_login'); ?></th>
                    <td><?php echo $user->count_login; ?></td>
                </tr>
                </tbody>
            </table>
		</div>

	</div>

    </br>

