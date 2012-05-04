<?php

session_start();

require_once('inc/config.php');
require_once('inc/util.php');

if (isset($_GET['fileName']))
{
	$file = $_GET['fileName'];
	$fileName = substr($file, strrpos($file, "/") + 1);
	$flash_vars = "vpls=&vsng=" . urlencode("getfile.php?file=" . urlencode($file));
}
elseif (isset($_GET["playlist"]))
{
	$playlist = $_GET["playlist"];
	$flash_vars = "vpls=" . urlencode("getplaylist.php?playlist=$playlist") . "&vsng=";
	
	$con = db_connect($config['db_name']);
	
	if (mysqli_connect_errno())
		die("Database connection failed: " . mysqli_connect_errno());
	
	$user_cmd = $con->prepare("SELECT owner, public FROM $config[db_table_prefix]_playlists WHERE listID = ?");
	$user_cmd->bind_param("i", $playlist);
	$user_cmd->execute();
	$user_cmd->bind_result($owner, $public);
	$user_cmd->fetch();
	
	if ($owner != $_SESSION["username"] && !$public)
	{
			die("You do not have permission to open this playlist");
	}
	
	$user_cmd->close();
	$con->close();
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
	<head>
		<script type="text/javascript">
			function sendCommand(command)
			{
				var player;
				
				if (window.document["player"]) //Mozilla/IE
					player = window.document["player"];
				else if (navigator.appName.indexOf("Microsoft Internet") == -1 && document.embeds && document.embeds["player"]) //Mozilla
					player = document.embeds["player"];
				else if (navigator.appName.indexOf("Microsoft Internet") != -1) //IE
					player = window["player"];
				else
					alert("Error: Could not communicate with the player.");
					
				player.sendCommand(command);
			}
		</script>
	</head>
	<body id="playerPage" style="background: #000000; margin: 0;">
		<object width="550" height="540" id="player">
			<param name="movie" value="player.swf">
			<param name="allowScriptAccess" value="always" />
			<param name="FlashVars" value="<?php print $flash_vars; ?>" />
			<embed src="player.swf" width="550" height="540" allowScriptAccess="always" name="player"
				FlashVars="<?php print $flash_vars; ?>"></embed>
		</object>
	</body>
</html>