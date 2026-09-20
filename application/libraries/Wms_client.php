<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * WMS Client Library for CodeIgniter
 * 
 * This library handles WMS (Web Map Service) requests for GetFeatureInfo and GetMap
 */
class Wms_client 
{
    protected $CI;
    protected $client;
    protected $defaultParams;
    
    public function __construct()
    {
        // Load composer autoload only when needed
        if (!class_exists('GuzzleHttp\Client')) {
            // Build cross-platform autoload path
            $autoload_path = $this->buildPath(FCPATH, 'vendor', 'autoload.php');
            
            if (file_exists($autoload_path)) {
                require_once $autoload_path;
            } else {
                throw new Exception('Composer autoload not found at: ' . $autoload_path . '. Please run composer install.');
            }
        }
        
        $this->CI =& get_instance();
        
        // Load WMS configuration
        $this->CI->config->load('wms');
        
        $this->client = new \GuzzleHttp\Client([
            'timeout' => $this->CI->config->item('wms_timeout') ?: 30,
            'connect_timeout' => $this->CI->config->item('wms_connect_timeout') ?: 5,
            'allow_redirects' => false,
            'verify' => $this->CI->config->item('wms_verify_tls') !== false
        ]);
        
        // Set default parameters
        $this->defaultParams = [
            'SERVICE' => 'WMS',
            'VERSION' => $this->CI->config->item('wms_default_version') ?: '1.3.0',
            'FORMAT' => $this->CI->config->item('wms_default_format') ?: 'application/json',
            'CRS' => $this->CI->config->item('wms_default_crs') ?: 'EPSG:4326'
        ];
    }
    
    /**
     * Build cross-platform file path
     * 
     * @param string ...$parts Path parts
     * @return string Complete path with proper separators
     */
    private function buildPath(...$parts)
    {
        $path = implode(DIRECTORY_SEPARATOR, $parts);
        return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    }
    
