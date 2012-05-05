<?php

session_start();

require_once('inc/config.php');
require_once('inc/util.php');

function add_songs_to_playlist($containing_folder, $song_list, $playlist_id)
{
	require('inc/config.php');

	$con = db_connect($config['db_name']);
	
	$song_count_cmd = $con->prepare("SELECT COUNT(*) FROM $config[db_table_prefix]_playlists WHERE listID = ?");
	$song_count_cmd->bind_param("i", $playlist_id);
	$song_count_cmd->execute();
	$song_count_cmd->bind_result($song_count);
	
	$track_number = $song_count + 1;
	
	$song_count_cmd->close();

	foreach ($song_list as $song)
	{
		$title = substr($song, 0, strrpos($song, "."));
	
		$add_songs_cmd = $con->prepare("INSERT INTO $config[db_table_prefix]_playlist_songs (playlistID, track, path, filename, title) VALUES (?, ?, ?, ?, ?)");
		$add_songs_cmd->bind_param("iisss", $playlist_id, $track_number, $containing_folder, $song, $title);
		$add_songs_cmd->execute() or die("Error adding file '$song' to playlist.");
		$add_songs_cmd->close();
	
		$track_number++;
	}

	$track_number--;

	$update_playlist_cmd = $con->prepare("UPDATE $config[db_table_prefix]_playlists SET songs = ? WHERE listID = ?");
	$update_playlist_cmd->bind_param("ii", $track_number, $playlist_id);
	$update_playlist_cmd->execute() or die("Error updating track count");
	$update_playlist_cmd->close();
	$con->close();
}

function remove_songs_from_playlist($playlist_id, $track_list)
{
	require('inc/config.php');

	$con = db_connect($config['db_name']);
	$track = 0;
	
	$remove_song_cmd = $con->prepare("DELETE FROM $config[db_table_prefix]_playlist_songs WHERE playlistID = ? AND track = ?");
	$remove_song_cmd->bind_param("ii", $playlist_id, $track);
	
	foreach ($track_list as $current_track)
	{
		$track = $current_track;
		$remove_song_cmd->execute();
	}
	
	$remove_song_cmd->close();
	
	$get_song_ids_cmd = $con->prepare("SELECT songID FROM $config[db_table_prefix]_playlist_songs WHERE playlistID = ? ORDER BY songID ASC");
	$get_song_ids_cmd->bind_param("i", $playlist_id);
	$get_song_ids_cmd->execute();
	$get_song_ids_cmd->store_result();
	$get_song_ids_cmd->bind_result($song_id);
	
	$track = 1;
	
	$update_track_num_cmd = $con->prepare("UPDATE $config[db_table_prefix]_playlist_songs SET track = ? WHERE songID = ?");
	$update_track_num_cmd->bind_param("ii", $track, $song_id_param);
	
	while ($get_song_ids_cmd->fetch())
	{
		$song_id_param = $song_id;
		$update_track_num_cmd->execute();
		
		$track++;
	}
	
	$update_track_num_cmd->close();
	$get_song_ids_cmd->close();
	
	$track--;
	
	$update_playlist_cmd = $con->prepare("UPDATE $config[db_table_prefix]_playlists SET songs = ? WHERE listID = ?");
	$update_playlist_cmd->bind_param("ii", $track, $playlist_id);
	$update_playlist_cmd->execute() or die("Error updating track count");
	$update_playlist_cmd->close();
	
	$con->close();
}

