<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Wms_report_service
{
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->config->load('phpword');
        $this->CI->config->load('wms');
        $this->CI->load->model('qgisproject_model');
        $this->CI->load->model('project_model');
        $this->CI->load->library('wms_client');
        $this->CI->load->library('feature_info_formatter');
    }

    public function collectFeatureReport(array $input, $username)
    {
        $request = $this->validateRequest($input);
        $project = $this->resolveProject($request['project_name']);
        $layer = $this->resolveLayer($project['properties'], $request['layer_id']);
        $request['template'] = $this->selectFeatureReportTemplate($project['name'], $layer['id'], $override);
        $relations = [];
        if ($override) {
            $this->CI->load->library('phpword_lib');
            $requirements = $this->CI->phpword_lib->getFeatureReportRelations($request['template']);
            if (!empty($requirements)) {
                $relations = $this->prepareFeatureRelations($project['properties'], $layer, $requirements);
            }
        }
        $featureParams = [
            'URL' => $project['url'],
            'VERSION' => $project['version'],
            'CRS' => $project['properties']->crs,
            'LAYERS' => $layer['qgis_layer'],
            'QUERY_LAYERS' => $layer['qgis_layer'],
            'WIDTH' => 1,
            'HEIGHT' => 1,
            'FEATURE_COUNT' => 1,
            'INFO_FORMAT' => 'text/xml',
            'WITH_GEOMETRY' => 'false',
            'FILTER' => $this->buildFeatureFilter($layer, $request['feature_id'], $request['feature_field'])
        ];

        $this->releaseSessionLock();
        $featureData = $this->CI->wms_client->getFeatureInfo($featureParams);
        if (empty($featureData['bbox']) || !is_array($featureData['bbox'])) {
            log_message('error', 'QGIS GetFeatureInfo response missing bbox: ' . json_encode($featureData));
            throw new OutOfBoundsException('QGIS did not return a feature bounding box');
        }
        $features = $this->normalizeFeatures($featureData['features']);
        $selected = array_values(array_filter($features, function ($feature) use ($request) {
            return $feature['id'] === $request['feature_id'];
        }));
        if (empty($selected)) {
            throw new OutOfBoundsException('Selected feature was not returned by WMS');
        }
        if ($selected[0]['geometry_type'] === '') {
            $selected[0]['geometry_type'] = $layer['geometry_type'];
        }
        $mapBbox = isset($selected[0]['bbox']) && is_array($selected[0]['bbox'])
            ? $selected[0]['bbox']
            : $featureData['bbox'];
        $selected[0]['fields'] = $this->formatFeatureFields($selected[0], $project['name'], $request['client']);

        $data = [
            'title' => $project['display_name'],
            'description' => $project['description'],
            'project_name' => $project['name'],
            'layer_name' => $layer['name'],
            'bbox' => $this->formatBbox($mapBbox),
            'scope' => 'selected',
            'feature_count' => 1,
            'generated_date' => date('Y-m-d H:i:s'),
            'generated_by' => $username,
            'features' => [$selected[0]]
        ];

        if (!empty($relations)) {
            $data['relations'] = $this->collectRelatedRecords($project, $relations, $selected[0]['id'], $request['client']);
        }

        if ($request['include_map']) {
            $mapContext = $this->buildMapContext($mapBbox);
            $externalLayers = $this->resolveExternalLayers($request['external_layers']);
            $data['map_image'] = $this->CI->wms_client->getMap([
                'URL' => $project['url'],
                'VERSION' => $project['version'],
                'CRS' => $project['properties']->crs,
                'LAYERS' => implode(',', array_merge($externalLayers['layers'], [$layer['qgis_layer']])),
                'BBOX' => $mapContext['bbox'],
                'WIDTH' => $mapContext['width'],
                'HEIGHT' => $mapContext['height'],
                'DPI' => $this->CI->config->item('wms_feature_report_dpi'),
                'FORMAT' => 'image/png',
                'TRANSPARENT' => 'true',
                'SELECTION' => $this->buildFeatureSelection($layer, $request['feature_id']),
                'EXTERNAL_WMS_PARAMS' => $externalLayers['params']
            ]);
        }

        return [
            'data' => $data,
            'template' => $request['template'],
            'temporary_files' => isset($data['map_image']) ? [$data['map_image']] : []
        ];
    }

    /**
     * Report for all features intersecting one map location in the requested layers.
     */
    public function collectLocationReport(array $input, $username)
    {
        $prepared = $this->prepareLocationReport($input, $username);
        $this->releaseSessionLock();
        return $this->executeLocationReport($prepared);
    }

    /**
     * Validate the request and resolve project/layers. Needs the user session.
     * The returned array is plain data, so it can be stored for a deferred job.
     */
    public function prepareLocationReport(array $input, $username)
    {
        $request = $this->validateLocationRequest($input);
        $project = $this->resolveProject($request['project_name']);
        $crs = (string)$project['properties']->crs;
        if ($request['crs'] !== '' && strcasecmp($request['crs'], $crs) !== 0) {
            throw new InvalidArgumentException('crs must match the project CRS ' . $crs);
        }

        $layers = $this->resolveLocationLayers($project['properties'], $request['layers'], $skipped);
        if (empty($layers)) {
            throw new InvalidArgumentException('None of the requested layers can be queried');
        }
        $maxLayers = max(1, (int)($this->CI->config->item('wms_report_max_layers') ?: 20));
        if (count($layers) > $maxLayers) {
            throw new InvalidArgumentException('At most ' . $maxLayers . ' layers can be requested, groups expand to ' . count($layers));
        }
        $qgisLayers = implode(',', array_map(function ($layer) {
            return $layer['qgis_layer'];
        }, $layers));

        $featureInfoParams = array_merge($this->locationToleranceParams(), [
            'URL' => $project['url'],
            'VERSION' => $project['version'],
            'CRS' => $crs,
            'LAYERS' => $qgisLayers,
            'QUERY_LAYERS' => $qgisLayers,
            'STYLES' => str_repeat(',', count($layers) - 1),
            'BBOX' => implode(',', $request['bbox']),
            'WIDTH' => $request['width'],
            'HEIGHT' => $request['height'],
            'FEATURE_COUNT' => max(1, (int)($this->CI->config->item('wms_location_report_feature_count') ?: 10)),
            'INFO_FORMAT' => 'text/xml',
            'WITH_GEOMETRY' => 'false',
            'WITH_MAPTIP' => 'false',
            'TIMEOUT' => $this->CI->config->item('wms_location_report_timeout'),
            'MAX_RESPONSE_SIZE' => $this->CI->config->item('wms_location_report_max_response_size')
        ]);
        if (version_compare($project['version'], '1.3.0', '>=')) {
            $featureInfoParams['I'] = $request['i'];
            $featureInfoParams['J'] = $request['j'];
        } else {
            $featureInfoParams['X'] = $request['i'];
            $featureInfoParams['Y'] = $request['j'];
        }

        list($minX, $minY, $maxX, $maxY) = $request['bbox'];
        $location = [
            'x' => $minX + (($request['i'] + 0.5) / $request['width']) * ($maxX - $minX),
            'y' => $maxY - (($request['j'] + 0.5) / $request['height']) * ($maxY - $minY)
        ];

        return [
            'project' => [
                'name' => $project['name'],
                'display_name' => $project['display_name'],
                'description' => $project['description']
            ],
            'layers' => $layers,
            'skipped_layers' => $skipped,
            'feature_info_params' => $featureInfoParams,
            'maps' => $this->prepareMaps($project, $request['maps'], $request['bbox'], $location),
            'crs' => $crs,
            'bbox' => $request['bbox'],
            'location' => $location,
            'client' => $request['client'],
            'address' => $request['address'],
            'template' => $request['template'],
            'username' => (string)$username
        ];
    }

    /**
     * Run the WMS request and build the report data. Uses no session state.
     */
    public function executeLocationReport(array $prepared)
    {
        $featureData = $this->CI->wms_client->getFeatureInfo($prepared['feature_info_params']);
        $features = $this->normalizeFeatures(isset($featureData['features']) ? $featureData['features'] : []);

        $layerLookup = [];
        $reportLayers = [];
        foreach ($prepared['layers'] as $index => $layer) {
            foreach ([$layer['qgis_layer'], $layer['id'], $layer['name']] as $alias) {
                if ($alias !== '' && !isset($layerLookup[$alias])) {
                    $layerLookup[$alias] = $index;
                }
            }
            $reportLayers[$index] = [
                'id' => $layer['id'],
                'name' => $layer['name'],
                'group' => isset($layer['group_path']) ? $layer['group_path'] : '',
                'requested_group' => isset($layer['requested_group']) ? $layer['requested_group'] : '',
                'geometry_type' => $layer['geometry_type'],
                'feature_count' => 0,
                'features' => []
            ];
        }

        $totalCount = 0;
        $unmatched = [];
        foreach ($features as $feature) {
            if (!isset($layerLookup[$feature['layer']])) {
                $unmatched[$feature['layer']] = true;
                continue;
            }
            $index = $layerLookup[$feature['layer']];
            if ($feature['geometry_type'] === '') {
                $feature['geometry_type'] = $reportLayers[$index]['geometry_type'];
            }
            $feature['layer'] = $reportLayers[$index]['name'];
            $feature['fields'] = $this->formatFeatureFields($feature, $prepared['project']['name'], $prepared['client']);
            $reportLayers[$index]['features'][] = $feature;
            $reportLayers[$index]['feature_count']++;
            $totalCount++;
        }
        if (!empty($unmatched)) {
            log_message('error', 'Location report: GetFeatureInfo returned unrequested layers: ' . implode(', ', array_keys($unmatched)));
        }

        list($maps, $skippedMaps, $temporaryFiles) = $this->renderMaps(isset($prepared['maps']) ? $prepared['maps'] : []);

        $data = [
            'title' => $prepared['project']['display_name'],
            'description' => $prepared['project']['description'],
            'project_name' => $prepared['project']['name'],
            'crs' => $prepared['crs'],
            'bbox' => $this->formatBbox($prepared['bbox']),
            'location_x' => $this->formatCoordinate($prepared['location']['x']),
            'location_y' => $this->formatCoordinate($prepared['location']['y']),
            'location_address' => isset($prepared['address']) ? $prepared['address'] : '',
            'scope' => 'location',
            'layer_count' => count($reportLayers),
            'feature_count' => $totalCount,
            'generated_date' => date('Y-m-d H:i:s'),
            'generated_by' => $prepared['username'],
            'layers' => array_values($reportLayers),
            'skipped_layers' => isset($prepared['skipped_layers']) ? $prepared['skipped_layers'] : [],
            'map_count' => count($maps),
            'maps' => $maps,
            'skipped_maps' => $skippedMaps
        ];

        return [
            'data' => $data,
            'template' => $prepared['template'],
            'temporary_files' => $temporaryFiles
        ];
    }

    /**
     * Validate layouts and layers of the requested maps and build their GetPrint parameters.
     */
    protected function prepareMaps(array $project, array $maps, array $bbox, array $location)
    {
        if (empty($maps)) {
            return [];
        }
        $properties = $project['properties'];
        $layouts = isset($properties->layouts) && is_array($properties->layouts) ? $properties->layouts : [];
        $dpi = max(1, (int)($this->CI->config->item('wms_print_dpi') ?: 150));
        $symbol = (string)$this->CI->config->item('wms_print_highlight_symbol');

        $prepared = [];
        foreach ($maps as $map) {
            if (!in_array($map['template'], $layouts, true)) {
                throw new DomainException('Print layout is not available in the current project: ' . $map['template']);
            }
            $layers = $this->resolveMapLayers($properties, $map['layers']);
            $external = $this->resolveExternalLayers($map['external_layers']);
            $params = [
                'URL' => $project['url'],
                'VERSION' => $project['version'],
                'CRS' => (string)$properties->crs,
                'TEMPLATE' => $map['template'],
                'EXTENT' => implode(',', $bbox),
                // WMS order: the first layer is drawn at the bottom, external backgrounds first.
                'LAYERS' => implode(',', array_merge($external['layers'], $layers)),
                'DPI' => $dpi,
                'EXTERNAL_WMS_PARAMS' => $external['params'],
                'TIMEOUT' => $this->CI->config->item('wms_print_timeout')
            ];
            if (!empty($properties->print_metadata_layers)) {
                $params['LAYERS'] = implode(',', array_unique(array_merge(
                    $external['layers'], $layers, $properties->print_metadata_layers
                )));
                $params['FILTER'] = implode(';', array_map(function ($layer) {
                    return $layer . ':"user_name" = \'\' AND "user_name" != \'\'';
                }, $properties->print_metadata_layers));
            }
            if ($symbol !== '') {
                $params['HIGHLIGHT_GEOM'] = 'POINT(' . $location['x'] . ' ' . $location['y'] . ')';
                $params['HIGHLIGHT_SYMBOL'] = $symbol;
            }
            $prepared[] = [
                'title' => $map['title'],
                'template' => $map['template'],
                'layers' => array_merge($map['external_layers'], $map['layers']),
                'dpi' => $dpi,
                'print_params' => $params
            ];
        }
        return $prepared;
    }

    /**
     * Map layers may be any visible project layer (also rasters and non-queryable layers)
     * or a layer tree group, which is passed to QGIS by its WMS name. Returns WMS names in request order.
     */
    protected function resolveMapLayers($properties, array $requested)
    {
        $groups = !empty($properties->skip_group_names) || !isset($properties->groups) ? [] : $properties->groups;
        $names = [];
        foreach ($requested as $name) {
            if (isset($properties->layers[$name])) {
                $names[] = $this->resolveLayer($properties, $name)['qgis_layer'];
                continue;
            }
            $match = null;
            foreach ($groups as $group) {
                if ($group->name === $name || ($group->short_name !== '' && $group->short_name === $name)) {
                    $match = $group;
                    break;
                }
            }
            if ($match === null) {
                throw new DomainException('Map layer or group is not available in the current project: ' . $name);
            }
            $names[] = $match->short_name !== '' ? $match->short_name : $match->name;
        }
        return array_values(array_unique($names));
    }

    /**
     * Run the GetPrint requests in parallel. A failed map is reported in skipped maps,
     * it does not fail the report.
     */
    protected function renderMaps(array $maps)
    {
        if (empty($maps)) {
            return [[], [], []];
        }
        $images = $this->CI->wms_client->getPrints(array_map(function ($map) {
            return $map['print_params'];
        }, $maps));

        // Word pages can be at most 22 inches on each side.
        $maxPageInches = 22;
        $rendered = [];
        $skipped = [];
        $temporaryFiles = [];
        foreach ($maps as $index => $map) {
            $image = isset($images[$index]) ? $images[$index] : ['error' => 'no response'];
            if (!empty($image['path'])) {
                $temporaryFiles[] = $image['path'];
            }
            $name = $map['title'] !== '' ? $map['title'] : $map['template'];
            if (empty($image['path'])) {
                $skipped[] = ['name' => $name, 'reason' => 'map could not be rendered'];
                continue;
            }
            if ($image['width'] / $map['dpi'] > $maxPageInches || $image['height'] / $map['dpi'] > $maxPageInches) {
                log_message('error', 'Location report: print layout ' . $map['template'] . ' is larger than a Word page');
                $skipped[] = ['name' => $name, 'reason' => 'layout page is too large'];
                continue;
            }
            $rendered[] = [
                'title' => $map['title'],
                'template' => $map['template'],
                'layers' => $map['layers'],
                'image_path' => $image['path'],
                'image_width' => $image['width'],
                'image_height' => $image['height'],
                'dpi' => $map['dpi']
            ];
        }
        return [$rendered, $skipped, $temporaryFiles];
    }

    protected function validateLocationRequest(array $input)
    {
        $projectName = isset($input['project']) && is_scalar($input['project']) ? trim((string)$input['project']) : '';
        if ($projectName === '') {
            throw new InvalidArgumentException('project is required');
        }
        $client = isset($input['client']) && is_scalar($input['client']) ? trim((string)$input['client']) : '';
        if ($client !== '' && !preg_match('/^[A-Za-z0-9_.-]{1,64}$/', $client)) {
            throw new InvalidArgumentException('client must be a simple name');
        }

        $layers = $this->readNameList($this->readInputValue($input, ['QUERY_LAYERS', 'query_layers']), 'QUERY_LAYERS');
        if (empty($layers)) {
            throw new InvalidArgumentException('QUERY_LAYERS is required');
        }
        $maxLayers = max(1, (int)($this->CI->config->item('wms_report_max_layers') ?: 20));
        if (count($layers) > $maxLayers) {
            throw new InvalidArgumentException('At most ' . $maxLayers . ' layers can be requested');
        }

        $bbox = $this->readInputValue($input, ['bbox', 'BBOX']);
        $bbox = is_scalar($bbox) ? explode(',', (string)$bbox) : $bbox;
        if (!is_array($bbox) || count($bbox) !== 4) {
            throw new InvalidArgumentException('bbox must contain minx,miny,maxx,maxy');
        }
        $bbox = array_map(function ($value) {
            $value = is_scalar($value) ? trim((string)$value) : '';
            if (!is_numeric($value) || !is_finite((float)$value)) {
                throw new InvalidArgumentException('bbox must contain numbers');
            }
            return (float)$value;
        }, array_values($bbox));
        if ($bbox[0] >= $bbox[2] || $bbox[1] >= $bbox[3]) {
            throw new InvalidArgumentException('bbox is empty');
        }

        $maxSize = max(1, (int)($this->CI->config->item('wms_location_report_max_size') ?: 4096));
        $width = $this->readIntegerInput($input, ['width', 'WIDTH'], 1, $maxSize, 'width');
        $height = $this->readIntegerInput($input, ['height', 'HEIGHT'], 1, $maxSize, 'height');
        $i = $this->readIntegerInput($input, ['i', 'I'], 0, $width - 1, 'i');
        $j = $this->readIntegerInput($input, ['j', 'J'], 0, $height - 1, 'j');

        $crs = $this->readInputValue($input, ['crs', 'CRS', 'SRS']);
        $crs = is_scalar($crs) ? trim((string)$crs) : '';

        return [
            'project_name' => $projectName,
            'client' => $client,
            'address' => $this->readSingleLineText($input, ['address', 'location_address'], 255, 'address'),
            'layers' => $layers,
            'bbox' => $bbox,
            'width' => $width,
            'height' => $height,
            'i' => $i,
            'j' => $j,
            'crs' => $crs,
            'maps' => $this->readMapRequests($input),
            'template' => $this->validateTemplate($this->CI->config->item('phpword_location_report_template_name'))
        ];
    }

    /**
     * Comma list or array of names, trimmed, without empty values and duplicates.
     */
    protected function readNameList($value, $label)
    {
        if ($value === null) {
            return [];
        }
        if (is_scalar($value)) {
            $value = explode(',', (string)$value);
        }
        if (!is_array($value)) {
            throw new InvalidArgumentException($label . ' must be a comma list or an array');
        }
        return array_values(array_unique(array_filter(array_map(function ($name) {
            return is_scalar($name) ? trim((string)$name) : '';
        }, $value), function ($name) {
            return $name !== '';
        })));
    }

    /**
     * Optional maps[n][template|layers|external_layers|title] for GetPrint pages, in index order.
     */
    protected function readMapRequests(array $input)
    {
        if (!isset($input['maps']) || $input['maps'] === '') {
            return [];
        }
        $maps = $input['maps'];
        if (!is_array($maps)) {
            throw new InvalidArgumentException('maps must be an array');
        }
        $maxMaps = max(0, (int)$this->CI->config->item('wms_location_report_max_maps'));
        if (count($maps) > $maxMaps) {
            throw new InvalidArgumentException('At most ' . $maxMaps . ' maps can be requested');
        }
        $maxLayers = max(1, (int)($this->CI->config->item('wms_report_max_layers') ?: 20));
        ksort($maps);

        $result = [];
        foreach (array_values($maps) as $index => $map) {
            $label = 'maps[' . $index . ']';
            if (!is_array($map)) {
                throw new InvalidArgumentException($label . ' must contain template and layers');
            }
            $template = $this->readSingleLineText($map, ['template'], 255, $label . '[template]');
            if ($template === '') {
                throw new InvalidArgumentException($label . '[template] is required');
            }
            $layers = $this->readNameList(isset($map['layers']) ? $map['layers'] : null, $label . '[layers]');
            $externalLayers = $this->readNameList(isset($map['external_layers']) ? $map['external_layers'] : null, $label . '[external_layers]');
            if (empty($layers) && empty($externalLayers)) {
                throw new InvalidArgumentException($label . '[layers] is required');
            }
            if (count($layers) + count($externalLayers) > $maxLayers) {
                throw new InvalidArgumentException('At most ' . $maxLayers . ' layers can be requested in ' . $label);
            }
            $result[] = [
                'template' => $template,
                'layers' => $layers,
                'external_layers' => $externalLayers,
                'title' => $this->readSingleLineText($map, ['title'], 255, $label . '[title]')
            ];
        }
        return $result;
    }

    protected function readInputValue(array $input, array $names)
    {
        foreach ($names as $name) {
            if (isset($input[$name])) {
                return $input[$name];
            }
        }
        return null;
    }

    /**
     * Optional client-supplied text (e.g. an address) reduced to one trimmed line.
     */
    protected function readSingleLineText(array $input, array $names, $maxLength, $label)
    {
        $value = $this->readInputValue($input, $names);
        if ($value === null) {
            return '';
        }
        if (!is_scalar($value)) {
            throw new InvalidArgumentException($label . ' must be text');
        }
        $value = (string)$value;
        if (!mb_check_encoding($value, 'UTF-8')) {
            throw new InvalidArgumentException($label . ' must be valid UTF-8');
        }
        $value = trim(preg_replace('/[\p{Cc}\p{Zl}\p{Zp}\s]+/u', ' ', $value));
        if (mb_strlen($value, 'UTF-8') > $maxLength) {
            throw new InvalidArgumentException($label . ' must be at most ' . $maxLength . ' characters');
        }
        return $value;
    }

    protected function readIntegerInput(array $input, array $names, $min, $max, $label)
    {
        $value = $this->readInputValue($input, $names);
        $value = is_scalar($value) ? trim((string)$value) : '';
        if (!preg_match('/^\d+$/', $value) || (int)$value < $min || (int)$value > $max) {
            throw new InvalidArgumentException($label . ' must be an integer between ' . $min . ' and ' . $max);
        }
        return (int)$value;
    }

    protected function locationToleranceParams()
    {
        $tolerances = $this->CI->config->item('wms_location_report_tolerances');
        $params = [];
        if (is_array($tolerances)) {
            foreach (['FI_POINT_TOLERANCE', 'FI_LINE_TOLERANCE', 'FI_POLYGON_TOLERANCE'] as $name) {
                if (isset($tolerances[$name]) && is_numeric($tolerances[$name])) {
                    $params[$name] = (int)$tolerances[$name];
                }
            }
        }
        return $params;
    }

    protected function formatCoordinate($value)
    {
        return rtrim(rtrim(number_format((float)$value, 2, '.', ''), '0'), '.');
    }

    protected function validateRequest(array $input)
    {
        $projectName = isset($input['project']) && is_scalar($input['project'])
            ? trim((string)$input['project'])
            : (isset($input['projectData']['name']) && is_scalar($input['projectData']['name'])
                ? trim((string)$input['projectData']['name'])
                : '');
        $layerId = isset($input['layer_id']) ? trim((string)$input['layer_id']) : '';
        $featureId = isset($input['feature_id']) ? trim((string)$input['feature_id']) : '';
        $featureField = isset($input['feature_field']) ? trim((string)$input['feature_field']) : '';
        $client = isset($input['client']) && is_scalar($input['client']) ? trim((string)$input['client']) : '';
        if ($projectName === '') {
            throw new InvalidArgumentException('project is required');
        }
        if ($layerId === '') {
            throw new InvalidArgumentException('layer_id is required');
        }
        if ($featureId === '') {
            throw new InvalidArgumentException('feature_id is required');
        }
        if ($featureField !== '' && !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $featureField)) {
            throw new InvalidArgumentException('feature_field must be a valid field name');
        }
        if ($client !== '' && !preg_match('/^[A-Za-z0-9_.-]{1,64}$/', $client)) {
            throw new InvalidArgumentException('client must be a simple name');
        }

        $externalLayers = isset($input['external_layers']) ? $input['external_layers'] : [];
        if (!is_array($externalLayers)) {
            throw new InvalidArgumentException('external_layers must be an array');
        }
        $externalLayers = array_values(array_unique(array_filter(array_map(function ($layerName) {
            return is_scalar($layerName) ? trim((string)$layerName) : '';
        }, $externalLayers), function ($layerName) {
            return $layerName !== '';
        })));

        return [
            'project_name' => $projectName,
            'layer_id' => $layerId,
            'feature_id' => $featureId,
            'feature_field' => $featureField,
            'client' => $client,
            'include_map' => filter_var(isset($input['include_map']) ? $input['include_map'] : false, FILTER_VALIDATE_BOOLEAN),
            'external_layers' => $externalLayers
        ];
    }

    protected function selectFeatureReportTemplate($projectName, $layerId, &$override)
    {
        $templates = $this->CI->config->item('phpword_feature_report_templates') ?? [];
        if (!is_array($templates) || (isset($templates[$projectName]) && !is_array($templates[$projectName]))) {
            throw new RuntimeException('Invalid feature report template configuration');
        }
        $override = isset($templates[$projectName]) && array_key_exists($layerId, $templates[$projectName]);
        $name = $override ? $templates[$projectName][$layerId] : $this->CI->config->item('phpword_default_template_name');
        if (!is_string($name)) {
            throw new RuntimeException('Invalid feature report template name');
        }
        return $this->validateTemplate($name);
    }

    protected function prepareFeatureRelations($properties, array $parent, array $requirements)
    {
        if (count($requirements) > $this->relationLimit('max_relations', 10)) {
            throw new RuntimeException('Feature report requests too many relations');
        }
        $config = $this->CI->qgisproject_model->read_project_json();
        $configured = $config['relations'] ?? [];
        if (!is_array($configured) || (isset($configured['hideJoinField']) && !is_bool($configured['hideJoinField']))) {
            throw new RuntimeException('Invalid project relations configuration');
        }
        $definitions = $configured[$parent['name']] ?? [];
        if (!is_array($definitions) || array_values($definitions) !== $definitions) {
            throw new RuntimeException('Layer relations must be an array of definitions');
        }
        $byName = [];
        foreach ($definitions as $definition) {
            if (!is_array($definition) || !isset($definition['relate_layer']) || !is_string($definition['relate_layer'])) {
                throw new RuntimeException('Invalid related layer definition');
            }
            $name = $definition['relate_layer'];
            if (isset($byName[$name])) {
                throw new RuntimeException('Duplicate related layer definition: ' . $name);
            }
            $byName[$name] = $definition;
        }
        $relations = [];
        $seen = [];
        foreach ($requirements as $name => $requirement) {
            if (!isset($byName[$name])) {
                throw new RuntimeException('Template relation is not configured for this layer: ' . $name);
            }
            $definition = $byName[$name];
            foreach (['join_field', 'display_field'] as $option) {
                if ($option === 'display_field' && !isset($definition[$option])) {
                    continue;
                }
                if (!isset($definition[$option]) || !is_string($definition[$option])
                    || !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $definition[$option])) {
                    throw new RuntimeException('Invalid related layer ' . $option);
                }
            }
            $layerId = $name;
            if (!isset($properties->layers[$layerId])) {
                $matches = [];
                foreach ($properties->layers as $id => $candidate) {
                    if ($candidate->layername === $name) {
                        $matches[] = $id;
                    }
                }
                if (count($matches) !== 1) {
                    throw new DomainException('Related layer is unavailable or ambiguous: ' . $name);
                }
                $layerId = $matches[0];
            }
            $layer = $this->resolveLayer($properties, $layerId);
            if (!$layer['identifiable']) {
                throw new DomainException('Related layer is not queryable: ' . $name);
            }
            if (isset($seen[$layer['id']])) {
                throw new RuntimeException('Duplicate related table in report');
            }
            $seen[$layer['id']] = true;
            $relations[$name] = [
                'layer' => $layer,
                'join_field' => $definition['join_field'],
                'fields' => $requirement['fields'],
                'hide_join_field' => !empty($configured['hideJoinField'])
            ];
        }
        return $relations;
    }

    protected function relationLimit($suffix, $default)
    {
        return max(1, (int)($this->CI->config->item('wms_feature_report_' . $suffix) ?: $default));
    }

    protected function collectRelatedRecords(array $project, array $relations, $parentId, $client)
    {
        $deadline = microtime(true) + $this->relationLimit('relation_time_limit', 60);
        $perRelation = $this->relationLimit('relation_max_records', 100);
        $maxTotal = $this->relationLimit('relation_max_total_records', 500);
        $total = 0;
        $result = [];
        foreach ($relations as $name => $relation) {
            if (microtime(true) >= $deadline) {
                throw new RuntimeException('Related record query time limit exceeded');
            }
            $response = $this->CI->wms_client->getFeatureInfo([
                'URL' => $project['url'],
                'VERSION' => $project['version'],
                'CRS' => $project['properties']->crs,
                'LAYERS' => $relation['layer']['qgis_layer'],
                'QUERY_LAYERS' => $relation['layer']['qgis_layer'],
                'WIDTH' => 1,
                'HEIGHT' => 1,
                'FEATURE_COUNT' => min($perRelation, $maxTotal - $total) + 1,
                'INFO_FORMAT' => 'text/xml',
                'WITH_GEOMETRY' => 'false',
                'WITH_MAPTIP' => 'false',
                'FILTER' => $this->buildFeatureFilter($relation['layer'], $parentId, $relation['join_field']),
                'TIMEOUT' => $this->relationLimit('relation_timeout', 10),
                'DEADLINE' => $deadline,
                'MAX_RESPONSE_SIZE' => $this->relationLimit('relation_max_response_size', 2 * 1024 * 1024),
                'REDACT_LOGS' => true,
                'ALLOW_EMPTY_BBOX' => true
            ]);
            $features = $this->normalizeFeatures($response['features']);
            $total += count($features);
            if (count($features) > $perRelation || $total > $maxTotal) {
                throw new RuntimeException('Related records exceed the report limit');
            }
            $records = [];
            $available = [];
            foreach ($features as $feature) {
                $properties = $feature['properties'];
                $join = $relation['join_field'];
                if (isset($properties[$join]) && (string)$properties[$join] !== (string)$parentId) {
                    throw new RuntimeException('Related record did not match the selected feature');
                }
                $available += array_fill_keys(array_keys($properties), true);
                $fields = $this->formatFeatureFields($feature, $project['name'], $client);
                $record = [];
                foreach ($fields as $field) {
                    if (!empty($field['hidden']) || ($relation['hide_join_field'] && $field['name'] === $join)) {
                        continue;
                    }
                    $record[$field['name']] = $this->CI->feature_info_formatter->toText($field);
                }
                $records[] = $record;
            }
            foreach ($relation['fields'] as $field) {
                if (!empty($features) && !isset($available[$field])
                    && !($relation['hide_join_field'] && $field === $relation['join_field'])) {
                    throw new RuntimeException('Unknown relation template field: ' . $name . ':' . $field);
                }
            }
            $result[$name] = ['records' => $records];
        }
        if (microtime(true) > $deadline) {
            throw new RuntimeException('Related record query time limit exceeded');
        }
        return $result;
    }

    protected function resolveProject($projectName)
    {
        $currentName = $this->CI->session->userdata('project');
        if (empty($currentName) || (string)$currentName !== $projectName) {
            throw new DomainException('Project is not available in the current session');
        }

        $projects = $this->CI->project_model->get_project_by_name($projectName);
        $project = !empty($projects) ? $projects[0] : null;
        if (empty($project)) {
            throw new DomainException('Project is not available');
        }

        $check = $this->CI->qgisproject_model->check_qgs_file($project->id);
        if (empty($check['valid'])) {
            throw new UnexpectedValueException('Current map project could not be read');
        }
        $this->CI->qgisproject_model->qgs_file = $check['name'];
        $this->CI->qgisproject_model->name = $project->name;
        $this->CI->qgisproject_model->qgs_layers = [];
        $this->CI->qgisproject_model->read_qgs_file();
        $properties = $this->CI->qgisproject_model->get_project_properties();

        return [
            'name' => $project->name,
            'display_name' => $project->display_name !== '' ? $project->display_name : $project->name,
            'description' => (string)$project->description,
            'url' => str_replace('gisportal/', '', base_url('/proxy/' . rawurlencode($project->name))),
            'version' => $this->CI->config->item('wms_default_version') ?: '1.3.0',
            'properties' => $properties
        ];
    }

    protected function resolveLayer($properties, $layerId)
    {
        if (!isset($properties->layers[$layerId]) || !empty($properties->layers[$layerId]->restricted)) {
            throw new DomainException('Layer is not available in the current project');
        }
        $layer = $properties->layers[$layerId];
        return [
            'id' => (string)$layer->id,
            'qgis_layer' => $properties->use_ids ? $layer->id : $layer->layername,
            'name' => $layer->layername,
            'key' => isset($layer->key) ? (string)$layer->key : '',
            'geometry_type' => isset($layer->geom_type) ? (string)$layer->geom_type : '',
            'identifiable' => !isset($layer->identifiable) || $layer->identifiable,
            'group_path' => isset($layer->group_path) && is_array($layer->group_path) ? implode(' / ', $layer->group_path) : '',
            'requested_group' => ''
        ];
    }

    /**
     * Resolve requested layer ids and layer tree group names (or group WMS short names)
     * to the queryable layers in tree order. Groups expand to all descendant layers.
     * Layers that cannot be queried are reported in $skipped instead of failing the request.
     */
    protected function resolveLocationLayers($properties, array $requested, &$skipped)
    {
        $skipped = [];
        $layers = [];
        $seen = [];
        $groups = !empty($properties->skip_group_names) || !isset($properties->groups) ? [] : $properties->groups;

        foreach ($requested as $name) {
            $candidates = [];
            $requestedGroup = '';
            if (isset($properties->layers[$name])) {
                $candidates[] = $this->resolveLayer($properties, $name);
            } else {
                $matched = false;
                foreach ($groups as $group) {
                    if ($group->name !== $name && ($group->short_name === '' || $group->short_name !== $name)) {
                        continue;
                    }
                    $matched = true;
                    $requestedGroup = $group->name;
                    foreach ($group->layer_ids as $layerId) {
                        $candidates[] = $this->resolveLayer($properties, $layerId);
                    }
                }
                if (!$matched) {
                    throw new DomainException('Layer or group is not available in the current project: ' . $name);
                }
                if (empty($candidates)) {
                    $skipped[] = ['name' => $requestedGroup, 'reason' => 'empty group'];
                }
            }

            foreach ($candidates as $layer) {
                if (isset($seen[$layer['id']])) {
                    continue;
                }
                $seen[$layer['id']] = true;
                $layer['requested_group'] = $requestedGroup;
                if (!$layer['identifiable']) {
                    $skipped[] = ['name' => $layer['name'], 'reason' => 'not queryable'];
                } elseif ($layer['geometry_type'] === '' || strcasecmp($layer['geometry_type'], 'No geometry') === 0) {
                    $skipped[] = ['name' => $layer['name'], 'reason' => 'no geometry'];
                } else {
                    $layers[] = $layer;
                }
            }
        }

        return $layers;
    }

    protected function buildFeatureFilter(array $layer, $featureId, $featureField)
    {
        $field = $featureField !== '' ? $featureField : $layer['key'];
        if ($field === '' || !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $field)) {
            throw new InvalidArgumentException('No valid feature key is configured for this layer');
        }
        $value = str_replace("'", "''", (string)$featureId);
        return $layer['qgis_layer'] . ':"' . $field . '" = \'' . $value . '\'';
    }

    protected function buildFeatureSelection(array $layer, $featureId)
    {
        if (strpos((string)$featureId, ',') !== false || strpos((string)$featureId, ':') !== false) {
            throw new InvalidArgumentException('feature_id cannot contain a comma or colon when including a map');
        }

        return $layer['qgis_layer'] . ':' . $featureId;
    }

    protected function resolveExternalLayers(array $requestedLayers)
    {
        $configuredLayers = $this->CI->config->item('wms_feature_report_external_layers');
        $configuredLayers = is_array($configuredLayers) ? $configuredLayers : [];
        $layers = [];
        $params = [];

        foreach ($requestedLayers as $layerName) {
            if (!isset($configuredLayers[$layerName]) || !is_array($configuredLayers[$layerName])) {
                throw new InvalidArgumentException('External WMS layer is not available: ' . $layerName);
            }
            $layerConfig = $configuredLayers[$layerName];
            foreach (['url', 'format', 'crs', 'layers', 'styles'] as $option) {
                if (!array_key_exists($option, $layerConfig) || !is_scalar($layerConfig[$option])) {
                    throw new UnexpectedValueException('External WMS layer configuration is invalid: ' . $layerName);
                }
                $params[$layerName . ':' . $option] = (string)$layerConfig[$option];
            }
            $layers[] = 'EXTERNAL_WMS:' . $layerName;
        }

        return ['layers' => $layers, 'params' => $params];
    }

    protected function buildMapContext(array $bbox)
    {
        if (count($bbox) < 4 || !is_numeric($bbox[0]) || !is_numeric($bbox[1]) || !is_numeric($bbox[2]) || !is_numeric($bbox[3])) {
            throw new OutOfBoundsException('QGIS did not return a valid feature bounding box');
        }
        $minX = (float)$bbox[0];
        $minY = (float)$bbox[1];
        $maxX = (float)$bbox[2];
        $maxY = (float)$bbox[3];
        $margin = (float)$this->CI->config->item('wms_feature_report_bbox_margin');
        $marginX = max(abs($maxX - $minX) * $margin, 1.0);
        $marginY = max(abs($maxY - $minY) * $margin, 1.0);
        $contextWidth = ($maxX - $minX) + (2 * $marginX);
        $contextHeight = ($maxY - $minY) + (2 * $marginY);
        $minimumExtent = max(0, (float)$this->CI->config->item('wms_feature_report_min_extent_meters'));
        if ($contextWidth < $minimumExtent) {
            $marginX += ($minimumExtent - $contextWidth) / 2;
            $contextWidth = $minimumExtent;
        }
        if ($contextHeight < $minimumExtent) {
            $marginY += ($minimumExtent - $contextHeight) / 2;
            $contextHeight = $minimumExtent;
        }
        $width = max(1, (int)$this->CI->config->item('wms_feature_report_width'));
        $height = max(1, (int)round($width * ($contextHeight / $contextWidth)));
        return [
            'bbox' => implode(',', [$minX - $marginX, $minY - $marginY, $maxX + $marginX, $maxY + $marginY]),
            'width' => $width,
            'height' => $height
        ];
    }

    protected function formatBbox(array $bbox)
    {
        return implode(',', array_slice($bbox, 0, 4));
    }

    protected function releaseSessionLock()
    {
        if (function_exists('session_status') && session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
    }

    protected function validateTemplate($templateName)
    {
        $templateName = (string)$templateName;
        if ($templateName !== basename($templateName) || strtolower(pathinfo($templateName, PATHINFO_EXTENSION)) !== 'docx') {
            throw new InvalidArgumentException('Invalid template');
        }
        $directory = realpath(APPPATH . 'templates');
        $path = realpath(APPPATH . 'templates' . DIRECTORY_SEPARATOR . $templateName);
        if ($directory === false || $path === false || strpos($path, $directory . DIRECTORY_SEPARATOR) !== 0) {
            throw new InvalidArgumentException('Template was not found');
        }
        return $templateName;
    }

    protected function formatFeatureFields(array $feature, $projectName, $client = '')
    {
        $properties = isset($feature['properties']) && is_array($feature['properties']) ? $feature['properties'] : [];
        $this->CI->feature_info_formatter->setProject($projectName);
        $this->CI->feature_info_formatter->setClient($client);
        $attributes = isset($feature['attributes']) && is_array($feature['attributes'])
            ? $feature['attributes']
            : array_keys($properties);
        return $this->CI->feature_info_formatter->formatFeature($attributes, $properties);
    }

    protected function normalizeFeatures(array $features)
    {
        $normalized = [];
        foreach ($features as $index => $feature) {
            $properties = isset($feature['properties']) && is_array($feature['properties']) ? $feature['properties'] : [];
            $normalized[] = [
                'id' => isset($feature['id']) ? (string)$feature['id'] : (isset($properties['id']) ? (string)$properties['id'] : (string)$index),
                'layer' => isset($feature['layer']) ? (string)$feature['layer'] : '',
                'geometry_type' => isset($feature['geometry']['type']) ? (string)$feature['geometry']['type'] : '',
                'properties' => $properties,
                'bbox' => isset($feature['bbox']) && is_array($feature['bbox']) ? $feature['bbox'] : null
            ];
        }
        return $normalized;
    }
}
