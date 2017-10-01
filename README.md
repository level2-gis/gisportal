Web portal for QGIS projects published by EQWC
==============================================

This is start page - Web portal for browsing and opening QGIS projects published with [Extended QGIS Web Client.](https://github.com/uprel/gisapp)

User registration and login is now part of this. Next steps are planned to have complete web administration part for publishing projects and layers, delegating user permissions...

Built with Codeigniter and Bootstrap.

## Demo
Visit **<a target="_blank" href="http://test.level2.si">Demo by Level2</a>**

## Setup

You go through this after you setup EQWC!

1. Checkout into web root to have gisportal folder beside gisapp folder (EQWC)
1. Setup database connection in application/config/database.php
1. Setup base_site and other EQWC settings at bottom of application/config/config.php
1. Edit header_logo.png in assets/img folder.
1. Enable integration with gisportal in gisapp/client_common/settings.js
1. To preserve session information from gisapp to gisportal you have to edit php.ini and change line

    ```
    session.name = PHPSESSID
    to
    session.name = sess_
    ```

    This means that you login to gisportal and then browse all public projects or projects you have permission without
    new login.

## Email service
If you entered correct gmail info at the bottom of config.php you enabled email service using Google SMTP server. That means you don't need to setup own mail server. This service can be used to sending emails from gisapp or gisportal.

[Test mail - localhost example](http://localhost/gisportal/index.php/mail/test)

Email service is currently used with new User Feedback control in gisapp and with Editor plugin.
It is planned to be used with gisportal (for sending emails to users) and for other tasks in gisapp.

If you have problems sending email check this settings for your Google account:
1. Login to Google My account and go to the My Account page. Click the Signing in to Google link from Sign-in & security section.
![google1](http://level2.si/wp-content/uploads/2017/10/google_account1.png)
1. Scroll down the Password & sign-in method section and turn Off the 2-Step Verification. 
![google2](http://level2.si/wp-content/uploads/2017/10/google_account2.png)
1. Scroll down the Connected apps & sites section and turn On Allow less secure apps.
![google3](http://level2.si/wp-content/uploads/2017/10/google_account3.png)

## Thumbnail images

gisportal uses thumbnail images for client and project display. Copy images to assets/img/clients and assets/img/projects
folder with client or project name as it is in database in PNG format.

## Support

Contact us for:
* support
* custom development

Uroš Preložnik, http://level2.si