function duplicate_songs($playlist_id, $song_list)
{
	require('inc/config.php');

	$con = db_connect($config['db_name']);
	
	$songs_count_cmd = $con->prepare("SELECT COUNT(*) FROM $config[db_table_prefix]_playlist_songs WHERE playlistID = ?") or die("whut");
	$songs_count_cmd->bind_param("i", $playlist_id);
	$songs_count_cmd->execute();
	$songs_count_cmd->bind_result($song_count);
	$songs_count_cmd->fetch();
	$track_number = $song_count + 1;
	$songs_count_cmd->close();
	
	$select_song_cmd = $con->prepare("SELECT * FROM $config[db_table_prefix]_playlist_songs WHERE playlistID = ? AND track = ?");
	$select_song_cmd->bind_param("ii", $playlist_id, $song);
	
	$insert_song_cmd = $con->prepare("INSERT INTO $config[db_table_prefix]_playlist_songs (playlistID, track, path, filename, title) VALUES (?, ?, ?, ?, ?)") or die("fffuuuuu");
	$insert_song_cmd->bind_param("iisss", $playlist_id, $track_number, $path, $filename, $title);
	
	foreach ($song_list as $song)
	{
		$select_song_cmd->execute();
		$select_song_cmd->store_result();
		
		$row = fetch_assoc($select_song_cmd);
		
		$path = $row["path"];
		$filename = $row["filename"];
		$title = $row["title"];
		
		$insert_song_cmd->execute();
		$select_song_cmd->free_result();
		
		$track_number++;
	}
	
	$select_song_cmd->close();
	$insert_song_cmd->close();
	
	$track_number--;
	$update_playlist_cmd = $con->prepare("UPDATE $config[db_table_prefix]_playlists SET songs = ? WHERE listID = ?");
	$update_playlist_cmd->bind_param("ii", $playlist_id, $track_number);
	$update_playlist_cmd->execute();
	$update_playlist_cmd->close();
	
	$con->close();
}

function shuffle_songs($playlist_id, $song_list)
{
	require('inc/config.php');

	$con = db_connect($config['db_name']);
	
	if (is_null($song_list))
	{
		$get_songs_cmd = $con->prepare("SELECT songID, track FROM $config[db_table_prefix]_playlist_songs WHERE playlistID = ?");
		$get_songs_cmd->bind_param("i", $playlist_id);
		$get_songs_cmd->execute();
		
		while ($row = fetch_assoc($get_songs_cmd))
		{
			$songs_to_shuffle["song_id"][] = $row["songID"];
			$songs_to_shuffle["track"][] = $row["track"];
		}
		
		$get_songs_cmd->close();
	}
	else
	{
		$get_song_cmd = $con->prepare("SELECT songID FROM $config[db_table_prefix]_playlist_songs WHERE playlistID = ? and track = ?");
		$get_song_cmd->bind_param("ii", $playlist_id, $track);
		
		foreach ($song_list as $track)
		{
			$get_song_cmd->execute() or die("Could not find song $track.");
			$get_song_cmd->bind_result($song_id);
			$get_song_cmd->fetch();
			
			$songs_to_shuffle["song_id"][] = $song_id;
			$songs_to_shuffle["track"][] = $track;
		}
		
		$get_song_cmd->close();
	}
	
	shuffle($songs_to_shuffle["track"]);
	
	$update_track_num_cmd = $con->prepare("UPDATE $config[db_table_prefix]_playlist_songs SET track = ? WHERE songID = ?");
	$update_track_num_cmd->bind_param("ii", $track, $song_id);
	
	for ($i = 0; $i < count($songs_to_shuffle["song_id"]); $i++)
	{
		$track = $songs_to_shuffle["track"][$i];
		$song_id = $songs_to_shuffle["song_id"][$i];
		$update_track_num_cmd->execute();
	}
	
	$update_track_num_cmd->close();
	$con->close();
}

function move_song($playlist_id, $track, $move)
{
	if ($move == 0) return;
	
	require('inc/config.php');
	
	$con = db_connect($config['db_name']);
	
	$song_count_cmd = $con->prepare("SELECT COUNT(*) FROM $config[db_table_prefix]_playlist_songs WHERE playlistID = ?");
	$song_count_cmd->bind_param("i", $playlist_id);
	$song_count_cmd->execute();
	$song_count_cmd->bind_result($song_count_result);
	$song_count_cmd->fetch();
	
	$song_count = $song_count_result;
	
	$song_count_cmd->close();
	
	$get_songs_cmd = $con->prepare("SELECT * FROM $config[db_table_prefix]_playlist_songs WHERE playlistID = ? AND track BETWEEN ? AND ? ORDER BY track ASC");
	$get_songs_cmd->bind_param("iii", $playlist_id, $lower_bound, $upper_bound);
	
	$lower_bound = ($move > 0) ? $track : max(1, $track + $move);
	$upper_bound = ($move > 0) ? min($song_count, $track + $move) : $track;
	
	if ($upper_bound - $lower_bound <= 0) die("Error");
	
	$get_songs_cmd->execute();

	$i = 0;

	while ($row = fetch_assoc($get_songs_cmd))
	{
		$songs[$i]["song_id"] = $row["songID"];
		$songs[$i]["track"] = $row["track"];
		
		$i++;
	}
	
	$get_songs_cmd->close();

	$update_track_cmd = $con->prepare("UPDATE $config[db_table_prefix]_playlist_songs SET track = ? WHERE songID = ?");
	$update_track_cmd->bind_param("ii", $new_track, $song_id);
	
	foreach ($songs as $song)
	{
		if ($song["track"] == $track)
			$new_track = max($lower_bound, min($upper_bound, $track + $move));
		else
			$new_track = max($lower_bound, min($upper_bound, $song["track"] - (($move > 0) ? 1 : -1))); 
		
		$song_id = $song["song_id"];
		
		$update_track_cmd->execute();
	}
	
	$update_track_cmd->close();
	$con->close();
}

