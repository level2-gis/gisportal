<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * 
 * RSS Parser Library.
 * https://github.com/lanuma/simple-rss-parser
 * 
 * @author Angga Lanuma <me@lanuma.web.id>
 */
class Rss_parser
{

    private $RSS_URl;
    private $xml;
    private $DATA = array();
    private $limit = 10;


    function __construct($config = array())
    {
        empty($config) OR $this->init($config);

        $this->loadRss();
    }

    public function init($config = array())
    {
        if (!empty($config['url']))
            $this->RSS_URl = $config['url'];

        if (!empty($config['limit']))
            $this->limit = $config['limit'];
    }

    /**
     * proses parsing rss
     * 
     * @return array
     */
    public function parse()
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
        if (empty($this->RSS_URl))
        {
            throw new Rss_libException('RSS URL belum di set');
        } else
        {
            $data = $this->httpRequest($this->RSS_URl);
        }

        $this->xml = new \SimpleXMLElement($data, LIBXML_NOERROR | LIBXML_NOWARNING);

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
            curl_setopt($ch, CURLOPT_USERAGENT, 'RSS Parser/1.0 (by Angga Lanuma)');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);

            $content = curl_exec($ch);
            curl_close($ch);

            return $content;
        } else
        {
            return file_get_contents($url);
        }
    }

    public function setUrl($url)
    {
        $this->RSS_URl = $url;

        return $this;
    }
    
    public function setLimit($limit)
    {
        $this->limit = $limit;
        
        return $this;
    }

    public function getUrl()
    {
        return $this->RSS_URl;
    }

}

class Rss_libException extends Exception
{
    
}
