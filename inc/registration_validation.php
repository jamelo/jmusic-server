<?php

function str_is_valid_username($s)
{
	$allowed_chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890_-.";
	$flag = true;
	
	for ($i = 0; $i < strlen($s); $i ++)
	{
		if (strpos($allowed_chars, substr($s, $i, 1)) === false)
		{
			$flag = false;
			break;
		}
	}
	
	return $flag;
}

function str_is_valid_password($s)
{
	if (strlen($s) < 8) return false;
	else return true;
}

?>