function rearrange_songs($playlist_id, $track_list, $insertion_point)
{
	if (count($track_list) <= 0 || $insertion_point < 0) return;
	
	require('inc/config.php');
	
	$con = db_connect($config['db_name']);
	
	$song_count_cmd = $con->prepare("SELECT COUNT(*) FROM $config[db_table_prefix]_playlist_songs WHERE playlistID = ?");
	$song_count_cmd->bind_param("i", $playlist_id);
	$song_count_cmd->execute();
	$song_count_cmd->bind_result($song_count_result);
	$song_count_cmd->fetch();
	
	$song_count = $song_count_result;
	
	$song_count_cmd->close();
	
	
	if ($insertion_point > $song_count - count($track_list)) $insertion_point = $song_count - count($track_list);
	
	$get_songs_cmd = $con->prepare("SELECT songID, track FROM $config[db_table_prefix]_playlist_songs WHERE playlistID = ? ORDER BY track ASC");
	$get_songs_cmd->bind_param("i", $playlist_id);
	$get_songs_cmd->execute();
	
	while ($row = fetch_assoc($get_songs_cmd))
	{
		$song["track"] = $row["track"];
		$song["songID"] = $row["songID"];
		$songs[] = $song;
	}
	
	$get_songs_cmd->close();
	
	$update_track_cmd = $con->prepare("UPDATE $config[db_table_prefix]_playlist_songs SET track = ? WHERE songID = ?");
	$update_track_cmd->bind_param("ii", $new_track, $song_id);
	
	$num_tracks_under_insertion_point = 0;
	
	foreach ($track_list as $t)
	{
		if ($t <= $insertion_point) $num_tracks_under_insertion_point ++;
	}
	
	
	$i = 1;
	$track_number = 0;
	$insertion_track_number_start = $insertion_point - $num_tracks_under_insertion_point;
	$insertion_track_number = $insertion_track_number_start;
	
	foreach ($songs as $song)
	{
		if (array_search($i, $track_list) === false)
		{
			$track_number++;
			if ($track_number == $insertion_track_number_start + 1)
			{
				$track_number += count($track_list);
			}
			
			$new_track = $track_number;
		}
		else
		{
			$insertion_track_number++;
			$new_track = $insertion_track_number;
		}
		
		$i++;
		$song_id = $song["songID"];
		$update_track_cmd->execute();
	}
	
	$update_track_cmd->close();
	$con->close();
}

function create_playlist($owner)
{
	require('inc/config.php');
	
	$con = db_connect($config['db_name']);
	$create_playlist_cmd = $con->prepare("INSERT INTO $config[db_table_prefix]_playlists (name, owner) VALUES (?, ?)");
	$create_playlist_cmd->bind_param("ss", $list_name, $owner);
	$list_name = "New Playlist";
	$create_playlist_cmd->execute();
	$new_id = $create_playlist_cmd->insert_id;
	$create_playlist_cmd->close();
	$con->close();
	
	return $new_id;
}

if (isset($_POST["folder"])) $folder = $_POST["folder"]; else $folder = "";
if (isset($_POST["action"])) $action = $_POST["action"];

if (isset($_POST["playlist"]))
	$playlist = $_POST["playlist"]; 
elseif (isset($_GET["playlist"]) && $_GET["playlist"] != "")
	$playlist = $_GET["playlist"];
