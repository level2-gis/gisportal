<?php
/*
|--------------------------------------------------------------------------
| GISPORTAL and EQWC own configs
|
| This settings were before 2019/02/25 at the bottom of config.php
| For easier updating its now own file with new settings added.
| This settings in config.php should be deleted.
|
| This is config template file. You need to copy it to gisportal.php and
| adjust values.
|
|--------------------------------------------------------------------------
|
*/
defined('BASEPATH') OR exit('No direct script access allowed');

$config['company'] = 'Company name';
$config['company_url'] = 'company url';

$config['web_client_url'] = '../../gisapp/';  //relative to base_site defined on top

//Set which languages show up for user selection.
//New languages must be added to array (translation must exist)
//Unwanted languages can be removed
//key= 2 letter iso language code,
//name= english language name as it is in CI
//native = native language name displayed
$config['available_languages'] = array(
    'en' => array(
        'name' => 'english',
        'native' => 'English'
    ),
    'de' => array(
        'name' => 'german',
        'native' => 'Deutsch'
    ),
//    'it' => array(
//        'name' => 'italian',
//        'native' => 'Italiano'
//    ),
    'no' => array(
        'name' => 'norwegian',
        'native' => 'norsk'
    ),
    'pl' => array(
        'name' => 'polish',
        'native' => 'polski'
    ),
//    'sk' => array(
//        'name' => 'slovak',
//        'native' => 'slovenský'
//    ),
    'sl' => array(
        'name' => 'slovenian',
        'native' => 'slovenščina'
    ),
    'es' => array(
        'name' => 'spanish',
        'native' => 'Español'
    ),
    'sv' => array(
        'name' => 'swedish',
        'native' => 'svenska'
    ),
    'ru' => array(
        'name' => 'russian',
        'native' => 'русский'
    )
);

/*
File upload main directory, must exist on disk with full permission (chmod 777)
Can be outside of gisportal application
You have to put absolute file path like below, and also  make proper Apache configuration

Alias /uploads /home/uploads

<Directory /home/uploads/>
Options Indexes FollowSymLinks
AllowOverride None
Require all granted
</Directory>
*/
$config['main_upload_dir'] = '/home/uploads/';
$config['main_upload_web'] = './uploads/';

//possible values QGS_MAIN, QGS_CLIENT
$config['qgis_project_default_location'] = QGS_MAIN;