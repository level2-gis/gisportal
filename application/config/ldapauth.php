<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
|--------------------------------------------------------------------------
| LICENSE
|--------------------------------------------------------------------------
|    This file is part of ldap_auth.php
|
|    ldap_auth is free software: you can redistribute it and/or modify
|    it under the terms of the GNU General Public License as published by
|    the Free Software Foundation, either version 3 of the License, or
|    (at your option) any later version.
|
|    ldap_auth is distributed in the hope that it will be useful,
|    but WITHOUT ANY WARRANTY; without even the implied warranty of
|    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
|    GNU General Public License for more details.
|
|    You should have received a copy of the GNU General Public License
|    along with ldap_auth.  If not, see <http://www.gnu.org/licenses/>.
| 
|--------------------------------------------------------------------------
| LDAP configuration
|--------------------------------------------------------------------------
| Author: Dwayne Hale
|This configuration file belongs to the LDAP library ldap_auth.php
|
|
|
| If these are not set then your LDAP auths will not work.
| If something in this file confuses you, you probably shouldn't be editing it.
|
*/
$config['ldap_server']="ldaps://127.0.0.1"; //set this to your LDAP server name.
$config['ldap_port']="686";
$config['user_prefix']="AD\\"; //if you specify your domain you must escape the backslash '\' with a backslash '\\'
$config['user_suffix'] = NULL;
$config['dc'] = "";
