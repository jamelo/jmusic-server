<?php

/*
 ******************************************************
 *	Prints the contents of a playlist into an XML file
 *	that is compatible with the Flash music player.
 ******************************************************
*/

session_start();

require_once('inc/config.php');
require_once('inc/util.php');

header('Content-Type: text/xml');
header('Pragma: no-cache');

$xml_start = '<?xml version=\"1.0\" encoding=\"UTF-8\"?><playlist>';
$xml_end = '</playlist>';

if (!isset($_GET["playlist"]) || $_GET["playlist"] == 0 || $_GET["playlist"] == "")
	die("$xml_start<error>No playlist specified.</error>$xml_end");

$playlist = $_GET["playlist"];

$con = db_connect($config['db_name']);

if (mysqli_connect_errno())
	die("$xml_start<error>Database connection failed: " . mysqli_connect_errno() . "</error>$xml_end");

$user_cmd = $con->prepare("SELECT owner, public FROM $config[db_table_prefix]_playlists WHERE listID = ?");
$user_cmd->bind_param("i", $playlist);
$user_cmd->execute();
$user_cmd->bind_result($owner, $public);
$user_cmd->fetch();

if ($owner != $_SESSION["username"] && !$public)
	die("$xml_start<error>You do not have permission to open this playlist</error>$xml_end");
	
$user_cmd->close();

$songs_cmd = $con->prepare("SELECT * FROM $config[db_table_prefix]_playlist_songs WHERE playlistID = ? ORDER BY track ASC");
$songs_cmd->bind_param("i", $playlist);
$songs_cmd->execute();

print $xml_start;

while ($row = fetch_assoc($songs_cmd))
	print "<song><![CDATA[getfile.php?file=" . urlencode($row["path"] . "/" . $row["filename"]) . "]]></song>";

print $xml_end;

$songs_cmd->close();
$con->close();

?>