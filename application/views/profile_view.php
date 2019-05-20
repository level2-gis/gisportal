<div class="page-header clearfix">
    <h1 class="col-md-8"><span class="glyphicon glyphicon-user" aria-hidden="true"></span> <?php echo html_escape($user->display_name); ?></h1>

</div>

	<div class="row">
		<div class="col-md-8">
            <?php if (!empty($role_scope)) : ?>
                <div class="alert alert-info" role="alert"><?php echo html_escape($role_scope . ' ' . $role_name); ?></div>
            <?php endif ?>
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
                    <th><?php echo $this->lang->line('gp_organization'); ?></th>
                    <td><?php echo $user->organization; ?></td>
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
                <tr>
                    <th><?php echo $this->lang->line('gp_language'); ?></th>
                    <td>
                        <select onchange="javascript:window.location.href='<?php echo site_url('/language/switchlang/') ?>'+this.value;">
                            <?php foreach ($available_languages as $lang_key => $lang_value): ?>
                                <option value="<?php echo $lang_key?>" <?php if($lang_key == $lang) echo 'selected="selected"'; ?>><?php echo $lang_value['native']?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                </tbody>
            </table>
		</div>

	</div>

    </br>

