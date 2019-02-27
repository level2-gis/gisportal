<?php

class LanguageLoader
{
    function initialize()
    {
        $ci =& get_instance();
        $defLang = $ci->config->item('language');

        $siteCode = $ci->session->userdata('lang');
        if ($siteCode) {
            $siteLang = get_language_name($siteCode);
            $ci->lang->load('auth', $siteLang);
            $ci->lang->load('gisportal', $siteLang);
        } else {
            $defCode = get_code($defLang);
            $ci->session->set_userdata('lang', $defCode);
            $ci->lang->load('auth', $defLang);
            $ci->lang->load('gisportal', $defLang);
        }
    }
}
