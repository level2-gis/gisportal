<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * 
 * RSS Parser Library with Caching Support
 * Extended from original Rss_parser with file-based caching
 * 
 * @author Extended for GIS Portal
 * @original_author Angga Lanuma <me@lanuma.web.id>
 */
class Rss_parser_cached
{
    private $RSS_URL;
    private $xml;
    private $DATA = array();
    private $limit = 10;
    private $cache_duration = 3600; // 1 hour in seconds
    private $cache_dir;
    private $cache_enabled = true;

    function __construct($config = array())
    {
        // Set default cache directory
        $this->cache_dir = APPPATH . 'cache/rss/';
        
        // Create cache directory if it doesn't exist
        if (!is_dir($this->cache_dir)) {
            mkdir($this->cache_dir, 0755, true);
        }
        
        empty($config) OR $this->init($config);
        $this->loadRss();
    }

    public function init($config = array())
    {
        if (!empty($config['url']))
            $this->RSS_URL = $config['url'];

        if (!empty($config['limit']))
            $this->limit = $config['limit'];
            
        if (isset($config['cache_duration']))
            $this->cache_duration = $config['cache_duration'];
            
        if (isset($config['cache_enabled']))
            $this->cache_enabled = $config['cache_enabled'];
            
        if (!empty($config['cache_dir']))
            $this->cache_dir = $config['cache_dir'];
    }

    /**
     * Get cache file path based on RSS URL
     */
    private function getCacheFilePath()
    {
        $cache_key = md5($this->RSS_URL . $this->limit);
        return $this->cache_dir . 'rss_' . $cache_key . '.cache';
    }

    /**
     * Check if cache file exists and is still valid
     */
    private function isCacheValid()
    {
        if (!$this->cache_enabled) {
            return false;
        }
        
        $cache_file = $this->getCacheFilePath();
        
        if (!file_exists($cache_file)) {
            return false;
        }
        
        $cache_time = filemtime($cache_file);
        $current_time = time();
        
        return ($current_time - $cache_time) < $this->cache_duration;
    }

    /**
     * Get cached RSS data
     */
    private function getCachedData()
    {
        $cache_file = $this->getCacheFilePath();
        
        if (file_exists($cache_file)) {
            $cached_data = file_get_contents($cache_file);
            return unserialize($cached_data);
        }
        
        return false;
    }

    /**
     * Save RSS data to cache
     */
    private function setCachedData($data)
    {
        if (!$this->cache_enabled) {
            return;
        }
        
        $cache_file = $this->getCacheFilePath();
        $serialized_data = serialize($data);
        
        file_put_contents($cache_file, $serialized_data, LOCK_EX);
    }

    /**
     * Process RSS parsing with caching
     * 
     * @return array
     */
    public function parse()
    {
        // Try to get cached data first
        if ($this->isCacheValid()) {
            $cached_data = $this->getCachedData();
            if ($cached_data !== false) {
                log_message('debug', 'RSS Parser: Using cached data for ' . $this->RSS_URL);
                return $cached_data;
            }
        }

        // Parse fresh data
        $data = $this->parseFresh();
        
        // Cache the result
        if (!empty($data)) {
            $this->setCachedData($data);
            log_message('debug', 'RSS Parser: Cached fresh data for ' . $this->RSS_URL);
        }
        
        return $data;
    }

    /**
     * Parse RSS without caching (original logic)
     * 
     * @return array
     */
    private function parseFresh()
    {
        $data = array();
        if (!empty($this->xml))
        {
            $vidio = $this->xml->channel;
            $vidio_keys = array_keys((array) $vidio->item);
            $data = $this->_get_channel(get_object_vars($this->xml));
            $data['item'] = array();
            $counter = 1;
            foreach ($vidio->item as $vid)
            {
                $temp2 = array();

                foreach ($vidio_keys as $key)
                {
                    $temp2[$key] = (string) $vid->{$key};
                }
                $ns = $vid->getNamespaces(true);
                foreach (array_keys($ns) as $namespace)
                {
                    $child = get_object_vars($vid->children($ns[$namespace]));

                    foreach ($child as $c => $d)
                    {
                        if ((string) $c !== null)
                        {
                            $temp2[$namespace . '_' . $c] = (string) $d; 
                        }
                    }
                }
                $nss = !empty($ns['media']) ? $vid->children($ns['media']) : NULL;
                if (!empty($nss))
                {
                    foreach ($nss->content as $content)
                    {
                        $keys = array_keys((array) $content);

                        foreach ($keys as $key)
                        {
                            if ($key == 'player' || $key == 'thumbnail')
                            {
                                $temp2['media_' . $key] = (string) $content->{$key}->attributes()->url;
                            } else
                            {
                                $temp2['media_' . $key] = (string) $content->{$key};
                            }
                        }
                    }
                }
                unset($temp2['media_content']);
                array_push($data['item'], $temp2);

                if ($counter >= $this->limit) break;
                
                $counter++;
            }
        }
        return $data;
    }
    