else
	die("No playlist selected.");

//--------------------------Check if user has permissions-------------------------------------------------

  
if (!(isset($_POST["playlist"]) && $_POST["playlist"] == "newList"))
{
	$con = db_connect("fileserver");
	$cmd = $con->prepare("SELECT * FROM $config[db_table_prefix]_playlists WHERE listID = ?");
	$cmd->bind_param("i", $playlist);
	$cmd->execute();
	
	$row = fetch_assoc($cmd);
	
	if (strtolower($row["owner"]) != strtolower($_SESSION["username"]))
	{
		if ($row["public"])
			unset($action);
		else
		{
			$cmd->close();
			$con->close();
			die("You do not have permission to view this playlist");
		}
	}
	
	$cmd->close();
	$con->close();
}
else
{
	$playlist = create_playlist($_SESSION["username"]);
}

if (isset($action))
{
	switch ($action)
	{
		case "add":
			if (isset($_POST["songs"]))
				add_songs_to_playlist($folder, $_POST["songs"], $playlist);
			break;
		case "delete":
			if (isset($_POST["songs"]))
				remove_songs_from_playlist($playlist, $_POST["songs"]);
			break;
		case "duplicate":
			if (isset($_POST["songs"]))
				duplicate_songs($playlist, $_POST["songs"]);
			else
				die("no songs");
			break;
		case "shuffle":
			if (isset($_POST["songs"]))
				shuffle_songs($playlist, $_POST["songs"]);
			else
				shuffle_songs($playlist, null);
			break;
		case "moveup":
			if (isset($_POST["songs"]))
				move_song($playlist, $_POST["songs"][0], -1);
			else
				die("Error 4501");
			break;
		case "movedown":
			if (isset($_POST["songs"]))
				move_song($playlist, $_POST["songs"][0], 1);
			else
				die("Error 4502");
			break;
		case "moveto":
			if (isset($_POST["songs"]) && isset($_POST["userInput"]))
				move_song($playlist, $_POST["songs"][0], $_POST["userInput"] - $_POST["songs"][0]);
			else
				die("Error 4503");
			break;
		case "rearrange":
			if (isset($_POST["songs"]) && isset($_POST["insertionPoint"]))
				rearrange_songs($playlist, $_POST["songs"], $_POST["insertionPoint"]);
			else
				die("Error 4504");
			break;
			
	}
	
	header("Location: playlist.php?playlist=$playlist");
	exit;
}

$con = db_connect($config['db_name']);

$playlist_name_cmd = $con->prepare("SELECT name FROM $config[db_table_prefix]_playlists WHERE listID = ?");
$playlist_name_cmd->bind_param("i", $playlist);
$playlist_title = execute_scalar($playlist_name_cmd);
$playlist_name_cmd->close();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
	<head>
		<title><?php print $playlist_title; ?></title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

		<link rel="stylesheet" type="text/css" href="css/styles.css" />
		<script type="text/javascript" src="js/common.js"></script>
		<script type="text/javascript" src="js/rowSelect.js"></script>
	</head>

	<body onload="createEllipsis(); initRowSelection('playlistTable', true);">
		<div id="background"></div>
		
		<img src="music.png" style="position: relative; left: 50%; margin-left: -248px;" />
		
  		<div style="width: 80%; margin-left: 10%; margin-right: 10%; color: #FFFFFF;">
    		<div style="position: absolute"><a href="index.php">Home</a></div>
    		<div style="width: 100%; text-align: right;"><a href="logout.php">Sign Out</a></div>
  		</div>

		<form id="songsForm" action="playlist.php" method="POST">
			<input name="playlist" value="<?php print $playlist; ?>" type="hidden"  />
			<table id="playlistTable" class="maintable"> 
				<thead>
					<tr>
						<th colspan="7">
							<h3><?php print $playlist_title; ?> - 
								<span style="text-decoration: underline; cursor: pointer;" onclick="playList(<?php print $playlist; ?>); return false;">Play All</span> - 
								<a href="downloadlist.php?playlist=<?php print $playlist; ?>">Download Playlist</a>
							</h3>
							<span align="right">
								<select name="action" id="action">
									<option>Action:</option>
									<option value="delete" onclick="dialogBox('Are you sure you want to remove the selected song(s)?', 'Delete Confirmation', false, 'yesno', '');">Delete</option>
									<option value="duplicate" onclick="document.getElementById('songsForm').submit();">Duplicate</option>
									<option value="shuffle" onclick="document.getElementById('songsForm').submit();">Shuffle</option>
									<option value="moveup" onclick="document.getElementById('songsForm').submit();">Move Up</option>
									<option value="movedown" onclick="document.getElementById('songsForm').submit();">Move Down</option>
									<option value="moveto" onclick="document.getElementById('songsForm').submit();">Move To Position</option>
									<option value="rearrange">Rearrange Songs</option>
								</select>
							</span>
						</th>
					</tr>
					<tr>
						<th>Track</th>
						<th>Name</th>
						<th style="text-align: center;" colspan="5">Action</th>
					</tr>
				</thead>
				<tfoot>
					<tr>
						<th>Track</th>
						<th>Name</th>
						<th style="text-align: center;" colspan="5">Action</th>
					</tr>
				</tfoot>
				<tbody>
