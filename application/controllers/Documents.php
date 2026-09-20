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

    /**
    * GET /documents/feature_report
     * Generate a DOCX report for one feature in the authenticated project.
     */
    public function feature_report()
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

            $user = $this->ion_auth->user()->row();
            $displayName = !empty($user->user_display_name) ? $user->user_display_name : $user->username;
            $report = $this->wms_report_service->collectFeatureReport($input, $displayName);
            $temporaryFiles = $report['temporary_files'];
            $document = $this->_generateDocument($report['data'], $report['template']);
            $filename = $this->_buildDownloadFilename($report['data'], $input);
            $this->phpword_lib->downloadDocument($document, $filename, 'Word2007');
        } catch (InvalidArgumentException $e) {
            return $this->_jsonError(422, $e->getMessage());
        } catch (DomainException $e) {
            return $this->_jsonError(403, $e->getMessage());
        } catch (OutOfBoundsException $e) {
            return $this->_jsonError(404, $e->getMessage());
        } catch (UnexpectedValueException $e) {
            log_message('error', 'Feature report project failure: ' . $e->getMessage());
            return $this->_jsonError(502, 'Project could not be loaded');
        } catch (Exception $e) {
            log_message('error', 'Feature report generation failed: ' . $e->getMessage());
            return $this->_jsonError(502, 'Feature report generation failed');
        } finally {
            foreach ($temporaryFiles as $temporaryFile) {
                if (is_file($temporaryFile)) {
                    @unlink($temporaryFile);
                }
            }
        }
    }

    private function _generateDocument(array $reportData, $templateName)
    {
        $templatePath = APPPATH . 'templates' . DIRECTORY_SEPARATOR . $templateName;
        if (!is_file($templatePath)) {
            throw new UnexpectedValueException('Report template was not found');
        }

        $templateProcessor = $this->phpword_lib->loadTemplate($templatePath);
        return $this->phpword_lib->replaceWmsData($templateProcessor, $reportData);
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
