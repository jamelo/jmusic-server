<?php

require_once('inc/registration_validation.php');
require_once('inc/config.php');
require_once('inc/util.php');

if(is_logged_in()) header('Location: index.php');

if (isset($_GET["location"]))
	$next_page = $_GET["location"];
else
	$next_page = "index.php";

$errors = 0;

if (isset($_POST["s"]))
{
	if (strlen($_POST["username"]) < 4 || strlen($_POST["username"]) > 20)
	{
		$error[$errors] = "Username must be between 4 and 20 characters long.";
		$errors ++;
	}
	
	if (!str_is_valid_username(trim($_POST["username"])))
	{
		$error[$errors] = "Username can only contain letters (A-Z, a-z), numbers (0-9), periods (.), hyphens (-) and underscores (_).";
		$errors ++;
	}
	
	if (!str_is_valid_password($_POST["password"]))
	{
		$error[$errors] = "Password must be at least 8 characters long and must contain uppercase letters (A-Z), lowercase letters (a-z), numbers (0-9) and at least one punctuation character.";
		$errors ++;
	}
	
	if (strcmp($_POST["password"], $_POST["vpassword"]) != 0)
	{
		$error[$errors] = "Passwords do not match.";
		$errors ++;
	}
	
	if (strcmp($_POST["passcode"], generate_passcode(trim($_POST["username"]), trim($_POST["email"]))) != 0 && !$config['open_registration'])
	{
		$error[$errors] = "Invalid secret code.";
		$errors ++;
	}
	
	if ($errors == 0)
	{
		$con = db_connect($user_database);
    	
    	$user_cmd = $con->prepare("SELECT COUNT(*) FROM $config[db_table_prefix]_users WHERE username = ?");
    	$user_cmd->bind_param("s", trim($_POST["username"]));
    	$user_cmd->execute();
    	$user_cmd->bind_result($user_count);
    	$user_cmd->fetch();
    	
    	if (($user_count) > 0)
    	{
			$error[$errors] = "Username is already in use. Please pick another.";
			$errors ++;
			
			$user_cmd->close();
			$con->close();
		}
		else
		{
			$user_cmd->close();
			
			if (isset($_POST["email"]))
				$email = trim($_POST["email"]);
			else
				$email = "";
				
			$insert_user_cmd = $con->prepare("INSERT INTO $config[db_table_prefix]_users (username, password, email) VALUES (?, ?, ?)");
			$insert_user_cmd->bind_param("sss", trim($_POST["username"]),
												password_hash($_POST["password"]), 
												$email);
			$insert_user_cmd->execute() or die("Database error: cannot create new user");
			
			$user_id = $insert_user_cmd->insert_id;
			
			$insert_user_cmd->close();
			
			$con->close();
			
			session_start();
			
			$_SESSION[$config['session_name']] = array();
			
			$_SESSION[$config['session_name']]["userID"] = $user_id;
    		$_SESSION[$config['session_name']]["username"] = $_POST["username"];
    		$_SESSION[$config['session_name']]["email"] = $_POST["email"];
?>

<html>
	<head>
		<meta http-equiv="refresh" content="3;url=<?php echo $next_page; ?>" />
		<link rel="stylesheet" type="text/css" href="css/styles.css" />
		<title>Registration Successful</title>
	</head>
    <body>
    	<h1>Registration Successful</h1>
    	<p>You will now be redirected automatically. If not, <a href="<?php echo $next_page; ?>">click here</a>.</p>
    </body>
</html>

<?php
			exit;
		}
	}
}

?>

<html>
<head>
<title>Register</title>
<link rel="stylesheet" type="text/css" href="css/styles.css" />
</head>
<body>
<h1>Register</h1>


<?php

if ($errors > 0)
{
	foreach($error as $err)
		echo "<p class=\"error\">$err</p>\n";
}

?>

<form action="register.php?location=<?php echo $next_page; ?>" method="POST">
	<label for="username">Username*:</label><br />
	<input type="text" name="username" value="<?php if (isset($_POST["s"])) echo $_POST["username"]; ?>" /><br />
	<label for="password">Password*:</label><br />
	<input type="password" name="password" /><br />
	<label for="vpassword">Verify Password*:</label><br />
	<input type="password" name="vpassword" /><br />
	<label for="email">Email Address:</label><br />
	<input type="text" name="email" value="<?php if (isset($_POST["s"])) echo $_POST["email"]; ?>" /><br />
	<label for="passcode">Secret Code*:</label><br />
	<?php if (!$config['open_registration']) { ?>
	<input type="text" name="passcode" size="32" value="<?php if (isset($_POST["s"])) echo $_POST["passcode"]; ?>" /><br />
	<?php } ?>
	<input type="submit" name="s" value="Register" />
</form>

<p>* - Required fill.</p>

<h3>Protips</h3>

<ul>
<li>Your username must be between 4 and 20 characters and can only contain letters (A-Z, a-z), numbers 
(0-9), periods (.), hyphens (-) and underscores (_).</li>

<li>Your password must be at least 8 characters long.</li>

<?php if (!$config['open_registration']) { ?>
<li>To obtain the secret code, contact me and give me the username and email address entered above. You must
use the same username and email address in the form above as the one you gave to me to obtain the secret code.</li>
</ul>
<?php } ?>

</body>
</html>