<?php

/*
 *******************************************************
 *	Prints the contents of a playlist into an M3U
 *	formatted file.
 *******************************************************
*/

session_start();


require_once('inc/config.php');
require_once('inc/util.php');

if ($config['require_login']) ensure_login();

$_SESSION[$config['session_name']]['ip'] = $_SERVER['REMOTE_ADDR'];


if (isset($_GET['playlist']) && $_GET['playlist'] > 0)
	$playlist = $_GET['playlist'];
else
	die('No playlist selected.');

$con = db_connect($config['db_name']);

$cmd = $con->prepare("SELECT * FROM $config[db_table_prefix]_playlists WHERE listID = ?");
$cmd->bind_param('i', $playlist);
$cmd->execute();

$row = fetch_assoc($cmd);

if (!is_logged_in() || $row['owner'] != $_SESSION[$config['session_name']]['username'])
{
	if ($row['public'])
		$action = '';
	else
	{
		$cmd->close();
		$con->close();
		die('You do not have permission to view this playlist');
	}
}

$cmd->close();

$contents_cmd = $con->prepare("SELECT * FROM $config[db_table_prefix]_playlist_songs WHERE playlistID = ?");
$contents_cmd->bind_param('i', $playlist);
$contents_cmd->execute();

header('Content-Disposition: atachment; filename=playlist.m3u');
header('Content-Type: application/octet-stream');
header('Pragma: no-cache');
header('Expires: 0');

print "#EXTM3U\r\n";

if ($config['require_login']) 
	$session_info = session_id();
else
	$session_info = '';

while ($row = fetch_assoc($contents_cmd))
{
	print "#EXTINF:-1, " . substr($row['filename'], 0, strrpos($row['filename'], '.')) . "\r\n";
	print rtrim($config['base_url'], "/") . "/getfile.php?s=$session_info&file=" . urlencode("$row[path]/$row[filename]") . "\r\n";
}

?>