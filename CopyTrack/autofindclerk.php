<?php

include(__DIR__ . "\\..\\copytrack-src\\credentials.php");
 
// Processing a 6 MB dictionary file!
ini_set('memory_limit', '128M');

if(!isset($GLOBALS["___mysqli_ston"])) {
	$dbconn = ($GLOBALS["___mysqli_ston"] = mysqli_connect($dbhost,  $dbuser,  $dbpasswd)) or die("Couldn't Connect");
	//mysql_set_charset('utf8',$dbconn); // Necessary for char data to be pulled from db with correct charset.
	mysqli_set_charset($dbconn,'utf8');
	((bool)mysqli_query($GLOBALS["___mysqli_ston"], "USE $dbname"));
}
 
$found = array();
$limit = 15;

$value = $_POST['value'];
if (!$value) { $value = $_GET['valu']; }

if (is_string($value) )
{
	$query = "SELECT clerk_name FROM operators ORDER BY clerk_name";
	$result = mysqli_query($GLOBALS["___mysqli_ston"], $query);
	$words = '';
	while ($row = mysqli_fetch_array($result))
	{
		$words .= $row['clerk_name'] . "\n";
	}
	preg_match_all('/^(.*)'. preg_quote($value) .'(.*)$/mi', $words, $match);
	$found = array_slice(array_values($match[0]), 0, $limit);
}
 
header('Content-type: application/json');
echo json_encode($found);

?>