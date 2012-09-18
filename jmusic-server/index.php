<?php

session_start();

require_once("inc/util.php");
require_once("inc/config.php");

$logged_in = is_logged_in();

if ($config['require_login']) ensure_login();

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

		<link rel="stylesheet" type="text/css" href="css/styles.css" />
		<script type="text/javascript" src="js/common.js"></script>
		<script type="text/javascript" src="js/rowSelect.js"></script>
	</head>

	<body onload="createEllipsis(); initRowSelection('fileSystemTable', false);">
		<div id="background"></div>

		<img src="music.png" style="position: relative; left: 50%; margin-left: -248px;" />
		
		<div style="width: 80%; margin-left: 10%; margin-right: 10%; color: #FFFFFF;">
    		<div style="position: absolute"><a href="index.php">Home</a></div>
			<?php if($logged_in) { ?><div style="width: 100%; text-align: right;">Signed in as <?php print $_SESSION[$config['session_name']]['username']; ?>. <a href="logout.php">Sign Out</a></div><?php } ?>
			<?php if(!$logged_in) { ?><div style="width: 100%; text-align: right;"><a href="login.php">Sign In</a></div><?php } ?>
		</div>

<?php

//Discard unsafe folder paths.

if (isset($_GET["folder"]))
{
	$abs_dir = realpath(formatFolder($config['base_dir'] . "/" . $_GET["folder"]));
	$base_dir = realpath($config['base_dir']);
	
	if (substr($abs_dir, 0, strlen($base_dir)) === $base_dir)
		$folder = formatFolder(substr($abs_dir, strlen($base_dir)));
	else
		$folder = "";
}
else
	$folder = "";

//--------------------------Display Playlists------------------------------------------------

//Display playlist tables only if on the home page

