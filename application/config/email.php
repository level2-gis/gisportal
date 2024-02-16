<?php
/**
 * Email configuration to use Google SMTP server
 * Set own Google account username and password
 */
$config = array(
    'protocol'  => 'smtp',
    'smtp_host' => 'ssl://smtp.googlemail.com',
    'smtp_port' => 465,
    'smtp_user' => 'gmail email',
    'smtp_pass' => 'gmail password',
    'charset'   => 'utf-8',
	'crlf' 		=> "\r\n",
    'newline'   => "\r\n",
    'mailtype'  => 'html'
);
