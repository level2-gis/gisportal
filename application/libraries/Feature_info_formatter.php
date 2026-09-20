<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Normalize feature-info field names and values before they are rendered.
 *
 * The formatter returns data instead of markup, so the same result can be
 * rendered as DOCX, HTML, XML or plain text without trusting field values.
 */
class Feature_info_formatter
{
    protected $CI;
    protected $noDataValue;
    protected $trueText;
    protected $falseText;
    protected $fieldTemplates;
    protected $filesField;
    protected $project;
    protected $client;
    protected $decimals;
    protected $decimalSeparator;
    protected $thousandsSeparator;
    protected $dateFormat;
    protected $dateTimeFormat;

    public function __construct(array $params = [])
    {
        $this->CI =& get_instance();
        $this->CI->config->load('feature_format', true, true);

        $this->noDataValue = $this->option($params, 'noDataValue', 'feature_format_no_data_value', '');
        $this->trueText = $this->option($params, 'trueText', 'feature_format_true_text', 'true');
        $this->falseText = $this->option($params, 'falseText', 'feature_format_false_text', 'false');
        $this->filesField = strtoupper($this->option($params, 'filesField', 'feature_format_files_field', 'FILES'));
        $this->decimals = (int)$this->option($params, 'decimals', 'feature_format_decimals', 3);
        $this->decimalSeparator = $this->option($params, 'decimalSeparator', 'feature_format_decimal_separator', ',');
        $this->thousandsSeparator = $this->option($params, 'thousandsSeparator', 'feature_format_thousands_separator', '.');
        $this->dateFormat = $this->option($params, 'dateFormat', 'feature_format_date', 'Y-m-d');
        $this->dateTimeFormat = $this->option($params, 'dateTimeFormat', 'feature_format_datetime', 'Y-m-d H:i:s');
        $this->project = isset($params['project']) ? (string)$params['project'] : '';
        $this->client = isset($params['client']) ? (string)$params['client'] : '';

        $templates = isset($params['fieldTemplates'])
            ? $params['fieldTemplates']
            : $this->CI->config->item('feature_format_field_templates', 'feature_format');
        $this->fieldTemplates = is_array($templates) ? $templates : [];
    }

    /**
     * Project name used by %PROJECT% placeholders.
     *
     * @param string $project
     */
    public function setProject($project)
    {
        $this->project = (string)$project;
    }

    /**
     * Client name used by %CLIENT% placeholders, for example in media URLs.
     *
     * @param string $client
     */
    public function setClient($client)
    {
        $this->client = (string)$client;
    }

    /**
     * @param array $attributes Field definitions keyed by name, or a list of names/definitions.
     * @param array $values Raw feature values keyed by field name.
     * @param array $options Optional: includeHidden, includeMaptip, includeGeometry, aliases.
     * @return array Normalized fields in the same order as $attributes.
     */
    public function formatFeature(array $attributes, array $values, array $options = [])
    {
        $result = [];
        $includeHidden = !empty($options['includeHidden']);
        $includeMaptip = !empty($options['includeMaptip']);
        $includeGeometry = !empty($options['includeGeometry']);
        $aliases = isset($options['aliases']) && is_array($options['aliases']) ? $options['aliases'] : [];

        foreach ($attributes as $key => $definition) {
            if (!is_array($definition)) {
                $name = is_string($definition) ? $definition : (string)$key;
                $definition = [];
            } else {
                $name = isset($definition['name']) ? (string)$definition['name'] : (string)$key;
            }
            if ($name === '') {
                continue;
            }

            $upperName = $this->upper($name);
            if (!$includeMaptip && $upperName === 'MAPTIP') {
                continue;
            }
            if (!$includeGeometry && $upperName === 'GEOMETRY') {
                continue;
            }

            $hidden = !empty($definition['hidden']) || !empty($definition['hidden_attributes']);
            if ($hidden && !$includeHidden) {
                continue;
            }

            $template = $this->templateFor($upperName);
            $rawValue = array_key_exists($name, $values) ? $values[$name] : null;
            $rawValue = $this->normalizeNull($rawValue);

            $alias = isset($definition['alias']) && $definition['alias'] !== ''
                ? $definition['alias']
                : (isset($aliases[$name]) ? $aliases[$name] : null);
            if ($alias !== null && $alias !== '') {
                $label = $this->upper($alias);
            } elseif (isset($template['newName']) && $template['newName'] !== '') {
                // newName is already cased as configured.
                $label = $template['newName'];
            } else {
                $label = $this->upper($name);
            }

            $isFiles = $upperName === $this->filesField;
            if ($isFiles) {
                // Files are shown as a single image row, not a label/value pair.
                $result[] = [
                    'name' => $name,
                    'label' => $label,
                    'raw' => $rawValue,
                    'value' => null,
                    'type' => isset($definition['type']) ? $definition['type'] : null,
                    'hidden' => $hidden,
                    'is_files' => true,
                    'is_boolean' => false,
                    'template' => $template,
                    'url' => null,
                    'image' => $this->buildFilesImage($rawValue),
                    'links' => null
                ];
                continue;
            }

            $links = $this->buildLinks($template, $rawValue);
            $value = $links !== null ? $links : $this->formatValue($rawValue, $definition, $template, $upperName);

            $result[] = [
                'name' => $name,
                'label' => $label,
                'raw' => $rawValue,
                'value' => $value,
                'type' => isset($definition['type']) ? $definition['type'] : null,
                'hidden' => $hidden,
                'is_files' => false,
                'is_boolean' => $this->isBoolean($rawValue, $definition),
                'template' => $template,
                'url' => $links === null ? $this->buildUrl($template, $rawValue) : null,
                'image' => $this->buildImage($template, $rawValue),
                'links' => $links
            ];
        }

        return $result;
    }

