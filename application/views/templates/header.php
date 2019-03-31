<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">

    <title><?php echo $title; ?> | <?php echo $this->lang->line('gp_portal_title'); ?></title>
    <!--link the bootstrap css file-->
    <link rel="stylesheet" href="<?php echo base_url("assets/css/bootstrap.min.css"); ?>">
    <link rel="stylesheet" href="<?php echo base_url("assets/css/bootstrap-table.min.css"); ?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/css/font-awesome.min.css');?>">
    <link rel="stylesheet" href="<?php echo base_url("assets/css/1-col-portfolio.css?v=20180310"); ?>">
    <link rel="stylesheet" href="<?php echo base_url("assets/css/site.css?v=20180315"); ?>">
    <script type="text/javascript" src="<?php echo base_url("assets/js/jquery.js"); ?>"></script>
    <script type="text/javascript" src="<?php echo base_url("assets/js/bootstrap.min.js"); ?>"></script>
    <script type="text/javascript" src="<?php echo base_url("assets/js/bootstrap-filestyle.min.js"); ?>"></script>
    <script type="text/javascript" src="<?php echo base_url("assets/js/bootbox.min.js"); ?>"></script>
    <script type="text/javascript" src="<?php echo base_url("assets/js/bootstrap-table.min.js"); ?>"></script>
    <script type="text/javascript" src="<?php echo base_url("assets/js/locale/bootstrap-table-".$lang.".js"); ?>"></script>
    <script type="text/javascript" src="<?php echo base_url("assets/js/jquery.selectlistactions.js"); ?>"></script>
    <script type="text/javascript" src="<?php echo base_url("assets/js/gisportal_common.js?v=20190320"); ?>"></script>
    <script type="text/javascript">
        var GP = {
            clientRequired:         '<?php echo $this->lang->line('gp_client_required'); ?>',
            noFile:                 '<?php echo $this->lang->line('gp_no_file'); ?>',
            onlyQgs:                '<?php echo $this->lang->line('gp_only_qgs'); ?>',
            differentProjects:      '<?php echo $this->lang->line('gp_diff_proj'); ?>',
            deleteProject:          '<?php echo $this->lang->line('gp_del_proj'); ?>',
            deleteGeneral:          '<?php echo $this->lang->line('gp_del_general'); ?>',
            stopService:            '<?php echo $this->lang->line('gp_stop_service'); ?>',
            publishPublicService:   '<?php echo $this->lang->line('gp_publish_public_service'); ?>',
            publishPrivateService:  '<?php echo $this->lang->line('gp_publish_private_service'); ?>',
            selectGroup:            '<?php echo $this->lang->line('gp_select_group'); ?>'
        };
    </script>
</head>

<body>

<!-- Navigation -->
<nav class="navbar navbar-inverse navbar-fixed-top" role="navigation">
    <div class="container">
        <!-- Brand and toggle get grouped for better mobile display -->
        <div class="navbar-header">
            <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#navbar1">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" target="_blank" href="<?php echo $this->config->item('company_url'); ?>">
                <img height="32px" src="<?php echo base_url("assets/img/header_logo.png"); ?>" alt="">
            </a>
            <a class="navbar-brand" href="<?php echo base_url(); ?>"><?php echo $this->lang->line('gp_portal_title'); ?></a>
        </div>
        <!-- Collect the nav links, forms, and other content for toggling -->
        <div class="collapse navbar-collapse" id="navbar1">
            <ul class="nav navbar-nav navbar-right">
                <?php if ($logged_in){ ?>

                    <?php if ($is_admin){ ?>
                        <li><a href="<?php echo site_url('/clients'); ?>"><i class="fa fa-folder"></i> <span><?php echo $this->lang->line('gp_clients_title'); ?></span></a></li>
                        <li><a href="<?php echo site_url('/project_groups'); ?>"><i class="fa fa-list"></i> <span><?php echo $this->lang->line('gp_groups_title'); ?></span></a></li>
                    <?php } ?>

                    <li><a href="<?php echo site_url('/projects'); ?>"><i class="fa fa-file-text"></i> <span><?php echo $this->lang->line('gp_projects_title'); ?></span></a></li>

                    <?php if ($is_admin){ ?>
                        <li><a href="<?php echo site_url('/layers'); ?>"><i class="fa fa-database"></i> <span><?php echo $this->lang->line('gp_layers_title'); ?></span></a></li>
                        <li><a href="<?php echo site_url('/users'); ?>"><i class="fa fa-group"></i> <span><?php echo $this->lang->line('gp_users_title'); ?></span></a></li>
                    <?php } ?>

                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"><span class="glyphicon glyphicon-user" aria-hidden="true"></span> <?php echo $this->session->userdata('user_name'); ?> <span class="caret"></span></a>
                        <ul class="dropdown-menu">
                            <li><a href="<?php echo site_url('/profile') ?>"><?php echo $this->lang->line('gp_profile_title'); ?></a></li>
                            <li><a href="<?php echo site_url('/auth/logout') ?>"><?php echo $this->lang->line('gp_log_out'); ?></a></li>
                        </ul>
                    </li>
                <?php } else { ?>
                    <li><a href="<?php echo site_url('/auth/login') ?>"><?php echo $this->lang->line('gp_login'); ?></a></li>
                    <li><a href="<?php echo site_url('/signup') ?>"><?php echo $this->lang->line('gp_register'); ?></a></li>
                <?php } ?>
            </ul>
        </div>
        <!-- /.navbar-collapse -->
    </div>
    <!-- /.container -->
</nav>
<div class="container body-content">