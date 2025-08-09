<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * RSS Cache CLI Controller
 * 
 * Command line interface for managing RSS cache
 * Usage: php index.php rss_cache_cli [action]
 * Actions: clear, clear_all, status, refresh
 */
class Rss_cache_cli extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        
        // Only allow CLI access
        if (!$this->input->is_cli_request()) {
            show_error('This script can only be run from the command line.');
        }
        
        $this->load->helper('rss');
    }

    /**
     * Default method - show help
     */
    public function index()
    {
        $this->help();
    }

    /**
     * Display help information
     */
    public function help()
    {
        echo "RSS Cache Management CLI\n";
        echo "========================\n\n";
        echo "Usage: php index.php rss_cache_cli [action]\n\n";
        echo "Available actions:\n";
        echo "  status     - Show cache status and configuration\n";
        echo "  clear      - Clear RSS feed cache\n";
        echo "  clear_all  - Clear all cache files\n";
        echo "  refresh    - Force refresh RSS feed\n";
        echo "  help       - Show this help message\n\n";
        echo "Examples:\n";
        echo "  php index.php rss_cache_cli status\n";
        echo "  php index.php rss_cache_cli clear\n";
        echo "  php index.php rss_cache_cli refresh\n\n";
    }

    /**
     * Show cache status
     */
    public function status()
    {
        echo "RSS Cache Status\n";
        echo "================\n\n";
        
        $cache_enabled = $this->config->item('rss_cache_enabled');
        $cache_duration = $this->config->item('rss_cache_duration') ?: 3600;
        $cache_dir = $this->config->item('rss_cache_dir') ?: APPPATH . 'cache/rss/';
        $rss_url = $this->config->item('rss_feed_url');
        
        echo "Configuration:\n";
        echo "  RSS URL: " . ($rss_url ?: 'Not configured') . "\n";
        echo "  Cache Enabled: " . ($cache_enabled ? 'Yes' : 'No') . "\n";
        echo "  Cache Duration: {$cache_duration} seconds (" . round($cache_duration/60, 1) . " minutes)\n";
        echo "  Cache Directory: {$cache_dir}\n\n";
        
        // Get cache information
        $cache_info = get_rss_cache_info($cache_dir);
        
        echo "Cache Files:\n";
        echo "  Total Files: {$cache_info['total_files']}\n";
        echo "  Total Size: " . format_rss_cache_size($cache_info['total_size']) . "\n\n";
        
        if (!empty($cache_info['files'])) {
            echo "File Details:\n";
            foreach ($cache_info['files'] as $file) {
                $age_hours = round($file['age'] / 3600, 1);
                $is_expired = $file['age'] > $cache_duration;
                $status = $is_expired ? '[EXPIRED]' : '[VALID]';
                $size = format_rss_cache_size($file['size']);
                
                echo "  {$file['name']} - {$size} - {$age_hours}h old {$status}\n";
            }
        } else {
            echo "  No cache files found.\n";
        }
        
        echo "\n";
    }

    /**
     * Clear RSS cache
     */
    public function clear()
    {
        $rss_url = $this->config->item('rss_feed_url');
        
        if (empty($rss_url)) {
            echo "Error: No RSS feed URL configured.\n";
            return;
        }

        echo "Clearing RSS cache for: {$rss_url}\n";
        
        try {
            $rss_config = [
                'url' => $rss_url,
                'limit' => $this->config->item('rss_feed_limit') ?: 10,
                'cache_enabled' => TRUE,
                'cache_duration' => $this->config->item('rss_cache_duration') ?: 3600,
                'cache_dir' => $this->config->item('rss_cache_dir') ?: APPPATH . 'cache/rss/'
            ];
            
            $this->load->library('rss_parser_cached', $rss_config);
            $this->rss_parser_cached->clearCache();
            
            echo "RSS cache cleared successfully.\n";
        } catch (Exception $e) {
            echo "Error clearing cache: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Clear all cache files
     */
    public function clear_all()
    {
        $cache_dir = $this->config->item('rss_cache_dir') ?: APPPATH . 'cache/rss/';
        
        echo "Clearing all RSS cache files from: {$cache_dir}\n";
        
        $cleared = clear_rss_cache($cache_dir);
        
        echo "Cleared {$cleared} cache files.\n";
    }

    /**
     * Force refresh RSS feed
     */
    public function refresh()
    {
        $rss_url = $this->config->item('rss_feed_url');
        
        if (empty($rss_url)) {
            echo "Error: No RSS feed URL configured.\n";
            return;
        }

        echo "Refreshing RSS feed: {$rss_url}\n";
        
        try {
            $rss_config = [
                'url' => $rss_url,
                'limit' => $this->config->item('rss_feed_limit') ?: 10,
                'cache_enabled' => TRUE,
                'cache_duration' => $this->config->item('rss_cache_duration') ?: 3600,
                'cache_dir' => $this->config->item('rss_cache_dir') ?: APPPATH . 'cache/rss/'
            ];
            
            // Clear cache first
            $this->load->library('rss_parser_cached', $rss_config);
            $this->rss_parser_cached->clearCache();
            
            // Force fresh load
            $fresh_data = $this->rss_parser_cached->parse();
            
            if (!empty($fresh_data)) {
                $item_count = isset($fresh_data['item']) ? count($fresh_data['item']) : 0;
                echo "RSS feed refreshed successfully. Found {$item_count} items.\n";
            } else {
                echo "RSS feed refreshed but no data received. Please check the feed URL.\n";
            }
        } catch (Exception $e) {
            echo "Error refreshing feed: " . $e->getMessage() . "\n";
        }
    }
}
