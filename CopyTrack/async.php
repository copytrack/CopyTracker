<?php

$dbname = "copytrack";
$dbhost = "localhost";
$dbuser = "root";
$dbpasswd = "copy391200";

function filter($content)
{
	$content = trim($content);
	$content = htmlentities($content);
	$content = addslashes($content);
	return $content;
}

function cleanData($str)
{
	$str = trim($str); 
	$str = preg_replace('/\s+/', ' ', $str);
	return $str;
}

$dbconn = ($GLOBALS["___mysqli_ston"] = mysqli_connect($dbhost,  $dbuser,  $dbpasswd)) or die("Couldn't Connect");
//mysql_set_charset('utf8',$dbconn); // Necessary for char data to be pulled from db with correct charset.
((bool)mysqli_query($GLOBALS["___mysqli_ston"], "USE $dbname"));

$postdata['acct_id'] = cleanData($_POST['acct_id']);
$postdata['bw'] = cleanData($_POST['bw']);
$postdata['startbal_bw'] = cleanData($_POST['startbal_bw']);
$postdata['color'] = cleanData($_POST['color']);
$postdata['startbal_color'] = cleanData($_POST['startbal_color']);
$postdata['clerk_id'] = cleanData($_POST['clerk_id']);
		

$sql = "UPDATE accounts
		SET  = copies_bw = (copies_bw - " . $postdata['bw'] . ",
			copies_color = (copies_color - " . $postdata['color'] . ",
		WHERE acct_id = " . $postdata['acct_id'];

if (mysqli_query($dbconn, $sql))
{
	
	$query = "INSERT INTO transactions (acct_id, trans_timestamp, trans_notes, copies_bw, copies_color, startbal_bw, startbal_color, oper_id)
					VALUES ('".$postdata['acct_id']."',
							'".time()."',
							'".$postdata['trans_notes']."',
							'".$postdata['bw']."',
							'".$postdata['color']."',
							'".$postdata['startbal_bw']."',
							'".$postdata['startbal_color']."',
							'".$postdata['clerk_id']."')";
	mysqli_query($dbconn, $query) or die('Failure');

	echo "Success";
}
else
{
	echo "Failure";
}

?>