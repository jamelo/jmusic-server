<?php

/*
 *************************************************
 *	Streams a specified file to the client
 *************************************************
*/

set_time_limit(3000);

require_once('inc/config.php');
require_once('inc/util.php');
  
if (isset($_GET['s']) && $config['require_login'])
{
	session_id($_GET['s']);
	session_start();
	
	if ($_SESSION[$config['session_name']]["ip"] != $_SERVER["REMOTE_ADDR"])
		die("You are not permitted to access this page.");
}
else
{
	session_start();
	
	if ($config['require_login']) ensure_login();
}

$abs_path = realpath(formatFolder($config['base_dir'] . "/" . $_GET["file"]));
$base_dir = realpath($config['base_dir']);
	
if (substr($abs_path, 0, strlen($base_dir)) === $base_dir)
	$filePath = $abs_path;
else
	die('File does not exist.');

$fileName = substr($filePath, strrpos($filePath, "/") + 1);
 
if (strstr($filePath, "/../") || strstr($filePath, "/./"))
    exit;

if (!file_exists($filePath)) die();

header("Content-Disposition: atachment; filename=\"$fileName\"");
header("Content-Type: application/octet-stream");
header("Content-Length: " . filesize($filePath));
header("Pragma: no-cache");
header("Expires: 0");

$file = @fopen($filePath,"rb");

if ($file)
{
	while(!feof($file))
	{
		print(fread($file, 8192));
		flush();
		
		if (connection_status() != 0)
		{
			@fclose($file);
			die();
		}
	}
	
	@fclose($file);
}

exit();
?>