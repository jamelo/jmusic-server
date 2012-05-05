<?php

session_start();

$banned_until = $_SESSION[$config['session_name']]["banned_until"];
$banned_name = $_SESSION[$config['session_name']]["banned_name"];

session_destroy();

if (isset($_GET["location"]))
	header("Location: $_GET[location]");
else
	header("Location: index.php");
	
session_start();

$_SESSION[$config['session_name']]["banned_until"] = $banned_until;
$_SESSION[$config['session_name']]["banned_name"] = $banned_name;

?>