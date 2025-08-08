<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * RSS Cache Management Controller
 * Provides administrative interface for managing RSS feed cache
 */
class Rss_cache extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        
        // Ensure only admin users can access this
        if (!$this->ion_auth->logged_in()) {
            redirect('/auth/login');
        }
        
        if (!$this->ion_auth->is_admin()) {
            show_error('You must be an administrator to access this page.', 403);
        }
    }

    /**
     * Display RSS cache status and management options
     */
    public function index()
    {
        $data['title'] = 'RSS Cache Management';
        $data['cache_enabled'] = $this->config->item('rss_cache_enabled');
        $data['cache_duration'] = $this->config->item('rss_cache_duration');
        $data['cache_dir'] = $this->config->item('rss_cache_dir');
        $data['rss_url'] = $this->config->item('rss_feed_url');
        
        // Get cache file information
        $cache_dir = $this->config->item('rss_cache_dir');
        $data['cache_files'] = [];
        
        if (is_dir($cache_dir)) {
            $files = glob($cache_dir . '*.cache');
            foreach ($files as $file) {
                $data['cache_files'][] = [
                    'name' => basename($file),
                    'size' => filesize($file),
                    'modified' => filemtime($file),
                    'age' => time() - filemtime($file)
                ];
            }
        }
        
        $this->load->view('admin/rss_cache_management', $data);
    }

    /**
     * Clear all RSS cache files
     */
    public function clear_all()
    {
        try {
            // Load the cached RSS parser
            $this->load->library('rss_parser_cached');
            $this->rss_parser_cached->clearAllCache();
            
            $this->session->set_flashdata('message', 'All RSS cache files have been cleared successfully.');
            $this->session->set_flashdata('message_type', 'success');
        } catch (Exception $e) {
            $this->session->set_flashdata('message', 'Error clearing cache: ' . $e->getMessage());
            $this->session->set_flashdata('message_type', 'error');
        }
        
        redirect('rss_cache');
    }

    /**
     * Clear cache for specific RSS feed
     */
    public function clear_feed()
    {
        $rss_url = $this->config->item('rss_feed_url');
        
        if (empty($rss_url)) {
            $this->session->set_flashdata('message', 'No RSS feed URL configured.');
            $this->session->set_flashdata('message_type', 'warning');
            redirect('rss_cache');
            return;
        }

        try {
            // Load the cached RSS parser with current configuration
            $rss_config = [
                'url' => $rss_url,
                'limit' => $this->config->item('rss_feed_limit'),
                'cache_enabled' => $this->config->item('rss_cache_enabled'),
                'cache_duration' => $this->config->item('rss_cache_duration'),
                'cache_dir' => $this->config->item('rss_cache_dir')
            ];
            
            $this->load->library('rss_parser_cached', $rss_config);
            $this->rss_parser_cached->clearCache();
            
            $this->session->set_flashdata('message', 'RSS feed cache has been cleared successfully.');
            $this->session->set_flashdata('message_type', 'success');
        } catch (Exception $e) {
            $this->session->set_flashdata('message', 'Error clearing cache: ' . $e->getMessage());
            $this->session->set_flashdata('message_type', 'error');
        }
        
        redirect('rss_cache');
    }

    /**
     * Force refresh RSS feed (clear cache and reload)
     */
    public function refresh_feed()
    {
        $rss_url = $this->config->item('rss_feed_url');
        
        if (empty($rss_url)) {
            $this->session->set_flashdata('message', 'No RSS feed URL configured.');
            $this->session->set_flashdata('message_type', 'warning');
            redirect('rss_cache');
            return;
        }

        try {
            // Clear cache first
            $rss_config = [
                'url' => $rss_url,
                'limit' => $this->config->item('rss_feed_limit'),
                'cache_enabled' => $this->config->item('rss_cache_enabled'),
                'cache_duration' => $this->config->item('rss_cache_duration'),
                'cache_dir' => $this->config->item('rss_cache_dir')
            ];
            
            $this->load->library('rss_parser_cached', $rss_config);
            $this->rss_parser_cached->clearCache();
            
            // Force fresh load
            $fresh_data = $this->rss_parser_cached->parse();
            
            if (!empty($fresh_data)) {
                $this->session->set_flashdata('message', 'RSS feed has been refreshed successfully. Found ' . count($fresh_data['item']) . ' items.');
                $this->session->set_flashdata('message_type', 'success');
            } else {
                $this->session->set_flashdata('message', 'RSS feed refreshed but no data received. Please check the feed URL.');
                $this->session->set_flashdata('message_type', 'warning');
            }
        } catch (Exception $e) {
            $this->session->set_flashdata('message', 'Error refreshing feed: ' . $e->getMessage());
            $this->session->set_flashdata('message_type', 'error');
        }
        
        redirect('rss_cache');
    }

    /**
     * AJAX endpoint to get cache status
     */
    public function status()
    {
        $this->output->set_content_type('application/json');
        
        $cache_dir = $this->config->item('rss_cache_dir');
        $cache_files = [];
        $total_size = 0;
        
        if (is_dir($cache_dir)) {
            $files = glob($cache_dir . '*.cache');
            foreach ($files as $file) {
                $size = filesize($file);
                $total_size += $size;
                $cache_files[] = [
                    'name' => basename($file),
                    'size' => $size,
                    'modified' => filemtime($file),
                    'age' => time() - filemtime($file)
                ];
            }
        }
        
        $status = [
            'cache_enabled' => $this->config->item('rss_cache_enabled'),
            'cache_duration' => $this->config->item('rss_cache_duration'),
            'total_files' => count($cache_files),
            'total_size' => $total_size,
            'files' => $cache_files
        ];
        
        $this->output->set_output(json_encode($status));
    }
}