if ($folder == "")
{
    $rowCount = 0;
  
?>
		<table id="publicPlaylists" class="maintable"> 
			<thead>
				<tr class="tableTitle">
					<th colspan="4">
						<h3><a href="#" class="collapseSwitch" onclick="collapseTable(this, 'publicPlaylists'); return false;">-</a>Public Playlists</h3>
					</th>
				</tr>
				<tr>
					<th>User</th>
					<th>Playlist Name</th>
					<th>Songs</th>
					<th>Play</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<th>User</th>
					<th>Playlist Name</th>
					<th>Songs</th>
					<th>Play</th>
				</tr>
			</tfoot>
<?php

	$shadeRow = true;

	$con = db_connect($config['db_name']); 
  
	$result = $con->query("SELECT * FROM $config[db_table_prefix]_playlists WHERE public = 1");
 
	while ($row = $result->fetch_assoc())
	{
		$rowClass = $shadeRow ? "oddrow" : "evenrow";
		$shadeRow = !$shadeRow; 

		$edithref = "playlist.php?playlist=$row[listID]";
		$playhref = "player.php?playlist=$row[listID]";

?>
			<tr class="<?php print $rowClass; ?>">
				<td class="user"><?php print $row['owner']; ?></td>
				<td class="name"><div class="noOverflow"><a href="<?php print $edithref; ?>"><?php print $row['name']; ?></a></div></td>
				<td class="songs"><?php print $row['songs']; ?></td>
				<td class="play"><a href="<?php print $playhref; ?>" target="_blank" onclick="playList(<?php print $row['listID']; ?>); return false;">Play</a></td>
			</tr>
<?php

		$rowCount ++;  
	}

	$result->close();

	//If table is not full, fill with empty rows

	while ($rowCount < 5)
	{
		$rowClass = $shadeRow ? 'oddrow' : 'evenrow';
		$shadeRow = !$shadeRow;
?>
			<tr class="<?php print $rowClass; ?>">
				<td class="user">&nbsp;</td>
				<td class="name">&nbsp;</td>
				<td class="songs">&nbsp;</td>
				<td class="play">&nbsp;</td>
			</tr>
<?php

		$rowCount ++;
	}
	
?>
		</table>
		
		<table id="privatePlaylists" class="maintable"> 
			<thead>
				<tr class="tableTitle">
					<th colspan="4">
						<h3><a href="#" class="collapseSwitch" onclick="collapseTable(this, 'privatePlaylists'); return false;">-</a>Private Playlists</h3>
					</th>
				</tr>
				<tr>
					<th>Playlist Name</th>
					<th>Songs</th>
					<th>Play</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<th>Playlist Name</th>
					<th>Songs</th>
					<th>Play</th> 
				</tr>
			</tfoot>
<?php
  
	$shadeRow = true;
	$rowCount = 0; 
  
	$rowClass = $shadeRow ? "oddrow" : "evenrow";
	$shadeRow = !$shadeRow;

?>
			<tr class="<?php print $rowClass; ?>">
				<td class="name"><a href="newplaylist.php">Create A Playlist</a></td>
				<td class="songs">&nbsp;</td>
				<td class="play">&nbsp;</td>
			</tr>
<?php

	$rowCount ++;
 	
	$user_playlists_cmd = $con->prepare("SELECT * FROM $config[db_table_prefix]_playlists WHERE owner = ?");
	$user_playlists_cmd->bind_param('s', $_SESSION['username']);
	$user_playlists_cmd->execute();
    
	while ($row = fetch_assoc($user_playlists_cmd))
	{
		$rowClass = $shadeRow ? 'oddrow' : 'evenrow';
		$shadeRow = !$shadeRow; 
 
		$href = "player.php?playlist=$row[listID]";

?>
			<tr class="<?php print $rowClass; ?>">
				<td class="name"><div class="noOverflow"><a href="playlist.php?playlist=<?php print $row['listID']; ?>"><?php print $row['name']; ?></a></div></td>
				<td class="songs"><?php print $row['songs']; ?></td>
				<td class="play"><a href="<?php print $href; ?>" target="_blank" onclick="playList(<?php print $row['listID']; ?>); return false;">Play</a></td>
			</tr>
<?php
		$rowCount ++;
    
    	$option['id'] = $row['listID'];
    	$option['name'] = $row['name'];
		$options[] = $option;
	}

	$user_playlists_cmd->close();
	$con->close();

	while($rowCount < 5)
	{
		$rowClass = $shadeRow ? 'oddrow' : 'evenrow';
		$shadeRow = !$shadeRow; 
		
?>
			<tr class="<?php print $rowClass; ?>">
				<td class="name">&nbsp;</td>
				<td class="songs">&nbsp;</td>
				<td class="play">&nbsp;</td>
			 </tr>
<?php

		$rowCount ++;
	}

?>
		</table>
<?php

 }

	$con = db_connect($config['db_name']);

	$user_playlists_cmd = $con->prepare("SELECT * FROM $config[db_table_prefix]_playlists WHERE owner = ?");
	$user_playlists_cmd->bind_param('s', $_SESSION['username']);
	$user_playlists_cmd->execute();
	
	$options = array();
    
	while ($row = fetch_assoc($user_playlists_cmd))
	{
		$option['id'] = $row['listID'];
		$option['name'] = $row['name'];
		$options[] = $option;
	}

	$user_playlists_cmd->close();
	$con->close();
  
  //--------------------------Display filesystem contents---------------------------------------------

?>

		<h2>/<?php print $folder; ?></h2>
		<form id="songsForm" action="playlist.php" method="POST">
			<input name="action" value="add" type="hidden"  />
			<input name="folder" value="<?php print $folder; ?>" type="hidden"  />
			<table id="fileSystemTable" class="maintable"> 
				<thead>
					<tr>
						<th colspan="4">
							<h3>Filesystem</h3>
							<select name="playlist">
								<option>Add to playlist</option>
								<option>---------</option>
								<option value="newList" onclick="document.getElementById('songsForm').submit();">Add to new playlist</option>
								<option>---------</option>
