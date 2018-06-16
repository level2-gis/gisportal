GIS portal and Administration part of Extended QGIS Web Client
=================================================================
Built with Codeigniter and Bootstrap.

Client part of EQWC is [**gisapp**](https://github.com/uprel/gisapp)

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

![admin1](https://github.com/uprel/gisportal/wiki/images/admin_projects_view1.png)

## Setup

> You go through this after you setup gisapp!

> This code relies on database from gisapp. Make sure you are running latest [database version](https://github.com/uprel/gisapp/wiki/3.-Managing-Database#upgrading)!

1. Checkout into web root to have gisportal folder beside gisapp folder (EQWC)

	```
	cd /var/www/html/
	git clone https://github.com/uprel/gisportal.git
	```
	
1. Setup database connection in application/config/database.php
1. Setup base_site, default language and other EQWC settings at bottom of application/config/config.php
1. Edit header_logo.png in assets/img folder.
1. Enable integration with gisportal in gisapp/client_common/settings.js
1. To preserve session information from gisapp to gisportal you have to edit php.ini and change line

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

## Email service
If you entered correct gmail info at the bottom of config.php you enabled email service using Google SMTP server. That means you don't need to setup own mail server. This service can be used to sending emails from gisapp or gisportal.

[Test mail - localhost example](http://localhost/gisportal/index.php/mail/test)

Email service is currently used with new User Feedback control in gisapp and with Editor plugin.
It is planned to be used with gisportal (for sending emails to users) and for other tasks in gisapp.

If you have problems sending email check this settings for your Google account: [Google-account-configuration](../../wiki/Google-account-configuration)

## Shortening URL
Now your gisportal URL looks like this:

```http://localhost/gisportal/index.php/login```

Read [Shortening URL on Wiki](https://github.com/uprel/gisportal/wiki/Shortening-URL) to remove "/gisportal/index.php", like this:

```http://localhost/login```

> You can test this on provided Demo link above!

## Translations

1. Check if your language exists in [system folder](https://github.com/uprel/gisportal/tree/master/system/language). 
1. If not get it from [Codeigniter Translations](https://github.com/bcit-ci/codeigniter3-translations)
1. Create folder with language name in [application/language](https://github.com/uprel/gisportal/tree/master/application/language) folder.
1. Copy gisportal_lang.php from any other languages to new language folder and translate contents.
1. Add new language in config/config.php at the bottom: $config['available_languages']. Here you can also remove unwanted languages.
1. If you wish you can change default language in application/config/config.php.

## Styling top menu (navbar)

You can change black background to default gray by changing 

```
navbar-inverse
to
navbar-default
```
in [/application/views/templates/header.php](https://github.com/uprel/gisportal/blob/9a657cf05c7fb6d6b9b6d38f561143656804eb57/application/views/templates/header.php#L26)

Or add lines below to /assets/css/style.css:

```
.navbar-inverse {
    background-color: #222;
    border-color: #080808;
}
```
And change colors as you wish.

## Contributing

Support this project by [**DONATING**](http://level2.si/product/donation-extended-qgis-web-client/).

You are also welcome to contribute to the project by testing, requesting new features, translating, submitting code, ...
Read this [tutorial about making changes to repositories](https://help.github.com/articles/fork-a-repo/).

Thank you!

## Credits

Thanks to all translators, donators and special thanks to following companies for supporting this project:
* GEL Consulting group, USA
* Geonord, Norway
* Swescan, Sweden

## Support

Contact us for:
* support
* custom development

Uroš Preložnik, http://level2.si

## Copyright

Gisportal (c) 2017 - 2018, Level2 Uroš Preložnik s.p. 
