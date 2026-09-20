<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| PHPWord Configuration
|--------------------------------------------------------------------------
|
| Configuration settings for PHPWord document generation
|
*/

// Default template settings
$config['phpword_default_template_name'] = 'feature_report.docx';
$config['phpword_default_template'] = APPPATH . 'templates' . DIRECTORY_SEPARATOR . $config['phpword_default_template_name'];

// Image settings for maps
$config['phpword_map_image_width'] = 500;

// Image width for the feature-report files field
$config['phpword_files_image_width'] = 150;
