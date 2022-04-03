GIS portal and Administration part of Extended QGIS Web Client
=================================================================
Built with Codeigniter and Bootstrap.

Client part of EQWC is [**gisapp**](https://github.com/uprel/gisapp)

> **Documentation below is obsolete and needs updating to gisportal v2**

## Users

- start page
- registration
- single login, browse projects with permission
- profile page, set own language

Visit **<a target="_blank" href="http://test.level2.si">Demo for users by Level2</a>**

## Administrators

Administrator is every user with admin=true in database.

- view/create/edit/delete clients/projects/layers
- set base or overlay layers for project (with order)
- view/edit/delete users
- delegate users to project
- activate available plugins for project
- upload/download QGIS Project file for QGIS Server
- use QGIS project templates

![admin1](https://github.com/uprel/gisportal/wiki/images/admin_projects_view1.png)

## Setup v2

> You go through this after you setup gisapp!

> This code relies on database from gisapp. Make sure you are running latest [database version](https://github.com/uprel/gisapp/wiki/3.-Managing-Database#upgrading)!

1. Checkout into web root to have gisportal folder beside gisapp folder (EQWC)

	```
	cd /var/www/html/
	git clone https://github.com/level2-gis/gisportal.git
	```
    This got you latest code from master. If you need specific version type:
    
    ```
    cd gisportal
	git checkout v2.0.0
	```
	
1. Setup database connection in `application/config/database.php`
1. Setup base site URL and default language in `application/config/config.php`
1. Copy `gisportal_template.php` to `gisportal.php` in `application/config/`.
1. Adjust gisportal specific settings in new `gisportal.php` file.
1. Upgrade gisapp database with gisportal specifics. Details are in [database/readme.txt](database/readme.txt).
1. Edit header_logo.png in `assets/img` folder.
1. Enable integration with gisportal in `gisapp/client_common/settings.js`
1. To preserve session information from gisapp to gisportal you have to edit `php.ini` and change line

    ```
    session.name = PHPSESSID
    to
    session.name = sess_
    ```

    To match default session time from gisportal (7200 sec) edit line
    
    ```
    session.gc_maxlifetime = 1440
    to
    session.gc_maxlifetime = 7200
    ```
    
    This means that you login to gisportal and then browse all public projects or projects you have permission without
    new login.
    
1.  If you want users to confirm their registration by getting email to activate account you need to enable Email service below and set to TRUE
    option 
    
    ```
    $config['email_activation']  
    ```
    
    in `application/third_party/ion_auth/config/ion_auth.php`    

1.  Navigate browser to http://your-server/gisportal/, you should see login page. Default login is `admin`, `admin`.

## Email service
You need to configure email for password reset and other email actions from gisportal and gisapp.
Current email configuration is for Gmail account to send emails using Google SMTP server. That means you don't need to setup own mail server.
Edit `application/config/email.php` with your own Gmail access.

[Test mail - localhost example](http://localhost/gisportal/index.php/mail/test)

If you have problems sending email check this settings for your Google account: [Google-account-configuration](../../wiki/Google-account-configuration)

## Shortening URL
Now your gisportal URL looks like this:

```http://your-server/gisportal/index.php/login```

Read [Shortening URL on Wiki](https://github.com/uprel/gisportal/wiki/Shortening-URL) to remove "/gisportal/index.php", like this:

```http://your-server/login```

> You can test this on provided Demo link above!

## Translations

1. Check if your language exists in [system folder](https://github.com/uprel/gisportal/tree/master/system/language). 
1. If not get it from [Codeigniter Translations](https://github.com/bcit-ci/codeigniter3-translations)
1. Create folder with language name in [application/language](https://github.com/uprel/gisportal/tree/master/application/language) folder.
1. Copy gisportal_lang.php from any other languages to new language folder and translate contents.
1. Add new language in config/gisportal.php at the bottom: $config['available_languages']. Here you can also remove unwanted languages.
1. If you wish you can change default language in application/config/config.php.
1. Get your language also for [Bootstrap Table plugin](https://github.com/wenzhixin/bootstrap-table/tree/develop/src/locale) and copy file to /assets/js/locale/ folder. Rename it to only contain language code, as other files in the folder (remove country code).

## Styling top menu (navbar)

You can change black background to default gray by changing 

```
navbar-inverse
to
navbar-default
```
/application/views/templates/header_template.php

## Contributing

Support this project by [**DONATING**](http://level2.si/product/donation-extended-qgis-web-client/).

You are also welcome to contribute to the project by testing, requesting new features, translating, submitting code, ...
Read this [tutorial about making changes to repositories](https://help.github.com/articles/fork-a-repo/).

Thank you!

## Credits

Thanks to all translators, donators and special thanks to following companies for supporting this project:
* Swescan, Sweden
* GEL Consulting group, USA
* Geonord, Norway
* soljoy, Austria

## Support

Contact us for:
* support
* custom development

Uroš Preložnik, http://level2.si

## Copyright

Gisportal (c) 2017 - 2019, Level2 Uroš Preložnik s.p. 
