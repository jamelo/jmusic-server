<?php

$config['base_dir'] = "music";					//Directory to search in for files
$config['base_url'] = "http://localhost/jmusic/";			//This is the URL at which the website will be situated. Required for printing the proper URL when exporting M3U playlist files

$config['db_host'] = '';				//Database server host name
$config['db_user'] = '';					//Database server username
$config['db_password'] = '';		//Database server password
$config['db_name'] = '';				//Name of the database
$config['db_table_prefix'] = 'ms';				//Prefix to prepend to table names

//Password cryptographic secret. Choose a random string (does not need to be remembered, 20 chars or longer). Keep this value secret to ensure security.
$config['password_secret'] = '';

//Cryptographic secret for generating registration keys. Choose a random string (does not need to be remembered, 20 chars or longer).
//Keep this value secret to ensure security. Use a different value than the one used for password_secret.
$config['registration_secret'] = '';			

$config['session_name'] = 'jmusic_server'; 		//Namespace for session data

$config['require_login'] = false;				//Set to true to require users to log in to view the site at all
$config['open_registration'] = false;			//Should registration be open to the public?

$config['file_types'] = array('mp3', 'wav', 'aac', 'm4a', 'mp4', 'aif',  'flac', 'ogg', 'txt');	//extensions of files that are permitted to be displayed on the site

?>