<?php

session_start();

require_once('inc/config.php');
require_once('inc/util.php');

if (isset($_SESSION[$config['session_name']]) && isset($_SESSION[$config['session_name']]['userID']))
{
	$signed_in = true;
}
else
{
	$signed_in = false;
	
	if (isset($_POST["username"]) && isset($_POST["password"]))
	{
		$con = db_connect($config['db_name']);
    	
    	$username = trim($_POST["username"]);
    	$password = $_POST["password"];
    	$hashword = password_hash($password);

		if ($user_cmd = $con->prepare("SELECT * FROM $config[db_table_prefix]_users WHERE username = ?"))
		{
    		$user_cmd->bind_param("s", $username);
    		$user_cmd->execute();
    		
    		if ($user = fetch_assoc($user_cmd))
    		{
    			$user_cmd->close();
    			
    			if (!isset($_SESSION["banned_until"]) || !($_SESSION["banned_until"] > time() && strcmp($_SESSION["banned_name"], $_GET["username"])))
    			{
    				if (strcmp($hashword, trim($user["password"])) == 0)
    				{
						$_SESSION[$config['session_name']] = array();
    					$_SESSION[$config['session_name']]["userID"] = $user["user_id"];
    					$_SESSION[$config['session_name']]["username"] = $user["username"];
    					$_SESSION[$config['session_name']]["email"] = $user["email"];
    			
    					$signed_in = true;
    					
    				} else {
    					$error =  "<p>Incorrect username and/or password.</p>";
    			
    					if (isset($_SESSION[$config['session_name']]["incorrect_password_count"]))
    						$_SESSION[$config['session_name']]["incorrect_password_count"]++;
    					else
    						$_SESSION[$config['session_name']]["incorrect_password_count"] = 1;
    				
    					if ($_SESSION[$config['session_name']]["incorrect_password_count"] > 5)
    					{
    						$_SESSION[$config['session_name']]["banned_until"] = time() + 30 * 60;
    						$_SESSION[$config['session_name']]["banned_name"] = $_POST["username"];
    					}
    				}
    			}
    			else
    			{
    				$user_cmd->close();
    				$error =  "<p>You have guessed your password incorrectly too many times. Please try again later.</p>";
    			}
    		}
    		else
    		{
    			$error =  "<p>Incorrect username and/or password.</p>";
    		}
    		
    		$con->close();
    	}
	}
}

if ($signed_in)
{
	if (isset($_GET["location"]))
		header("Location: " . urldecode($_GET["location"]));
	else
		header("Location: index.php");
	
	exit;
}

?>

<html>
<head>
<title>Sign In</title>
</head>
<body>
<h1>Sign In</h1>

<?php

if (isset($error))
	echo "<p class=\"error\">$error</p>";

if (isset($_GET["location"]))
	$location = trim($_GET["location"]);
else
	$location = "index.php";

if (isset($_GET["location"]))
	$action = "login.php?location=$location";
else
	$action = "login.php";

?>

<form action="<?php echo $action; ?>" method="POST">
<label for="username">Username:</label><br />
<input type="text" name="username" /><br />
<label for="password">Password:</label><br />
<input type="password" name="password" /><br />
<input type="submit" name="s" value="Sign In" />
</form>

<?php if ($config['open_registration']) { ?>
<p>Don't have an account? <a href="register.php?location=<?php echo $location; ?>">Register</a>.</p>
<?php } ?>

</body>
</html>