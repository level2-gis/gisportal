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

// Location report (GetFeatureInfo for all requested layers at one map location)
$config['wms_location_report_feature_count'] = 10; // Maximum features per layer
$config['wms_location_report_timeout'] = 30; // seconds per GetFeatureInfo attempt
$config['wms_location_report_max_response_size'] = 5 * 1024 * 1024; // 5MB
$config['wms_location_report_max_size'] = 4096; // Maximum WIDTH and HEIGHT of the client map context
$config['wms_location_report_time_limit'] = 120; // PHP execution time limit for the report request
// Optional QGIS Server search tolerances in pixels; leave empty to use QGIS defaults.
$config['wms_location_report_tolerances'] = [
	// 'FI_POINT_TOLERANCE' => 16,
	// 'FI_LINE_TOLERANCE' => 8,
	// 'FI_POLYGON_TOLERANCE' => 4
];

// Map response limit
$config['wms_max_image_size'] = 10 * 1024 * 1024; // 10MB

// Location report maps: QGIS Server GetPrint (PNG) of a project print layout, one full DOCX page per map.
// The PNG size is the layout page size at wms_print_dpi; the DOCX page gets the same physical size.
$config['wms_location_report_max_maps'] = 4;
$config['wms_print_dpi'] = 150;
$config['wms_print_timeout'] = 45; // seconds per GetPrint request, maps are requested in parallel
$config['wms_print_max_image_size'] = 20 * 1024 * 1024; // 20MB per PNG
$config['wms_print_max_pixels'] = 40000000; // A3 at 300 DPI is about 17.4 million pixels
// SLD symbol for the clicked location (map0:HIGHLIGHT_GEOM); empty string disables the marker.
$config['wms_print_highlight_symbol'] = '<StyledLayerDescriptor><UserStyle><FeatureTypeStyle><Rule>'
	. '<PointSymbolizer><Graphic><Mark><WellKnownName>circle</WellKnownName>'
	. '<Fill><SvgParameter name="fill">#000000</SvgParameter><SvgParameter name="fill-opacity">0</SvgParameter></Fill>'
	. '<Stroke><SvgParameter name="stroke">#00FFFF</SvgParameter><SvgParameter name="stroke-opacity">1</SvgParameter><SvgParameter name="stroke-width">4</SvgParameter></Stroke>'
	. '</Mark><Size>12</Size></Graphic></PointSymbolizer></Rule></FeatureTypeStyle></UserStyle></StyledLayerDescriptor>';

// The server derives the map context and image aspect ratio from the feature BBOX returned by QGIS.
$config['wms_feature_report_width'] = 800;
$config['wms_feature_report_dpi'] = 200;
$config['wms_feature_report_bbox_margin'] = 0.20; // 20% on each side
$config['wms_feature_report_min_extent_meters'] = 50; // Minimum ground width and height for small features

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