    /**
     * get RSS channel
     * 
     * @return Array
     */
    private function _get_channel($rss)
    {
        $tmp = array();

        foreach ($rss['channel'] as $key => $var)
        {
            $tmp[$key] = (string) $var;
        }

        unset($tmp['item']);

        return $tmp;
    }

    private function loadRss()
    {
        if (empty($this->RSS_URL))
        {
            throw new Rss_libException('RSS URL belum di set');
        } else
        {
            // Try to get cached XML first
            $cache_file_xml = $this->cache_dir . 'xml_' . md5($this->RSS_URL) . '.cache';
            
            if ($this->cache_enabled && file_exists($cache_file_xml) && 
                (time() - filemtime($cache_file_xml)) < $this->cache_duration) {
                $data = file_get_contents($cache_file_xml);
                log_message('debug', 'RSS Parser: Using cached XML for ' . $this->RSS_URL);
            } else {
                $data = $this->httpRequest($this->RSS_URL);
                
                // Cache the raw XML
                if ($this->cache_enabled && !empty($data)) {
                    file_put_contents($cache_file_xml, $data, LOCK_EX);
                }
            }
        }

        if (!empty($data)) {
            $this->xml = new \SimpleXMLElement($data, LIBXML_NOERROR | LIBXML_NOWARNING);
        }

        return $this;
    }

    private static function httpRequest($url)
    {
        if (extension_loaded('curl'))
        {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_FAILONERROR, 1);
            if (!ini_get('open_basedir'))
            {
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            }
            curl_setopt($ch, CURLOPT_USERAGENT, 'RSS Parser/2.0 (GIS Portal with Cache)');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);

            $content = curl_exec($ch);
            
            if (curl_error($ch)) {
                log_message('error', 'RSS Parser cURL error: ' . curl_error($ch));
            }
            
            curl_close($ch);

            return $content;
        } else
        {
            return file_get_contents($url);
        }
    }

    public function setUrl($url)
    {
        $this->RSS_URL = $url;
        return $this;
    }
    
    public function setLimit($limit)
    {
        $this->limit = $limit;
        return $this;
    }

    public function getUrl()
    {
        return $this->RSS_URL;
    }
    
    /**
     * Set cache duration in seconds
     */
    public function setCacheDuration($seconds)
    {
        $this->cache_duration = $seconds;
        return $this;
    }
    
    /**
     * Enable or disable caching
     */
    public function setCacheEnabled($enabled)
    {
        $this->cache_enabled = $enabled;
        return $this;
    }
    
    /**
     * Clear cache for current RSS URL
     */
    public function clearCache()
    {
        $cache_file = $this->getCacheFilePath();
        $cache_file_xml = $this->cache_dir . 'xml_' . md5($this->RSS_URL) . '.cache';
        
        if (file_exists($cache_file)) {
            unlink($cache_file);
        }
        
        if (file_exists($cache_file_xml)) {
            unlink($cache_file_xml);
        }
        
        return $this;
    }
    
    /**
     * Clear all RSS cache files
     */
    public function clearAllCache()
    {
        $files = glob($this->cache_dir . 'rss_*.cache');
        $xml_files = glob($this->cache_dir . 'xml_*.cache');
        
        foreach (array_merge($files, $xml_files) as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
        
        return $this;
    }
}

class Rss_libException extends Exception
{
    
}