    /**
     * Perform GetFeatureInfo request
     * 
     * @param array $params WMS GetFeatureInfo parameters
     * @return array Parsed feature data
     */
    public function getFeatureInfo($params)
    {
        // Required parameters for QGIS Server GetFeatureInfo
        $requiredParams = ['URL', 'LAYERS', 'QUERY_LAYERS', 'WIDTH', 'HEIGHT'];
        $version = isset($params['VERSION']) ? $params['VERSION'] : $this->defaultParams['VERSION'];
        $hasFilter = isset($params['FILTER']) && $params['FILTER'] !== '';
        if (!$hasFilter) {
            $requiredParams[] = version_compare($version, '1.3.0', '>=') ? 'I' : 'X';
            $requiredParams[] = version_compare($version, '1.3.0', '>=') ? 'J' : 'Y';
        }
        
        // Validate required parameters
        foreach ($requiredParams as $param) {
            if (!isset($params[$param])) {
                throw new InvalidArgumentException("Missing required parameter: $param");
            }
        }
        
        // Build request parameters with defaults for QGIS Server
        $crs = isset($params['CRS']) ? $params['CRS'] : $this->defaultParams['CRS'];
        $requestParams = array_merge($this->defaultParams, [
            'REQUEST' => 'GetFeatureInfo',
            'VERSION' => $version,
            'LAYERS' => $params['LAYERS'],
            'QUERY_LAYERS' => $params['QUERY_LAYERS'],
            'FEATURE_COUNT' => isset($params['FEATURE_COUNT']) ? $params['FEATURE_COUNT'] : 10,
            'INFO_FORMAT' => isset($params['INFO_FORMAT']) ? $params['INFO_FORMAT'] : 'application/json',
            'WITH_GEOMETRY' => isset($params['WITH_GEOMETRY']) ? $params['WITH_GEOMETRY'] : 'true'
        ]);

        foreach (['BBOX', 'WIDTH', 'HEIGHT'] as $contextParam) {
            if (isset($params[$contextParam])) {
                $requestParams[$contextParam] = $params[$contextParam];
            }
        }

        if ($hasFilter) {
            $requestParams['FILTER'] = $params['FILTER'];
        }

        if (version_compare($version, '1.3.0', '>=')) {
            $requestParams['CRS'] = $crs;
            if (isset($params['I'], $params['J'])) {
                $requestParams['I'] = $params['I'];
                $requestParams['J'] = $params['J'];
            }
        } else {
            unset($requestParams['CRS']);
            $requestParams['SRS'] = isset($params['SRS']) ? $params['SRS'] : $crs;
            if (isset($params['X'], $params['Y'])) {
                $requestParams['X'] = $params['X'];
                $requestParams['Y'] = $params['Y'];
            }
        }
        
        $requestUrl = $this->getRequestUrl($params);
        $requestOptions = $this->getRequestOptions($requestParams, $params);
        $requestOptions['timeout'] = $this->CI->config->item('wms_feature_info_timeout') ?: 10;
        $maxAttempts = max(1, (int)($this->CI->config->item('wms_feature_info_attempts') ?: 1));
        $retryDelayMs = max(0, (int)$this->CI->config->item('wms_feature_info_retry_delay_ms'));

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $response = $this->client->get($requestUrl, $requestOptions);
            
                $body = $response->getBody()->getContents();
                $maxResponseSize = $this->CI->config->item('wms_max_feature_response_size') ?: (2 * 1024 * 1024);
                if (strlen($body) > $maxResponseSize) {
                    throw new Exception('WMS GetFeatureInfo response is too large');
                }
                $parsed = $this->parseFeatureInfoResponse($body, $requestParams['INFO_FORMAT']);
                if (empty($parsed['bbox'])) {
                    log_message('error', 'WMS GetFeatureInfo raw response (no bbox): ' . substr($body, 0, 2000));
                }
                return $parsed;
            
            } catch (\GuzzleHttp\Exception\TransferException $e) {
                if ($attempt < $maxAttempts && $this->isTransientRequestFailure($e)) {
                    log_message('error', 'Transient WMS GetFeatureInfo failure on attempt ' . $attempt . ' of ' . $maxAttempts . ': ' . $e->getMessage());
                    if ($retryDelayMs > 0) {
                        usleep($retryDelayMs * 1000);
                    }
                    continue;
                }

                $responseInfo = 'no response';
                if ($e instanceof \GuzzleHttp\Exception\RequestException && $e->hasResponse()) {
                    $response = $e->getResponse();
                    $responseInfo = 'status=' . $response->getStatusCode()
                        . ' headers=' . json_encode($response->getHeaders())
                        . ' body=' . substr((string)$response->getBody(), 0, 1000);
                }
                $safeHeaders = $requestOptions['headers'] ?? [];
                if (isset($safeHeaders['Cookie'])) {
                    $safeHeaders['Cookie'] = '[REDACTED]';
                }
                log_message('error', 'WMS GetFeatureInfo request failed after ' . $attempt . ' attempt(s): GET ' . $requestUrl . '?' . http_build_query($requestOptions['query'])
                    . ' | Request headers: ' . json_encode($safeHeaders)
                    . ' | Auth: ' . json_encode($requestOptions['auth'] ?? null)
                    . ' | Response: ' . $responseInfo);
                throw new Exception('WMS GetFeatureInfo request failed: ' . $this->formatRequestException($e));
            }
        }
    }

    private function isTransientRequestFailure(\GuzzleHttp\Exception\TransferException $exception)
    {
        if (!($exception instanceof \GuzzleHttp\Exception\RequestException) || !$exception->hasResponse()) {
            return true;
        }

        return in_array($exception->getResponse()->getStatusCode(), [502, 503, 504], true);
    }

    private function formatRequestException(\GuzzleHttp\Exception\TransferException $exception)
    {
        if (!($exception instanceof \GuzzleHttp\Exception\RequestException) || !$exception->hasResponse()) {
            return $exception->getMessage();
        }

        $response = $exception->getResponse();
        $body = (string)$response->getBody();
        if ($body !== '') {
            $previousSetting = libxml_use_internal_errors(true);
            $xml = simplexml_load_string($body);
            libxml_clear_errors();
            libxml_use_internal_errors($previousSetting);

            if ($xml !== false) {
                $serviceExceptions = $xml->xpath('//*[local-name()="ServiceException"]');
                if (!empty($serviceExceptions)) {
                    $serviceException = $serviceExceptions[0];
                    $code = isset($serviceException['code']) ? (string)$serviceException['code'] : 'ServiceException';
                    return $code . ': ' . trim((string)$serviceException);
                }
            }
        }

        return 'HTTP ' . $response->getStatusCode() . ' returned by WMS service';
    }
    
    /**
     * Perform GetMap request
     * 
     * @param array $params WMS GetMap parameters
     * @return string Path to downloaded map image
     */
    public function getMap($params)
    {
        $requiredParams = ['URL', 'LAYERS', 'BBOX', 'WIDTH', 'HEIGHT'];
        
        // Validate required parameters
        foreach ($requiredParams as $param) {
            if (!isset($params[$param])) {
                throw new InvalidArgumentException("Missing required parameter: $param");
            }
        }
        
        // Build request parameters
        $version = isset($params['VERSION']) ? $params['VERSION'] : $this->defaultParams['VERSION'];
        $crs = isset($params['CRS']) ? $params['CRS'] : $this->defaultParams['CRS'];
        $requestParams = array_merge($this->defaultParams, [
            'REQUEST' => 'GetMap',
            'VERSION' => $version,
            'LAYERS' => $params['LAYERS'],
            'BBOX' => $params['BBOX'],
            'WIDTH' => $params['WIDTH'],
            'HEIGHT' => $params['HEIGHT'],
            'FORMAT' => isset($params['FORMAT']) ? $params['FORMAT'] : 'image/png',
            'TRANSPARENT' => isset($params['TRANSPARENT']) ? $params['TRANSPARENT'] : 'true'
        ]);

        if (version_compare($version, '1.3.0', '>=')) {
            $requestParams['CRS'] = $crs;
        } else {
            unset($requestParams['CRS']);
            $requestParams['SRS'] = isset($params['SRS']) ? $params['SRS'] : $crs;
        }
        
        // Add QGIS Server layer-specific request parameters if provided.
        if (isset($params['FILTER']) && !empty($params['FILTER'])) {
            $requestParams['FILTER'] = $params['FILTER'];
        }
        if (isset($params['SELECTION']) && $params['SELECTION'] !== '') {
            $requestParams['SELECTION'] = $params['SELECTION'];
        }
        if (isset($params['DPI']) && is_numeric($params['DPI']) && (int)$params['DPI'] > 0) {
            $requestParams['DPI'] = (int)$params['DPI'];
        }
        if (isset($params['EXTERNAL_WMS_PARAMS']) && is_array($params['EXTERNAL_WMS_PARAMS'])) {
            foreach ($params['EXTERNAL_WMS_PARAMS'] as $name => $value) {
                if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*:(url|format|crs|layers|styles)$/', (string)$name) && is_scalar($value)) {
                    $requestParams[$name] = (string)$value;
                }
            }
        }
        
        $requestUrl = $this->getRequestUrl($params);
        log_message('debug', 'WMS GetMap request: ' . $requestUrl . '?' . http_build_query($requestParams));

        try {
            $response = $this->client->get($requestUrl, $this->getRequestOptions($requestParams, $params));

            $contentType = strtolower($response->getHeaderLine('Content-Type'));
            if (strpos($contentType, 'image/') !== 0) {
                throw new Exception('WMS GetMap returned a non-image response');
            }
            
            // Save image to temporary file
            $imageData = $response->getBody()->getContents();
            $maxImageSize = $this->CI->config->item('wms_max_image_size') ?: (10 * 1024 * 1024);
            if (strlen($imageData) === 0 || strlen($imageData) > $maxImageSize || @getimagesizefromstring($imageData) === false) {
                throw new Exception('WMS GetMap returned an invalid image');
            }
            $tempDir = $this->buildPath(FCPATH, 'assets', 'temp_images') . DIRECTORY_SEPARATOR;
            
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            
            $filename = 'map_' . uniqid() . '.png';
            $imagePath = $tempDir . $filename;
            
            if (file_put_contents($imagePath, $imageData) === false) {
                throw new Exception('Could not save the WMS map image');
            }
            
            return $imagePath;
            
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            throw new Exception('WMS GetMap request failed: ' . $e->getMessage());
        }
    }

    private function getRequestUrl($params)
    {
        if (!isset($params['URL']) || !$this->isValidWmsUrl($params['URL'])) {
            throw new InvalidArgumentException('A valid HTTP or HTTPS WMS URL is required');
        }
        return $params['URL'];
    }

    private function isValidWmsUrl($url)
    {
        if (!is_string($url) || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }
        $scheme = strtolower((string)parse_url($url, PHP_URL_SCHEME));
        return in_array($scheme, ['http', 'https'], true);
    }

    private function getRequestOptions($query, $params)
    {
        $options = ['query' => $query];

        // Only forward the session cookie to hosts we trust, never to arbitrary/attacker-influenced URLs
        if ($this->isTrustedProxyHost($params['URL'] ?? '')) {
            $cookieName = $this->CI->config->item('sess_cookie_name') ?: 'ci_session';
            $cookieValue = $this->CI->input->cookie($cookieName);
            if ($cookieValue !== null && $cookieValue !== '') {
                $options['headers'] = ['Cookie' => $cookieName . '=' . $cookieValue];
            }
        }

        return $options;
    }

    private function isTrustedProxyHost($url)
    {
        $host = strtolower((string)parse_url((string)$url, PHP_URL_HOST));
        if ($host === '') {
            return false;
        }
        $trustedHosts = [strtolower((string)parse_url(base_url(), PHP_URL_HOST)), '127.0.0.1', 'localhost'];
        return in_array($host, $trustedHosts, true);
    }
    
    /**
     * Get WMS capabilities
     * 
     * @param string $url WMS service URL
     * @return array Parsed capabilities data
     */
    public function getCapabilities($url)
    {
        if (!$this->isValidWmsUrl($url)) {
            throw new InvalidArgumentException('A valid HTTP or HTTPS WMS URL is required');
        }
        $requestParams = array_merge($this->defaultParams, [
            'REQUEST' => 'GetCapabilities'
        ]);
        
        try {
            $response = $this->client->get($url, [
                'query' => $requestParams
            ]);
            
            $xml = $response->getBody()->getContents();
            $capabilities = simplexml_load_string($xml);
            
            return $this->parseCapabilities($capabilities);
            
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            throw new Exception('WMS GetCapabilities request failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Parse GetFeatureInfo response
     * 
     * @param array $data Raw feature data
     * @return array Parsed and structured feature data
     */
    protected function parseFeatureInfoResponse($body, $infoFormat)
    {
        if (stripos((string)$infoFormat, 'xml') !== false) {
            $previousSetting = libxml_use_internal_errors(true);
            $xml = simplexml_load_string($body);
            libxml_clear_errors();
            libxml_use_internal_errors($previousSetting);

            if ($xml === false) {
                throw new Exception('WMS GetFeatureInfo returned invalid XML');
            }

            $exceptions = $xml->xpath('//*[local-name()="ServiceException" or local-name()="Exception" or local-name()="ExceptionText"]');
            if (!empty($exceptions)) {
                throw new Exception('WMS GetFeatureInfo returned a service exception');
            }

            return $this->parseFeatureInfoXml($xml);
        }

        $data = json_decode($body, true);
        if (!is_array($data) || json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('WMS GetFeatureInfo returned invalid JSON');
        }
        if (isset($data['exception']) || isset($data['ExceptionReport'])) {
            throw new Exception('WMS GetFeatureInfo returned a service exception');
        }

        return $this->parseFeatureInfo($data);
    }

    protected function parseFeatureInfoXml(SimpleXMLElement $xml)
    {
        $result = [
            'features' => [],
            'feature_count' => 0,
            'layer_name' => '',
            'bbox' => '',
            'generated_date' => date('Y-m-d H:i:s'),
            'generated_by' => 'WMS Client'
        ];

        $responseBbox = $xml->xpath('/*[local-name()="GetFeatureInfoResponse"]/*[local-name()="BoundingBox"]');
        if (!empty($responseBbox)) {
            $result['bbox'] = $this->parseXmlBoundingBox($responseBbox[0]);
        }

        $layers = $xml->xpath('//*[local-name()="Layer"]');
        foreach ($layers as $layer) {
            $layerName = isset($layer['name']) ? (string)$layer['name'] : '';
            if ($result['layer_name'] === '' && $layerName !== '') {
                $result['layer_name'] = $layerName;
            }

            foreach ($layer->xpath('./*[local-name()="Feature"]') as $feature) {
                $properties = [];
                foreach ($feature->xpath('./*[local-name()="Attribute"]') as $attribute) {
                    $name = isset($attribute['name']) ? (string)$attribute['name'] : '';
                    if ($name !== '') {
                        $properties[$name] = isset($attribute['value']) ? (string)$attribute['value'] : trim((string)$attribute);
                    }
                }

                $featureBbox = '';
                $featureBboxes = $feature->xpath('./*[local-name()="BoundingBox"]');
                if (!empty($featureBboxes)) {
                    $featureBbox = $this->parseXmlBoundingBox($featureBboxes[0]);
                    if (empty($result['bbox'])) {
                        $result['bbox'] = $featureBbox;
                    }
                }

                $featureId = isset($feature['id']) ? (string)$feature['id'] : (isset($properties['id']) ? (string)$properties['id'] : (string)count($result['features']));
                $featureData = [
                    'id' => $featureId,
                    'layer' => $layerName,
                    'properties' => $properties
                ];
                if (is_array($featureBbox)) {
                    $featureData['bbox'] = $featureBbox;
                }

                $result['features'][] = $featureData;
            }
        }

        $result['feature_count'] = count($result['features']);
        return $result;
    }

    protected function parseXmlBoundingBox(SimpleXMLElement $bbox)
    {
        foreach (['minx', 'miny', 'maxx', 'maxy'] as $attribute) {
            if (!isset($bbox[$attribute]) || !is_numeric((string)$bbox[$attribute])) {
                return '';
            }
        }

        return [
            (float)$bbox['minx'],
            (float)$bbox['miny'],
            (float)$bbox['maxx'],
            (float)$bbox['maxy']
        ];
    }

    protected function parseFeatureInfo($data)
    {
        $result = [
            'features' => [],
            'feature_count' => 0,
            'layer_name' => '',
            'bbox' => '',
            'generated_date' => date('Y-m-d H:i:s'),
            'generated_by' => 'WMS Client'
        ];
        
        if (isset($data['features']) && is_array($data['features'])) {
            $result['features'] = $data['features'];
            $result['feature_count'] = count($data['features']);
            
            // Extract layer name from first feature if available
            if (!empty($data['features'][0]['layer'])) {
                $result['layer_name'] = $data['features'][0]['layer'];
            }
        }
        
        // Handle different response formats
        if (isset($data['type']) && $data['type'] === 'FeatureCollection') {
            $result['features'] = $data['features'];
            $result['feature_count'] = count($data['features']);
        }

        if (isset($data['bbox']) && is_array($data['bbox'])) {
            $result['bbox'] = $data['bbox'];
        } elseif (!empty($result['features'][0]['bbox']) && is_array($result['features'][0]['bbox'])) {
            $result['bbox'] = $result['features'][0]['bbox'];
        }
        
        return $result;
    }
    
    /**
     * Parse WMS capabilities
     * 
     * @param SimpleXMLElement $capabilities
     * @return array Parsed capabilities data
     */
    protected function parseCapabilities($capabilities)
    {
        $result = [
            'service' => [],
            'layers' => []
        ];
        
        // Parse service information
        if (isset($capabilities->Service)) {
            $service = $capabilities->Service;
            $result['service'] = [
                'title' => (string)$service->Title,
                'abstract' => (string)$service->Abstract,
                'version' => (string)$capabilities['version']
            ];
        }
        
        // Parse layer information
        if (isset($capabilities->Capability->Layer)) {
            $this->parseLayer($capabilities->Capability->Layer, $result['layers']);
        }
        
        return $result;
    }
    
    /**
     * Recursively parse layer information
     * 
     * @param SimpleXMLElement $layer
     * @param array &$layers
     */
    protected function parseLayer($layer, &$layers)
    {
        $layerData = [
            'name' => (string)$layer->Name,
            'title' => (string)$layer->Title,
            'abstract' => (string)$layer->Abstract,
            'queryable' => (string)$layer['queryable'] === '1',
            'children' => []
        ];
        
        // Parse bounding box
        if (isset($layer->BoundingBox)) {
            $bbox = $layer->BoundingBox;
            $layerData['bbox'] = [
                'minx' => (float)$bbox['minx'],
                'miny' => (float)$bbox['miny'],
                'maxx' => (float)$bbox['maxx'],
                'maxy' => (float)$bbox['maxy'],
                'crs' => (string)$bbox['CRS']
            ];
        }
        
        // Parse supported formats
        if (isset($layer->Style)) {
            $layerData['styles'] = [];
            foreach ($layer->Style as $style) {
                $layerData['styles'][] = [
                    'name' => (string)$style->Name,
                    'title' => (string)$style->Title
                ];
            }
        }
        
        $layers[] = $layerData;
        
        // Parse child layers
        if (isset($layer->Layer)) {
            foreach ($layer->Layer as $childLayer) {
                $this->parseLayer($childLayer, $layerData['children']);
            }
        }
    }
    
    /**
     * Build WMS request URL
     * 
     * @param string $baseUrl Base WMS service URL
     * @param array $params Request parameters
     * @return string Complete request URL
     */
    public function buildRequestUrl($baseUrl, $params)
    {
        $queryString = http_build_query(array_merge($this->defaultParams, $params));
        return $baseUrl . '?' . $queryString;
    }
    
    /**
     * Validate WMS parameters
     * 
     * @param array $params Parameters to validate
     * @param array $required Required parameter names
     * @return bool
     */
    public function validateParams($params, $required)
    {
        foreach ($required as $param) {
            if (!isset($params[$param]) || empty($params[$param])) {
                return false;
            }
        }
        return true;
    }
}
