<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * RSS Helper Functions
 * 
 * Helper functions for RSS feed management with caching support
 */

if (!function_exists('load_rss_feed'))
{
    /**
     * Load RSS feed with caching support
     * 
     * @param object $CI CodeIgniter instance
     * @return array|false RSS data or false if no RSS configured
     */
    function load_rss_feed($CI)
    {
        // Check if RSS feed URL is configured
        if (empty($CI->config->item('rss_feed_url'))) {
            return false;
        }
        
        // Prepare RSS configuration
        $rss_config = [
            'url' => $CI->config->item('rss_feed_url'),
            'limit' => $CI->config->item('rss_feed_limit') ?: 10,
            'cache_enabled' => $CI->config->item('rss_cache_enabled') !== FALSE,
            'cache_duration' => $CI->config->item('rss_cache_duration') ?: 3600,
            'cache_dir' => $CI->config->item('rss_cache_dir') ?: APPPATH . 'cache/rss/'
        ];
        
        try {
            // Always use cached parser (it handles both cached and non-cached modes)
            $CI->load->library('rss_parser_cached', $rss_config);
            $rss_data = $CI->rss_parser_cached->parse();
            
            // Add last login timestamp for new item detection
            if (!empty($rss_data)) {
                $rss_data['last_login'] = $CI->session->userdata('old_last_login');
                return ['rss' => $rss_data];
            }
            
        } catch (Exception $e) {
            // Log error and return false
            log_message('error', 'RSS Feed Error: ' . $e->getMessage());
        }
        
        return false;
    }
}

if (!function_exists('format_rss_cache_size'))
{
    /**
     * Format cache file size in human readable format
     * 
     * @param int $bytes File size in bytes
     * @return string Formatted size
     */
    function format_rss_cache_size($bytes)
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' B';
        }
    }
}

if (!function_exists('get_rss_cache_info'))
{
    /**
     * Get RSS cache information
     * 
     * @param string $cache_dir Cache directory path
     * @return array Cache information
     */
    function get_rss_cache_info($cache_dir)
    {
        $info = [
            'total_files' => 0,
            'total_size' => 0,
            'files' => []
        ];
        
        if (!is_dir($cache_dir)) {
            return $info;
        }
        
        $files = glob($cache_dir . '*.cache');
        
        foreach ($files as $file) {
            $size = filesize($file);
            $info['total_size'] += $size;
            $info['files'][] = [
                'name' => basename($file),
                'size' => $size,
                'modified' => filemtime($file),
                'age' => time() - filemtime($file)
            ];
        }
        
        $info['total_files'] = count($info['files']);
        
        return $info;
    }
}

if (!function_exists('clear_rss_cache'))
{
    /**
     * Clear RSS cache files
     * 
     * @param string $cache_dir Cache directory path
     * @param string $pattern File pattern to clear (optional)
     * @return int Number of files cleared
     */
    function clear_rss_cache($cache_dir, $pattern = '*.cache')
    {
        $cleared = 0;
        
        if (!is_dir($cache_dir)) {
            return $cleared;
        }
        
        $files = glob($cache_dir . $pattern);
        
        foreach ($files as $file) {
            if (file_exists($file) && unlink($file)) {
                $cleared++;
            }
        }
        
        return $cleared;
    }
}
