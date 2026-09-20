<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| WMS Client Configuration
|--------------------------------------------------------------------------
|
| Configuration settings for WMS (Web Map Service) client
|
*/

// Default WMS protocol settings
$config['wms_default_version'] = '1.3.0';
$config['wms_default_crs'] = 'EPSG:3794';
$config['wms_default_format'] = 'application/json';

// Request timeout settings
$config['wms_timeout'] = 30; // seconds
$config['wms_connect_timeout'] = 5; // seconds
$config['wms_verify_tls'] = true;
$config['wms_feature_info_timeout'] = 10; // seconds per attempt
$config['wms_feature_info_attempts'] = 2;
$config['wms_feature_info_retry_delay_ms'] = 200;

// GetFeatureInfo settings
$config['wms_feature_count'] = 10; // Maximum features to retrieve (as requested)
$config['wms_report_max_layers'] = 20;
$config['wms_max_feature_response_size'] = 2 * 1024 * 1024; // 2MB

// Map response limit
$config['wms_max_image_size'] = 10 * 1024 * 1024; // 10MB

// The server derives the map context and image aspect ratio from the feature BBOX returned by QGIS.
$config['wms_feature_report_width'] = 800;
$config['wms_feature_report_dpi'] = 200;
$config['wms_feature_report_bbox_margin'] = 0.20; // 20% on each side

// External WMS layers clients may enable with external_layers[].
$config['wms_feature_report_external_layers'] = [
	'dof' => [
		'url' => 'http://195.206.229.96/wms/wms_dof025_latest',
		'format' => 'image/jpeg',
		'crs' => 'EPSG:3794',
		'layers' => 'dof025_latest',
		'styles' => ''
	]
];