    /**
     * Collapse formatted fields into label/value text pairs for documents.
     *
     * @param array $fields Result of formatFeature()
     * @return array List of ['label' => string, 'value' => string]
     */
    public function toTextRows(array $fields)
    {
        $rows = [];
        foreach ($fields as $field) {
            $rows[] = [
                'label' => $field['label'],
                'value' => $this->toText($field)
            ];
        }
        return $rows;
    }

    /**
     * Plain text rendering of a single formatted field.
     *
     * @param array $field
     * @param bool $includeUrl Append "(url)" for linked values; disable when the
     *                          caller renders the link separately (e.g. as a hyperlink).
     * @return string
     */
    public function toText(array $field, $includeUrl = true)
    {
        $value = isset($field['value']) ? $field['value'] : '';

        if (is_array($value)) {
            $parts = [];
            foreach ($value as $item) {
                if (is_array($item)) {
                    $text = isset($item['value']) ? (string)$item['value'] : '';
                    if ($includeUrl && !empty($item['url'])) {
                        $text = $text === '' ? (string)$item['url'] : $text . ' (' . $item['url'] . ')';
                    }
                    $parts[] = $text;
                } elseif (is_scalar($item)) {
                    $parts[] = (string)$item;
                }
            }
            return implode(', ', array_filter($parts, 'strlen'));
        }

        if (is_bool($value)) {
            return $value ? $this->trueText : $this->falseText;
        }
        if (!is_scalar($value)) {
            return (string)json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string)$value), ENT_QUOTES, 'UTF-8')));
        if (!$includeUrl) {
            return $text;
        }
        if (!empty($field['image'])) {
            return $text === '' ? (string)$field['image'] : $text . ' (' . $field['image'] . ')';
        }
        if (!empty($field['url']) && $text !== '') {
            return $text . ' (' . $field['url'] . ')';
        }
        return $text;
    }

    protected function option(array $params, $paramKey, $configKey, $default)
    {
        if (array_key_exists($paramKey, $params)) {
            return $params[$paramKey];
        }
        $value = $this->CI->config->item($configKey, 'feature_format');
        return $value === null ? $default : $value;
    }

    protected function templateFor($name)
    {
        foreach ($this->fieldTemplates as $key => $template) {
            if ($this->upper((string)$key) === $name && is_array($template)) {
                return $template;
            }
        }
        return [];
    }

    protected function normalizeNull($value)
    {
        if ($value === null || (is_string($value) && strtolower(trim($value)) === 'null')) {
            return $this->noDataValue;
        }
        return $value;
    }

    protected function formatValue($value, array $definition, array $template, $name)
    {
        if ($this->isBoolean($value, $definition)) {
            if ($value === true || (is_string($value) && strtolower($value) === 'true') || $value === 1 || $value === '1') {
                return $this->trueText;
            }
            if ($value === false || (is_string($value) && strtolower($value) === 'false') || $value === 0 || $value === '0') {
                return $this->falseText;
            }
        }

        $value = $this->formatType($value, $definition);
        if ($value !== null && $value !== '' && isset($template['template']) && $template['template'] !== '') {
            $value = str_replace(
                ['%VALUE%', '%PROJECT%', '%CLIENT%'],
                [(string)$value, $this->project, $this->client],
                $template['template']
            );
        }
        return $value;
    }

    protected function formatType($value, array $definition)
    {
        if ($value === null || $value === '') {
            return $value;
        }
        $type = isset($definition['type']) ? strtolower((string)$definition['type']) : '';
        if (strpos($type, 'double') !== false || strpos($type, 'float') !== false || strpos($type, 'real') !== false
            || strpos($type, 'numeric') !== false || strpos($type, 'decimal') !== false) {
            return is_numeric($value)
                ? number_format((float)$value, $this->decimals, $this->decimalSeparator, $this->thousandsSeparator)
                : $value;
        }
        if (strpos($type, 'datetime') !== false || strpos($type, 'timestamp') !== false || strpos($type, 'date') !== false) {
            $timestamp = strtotime((string)$value);
            if ($timestamp !== false) {
                $isDateTime = strpos($type, 'date') === false || strpos($type, 'datetime') !== false || strpos($type, 'timestamp') !== false;
                return date($isDateTime ? $this->dateTimeFormat : $this->dateFormat, $timestamp);
            }
        }
        return $value;
    }

    /**
     * Build the image URL for the files field: base_url + /uploads/ + client
     * + / + project + / + filename. Takes the first entry when the value is
     * a JSON-encoded list of files.
     *
     * @param mixed $value
     * @return string|null
     */
    protected function buildFilesImage($value)
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }
        $filename = $this->resolveFilesFilename($value);
        if ($filename === '') {
            return null;
        }
        $base = rtrim((string)$this->CI->config->item('base_url'), '/');
        $url = $base . '/uploads/' . rawurlencode($this->client) . '/' . rawurlencode($this->project) . '/' . rawurlencode($filename);
        return $this->sanitizeUrl($url);
    }

    protected function resolveFilesFilename($value)
    {
        $decoded = json_decode($value, true);
        if (is_array($decoded) && !empty($decoded)) {
            $first = reset($decoded);
            $filename = is_array($first) && isset($first['value']) ? $first['value'] : $first;
            return ltrim(trim((string)$filename), '/');
        }
        return ltrim(trim($value), '/');
    }

    /**
     * Resolve a field's/link's url. Supports:
     *   urlValue    - the raw value is itself the full URL (sanitized as-is)
     *   urlTemplate - full URL template, supports %VALUE% (raw-urlencoded), %PROJECT%, %CLIENT%
     *   url         - lookup URL prefix, the raw-urlencoded value is appended to it
     */
    protected function buildUrl(array $template, $value)
    {
        if ($value === null || $value === '' || !is_scalar($value)) {
            return null;
        }
        if (!empty($template['urlValue'])) {
            return $this->sanitizeUrl((string)$value);
        }
        if (!empty($template['urlTemplate'])) {
            $url = str_replace(
                ['%VALUE%', '%PROJECT%', '%CLIENT%'],
                [rawurlencode((string)$value), rawurlencode($this->project), rawurlencode($this->client)],
                $template['urlTemplate']
            );
            return $this->sanitizeUrl($url);
        }
        if (!isset($template['url']) || $template['url'] === '') {
            return null;
        }
        return $this->sanitizeUrl($template['url'] . rawurlencode((string)$value));
    }

    /**
     * Build multiple text+url pairs for fields configured with 'links' instead
     * of a single 'template'/'url' pair, e.g. several report links per value.
     * Each link supports the same url/urlTemplate/urlValue keys as buildUrl().
     *
     * @param array $template
     * @param mixed $value
     * @return array|null List of ['value' => text, 'url' => url], or null when not configured/applicable.
     */
    protected function buildLinks(array $template, $value)
    {
        if (empty($template['links']) || !is_array($template['links']) || $value === null || $value === '' || !is_scalar($value)) {
            return null;
        }
        $links = [];
        foreach ($template['links'] as $link) {
            if (!is_array($link)) {
                continue;
            }
            $url = $this->buildUrl($link, $value);
            if ($url === null) {
                continue;
            }
            $text = isset($link['template']) && $link['template'] !== ''
                ? str_replace(['%VALUE%', '%PROJECT%', '%CLIENT%'], [(string)$value, $this->project, $this->client], $link['template'])
                : (string)$value;
            $links[] = ['value' => $text, 'url' => $url];
        }
        return $links !== [] ? $links : null;
    }

    protected function buildImage(array $template, $value)
    {
        if (!isset($template['image']) || $template['image'] === '' || $value === null || $value === '' || !is_scalar($value)) {
            return null;
        }
        $url = str_replace(
            ['%VALUE%', '%PROJECT%', '%CLIENT%'],
            [rawurlencode((string)$value), rawurlencode($this->project), rawurlencode($this->client)],
            $template['image']
        );
        return $this->sanitizeUrl($url);
    }

    protected function sanitizeUrl($url)
    {
        $scheme = strtolower((string)parse_url($url, PHP_URL_SCHEME));
        return in_array($scheme, ['http', 'https'], true) ? $url : null;
    }

    protected function isBoolean($value, array $definition)
    {
        $type = isset($definition['type']) ? strtolower((string)$definition['type']) : '';
        $editType = isset($definition['editType']) ? strtolower((string)$definition['editType']) : '';
        return strpos($type, 'bool') !== false || $editType === 'checkbox'
            || is_bool($value) || (is_string($value) && in_array(strtolower($value), ['true', 'false'], true));
    }

    protected function upper($value)
    {
        return function_exists('mb_strtoupper') ? mb_strtoupper((string)$value, 'UTF-8') : strtoupper((string)$value);
    }
}
