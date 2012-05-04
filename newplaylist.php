<?php
  
  session_start();
  
  require_once('inc/config.php');
  require_once('inc/util.php');

  ensure_login();
  
  $playlistName = "New Playlist";
  $con = db_connect($config['db_name']);
  $cmd = $con->prepare("INSERT INTO $config[db_table_prefix]_playlists (name, owner) VALUES (?, ?)");
  $cmd->bind_param("ss", $playlistName, $_SESSION['username']);
  $cmd->execute();
  
  header("Location: index.php");

?>