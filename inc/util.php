<?php

function formatFolder($string)
{
	$formatedString = strtr($string, "\\", "/");
	$formatedString = strtr($formatedString, "//", "/");
	
	if (substr($formatedString, 0, 1) === "/")
		$formatedString = substr($formatedString, 1);

	return $formatedString;
}

function parentDir($currentDir)
{
	$currentDir = str_replace("\\", "/", $currentDir);
	while(strpos($currentDir, "//") !== false) $currentDir = str_replace("//", "/", $currentDir);
	
	$lastSlash = strrpos($currentDir, "/");
	
	if ($lastSlash === false)
		return "";
	else
		return substr($currentDir, 0, $lastSlash);
}

function friendly_file_size($size_in_bytes)
{
	if ($size_in_bytes >= 1073741824)
	{
		$fileSize = round($size_in_bytes / 10737418.24) * 0.01;
		$fileSizeUnit = "GB";
	}
	elseif ($size_in_bytes >= 1048576)
	{
		$fileSize = round($size_in_bytes / 10485.76) * 0.01;
		$fileSizeUnit = "MB";
	}
	elseif ($size_in_bytes >= 1024)
	{
		$fileSize = round($size_in_bytes / 10.24) * 0.01;
		$fileSizeUnit = "kB";
	}
	else
	{
		$fileSize = $size_in_bytes;
		$fileSizeUnit = "B";
	}
	
	return "$fileSize$fileSizeUnit";
}

function db_connect($db)
{
	include("config.php");

	$con = new mysqli($config['db_host'], $config['db_user'], $config['db_password'], $db);
	
	if (mysqli_connect_errno())
		die("Connecting to database failed: %s\n" . mysqli_connect_error());
	
	return $con;
}

//Executes a statement and returns the result as an associative array.
function fetch_assoc($stmt)
{
    $meta = $stmt->result_metadata();
    while ($field = $meta->fetch_field())
    {
        $params[] = &$row[$field->name];
    }

    call_user_func_array(array($stmt, 'bind_result'), $params);

	$c = array();

    if ($stmt->fetch()) {
        foreach($row as $key => $val)
        {
            $c[$key] = $val;
        }
    }
    
    return $c;
}

function execute_scalar($stmt)
{
	$stmt->execute();
	$stmt->bind_result($result);
	$stmt->fetch();
	$output = $result;
	
	return $output;	
}

function is_logged_in()
{
	include("config.php");

	return (isset($_SESSION[$config['session_name']]) && isset($_SESSION[$config['session_name']]['userID']));
}

function ensure_login()
{
	include("config.php");

	if (!(isset($_SESSION[$config['session_name']]) && isset($_SESSION[$config['session_name']]['userID'])))
	{
		header('Location: login.php');
		exit;
	}
}

function generate_passcode($username, $email)
{
	include("config.php");
	
	$hash = md5(strtolower($username) . $config['registration_secret'] . strtolower($email));
	return $hash;
}

function password_hash($password)
{
	include("config.php");
	
    $hashword = strrev(md5($password . $config['password_secret']));	
    return $hashword;
}

function random_string($length)
{
	$permissible_characters = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890`~!@#$%^&*()-_=+[]{}\\|;:'\",<.>/?";
	$pc_length = strlen($permissible_characters);
	
	$str = '';
	
	for ($i = 0; $i < $length; $i++)
		$str .= $permissible_characters[mt_rand(0, $pc_length - 1)];
	
	return $str;
}

?>