<?php
foreach ($options as $option)
{
	?>
								<option value="<?php print $option['id']; ?>" onclick="document.getElementById('songsForm').submit();"><?php print $option['name']; ?></option>
	<?php
}
?>
							</select>
						</th>
					</tr>
					<tr>
						<th>Type</th>
						<th>Name</th>
						<th>Size</th>
						<th>Download</th>
					</tr>
				</thead>
				<tfoot>
					<tr>
						<th>Type</th>
						<th>Name</th>
						<th>Size</th>
						<th>Download</th> 
					</tr>
				</tfoot>
				
<?php

$current_directory = $config['base_dir'];
if (isset($_GET['folder'])) $current_directory .= "/$folder";

if (!file_exists($current_directory)) die("Error 0100: Folder does not exist");

$folder_contents = scandir($current_directory);

//$index = 0;

$files = array();
$directories = array();
  
foreach ($folder_contents as $x)
{
	if ($x == ".") continue;
	if ($x == "..") continue;
	
	if (is_dir("$current_directory/$x"))
		$directories[] = $x;
	else
		$files[] = $x;
}

natcasesort($files);
natcasesort($directories);

$file_descriptions = array();
$file_descriptions[] = array('is_dir' => 1, 'name' => '.', 'size' => 0);
$file_descriptions[] = array('is_dir' => 1, 'name' => '..', 'size' => 0);

foreach ($directories as $x)
{
	$entry = array();
	$entry['is_dir'] = 1;
	$entry['name'] = $x;
	$entry['size'] = 0;
	
	$file_descriptions[] = $entry;
}

foreach ($files as $x)
{
	$entry = array();
	$entry['is_dir'] = 0;
	$entry['name'] = $x;
	$entry['size'] = filesize("$current_directory/$x");
	
	$file_descriptions[] = $entry;
}

$shadeRow = true;

//Display files and directories in the table

foreach ($file_descriptions as $x)
{
	if ($x['name'] === ".." && $folder == "") continue; //Skip diplsaying the parent folder link if at the top-most directory

	if ($x['is_dir'])
	{
		$type = 'Directory';
		$size_string = '';
	}
	else
	{
		$type = 'File';
		$size_string = friendly_file_size($x['size']);
	}
	
	$path = urlencode("$folder/$x[name]");
	
	if ($type === 'Directory') {

		if ($x['name'] === '.')
			$href = 'index.php?folder=';
		elseif ($x['name'] === '..')
			$href = "index.php?folder=" . urlencode(parentDir($folder));
		else
			$href = "index.php?folder=" . urlencode(formatFolder("$folder/$x[name]"));

		$target = "_self";
		$openLink = "<a href=\"$href\" target=\"$target\" style=\"margin-left: 20px;\">$x[name]</a>";
		$downloadLink = "";
	}
	else
	{
		$path = urlencode("$folder/$x[name]");
		$downloadLink = "<a href=\"getfile.php?file=$path\">Download</a>";
		$href = "player.php?fileName=$path";

		//Determine file type from file name
		
		$dotpos = strrpos($x['name'], '.');

		if ($dotpos === false)
			$filetype = '';
		else
			$filetype = strtolower(substr($x['name'], $dotpos + 1));
		
		//Skip filetypes that are not specified in the config file
		if (!in_array($filetype, $config['file_types'])) continue;

		//Allow MP3s, WAVs and AACs to be played in the Flash music player
		if ($filetype === "mp3" || $filetype === "wav" || $filetype === "aac")
			$openLink = "<input type=\"checkbox\" name=\"songs[]\" value=\"$x[name]\" /><a href=\"$href\" target=\"_blank\" onclick=\"return playSong('$path', event.altKey);\">$x[name]</a>";
		else
			$openLink = "<span style=\"margin-left: 20px;\">$x[name]</span>";
	}

	$rowClass = $shadeRow ? "oddrow" : "evenrow";
	$shadeRow = !$shadeRow;

	?>
				<tr class="<?php print $rowClass; ?>">
					<td class="type"><?php print $type; ?></td>
					<td class="name"><div class="noOverflow"><?php print $openLink; ?></div></td>
					<td class="size"><?php print $size_string; ?></td>
					<td class="download"><?php print $downloadLink; ?></td>
				</tr>
	<?php

}
?>

			</table>
		</form>
	</body>
</html>