<?php

$get_playlist_contents_cmd = $con->prepare("SELECT * FROM $config[db_table_prefix]_playlist_songs WHERE playlistID = ? ORDER BY track");
$get_playlist_contents_cmd->bind_param("i", $playlist);
$get_playlist_contents_cmd->execute();

$shade_row = true;

while($row = fetch_assoc($get_playlist_contents_cmd))
{
	$shade_row = !$shade_row;
	$row_class = $shade_row ? "oddrow" : "evenrow";
	$path = urlencode($row["path"] . "/" . $row["filename"]);
	$href = "player.php?fileName=" . $path;
?>
					<tr class="<?php print $row_class; ?>" >
						<td class="track" id="track"><?php print $row["track"]; ?></td>
						<td class="title"><input type="checkbox" name="songs[]" value="<?php print $row["track"]; ?>" /><a href="<?php print $href; ?>" target="_blank" onclick="return playSong('<?php print $path; ?>', event.altKey);" title="<?php print $row["title"]; ?>"><?php print $row["title"]; ?></a></td>
						<td class="button"><input type="image" src="images/buttons/moveup.png" name="songs[0]" value="<?php print $row["track"]; ?>" alt="Move Up" title="Move Up" onclick="document.getElementById('action').value = 'moveup'" /></td>
						<td class="button"><input type="image" src="images/buttons/movedown.png" name="songs[0]" value="<?php print $row["track"]; ?>" alt="Move Down" title="Move Down" onclick="document.getElementById('action').value = 'movedown'" /></td>
						<td class="button"><img src="images/buttons/moveto.png" alt="Move To Position" title="Move To Poisition..." onclick="document.getElementById('action').value = 'moveto'; dialogBox('What position would you like to move this song to?', 'Change Playlist Order', true, 'okcancel', '<?php print $row["track"]; ?>');" /></td>
						<td class="button"><img src="images/buttons/delete.png" alt="Remove" title="Remove" onclick="document.getElementById('action').value = 'delete'; dialogBox('Are you sure you want to remove the selected song(s)?', 'Delete Confirmation', false, 'yesno', '<?php print $row["track"]; ?>');" /></td>
						<td class="button"><a href="getfile.php?file=<?php print $path; ?>"><img src="images/buttons/save.png" alt="Download" title="Download" /></a></td>
					</tr>
<?php
}

$get_playlist_contents_cmd->close();
$con->close();

?>  
				</tbody>
			</table>

			<div id="dialogContainer">
				<div id="dialogBackground"></div>
				<div id="dialogBox">
					<h2 id="dialogTitle">Title</h2>
					<div id="dialogMessage">Message</div>
					<input type="text" id="dialogTextBox" name="userInput" />
					<input type="hidden" id="dialogValue" name="" value="" />
					<div id="dialogFooter">
						<input type="submit" id="dialogOkBtn" value="OK" />
						<input type="button" id="dialogCancelBtn" value="Cancel" onclick="getElementById('dialogContainer').style.visibility = 'hidden'; getElementById('dialogTextBox').style.visibility = 'hidden'; document.getElementById('dialogValue').name = '';" />
					</div>
				</div>
			</div>

		</form>
	</body>
</html>