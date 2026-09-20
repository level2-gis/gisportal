<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * PHPWord Library for CodeIgniter
 * 
 * This library provides a wrapper for PHPOffice/PHPWord functionality
 * specifically for generating documents with data from WMS services
 * 
 * Cross-platform compatible implementation
 */
class Phpword_lib 
{
    protected $phpWord;
    protected $CI;
    
    public function __construct()
    {
        $this->CI =& get_instance();
        
        // Load composer autoload only when needed
        if (!class_exists('PhpOffice\PhpWord\PhpWord')) {
            // Build cross-platform autoload path
            $autoload_path = $this->buildPath(FCPATH, 'vendor', 'autoload.php');
            
            if (file_exists($autoload_path)) {
                require_once $autoload_path;
            } else {
                throw new Exception('Composer autoload not found at: ' . $autoload_path . '. Please run composer install.');
            }
        }
        
        // Check if ZipArchive is available
        if (!class_exists('ZipArchive')) {
            throw new Exception('ZipArchive class not found. Please enable the ZIP extension in PHP.');
        }
        
        $this->phpWord = new \PhpOffice\PhpWord\PhpWord();
        
        // Load configuration
        $this->CI->config->load('phpword');
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
     * Create a new document from template
     * 
     * @param string $templatePath Path to the template file
     * @return \PhpOffice\PhpWord\TemplateProcessor
     */
    public function loadTemplate($templatePath = null)
    {
        if ($templatePath === null) {
            $templatePath = $this->CI->config->item('phpword_default_template');
        }
        
        if (!file_exists($templatePath)) {
            throw new Exception("Template file not found: " . $templatePath);
        }
        
        return new \PhpOffice\PhpWord\TemplateProcessor($templatePath);
    }
    
    /**
     * Create a simple document programmatically
     * 
     * @return \PhpOffice\PhpWord\PhpWord
     */
    public function createDocument()
    {
        return $this->phpWord;
    }
    
    /**
     * Replace placeholders in template with WMS data
     * 
     * @param \PhpOffice\PhpWord\TemplateProcessor $templateProcessor
     * @param array $wmsData Data retrieved from WMS service
     * @param array $placeholders Mapping of placeholders to data fields
     * @return \PhpOffice\PhpWord\TemplateProcessor
     */
    public function replaceWmsData($templateProcessor, $wmsData, $placeholders = [])
    {
        try {
            $templateVars = $templateProcessor->getVariables();
            
            // Default placeholder mappings
            $defaultPlaceholders = [
                'title' => 'title',
                'description' => 'description',
                'feature_count' => 'feature_count',
                'layer_name' => 'layer_name',
                'bbox' => 'bbox',
                'generated_date' => 'generated_date',
                'generated_by' => 'generated_by'
            ];
            
            $placeholders = array_merge($defaultPlaceholders, $placeholders);
            
            // Replace basic placeholders
            foreach ($placeholders as $placeholder => $dataKey) {
                $value = isset($wmsData[$dataKey]) ? $wmsData[$dataKey] : '';
                try {
                    $templateProcessor->setValue($placeholder, $this->formatTemplateValue($value));
                } catch (Exception $e) {
                    log_message('debug', "Could not set placeholder '$placeholder': " . $e->getMessage());
                }
            }

            // A selected-feature report may bind template variables directly
            // to QGIS attribute names, while report metadata stays reserved.
            if (isset($wmsData['scope']) && $wmsData['scope'] === 'selected' && count($wmsData['features']) === 1) {
                $feature = $wmsData['features'][0];
                $properties = $feature['properties'] ?? [];
                $rows = $this->buildPropertyRows($feature);
                $reserved = array_keys($placeholders);
                foreach (['feature_id' => 'id', 'feature_layer' => 'layer', 'feature_type' => 'geometry_type'] as $placeholder => $featureKey) {
                    if (in_array($placeholder, $templateVars, true)) {
                        $templateProcessor->setValue($placeholder, $this->formatTemplateValue($feature[$featureKey] ?? ''));
                    }
                }
                $formattedValues = [];
                foreach ($rows as $row) {
                    $formattedValues[$row['name']] = $row['value'];
                }
                foreach ($properties as $property => $value) {
                    if (in_array($property, $templateVars, true) && !in_array($property, $reserved, true)) {
                        $value = array_key_exists($property, $formattedValues) ? $formattedValues[$property] : $value;
                        $templateProcessor->setValue($property, $this->formatTemplateValue($value));
                    }
                }
                $this->replacePropertiesTable($templateProcessor, $rows);
            }
            
            // Handle feature data table if present
            if (isset($wmsData['features']) && is_array($wmsData['features'])) {
                $this->replaceFeatureTable($templateProcessor, $wmsData['features']);
            } else {
                // Set empty feature table
                $templateProcessor->setValue('feature_table', 'No features available.');
                $templateProcessor->setValue('features_table', 'No features available.');
                $templateProcessor->setValue('feature_count', '0');
            }
            
            // Handle images if present (from GetMap requests)
            if (isset($wmsData['map_image'])) {
                $this->replaceMapImage($templateProcessor, $wmsData['map_image']);
            } else {
                $templateProcessor->setValue('map_image', '');
            }
            
            return $templateProcessor;
            
        } catch (Exception $e) {
            log_message('error', 'PHPWord template processing failed: ' . $e->getMessage());
            throw new Exception("Failed to process template: " . $e->getMessage());
        }
    }
    
    /**
     * Replace feature data table in template
     * 
     * @param \PhpOffice\PhpWord\TemplateProcessor $templateProcessor
     * @param array $features Feature data from WMS GetFeatureInfo
     */
    protected function replaceFeatureTable($templateProcessor, $features) {
        try {
            log_message('debug', 'PHPWord: Starting feature table replacement');
            log_message('debug', 'PHPWord: Features count: ' . count($features));
            
            // Get template variables to find table structure
            $variables = $templateProcessor->getVariables();
            log_message('debug', 'PHPWord: Template variables: ' . json_encode($variables));
            
            // Support standard unsuffixed template rows and legacy #1 rows.
            $tableVars = [];
            foreach ($variables as $var) {
                if (preg_match('/^feature_(\w+)(?:#1)?$/', $var, $matches)) {
                    $tableVars[] = $matches[1];
                }
            }
            
            if (!empty($tableVars)) {
                log_message('debug', 'PHPWord: Found table variables: ' . json_encode($tableVars));
                
                // For PHPWord cloning to work, we need to use exactly the variable that appears in the template
                // Since PHPWord expects the base name, let's try a different approach
                
                $cloneSuccess = false;
                $cloneVar = in_array('feature_id', $variables, true) ? 'feature_id' : 'feature_id#1';
                $rowSuffix = $cloneVar === 'feature_id' ? '#' : '#1#';
                
                try {
                    log_message('debug', 'PHPWord: Attempting to clone row with variable: ' . $cloneVar);
                    
                    // Clone rows for each feature
                    $templateProcessor->cloneRow($cloneVar, count($features));
                    $cloneSuccess = true;
                    log_message('debug', 'PHPWord: Successfully cloned rows with: ' . $cloneVar);
                    
                } catch (Exception $cloneError) {
                    log_message('debug', 'PHPWord: Standard cloning failed: ' . $cloneError->getMessage());
                    
                    // Try manual approach - replace the first occurrence and duplicate manually
                    try {
                        // Replace values directly without cloning
                        foreach ($features as $index => $feature) {
                            $rowIndex = $index + 1;
                            
                            // Handle different data structures
                            $featureId = $feature['id'] ?? $feature['properties']['id'] ?? "FEAT_$rowIndex";
                            $featureName = $feature['name'] ?? $feature['properties']['name'] ?? $feature['properties']['NAME'] ?? 'Unknown';
                            $featureType = $feature['geometry_type'] ?? $feature['type'] ?? $feature['geometry']['type'] ?? 'Unknown';
                            $featureLayer = $feature['layer'] ?? '';
                            
                            // Format properties
                            $properties = '';
                            if (isset($feature['properties']) && is_array($feature['properties'])) {
                                $propArray = [];
                                foreach ($feature['properties'] as $key => $value) {
                                    if (!in_array(strtolower($key), ['name', 'id'])) {
                                        $propArray[] = "$key: $value";
                                    }
                                }
                                $properties = implode(', ', $propArray);
                            }
                            
                            // For the first row, replace the #1 variables
                            if ($rowIndex == 1) {
                                $templateProcessor->setValue("feature_id#1", $featureId);
                                $templateProcessor->setValue("feature_name#1", $featureName);
                                $templateProcessor->setValue("feature_type#1", $featureType);
                                $templateProcessor->setValue("feature_layer#1", $featureLayer);
                                $templateProcessor->setValue("feature_properties#1", $properties);
                                log_message('debug', "PHPWord: Replaced first row with feature data: ID=$featureId, Name=$featureName");
                            }
                        }
                        
                        // If we have only one feature, this will work
                        if (count($features) == 1) {
                            $cloneSuccess = true;
                        } else {
                            // For multiple features, we'll fall back to text format
                            log_message('debug', 'PHPWord: Multiple features detected, cannot clone properly, falling back to text');
                        }
                        
                    } catch (Exception $manualError) {
                        log_message('debug', 'PHPWord: Manual replacement also failed: ' . $manualError->getMessage());
                    }
                }
                
                if ($cloneSuccess && count($features) > 1) {
                    // Replace values in each cloned row (if cloning worked)
                    foreach ($features as $index => $feature) {
                        $rowIndex = $index + 1;
                        
                        // Handle different data structures
                        $featureId = $feature['id'] ?? $feature['properties']['id'] ?? "FEAT_$rowIndex";
                        $featureName = $feature['name'] ?? $feature['properties']['name'] ?? $feature['properties']['NAME'] ?? 'Unknown';
                        $featureType = $feature['geometry_type'] ?? $feature['type'] ?? $feature['geometry']['type'] ?? 'Unknown';
                        $featureLayer = $feature['layer'] ?? '';
                        
                        // Format properties
                        $properties = '';
                        if (isset($feature['properties']) && is_array($feature['properties'])) {
                            $propArray = [];
                            foreach ($feature['properties'] as $key => $value) {
                                if (!in_array(strtolower($key), ['name', 'id'])) {
                                    $propArray[] = "$key: $value";
                                }
                            }
                            $properties = implode(', ', $propArray);
                        }
                        
                        // Replace values using #rowIndex format
                        $templateProcessor->setValue("feature_id$rowSuffix$rowIndex", $featureId);
                        $templateProcessor->setValue("feature_name$rowSuffix$rowIndex", $featureName);
                        $templateProcessor->setValue("feature_type$rowSuffix$rowIndex", $featureType);
                        $templateProcessor->setValue("feature_layer$rowSuffix$rowIndex", $featureLayer);
                        $templateProcessor->setValue("feature_properties$rowSuffix$rowIndex", $properties);
                        
                        log_message('debug', "PHPWord: Replaced row $rowIndex with feature data: ID=$featureId, Name=$featureName");
                    }
                }
                
                if ($cloneSuccess) {
                    log_message('debug', 'PHPWord: Successfully processed feature table');
                    return true;
                }
            }
            
            // If we get here, table cloning failed - use fallback
            log_message('debug', 'PHPWord: Table cloning failed, using text replacement fallback');
            
            $featureText = $this->formatFeaturesAsText($features);
            
            $textPlaceholders = ['feature_table', 'features_table', 'feature_list', 'features'];
            foreach ($textPlaceholders as $placeholder) {
                if (in_array($placeholder, $variables)) {
                    $templateProcessor->setValue($placeholder, $featureText);
                    log_message('debug', "PHPWord: Used fallback text replacement for $placeholder");
                    return true;
                }
            }
            
        } catch (Exception $e) {
            log_message('error', 'PHPWord: Feature table replacement failed: ' . $e->getMessage());
            
            // Final fallback
            try {
                $featureText = $this->formatFeaturesAsText($features);
                $templateProcessor->setValue('feature_table', $featureText);
                return true;
            } catch (Exception $fallbackError) {
                log_message('error', 'PHPWord: All fallback attempts failed: ' . $fallbackError->getMessage());
            }
        }
        
        return false;
    }
    
    /**
     * Format features as text string (fallback when table cloning fails)
     * 
     * @param array $features Feature data
     * @return string Formatted text
     */
    private function formatFeaturesAsText($features) {
        if (empty($features)) {
            return 'No features found.';
        }
        
        $text = "Features Found: " . count($features) . "\n\n";
        
        foreach ($features as $index => $feature) {
            $featureNum = $index + 1;
            $featureId = $feature['id'] ?? $feature['properties']['id'] ?? "FEAT_$featureNum";
            $featureName = $feature['name'] ?? $feature['properties']['name'] ?? $feature['properties']['NAME'] ?? 'Unknown';
            $featureType = $feature['geometry_type'] ?? $feature['type'] ?? $feature['geometry']['type'] ?? 'Unknown';
            
            $text .= "$featureNum. $featureName (ID: $featureId)\n";
            $text .= "   Type: $featureType\n";
            if (!empty($feature['layer'])) {
                $text .= "   Layer: " . $feature['layer'] . "\n";
            }
            
            // Add properties
            if (isset($feature['properties']) && is_array($feature['properties'])) {
                $text .= "   Properties:\n";
                foreach ($feature['properties'] as $key => $value) {
                    if (!in_array(strtolower($key), ['name', 'id'])) {
                        $text .= "     - $key: $value\n";
                    }
                }
            }
            
            $text .= "\n";
        }
        
        return $text;
    }
    
    /**
     * Build label/value rows for one feature, using formatted fields when available.
     *
     * @param array $feature
     * @return array List of ['name' => string, 'label' => string, 'value' => string]
     */
    protected function buildPropertyRows(array $feature)
    {
        $properties = isset($feature['properties']) && is_array($feature['properties']) ? $feature['properties'] : [];

        if (!empty($feature['fields']) && is_array($feature['fields'])) {
            $this->CI->load->library('feature_info_formatter');
            $rows = [];
            foreach ($feature['fields'] as $field) {
                if (!empty($field['is_files'])) {
                    $rows[] = [
                        'name' => $field['name'],
                        'label' => $field['label'],
                        'value' => '',
                        'url' => null,
                        'links' => null,
                        'is_files' => true,
                        'image' => !empty($field['image']) ? $field['image'] : null
                    ];
                    continue;
                }
                $url = !empty($field['url']) ? $field['url'] : null;
                $links = !empty($field['links']) && is_array($field['links']) ? $field['links'] : null;
                $rows[] = [
                    'name' => $field['name'],
                    'label' => $field['label'],
                    // Omit the "(url)" suffix here since the url(s) are rendered as real hyperlinks.
                    'value' => $this->CI->feature_info_formatter->toText($field, $url === null && $links === null),
                    'url' => $url,
                    'links' => $links
                ];
            }
            return $rows;
        }

        $rows = [];
        foreach ($properties as $name => $value) {
            $rows[] = [
                'name' => $name,
                'label' => $name,
                'value' => $this->formatTemplateValue($value)
            ];
        }
        return $rows;
    }

    /**
     * Populate the feature properties table, cloning one row per attribute.
     *
     * Supports templates using ${property_name}/${property_value} row
     * placeholders (one row per attribute), and falls back to a single
     * ${feature_properties} placeholder for legacy templates.
     *
     * @param \PhpOffice\PhpWord\TemplateProcessor $templateProcessor
     * @param array $rows Label/value pairs produced by buildPropertyRows()
     */
    protected function replacePropertiesTable($templateProcessor, array $rows)
    {
        try {
            $variables = $templateProcessor->getVariables();

            if (in_array('property_name', $variables, true)) {
                $nameVar = 'property_name';
                $valueVar = 'property_value';
                $rowSuffix = '#';
            } elseif (in_array('property_name#1', $variables, true)) {
                $nameVar = 'property_name#1';
                $valueVar = 'property_value#1';
                $rowSuffix = '#1#';
            } else {
                // Legacy template without a dedicated properties table.
                if (in_array('feature_properties', $variables, true)) {
                    $templateProcessor->setValue('feature_properties', $this->formatPropertiesAsText($rows));
                }
                return;
            }

            if (empty($rows)) {
                $templateProcessor->setValue($nameVar, 'No properties available');
                $templateProcessor->setValue($valueVar, '');
                return;
            }

            $templateProcessor->cloneRow($nameVar, count($rows));

            $rowIndex = 1;
            foreach ($rows as $row) {
                $templateProcessor->setValue("property_name$rowSuffix$rowIndex", $this->formatTemplateValue($row['label']));
                $valueMacro = "property_value$rowSuffix$rowIndex";
                if (!empty($row['is_files'])) {
                    $this->setFilesImageValue($templateProcessor, $valueMacro, $row['image']);
                } elseif (!empty($row['links'])) {
                    $this->setHyperlinkListValue($templateProcessor, $valueMacro, $row['links']);
                } elseif (!empty($row['url'])) {
                    $linkText = $this->formatTemplateValue($row['value']);
                    $this->setHyperlinkValue($templateProcessor, $valueMacro, $row['url'], $linkText !== '' ? $linkText : $row['url']);
                } else {
                    $templateProcessor->setValue($valueMacro, $this->formatTemplateValue($row['value']));
                }
                $rowIndex++;
            }
        } catch (Exception $e) {
            log_message('error', 'PHPWord: Properties table replacement failed: ' . $e->getMessage());
        }
    }

    /**
     * Format properties as a single "label: value, ..." string (legacy fallback).
     *
     * @param array $rows
     * @return string
     */
    private function formatPropertiesAsText(array $rows)
    {
        if (empty($rows)) {
            return '';
        }
        $parts = [];
        foreach ($rows as $row) {
            $parts[] = $row['label'] . ': ' . $this->formatTemplateValue($row['value']);
        }
        return implode(', ', $parts);
    }

    /**
     * Render a clickable hyperlink in place of a macro using a HYPERLINK field
     * code. Unlike PHPWord's Link element, this needs no document relationship
     * (setComplexValue()'s Link writer leaves an unresolved r:id, which corrupts
     * the docx), so it's safe to use directly against the raw template XML.
     *
     * @param \PhpOffice\PhpWord\TemplateProcessor $templateProcessor
     * @param string $macro Macro name without the surrounding delimiters
     * @param string $url Absolute http(s) URL, assumed already validated/sanitized
     * @param string $text Visible link text
     */
    protected function setHyperlinkValue($templateProcessor, $macro, $url, $text)
    {
        $templateProcessor->replaceXmlBlock($macro, $this->buildHyperlinkFieldXml($url, $text), 'w:r');
    }

    /**
     * Render several hyperlinks (one per line) in place of a single macro.
     *
     * @param \PhpOffice\PhpWord\TemplateProcessor $templateProcessor
     * @param string $macro Macro name without the surrounding delimiters
     * @param array $links List of ['value' => text, 'url' => url]
     */
    protected function setHyperlinkListValue($templateProcessor, $macro, array $links)
    {
        $parts = [];
        foreach ($links as $link) {
            if (empty($link['url'])) {
                continue;
            }
            $text = isset($link['value']) && $link['value'] !== '' ? $link['value'] : $link['url'];
            $parts[] = $this->buildHyperlinkFieldXml($link['url'], $text);
        }
        if (empty($parts)) {
            return;
        }
        $templateProcessor->replaceXmlBlock($macro, implode('<w:r><w:br/></w:r>', $parts), 'w:r');
    }

    /**
     * Embed the files-field image directly in the value cell. Falls back to a
     * plain hyperlink to the image if it cannot be fetched/read.
     *
     * @param \PhpOffice\PhpWord\TemplateProcessor $templateProcessor
     * @param string $macro Macro name without the surrounding delimiters
     * @param string|null $imageUrl
     */
    protected function setFilesImageValue($templateProcessor, $macro, $imageUrl)
    {
        if (empty($imageUrl)) {
            $templateProcessor->setValue($macro, '');
            return;
        }
        try {
            $imageSize = @getimagesize($imageUrl);
            if ($imageSize === false || $imageSize[0] <= 0 || $imageSize[1] <= 0) {
                throw new UnexpectedValueException('Image dimensions could not be read');
            }
            $width = $this->CI->config->item('phpword_files_image_width') ?: 150;
            $height = (int)round($width * ($imageSize[1] / $imageSize[0]));
            $templateProcessor->setImageValue($macro, [
                'path' => $imageUrl,
                'width' => $width,
                'height' => $height
            ]);
        } catch (Exception $e) {
            log_message('error', 'PHPWord: Files image embed failed, falling back to link: ' . $e->getMessage());
            $this->setHyperlinkValue($templateProcessor, $macro, $imageUrl, $imageUrl);
        }
    }

    /**
     * Build a HYPERLINK field code XML fragment for a single link.
     *
     * @param string $url
     * @param string $text
     * @return string
     */
    private function buildHyperlinkFieldXml($url, $text)
    {
        $instr = 'HYPERLINK &quot;' . htmlspecialchars((string)$url, ENT_QUOTES | ENT_XML1, 'UTF-8') . '&quot; ';
        $displayText = htmlspecialchars((string)$text, ENT_QUOTES | ENT_XML1, 'UTF-8');

        return '<w:fldSimple w:instr="' . $instr . '">'
            . '<w:r><w:rPr><w:rStyle w:val="Hyperlink"/><w:color w:val="0563C1"/><w:u w:val="single"/></w:rPr>'
            . '<w:t xml:space="preserve">' . $displayText . '</w:t></w:r>'
            . '</w:fldSimple>';
    }

    /**
     * Replace map image in template
     * 
     * @param \PhpOffice\PhpWord\TemplateProcessor $templateProcessor
     * @param string $imagePath Path to the map image
     */
    protected function replaceMapImage($templateProcessor, $imagePath)
    {
        if (file_exists($imagePath)) {
            $imageSize = getimagesize($imagePath);
            if ($imageSize === false || $imageSize[0] <= 0 || $imageSize[1] <= 0) {
                throw new UnexpectedValueException('Map image dimensions could not be read');
            }
            $width = $this->CI->config->item('phpword_map_image_width') ?: 500;
            $height = (int)round($width * ($imageSize[1] / $imageSize[0]));
            $templateProcessor->setImageValue('map_image', [
                'path' => $imagePath,
                'width' => $width,
                'height' => $height
            ]);
        }
    }

    private function formatTemplateValue($value)
    {
        if ($value === null) {
            return '';
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_scalar($value)) {
            return (string)$value;
        }
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    
    /**
     * Output document for download
     * 
     * @param \PhpOffice\PhpWord\TemplateProcessor|\PhpOffice\PhpWord\PhpWord $document
     * @param string $filename Download filename
     * @param string $format Output format
     */
    public function downloadDocument($document, $filename, $format = 'Word2007')
    {
        // Set headers for download
        $mimeTypes = [
            'Word2007' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'PDF' => 'application/pdf',
            'HTML' => 'text/html'
        ];
        
        $mimeType = isset($mimeTypes[$format]) ? $mimeTypes[$format] : $mimeTypes['Word2007'];
        
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        if ($document instanceof \PhpOffice\PhpWord\TemplateProcessor) {
            $tempFile = $this->getTempFile('phpword');
            $document->saveAs($tempFile);
            readfile($tempFile);
            unlink($tempFile);
        } else if ($document instanceof \PhpOffice\PhpWord\PhpWord) {
            $writer = \PhpOffice\PhpWord\IOFactory::createWriter($document, $format);
            $writer->save('php://output');
        }
    }
    
    /**
     * Get temporary file path (cross-platform)
     * 
     * @param string $prefix File prefix
     * @return string Temporary file path
     */
    private function getTempFile($prefix = 'temp')
    {
        $tempDir = sys_get_temp_dir();
        return $tempDir . DIRECTORY_SEPARATOR . $prefix . '_' . uniqid() . '.tmp';
    }
    
}
