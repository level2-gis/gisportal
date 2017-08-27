This is start page - portal for browsing and opening QGIS projects defined to use in Extended QGIS Web Client.
User registration and login is now part of this.
Built with Codeigniter and Bootstrap.

## Setup

1. Checkout into gisportal folder beside gisapp folder (EQWC)
2. Setup database connection in application/config/database.php
3. Setup base_site and other EQWC settings at bottom of application/config/config.php
4. Edit header_logo.png in assets/img folder
5. To preserve session information from gisapp to gisportal you have to edit php.ini and change line

session.name = PHPSESSID
to
session.name = sess_

This means that you login to gisportal and then browse all public projects or projects you have permission without
new login.

## Support

Contact us for:
- support
- custom development

Uroš Preložnik, http://level2.si
