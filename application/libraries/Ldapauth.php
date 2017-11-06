<?php

if (!defined('BASEPATH')) exit('No direct script access allowed'); // This Prevents browsers from directly accessing this PHP file.

class Ldapauth {
	   protected $CI;

           public function __construct() {
                $this->CI =& get_instance();
       		$this->CI->config->load('ldapauth');
	   }

	   public function auth($username, $password)
           {

		if (!extension_loaded('ldap')) {
			return FALSE;
		}
			
		$server = $this->CI->config->item('ldap_server');
		$port = $this->CI->config->item('ldap_port');
                $user_prefix = $this->CI->config->item('user_prefix');
                $user_suffix = $this->CI->config->item('user_suffix');
                
		$dc = $this->CI->config->item('dc');
                
		$conn = ldap_connect($server,$port);
		$bind = @ldap_bind($conn,$user_prefix.$username.$user_suffix, $password);
            
		@ldap_close($conn);

                if ($bind){
                    return TRUE;
                    }
                else{
                    return FALSE;
                    }

	    }

	    public function info($username) {
	    }

}
