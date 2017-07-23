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
    <link rel="stylesheet" href="<?php echo base_url("assets/css/1-col-portfolio.css"); ?>">

</head>

<body>

<!-- Navigation -->
<nav class="navbar navbar-inverse navbar-fixed-top" role="navigation">
    <div class="container">
        <!-- Brand and toggle get grouped for better mobile display -->
        <div class="navbar-header">
            <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
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
                <?php if ($this->session->userdata('user_is_logged_in')){ ?>
                    <li><a href="<?php echo base_url(); ?>index.php/profile"><span class="glyphicon glyphicon-user" aria-hidden="true"></span> <?php echo $this->session->userdata('user_name'); ?></a></li>
                    <li><a href="<?php echo base_url(); ?>index.php/home/logout"><?php echo $this->lang->line('gp_log_out'); ?></a></li>
                <?php } else { ?>
                    <li><a href="<?php echo base_url(); ?>index.php/login"><?php echo $this->lang->line('gp_login'); ?></a></li>
                    <li><a href="<?php echo base_url(); ?>index.php/signup"><?php echo $this->lang->line('gp_register'); ?></a></li>
                <?php } ?>
            </ul>
        </div>
        <!-- /.navbar-collapse -->
    </div>
    <!-- /.container -->
</nav>
<div class="container">