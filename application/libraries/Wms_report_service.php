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
            'external_layers' => $externalLayers,
            'template' => $this->validateTemplate($this->CI->config->item('phpword_default_template_name'))
        ];
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
        if (!isset($properties->layers[$layerId])) {
            throw new DomainException('Layer is not available in the current project');
        }
        $layer = $properties->layers[$layerId];
        return [
            'qgis_layer' => $properties->use_ids ? $layer->id : $layer->layername,
            'name' => $layer->layername,
            'key' => isset($layer->key) ? (string)$layer->key : '',
            'geometry_type' => isset($layer->geom_type) ? (string)$layer->geom_type : ''
        ];
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
