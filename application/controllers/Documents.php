<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Documents extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('phpword_lib');
        $this->load->library('wms_report_service');
    }

    public function csrf_token()
    {
        $this->output
            ->set_header('Cache-Control: private, no-store')
            ->set_header('X-Content-Type-Options: nosniff');

        if ($this->input->method() !== 'get') {
            $this->output->set_header('Allow: GET');
            return $this->_jsonError(405, 'GET required');
        }
        if (!$this->ion_auth->logged_in()) {
            return $this->_jsonError(401, 'Authentication required');
        }

        $enabled = (bool)$this->config->item('csrf_protection');
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'enabled' => $enabled,
                'name' => $enabled ? $this->security->get_csrf_token_name() : null,
                'hash' => $enabled ? $this->security->get_csrf_hash() : null
            ]));
    }

    /**
    * GET /documents/feature_report
     * Generate a DOCX report for one feature in the authenticated project.
     */
    public function feature_report()
    {
        $this->_runReport(function ($input, $displayName) {
            return $this->wms_report_service->collectFeatureReport($input, $displayName);
        }, function ($report, $input) {
            return [
                $this->_generateDocument($report['data'], $report['template']),
                $this->_buildDownloadFilename($report['data'], $input)
            ];
        }, 'Feature report');
    }

    /**
     * GET|POST /documents/location_report
     * Generate a DOCX report of all features intersecting one map location in the
     * requested layers. Location uses the client map context: bbox, width, height, i, j.
     */
    public function location_report()
    {
        $timeLimit = (int)$this->config->item('wms_location_report_time_limit');
        if ($timeLimit > 0) {
            @set_time_limit($timeLimit);
        }

        $this->_runReport(function ($input, $displayName) {
            return $this->wms_report_service->collectLocationReport($input, $displayName);
        }, function ($report) {
            $templateProcessor = $this->_loadReportTemplate($report['template']);
            return [
                $this->phpword_lib->replaceLocationData($templateProcessor, $report['data']),
                $this->_buildLocationFilename($report['data'])
            ];
        }, 'Location report');
    }

    private function _runReport(callable $collect, callable $render, $label)
    {
        $temporaryFiles = [];

        try {
            if (!$this->ion_auth->logged_in()) {
                return $this->_jsonError(401, 'Authentication required');
            }

            $input = $this->input->get(NULL, true);
            $postInput = $this->input->post(NULL, true);
            if (is_array($postInput)) {
                $input = array_merge(is_array($input) ? $input : [], $postInput);
            }
            $input = is_array($input) ? $input : [];

            $user = $this->ion_auth->user()->row();
            $displayName = !empty($user->user_display_name) ? $user->user_display_name : $user->username;
            $report = $collect($input, $displayName);
            $temporaryFiles = $report['temporary_files'];
            list($document, $filename) = $render($report, $input);
            $this->phpword_lib->downloadDocument($document, $filename, 'Word2007');
        } catch (InvalidArgumentException $e) {
            return $this->_jsonError(422, $e->getMessage());
        } catch (DomainException $e) {
            return $this->_jsonError(403, $e->getMessage());
        } catch (OutOfBoundsException $e) {
            return $this->_jsonError(404, $e->getMessage());
        } catch (UnexpectedValueException $e) {
            log_message('error', $label . ' project failure: ' . $e->getMessage());
            return $this->_jsonError(502, 'Project could not be loaded');
        } catch (Throwable $e) {
            log_message('error', $label . ' generation failed: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() . "\n" . $e->getTraceAsString());
            return $this->_jsonError(502, $label . ' generation failed');
        } finally {
            foreach ($temporaryFiles as $temporaryFile) {
                if (is_file($temporaryFile)) {
                    @unlink($temporaryFile);
                }
            }
        }
    }

    private function _loadReportTemplate($templateName)
    {
        $templatePath = APPPATH . 'templates' . DIRECTORY_SEPARATOR . $templateName;
        if (!is_file($templatePath)) {
            throw new UnexpectedValueException('Report template was not found');
        }
        return $this->phpword_lib->loadTemplate($templatePath);
    }

    private function _generateDocument(array $reportData, $templateName)
    {
        $templateProcessor = $this->_loadReportTemplate($templateName);
        return $this->phpword_lib->replaceWmsData($templateProcessor, $reportData);
    }

    private function _buildLocationFilename(array $reportData)
    {
        $parts = [];
        foreach (['project_name', 'location_x', 'location_y'] as $key) {
            $value = isset($reportData[$key]) && is_scalar($reportData[$key]) ? (string)$reportData[$key] : '';
            $value = trim(preg_replace('/[^A-Za-z0-9_.-]+/', '_', $value), '.-_');
            if ($value !== '') {
                $parts[] = $value;
            }
        }
        return 'location_' . (empty($parts) ? 'report' : implode('_', $parts)) . '.docx';
    }

    private function _buildDownloadFilename(array $reportData, array $input)
    {
        $layerName = isset($reportData['layer_name']) && is_scalar($reportData['layer_name'])
            ? trim((string)$reportData['layer_name'])
            : (isset($input['layer_id']) ? trim((string)$input['layer_id']) : 'layer');
        $featureId = isset($input['feature_id']) && is_scalar($input['feature_id'])
            ? trim((string)$input['feature_id'])
            : 'feature';

        $layerName = preg_replace('/[^A-Za-z0-9_.-]+/', '_', $layerName);
        $featureId = preg_replace('/[^A-Za-z0-9_.-]+/', '_', $featureId);

        $layerName = trim($layerName, '.-_');
        $featureId = trim($featureId, '.-_');

        if ($layerName === '') {
            $layerName = 'layer';
        }
        if ($featureId === '') {
            $featureId = 'feature';
        }

        return $layerName . '_' . $featureId . '.docx';
    }

    private function _jsonError($status, $message)
    {
        $this->output
            ->set_content_type('application/json')
            ->set_status_header($status)
            ->set_output(json_encode([
                'success' => false,
                'message' => $message
            ]));
    }
}
