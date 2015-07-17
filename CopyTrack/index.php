<?php
error_reporting (E_ALL ^ E_NOTICE);

include(__DIR__ . "\\..\\copytrack-src\\credentials.php");

header('X-UA-Compatible: IE=IE8');

session_start();

$settings = simplexml_load_file("..\\copytrack-src\\settings.xml");

include(__DIR__ . "\\..\\copytrack-src\\timezone.php");
date_default_timezone_set($_TIMEZONE_);

if(!isset($_SESSION['searchInactive'])) $_SESSION['searchInactive'] = true;

if (isset($_GET['resetMode'])) //Change Session Settings
{
	switch ($_GET['resetMode'])
	{
		case "migration":
		$_SESSION['migration'] = false;
		break;
		case "quickComplete":
		$_SESSION['quickComplete'] = false;
		break;
		case "invertColor":
		$_SESSION['invertColor'] = false;
		break;
		case "searchInactive":
		$_SESSION['searchInactive'] = false;
		break;
	}
}

if (isset($_GET['setMode']))
{
	switch ($_GET['setMode'])
	{
		case "migration":
		$_SESSION['migration'] = true;
		break;
		case "quickComplete":
		$_SESSION['quickComplete'] = true;
		break;
		case "invertColor":
		$_SESSION['invertColor'] = true;
		break;
		case "searchInactive":
		$_SESSION['searchInactive'] = true;
		break;
		case "reset":
		$_SESSION['migration'] = false;
		$_SESSION['quickComplete'] = false;
		$_SESSION['invertColor'] = false;
		$_SESSION['searchInactive'] = false;
		break;
	}
}

// Constant: CC = CSS Style, following term is 'what'
define('CC_FIELD_INVALID'," invalid errorTip");

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
	$str = htmlentities($str);
	$str = preg_replace('/\s+/', ' ', $str);
	return $str;
}

function authClerk($id)
{
	GLOBAL $dbconn;
	$query = "SELECT clerk_id FROM operators WHERE clerk_id = '".$id."' LIMIT 1";
	$result = mysqli_query($dbconn, $query);
	$num_rows = mysqli_num_rows($result);
	if ($num_rows != 1) { return false; }
	else { return true; }
}

function getAcctNameById($id)
{
	GLOBAL $dbconn;
	$query = "SELECT account_name FROM accounts WHERE acct_id = '".$id."' LIMIT 1";
	$result = mysqli_query($dbconn, $query);
	$row = mysqli_fetch_array($result);
	return $row['account_name'];
}

function getClerkIniByCId($id)
{
	GLOBAL $dbconn;
	$query = "SELECT clerk_initials FROM operators WHERE clerk_id = '".$id."' LIMIT 1";
	$result = mysqli_query($dbconn, $query);
	$row = mysqli_fetch_array($result);
	return $row['clerk_initials'];
}

function genAcctHtmlBlock($id)
{
	GLOBAL $dbconn;
	$query = "SELECT * FROM accounts WHERE acct_id = '".$id."' LIMIT 1";
	$result = mysqli_query($dbconn, $query);
	$acctrow = mysqli_fetch_array($result);
	
	if (strlen($acctrow['account_phone']) != 10) { $acctrow['account_phone'] = '&nbsp;'; }
	
	$html = '
			<h2><span id="acct_id">'.$acctrow['acct_id'].'</span>Account</h2>
				<dl class="w25">
					<dt>Name: </dt><dd>'.$acctrow['account_name'].'</dd>
					<dt>Phone: </dt><dd>'.$acctrow['account_phone'].'</dd>
					<dt>Copies:</dt><dd><span style="border-left:5px solid #000;padding-left:5px;width:32%;display:inline-block;" id="fb_bw">'.$acctrow['copies_bw'].' BW</span><span style="border-left:5px solid #00ff00;padding-left:5px;width:32%;display:inline-block;" id="fb_color">'.$acctrow['copies_color'].' Color</span></dd>
					<dt>Notes:</dt><dd>'.$acctrow['account_notes'].'</dd>
					<dt>Status:</dt><dd>'.$acctrow['status'].'</dd>
					<dt>Negative Balances:</dt><dd>'.($acctrow['allow_neg_bal'] ? 'Allowed' : 'Not Allowed').'</dd>
				</dl>';
	return $html;
}

function genOperHtmlBlock($id)
{
	GLOBAL $dbconn;
	$query = "SELECT * FROM operators WHERE oper_id = '".$id."' LIMIT 1";
	$result = mysqli_query($dbconn, $query);
	$acctrow = mysqli_fetch_array($result);
	
	// Get current clerks info and check privileges
	if(isset($_SESSION['clerk_id']))
	{
		$query = "SELECT * FROM operators WHERE clerk_id = '".$_SESSION['clerk_id']."' LIMIT 1";
		$result = mysqli_query( $dbconn, $query);
		if (!$result) {
			die("Error: %s\n" . mysqli_error($dbconn));
		}
		$row = mysqli_fetch_array($result);
		
		// Blank out clerk ID if current clerk has insufficient level (less than 3)
		if($row['level'] < 3) $acctrow['clerk_id'] = '-';
	}
	
	$html = '
			<h2><span id="acct_id">'.$acctrow['oper_id'].'</span>Clerk</h2>
				<dl class="w25">
					<dt>Name: </dt><dd>'.$acctrow['clerk_name'].'</dd>
					<dt>Initials: </dt><dd>'.$acctrow['clerk_initials'].'</dd>
					<dt>ID:</dt><dd>'.$acctrow['clerk_id'].'</dd>
					<dt>Level:</dt><dd>'.convertLevel($acctrow['level']).'</dd>
					<dt>Status:</dt><dd>'.$acctrow['status'].'</dd>
				</dl>';
	return $html;
}

// Converts an operators level to a human readable value
function convertLevel($level)
{
	switch($level)
	{
		case 1:
			return 'New Hire';
			
		case 2:
			return 'Clerk';
			
		case 3:
			return 'Manager';
			
		default:
			return 'Invalid';
	}
}

function formError($formdata,$field)
{
	
}

$dbconn = ($GLOBALS["___mysqli_ston"] = mysqli_connect($dbhost,  $dbuser,  $dbpasswd)) or die("Couldn't Connect");
//mysql_set_charset('utf8',$dbconn); // Necessary for char data to be pulled from db with correct charset.
((bool)mysqli_query($GLOBALS["___mysqli_ston"], "USE $dbname"));
$action = (isset($_GET['action'])) ? $_GET['action'] : 'default';
$subaction = (isset($_GET['subaction'])) ? $_GET['subaction'] : 'default';
$name = (isset($_POST['name'])) ? addslashes($_POST['name']) : '';
$acct_id = (isset($_GET['acct_id'])) ? $_GET['acct_id'] : '';
$oper_id = (isset($_GET['oper_id'])) ? $_GET['oper_id'] : '';
$trans_id = (isset($_GET['trans_id'])) ? (int) $_GET['trans_id'] : '';
$cmd = (isset($_POST['cmd'])) ? $_POST['cmd'] : '';
$obj = (isset($_GET['obj'])) ? $_GET['obj'] : '';
$sort = (isset($_GET['sort'])) ? $_GET['sort'] : '';
if (isset($_GET['notice']))
{
	switch ($_GET['notice'])
	{
		case "dne":
			$notice = 'Could not find account - please try again.';
			break;
		case "userauth":
			$notice = 'Invalid Clerk ID: please try again.';
			break;
		case "acctexists":
			$notice = 'An account with that name already exists; please use a different name.';
			break;
		case "insufprivileges":
			$notice = 'Insufficient privileges; only managers can perform this action.';
			break;
		case "idexists":
			$notice = 'An account with that ID already exists; please use a different ID.';
			break;
		case "negbalnotenabled":
			$notice = 'Negative balances are not enabled.';
			break;
		case "negbalnotallowed":
			$notice = 'Negative balances are not allowed for this account.';
			break;
		case "savesuccessful":
			$notice = 'Save successful.';
			break;
	}
	$notice = '<div class="notice">'.$notice.'</div>';
}
$html = '';
///////////////////////////////////////
// ADD ACCOUNT
///////////////////////////////////////
if ($action == 'add_account')
{
	// NOTE: unset clerk_id in all actions except for change_settings for security purposes.
	if(isset($_SESSION['clerk_id'])) unset($_SESSION['clerk_id']);
	
	$formV = array();
	if ($cmd == 'new_acct')
	{
		$formV['v_name'] = filter($_POST['account_name']);
		$formV['v_phone'] = $_POST['account_phone'];
		$formV['v_notes'] = filter($_POST['account_notes']);
		$formV['v_time'] = time();
		
		if (empty($formV['v_name'])) {
			$formV['notValid'] = true;
			$formV['f_name'] = CC_FIELD_INVALID;
			$formV['t_name'] = ' title="You must enter an account name."';
		}
		
		if (isset($_POST['account_phone']))
		{
			if ((strlen($_POST['account_phone']) != 10 || !is_numeric($_POST['account_phone'])) && $_POST['account_phone'] != '')
			{
				$formV['notValid'] = true;
				$formV['f_phone'] = CC_FIELD_INVALID;
				$formV['t_phone'] = ' title="Phone must be 10 digits, numbers only."';
			}
		}
		
		if (!$formV['notValid'])
		{
			$sql = "SELECT account_name, acct_id FROM accounts WHERE account_name = '".$formV['v_name']."' LIMIT 1";
			$res = mysqli_query($dbconn, $sql);
			$num = mysqli_num_rows($res);
			if ($num == 1)
			{
				header("Location: ?action=add_account&notice=acctexists");
				die("Acct already exists");
			}
			
			$query = "INSERT INTO accounts (account_name, account_phone, account_notes, status, allow_neg_bal, creation_date)
						VALUES ('".$formV['v_name']."',
								'".$formV['v_phone']."',
								'".$formV['v_notes']."',
								'Active',
								".($settings->allow_default_neg_bal == 'true' ? "TRUE" : "FALSE").",
								'".$formV['v_time']."')";
		mysqli_query($dbconn, $query) or die('Failure');
		
		$query = " SELECT acct_id FROM accounts WHERE creation_date = '".$formV['v_time']."' LIMIT 1";
		$result = mysqli_query($dbconn, $query);
		$row = mysqli_fetch_array($result);
						
			header("Location: ?action=view_account&acct_id=".$row['acct_id']."&obj=add_copies");
		}
	}
	
	if ($cmd != 'new_acct' || $formV['notValid'] == true)
	{
		$js = '
		//var errorTips = new Tips(\'.errorTip\',{className:\'errorTipShell\'});
		
		$(\'account_name\').focus();

		';
		$html = '
		<h2>Add Account</h2>
			'.$notice.'
			<form action="?action=add_account" method="POST">
			<dl class="w25">
				<dt>Name: </dt><dd class="text-right"><input class="full'.$formV['f_name'].'" '.$formV['t_name'].' type="text" id="account_name" name="account_name" value="'.$formV['v_name'].'" /></dd>
				<dt>Phone #: </dt><dd class="text-right"><input class="full'.$formV['f_phone'].'" '.$formV['t_phone'].' type="text" name="account_phone" value="'.$formV['v_phone'].'" /></dd>
				<dt>Notes: </dt><dd class="text-right"><textarea class="txtarea" name="account_notes">'.$formV['v_notes'].'</textarea></dd>
				<dt>&nbsp;</dt><dd class="text-right"><input type="submit" value="Create" /></dd>
			</dl>
			<input type="hidden" name="cmd" value="new_acct" />
			</form>
		';
	}
}

///////////////////////////////////////
// EDIT ACCOUNT
///////////////////////////////////////
if ($action == 'edit_account')
{
	// NOTE: unset clerk_id in all actions except for change_settings for security purposes.
	if(isset($_SESSION['clerk_id'])) unset($_SESSION['clerk_id']);
	
	$formV = array();
	if ($cmd == 'edit_acct')
	{
		$formV['acct_id'] = $acct_id;
		$formV['v_name'] = filter($_POST['account_name']);
		$formV['v_phone'] = $_POST['account_phone'];
		$formV['v_notes'] = filter($_POST['account_notes']);
		$formV['v_status'] = $_POST['status'];
		$formV['v_allow_neg_bal'] = isset($_POST['allow_neg_bal']) && $_POST['allow_neg_bal'] ? 1 : 0;
		if (isset($_POST['account_phone']))
		{
			if ((strlen($_POST['account_phone']) != 10 || !is_numeric($_POST['account_phone'])) && $_POST['account_phone'] != '')
			{
				$formV['notValid'] = true;
				$formV['f_phone'] = CC_FIELD_INVALID;
				$formV['t_phone'] = ' title="Phone must be 10 digits, numbers only."';
			}
		}
		
		if (!$formV['notValid'])
		{	
			$sql = "UPDATE accounts
			SET account_name = '" . $formV['v_name'] . "',
				account_phone = '" . $formV['v_phone'] . "',
				account_notes = '" . $formV['v_notes'] . "',
				status = '" . $formV['v_status'] . "',
				allow_neg_bal = " . ($formV['v_allow_neg_bal'] ? "TRUE" : "FALSE") ."
			WHERE acct_id = '" . $formV['acct_id']."' LIMIT 1";

		$result = mysqli_query($dbconn, $sql);// or die('Failure');
		if (!$result)
		{
			die("Error: %s\n" . mysqli_error($dbconn));
		}
		
		$query = " SELECT acct_id FROM accounts WHERE acct_id = '".$formV['acct_id']."' LIMIT 1";
		$result = mysqli_query($dbconn, $query);
		$row = mysqli_fetch_array($result);
						
			header("Location: ?action=view_account&acct_id=".$row['acct_id']);
		}
	}
	
	if ($cmd != 'edit_acct' || $formV['notValid'] == true)
	{
		if ($formV['notValid'] != true)
		{
			$query = "SELECT * FROM accounts WHERE acct_id = '".$acct_id."' LIMIT 1";
			$result = mysqli_query($dbconn, $query);
			$acctrow = mysqli_fetch_array($result);
			
			$formV['v_name'] = $acctrow['account_name'];
			$formV['v_phone'] = (strlen($acctrow['account_phone']) == 10) ? $acctrow['account_phone'] : '';
			$formV['v_notes'] = $acctrow['account_notes'];
			$formV['v_status'] = $acctrow['status'];
			$formV['v_allow_neg_bal'] = $acctrow['allow_neg_bal'];
		}
	
		$js = '
		//var errorTips = new Tips(\'.errorTip\',{className:\'errorTipShell\'});
		
		//var checkPhone = new InputMask($(\'account_phone\'), { mask: \'(999) 999-9999\'3});
		';
		$html = '
		<h2>Edit Account</h2>
			'.$notice.'
			<form action="?action=edit_account&acct_id='.$acct_id.'" method="POST">
			<dl class="w33">
				<dt>Name: </dt><dd class="text-right"><input class="full" type="text" name="account_name" value="'.$formV['v_name'].'" /></dd>
				<dt>Phone #: </dt><dd class="text-right"><input class="full '.$formV['f_phone'].'" '.$formV['t_phone'].' type="text" name="account_phone" id="account_phone" value="'.$formV['v_phone'].'" /></dd>
				<dt>Notes: </dt><dd class="text-right"><textarea class="txtarea" name="account_notes">'.$formV['v_notes'].'</textarea></dd>
				<dt>Status: </dt><dd class="text-right">
					<select name="status" style="font-size: 2em">
						<option value="Active"'.(($formV['v_status'] == 'Active') ? ' selected' : '').'>Active</option>
						<option value="Inactive"'.(($formV['v_status'] == 'Inactive') ? ' selected' : '').'>Inactive</option>
					</select></dd>
				<dt>Allow negative balances</dt><dd class="text-right"><input type="checkbox" name="allow_neg_bal" value="1"'.($formV['v_allow_neg_bal'] ? ' checked' : '').'/></dd>
				<dt>&nbsp;</dt><dd class="text-right"><input type="submit" value="Edit" /></dd>
			</dl>
			<input type="hidden" name="cmd" value="edit_acct" />
			</form>
		';
	}
}

///////////////////////////////////////
// VIEW TRANSACTIONS
///////////////////////////////////////

else if ($action == 'view_transactions')
{
	// NOTE: unset clerk_id in all actions except for change_settings for security purposes.
	if(isset($_SESSION['clerk_id'])) unset($_SESSION['clerk_id']);
	
	///////////////////////////////////////
	// VIEW SINGLE TRANSACTION RESULT
	///////////////////////////////////////

	if ($trans_id)
	{
		$query = "SELECT * FROM transactions WHERE trans_id = '".$trans_id."' LIMIT 1";
		$result = mysqli_query($dbconn, $query);
		$row = mysqli_fetch_array($result);
		
		switch($row['trans_type'])
		{
			case "deposit":
				$deRow = 'Deposit Amount:';
				$rem_bw = $row['startbal_bw'] + $row['copies_bw'];
				$rem_color = $row['startbal_color'] + $row['copies_color'];
				$m = '+';
				break;
			case "debit":
				$deRow = 'Debit Amount:';
				$rem_bw = $row['startbal_bw'] - $row['copies_bw'];
				$rem_color = $row['startbal_color'] - $row['copies_color'];
				$m = '-';
				break;
			default:
				die("Error: Transaction Type Not Defined.");
				break;
		}
		
		$html = '
		<div class="grid_8 alpha">
			'.genAcctHtmlBlock($row['acct_id']).'
				
			<hr />
			
			<h3>Transaction Completed:</h3>
					<table class="table-right text-large"><th>&nbsp;</th><th>BW</th><th>Color</th></tr>
						<tr><td>Starting Balance:</td><td>'.$row['startbal_bw'].'</td><td>'.$row['startbal_color'].'</td></tr>
						<tr><td>'.$deRow.'</td><td>'.$row['copies_bw'].'</td><td>'.$row['copies_color'].'</td></tr>
						<tr class="balance_row"><td>Remaining:</td><td>'.$rem_bw.'</td><td>'.$rem_color.'</td></tr>
					</table><br /><br />
		</div>
		';
		
		$html_act = '
		<h3>Account Actions:</h3>
			<ul class="menu3">
				<li><a href="?action=view_transactions&acct_id='.$row['acct_id'].'">View All Transactions</a></li>
				<li><a href="?action=view_account&acct_id='.$row['acct_id'].'">Debit Copies</a></li>
				<li><a href="?action=view_account&acct_id='.$row['acct_id'].'&obj=add_copies">Add Copies</a></li>
				<li><a href="?action=edit_account&acct_id='.$row['acct_id'].'">Edit Account Info</a></li>
			</ul>
	';
	}
	///////////////////////////////////////
	// VIEW ACCOUNT TRANSACTIONS
	///////////////////////////////////////
	else if ($acct_id)
	{
		$query = "SELECT * FROM accounts WHERE acct_id = '".$acct_id."' LIMIT 1";
		$result = mysqli_query($dbconn, $query);
		$acctrow = mysqli_fetch_array($result);
		$query = "SELECT * FROM transactions WHERE acct_id = '".$acct_id."' ORDER BY trans_timestamp DESC";
		$result = mysqli_query($dbconn, $query);
		$rowlist = '<table><tr><th>TransID:</th><th>Clerk:</th><th>Date:</th><th>BW</th><th>Color</th><th>Notes:</th></tr>';
		while ($row = mysqli_fetch_array($result))
		{
			static $c = 0;
			if ($c % 2) { $rowlist .= '<tr class="even">'; }
			else { $rowlist .= '<tr class="odd">'; }
			$m = ($row['trans_type'] == 'deposit') ? '<span class="mkg">+</span>' : '<span glass="mkr"></span>';
			if ($row['trans_notes']) { $rownotes = '<span title="'.$row['trans_notes'].'" class="noteTip">Mouse over to view</span>'; } else { $rownotes = ''; }
			// ORIGINAL WITH MINUTES 
			//This was removed because the exact times are off and I don't know why.
			//$rowlist .= '<td class="text-right">'.$row['trans_id'].'</td><td>'.getClerkIniByCId($row['oper_id']).'</td><td>'.date('m-d-y g:ia',$row['trans_timestamp']).'</td><td class="text-right">'.$m.$row['copies_bw'].'</td><td class="text-right">'.$m.$row['copies_color'].'</td><td>'.$rownotes.'</td></tr>';
			$rowlist .= '<td class="text-right">'.$row['trans_id'].'</td><td>'.getClerkIniByCId($row['oper_id']).'</td><td>'.date('m-d-y',$row['trans_timestamp']).'</td><td class="text-right">'.$m.$row['copies_bw'].'</td><td class="text-right">'.$m.$row['copies_color'].'</td><td>'.$rownotes.'</td></tr>';
			$c++;
		}
		$rowlist .= '</table>';
		
		$html = '
		<div class="grid_8 alpha">
			'.genAcctHtmlBlock($acctrow['acct_id']).'
				
			<hr />
			
			<h2>All Transactions:</h2>
			'.$rowlist.'
			
		</div>';
		
		$html_act = '
		<h3>Account Actions:</h3>
			<ul class="menu3">
				<li><a href="?action=view_account&acct_id='.$acct_id.'">Debit Copies</a></li>
				<li><a href="?action=view_account&acct_id='.$acct_id.'&obj=add_copies">Add Copies</a></li>
				<li><a href="?action=edit_account&acct_id='.$acct_id.'">Edit Account Info</a></li>
			</ul>
	';
	}
	///////////////////////////////////////
	// VIEW CLERK TRANSACTIONS
	///////////////////////////////////////
	else if ($oper_id)
	{
		// Get clerk info
		$query = "SELECT clerk_name, clerk_id FROM operators WHERE oper_id = '".$oper_id."' LIMIT 1";
		$result = mysqli_query($dbconn, $query);
		$acctrow = mysqli_fetch_array($result);
		
		// Get transactions made by clerk
		$query = "SELECT * FROM transactions WHERE oper_id = '".$acctrow['clerk_id']."' ORDER BY trans_timestamp DESC";
		$result = mysqli_query($dbconn, $query);
		$rowlist = '<table><tr><th>TransID:</th><th>Date:</th><th>BW</th><th>Color</th><th>Notes:</th></tr>';
		while ($row = mysqli_fetch_array($result))
		{
			static $c = 0;
			if ($c % 2) { $rowlist .= '<tr class="even">'; }
			else { $rowlist .= '<tr class="odd">'; }
			$m = ($row['trans_type'] == 'deposit') ? '<span class="mkg">+</span>' : '<span glass="mkr"></span>';
			if ($row['trans_notes']) { $rownotes = '<span title="'.$row['trans_notes'].'" class="noteTip">Mouse over to view</span>'; } else { $rownotes = ''; }
			// ORIGINAL WITH MINUTES 
			//This was removed because the exact times are off and I don't know why.
			//$rowlist .= '<td class="text-right">'.$row['trans_id'].'</td><td>'.date('m-d-y g:ia',$row['trans_timestamp']).'</td><td class="text-right">'.$m.$row['copies_bw'].'</td><td class="text-right">'.$m.$row['copies_color'].'</td><td>'.$rownotes.'</td></tr>';
			$rowlist .= '<td class="text-right">'.$row['trans_id'].'</td><td>'.date('m-d-y',$row['trans_timestamp']).'</td><td class="text-right">'.$m.$row['copies_bw'].'</td><td class="text-right">'.$m.$row['copies_color'].'</td><td>'.$rownotes.'</td></tr>';
			$c++;
		}
		$rowlist .= '</table>';
		
		$html = '
		<div class="grid_8 alpha">
			
			<h2>All Transactions by '.$acctrow['clerk_name'].':</h2>
			'.$rowlist.'
			
		</div>';
	}
	else
	{
		$html = 'Transaction ID not specified.';
	}
}

///////////////////////////////////////
// VIEW REPORTS
///////////////////////////////////////
else if ($action == 'view_reports')
{
	// NOTE: unset clerk_id in all actions except for change_settings for security purposes.
	if(isset($_SESSION['clerk_id'])) unset($_SESSION['clerk_id']);
	
	if ($obj)
	{
		///////////////////////////////////////
		// ALL ACCOUNTS
		///////////////////////////////////////
		if ($obj == 'all_accounts')
		{
			$sortr = (isset($_GET['reverse'])) ? " DESC" : " ASC";
			switch ($sort)
			{
				case "acct_id":
					$sortsql = 'acct_id';
					$sortid = (!isset($_GET['reverse'])) ? '&reverse=1' : '';
					break;
				case "copies_bw":
					$sortsql = 'copies_bw';
					$sortbw = (!isset($_GET['reverse'])) ? '&reverse=1' : '';
					break;
				case "copies_color":
					$sortsql = 'copies_color';
					$sortcolor = (!isset($_GET['reverse'])) ? '&reverse=1' : '';
					break;
				case "status":
					$sortsql = 'status';
					$sortcolor = (!isset($_GET['reverse'])) ? '&reverse=1' : '';
					break;
				case "account_name":
				default:
					$sortsql = 'account_name';
					$sortname = (!isset($_GET['reverse'])) ? '&reverse=1' : '';
					break;
			}
			$sorturl = "?action=view_reports&obj=all_accounts";
			
			$query = "SELECT account_name, acct_id, copies_bw, copies_color, status FROM accounts ORDER BY " . $sortsql . $sortr;
			$result = mysqli_query($dbconn, $query);
			$acctlist = '<table>
							<tr><th><a href="'.$sorturl.'&sort=acct_id'.$sortid.'">Acct ID:</a></th><th><a href="'.$sorturl.'&sort=account_name'.$sortname.'">Account Name:</a></th><th><a href="'.$sorturl.'&sort=copies_bw'.$sortbw.'">BW</a></th><th><a href="'.$sorturl.'&sort=copies_color'.$sortcolor.'">Color</a></th><th><a href="'.$sorturl.'&sort=status'.$sortstatus.'">Status</a></th>';
			while ($row = mysqli_fetch_array($result))
			{
				static $i = 0;
				$evenodd = ($i % 2) ? 'even' : 'odd';
				$acctlist .= '<tr class="'.$evenodd.'"><td>'.$row['acct_id'].'</td><td><a href="?action=view_account&acct_id='.$row['acct_id'].'">'.$row['account_name'].'</a></td><td class="text-right">'.$row['copies_bw'].'</td><td class="text-right">'.$row['copies_color'].'</td><td class="text-right">'.$row['status'].'</td></tr>';
				$i++;
			}
			$acctlist .= '</table>';
			
			$query = "SELECT sum(copies_bw), sum(copies_color), count(acct_id) FROM accounts";
			$result = mysqli_query($GLOBALS["___mysqli_ston"], $query);
			$overall = mysqli_fetch_array($result);
			$query = "SELECT count(trans_id), sum(copies_bw), sum(copies_color) FROM transactions";
			$result = mysqli_query($GLOBALS["___mysqli_ston"], $query);
			$overall2 = mysqli_fetch_array($result);
			
			$html = '
			<h2>Overall:</h2>
				<div class="grid_4 alpha">
					Total Accounts: '.$overall['count(acct_id)'].'<br />
					Total BW Copies Avail: '.$overall['sum(copies_bw)'].'<br />
					Total Color Avail: '.$overall['sum(copies_color)'].'<br /><br />
				</div>
				
				<div class="grid_4 omega">
					Total Transactions: '.$overall2['count(trans_id)'].'<br />
					Total BW Copies Processed: '.$overall2['sum(copies_bw)'].'<br />
					Total Color Processed: '.$overall2['sum(copies_color)'].'<br /><br />
				</div>
				
				<hr />
				
			<h2>All Accounts:</h2>
				'.$acctlist.'
			';
					
		}
		
		///////////////////////////////////////
		// VIEW NEGATIVE BALANCES
		///////////////////////////////////////
		else if ($obj == 'view_negative_balances')
		{
			$sortr = (isset($_GET['reverse'])) ? " DESC" : " ASC";
			switch ($sort)
			{
				case "acct_id":
					$sortsql = 'acct_id';
					$sortid = (!isset($_GET['reverse'])) ? '&reverse=1' : '';
					break;
				case "copies_bw":
					$sortsql = 'copies_bw';
					$sortbw = (!isset($_GET['reverse'])) ? '&reverse=1' : '';
					break;
				case "copies_color":
					$sortsql = 'copies_color';
					$sortcolor = (!isset($_GET['reverse'])) ? '&reverse=1' : '';
					break;
				case "status":
					$sortsql = 'status';
					$sortcolor = (!isset($_GET['reverse'])) ? '&reverse=1' : '';
					break;
				case "account_name":
				default:
					$sortsql = 'account_name';
					$sortname = (!isset($_GET['reverse'])) ? '&reverse=1' : '';
					break;
			}
			$sorturl = "?action=view_reports&obj=view_negative_balances";
			
			$query = "SELECT account_name, acct_id, copies_bw, copies_color, status FROM accounts WHERE copies_bw < 0 OR copies_color < 0 ORDER BY " . $sortsql . $sortr;
			$result = mysqli_query($dbconn, $query);
			$acctlist = '<table>
							<tr><th><a href="'.$sorturl.'&sort=acct_id'.$sortid.'">Acct ID:</a></th><th><a href="'.$sorturl.'&sort=account_name'.$sortname.'">Account Name:</a></th><th><a href="'.$sorturl.'&sort=copies_bw'.$sortbw.'">BW</a></th><th><a href="'.$sorturl.'&sort=copies_color'.$sortcolor.'">Color</a></th><th><a href="'.$sorturl.'&sort=status'.$sortstatus.'">Status</a></th>';
			while ($row = mysqli_fetch_array($result))
			{
				static $i = 0;
				$evenodd = ($i % 2) ? 'even' : 'odd';
				$acctlist .= '<tr class="'.$evenodd.'"><td>'.$row['acct_id'].'</td><td><a href="?action=view_account&acct_id='.$row['acct_id'].'">'.$row['account_name'].'</a></td><td class="text-right">'.$row['copies_bw'].'</td><td class="text-right">'.$row['copies_color'].'</td><td class="text-right">'.$row['status'].'</td></tr>';
				$i++;
			}
			$acctlist .= '</table>';
			
			$query = "SELECT sum(copies_bw), sum(copies_color), count(acct_id) FROM accounts";
			$result = mysqli_query($GLOBALS["___mysqli_ston"], $query);
			$overall = mysqli_fetch_array($result);
			$query = "SELECT count(trans_id), sum(copies_bw), sum(copies_color) FROM transactions";
			$result = mysqli_query($GLOBALS["___mysqli_ston"], $query);
			$overall2 = mysqli_fetch_array($result);
			
			$html = '
			<h2>Overall:</h2>
				<div class="grid_4 alpha">
					Total Accounts: '.$overall['count(acct_id)'].'<br />
					Total BW Copies Avail: '.$overall['sum(copies_bw)'].'<br />
					Total Color Avail: '.$overall['sum(copies_color)'].'<br /><br />
				</div>
				
				<div class="grid_4 omega">
					Total Transactions: '.$overall2['count(trans_id)'].'<br />
					Total BW Copies Processed: '.$overall2['sum(copies_bw)'].'<br />
					Total Color Processed: '.$overall2['sum(copies_color)'].'<br /><br />
				</div>
				
				<hr />
				
			<h2>All Accounts with Negative Balances:</h2>
				'.$acctlist.'
			';
		}
		
		///////////////////////////////////////
		// ALL TRANSACTIONS
		///////////////////////////////////////
		else if ($obj == 'all_trans')
		{
			$query = "SELECT * FROM transactions ORDER BY trans_timestamp DESC";
			$result = mysqli_query($dbconn, $query);
			$translist = '<table class="moredata">
							<tr><th>Trans ID:</th><th>Date:</th><th>Account Name:</th><th>BW</th><th>Color</th><th>Notes:</th></tr>';
			while ($row = mysqli_fetch_array($result))
			{
				static $i = 0;
				$evenodd = ($i % 2) ? 'even' : 'odd';
				$typeclass = '';
				if ($row['trans_type'] == 'deposit') { $typeclass = ' deposit'; }
				$notes = (!empty($row['trans_notes'])) ? '<span class="noteTip" title="'.$row['trans_notes'].'">View Notes</span>' : '&nbsp;';
				// ORIGINAL WITH MINUTES $translist .= '<tr class="'.$evenodd.'"><td>'.$row['trans_id'].'</td><td>'.date('m-d-y g:ia',$row['trans_timestamp']).'<td><a href="?action=view_account&acct_id='.$row['acct_id'].'">'.getAcctNameById($row['acct_id']).'</a></td><td class="text-right'.$typeclass.'">'.$row['copies_bw'].'</td><td class="text-right'.$typeclass.'">'.$row['copies_color'].'</td><td>'.$notes.'</tr>';
				$translist .= '<tr class="'.$evenodd.'"><td>'.$row['trans_id'].'</td><td>'.date('m-d-y',$row['trans_timestamp']).'<td><a href="?action=view_account&acct_id='.$row['acct_id'].'">'.getAcctNameById($row['acct_id']).'</a></td><td class="text-right'.$typeclass.'">'.$row['copies_bw'].'</td><td class="text-right'.$typeclass.'">'.$row['copies_color'].'</td><td>'.$notes.'</tr>';
				$i++;
			}
			$translist .= '</table>';
			
			$html = '
				<h2>All Transactions:</h2>
				'.$translist.'
			';
		}
		
		///////////////////////////////////////
		// TRANSACTIONS BY DATE
		///////////////////////////////////////
		else if ($obj == 'trans_by_date')
		{
			if ($cmd == 'dateset')
			{
				// A bug appeared after adding view inactive customers by date.
				// If not explicitly set by the user, $_POST['date_prime'] had no value. This fixes the problem. Defaults to the current day.
				if(!$_POST['date_prime'])
				{
					$_POST['date_prime'] = date("m/d/Y");
				}
			
				$dp = explode('/',$_POST['date_prime']); // Date format should be m/d/Y
				$de = explode('/',$_POST['date_end']);
				$date_prime = mktime(0,0,0,$dp[0],$dp[1],$dp[2]);
				if (count($de) == 3) { $date_end = mktime(0,0,0,$de[0],$de[1],$de[2]); }
				if ($date_prime)
				{
					// Get filter options
					$clerk_name = isset($_POST['clerk_name']) ? $_POST['clerk_name'] : '';
					$show_neg_bal_only = isset($_POST['neg_bal_only']);
					$show_bw = isset($_POST['show_bw']);
					$show_color = isset($_POST['show_color']);
					
					$clerk_filer = '';
					$clerk_inititals = '';
					if(!empty($clerk_name))
					{
						$query = "SELECT clerk_id, clerk_initials FROM operators WHERE clerk_name = '".$clerk_name."'";
						$result = mysqli_query($dbconn, $query);
						$row = mysqli_fetch_array($result);
						$clerk_filer = ' AND oper_id = '.$row['clerk_id'];
						$clerk_initials = $row['clerk_initials'];
					}
					
					if ($date_end)
					{
						$enddate = ' to ' . date('M jS',$date_end); // for display to user.
						$endtime = $date_end + ((60 * 60 * 24 ) - 1);
						$query = "SELECT * FROM transactions WHERE trans_timestamp > '".$date_prime."' AND trans_timestamp < '".$endtime."'";
						if(!$show_bw || !$show_color)
						{
							$query .= $show_bw ? " AND copies_bw > 0" : "";
							$query .= $show_color ? " AND copies_color > 0" : "";
						}
						$query .= $clerk_filer;
						$query .= " ORDER BY trans_timestamp ASC";
					}
					else
					{
						$enddate = '';
						$endtime = $date_prime + ((60 * 60 * 24 ) - 1);
						$query = "SELECT * FROM transactions WHERE trans_timestamp > '".$date_prime."' AND trans_timestamp < '".$endtime."'";
						if(!$show_bw || !$show_color)
						{
							$query .= $show_bw ? " AND copies_bw > 0" : "";
							$query .= $show_color ? " AND copies_color > 0" : "";
						}
						$query .= $clerk_filer;
						$query .= " ORDER BY trans_timestamp ASC";
					}
					
					$result = mysqli_query($dbconn, $query);
					$translist = '<table class="moredata">
							<tr><th>Trans ID:</th><th>Clerk:</th><th>Date:</th><th>Account Name:</th>'.($show_bw ? '<th>BW</th>' : '').($show_color ? '<th>Color</th>' : '').'<th>Notes:</th></tr>';
					if($result)
					{
						while ($row = mysqli_fetch_array($result))
						{
							// Determine if the transaction caused a negative balance.
							if($show_neg_bal_only)
							{
								if($row['trans_type'] == 'debit')
								{
									$bw_bal = $row['startbal_bw'] - $row['copies_bw'];
									$color_bal = $row['startbal_color'] - $row['copies_color'];
								}
								else
								{
									$bw_bal = $row['startbal_bw'] + $row['copies_bw'];
									$color_bal = $row['startbal_color'] + $row['copies_color'];
								}
								
								// If not negative balance, skip transaction
								if($bw_bal >= 0 && $color_bal >= 0)
								{
									continue;
								}
							}
							
							static $i = 0;
							$evenodd = ($i % 2) ? 'even' : 'odd';
							$typeclass = '';
							if ($row['trans_type'] == 'deposit') { $typeclass = ' deposit'; }
							$notes = (!empty($row['trans_notes'])) ? '<span class="noteTip" title="'.$row['trans_notes'].'">View Notes</span>' : '&nbsp;';
							// ORIGINAL WITH MINUTES $translist .= '<tr class="'.$evenodd.'"><td>'.$row['trans_id'].'</td><td>'.date('m-d-y g:ia',$row['trans_timestamp']).'</td><td><a href="?action=view_account&acct_id='.$row['acct_id'].'">'.getAcctNameById($row['acct_id']).'</a></td><td class="text-right'.$typeclass.'">'.$row['copies_bw'].'</td><td class="text-right'.$typeclass.'">'.$row['copies_color'].'</td><td>'.$notes.'</tr>'."\n";
							$translist .= '<tr class="'.$evenodd.'"><td>'.$row['trans_id'].'</td><td>'.getClerkIniByCId($row['oper_id']).'</td><td>'.date('m-d-y',$row['trans_timestamp']).'</td><td><a href="?action=view_account&acct_id='.$row['acct_id'].'">'.getAcctNameById($row['acct_id']).'</a></td>'.($show_bw ? '<td class="text-right'.$typeclass.'">'.$row['copies_bw'].'</td>' : '').($show_color ? '<td class="text-right'.$typeclass.'">'.$row['copies_color'].'</td>' : '').'<td>'.$notes.'</tr>'."\n";
							$i++;
						}
					}
					$translist .= '</table>';
					
					$query = "SELECT count(trans_id), sum(copies_bw), sum(copies_color) FROM transactions WHERE trans_timestamp > '".$date_prime."' AND trans_timestamp < '".$endtime."' AND trans_type = 'debit'";
					$result = mysqli_query($dbconn, $query);
					$debitstat = mysqli_fetch_array($result);
					
					$query = "SELECT count(trans_id), sum(copies_bw), sum(copies_color) FROM transactions WHERE trans_timestamp > '".$date_prime."' AND trans_timestamp < '".$endtime."' AND trans_type = 'deposit'";
					$result = mysqli_query($dbconn, $query);
					$depositstat = mysqli_fetch_array($result);
					
					$num_trans = ($debitstat['count(trans_id)'] + $depositstat['count(trans_id)']);
					
					$html = '
						<h2>Overall Stats for '.date('M jS',$date_prime).$enddate.':</h2>
							<div class="grid_4 alpha">
								<dl class="w75">
									<dt>Total BW Copies Used:</dt><dd class="text-right">'.(int) $debitstat['sum(copies_bw)'].'</dd>
									<dt>Total Color Used:</dt><dd class="text-right">'.(int) $debitstat['sum(copies_color)'].'</dd>
									<dt>Total Transactions:</dt><dd class="text-right">'.$num_trans.'</dd>
								</dl>
							</div>
							
							<div class="grid_4 omega">
								<dl class="w75">
									<dt>Total BW Copies Added:</dt><dd class="text-right">'.(int) $depositstat['sum(copies_bw)'].'</dd>
									<dt>Total Color Added:</dt><dd class="text-right">'.(int) $depositstat['sum(copies_color)'].'</dd>
									<dt>&nbsp;</dt><dd class="text-right">&nbsp;</dd>
								</dl>
							</div>
						<hr />
						
						<h2>All Transactions for '.date('M jS',$date_prime).$enddate.':</h2>
						'.$translist.'
						<hr />
					';
					
					if ($_SESSION['quickComplete']) { $delay = 250; } else { $delay = 1000; }
					$js = "
						var inputWord = $('clerk_name');
				 
						new Autocompleter.Request.JSON(inputWord, 'autofindclerk.php', {
							'indicatorClass': 'autocompleter-loading',
							'selectMode': 'type-ahead',
							'delay': ".$delay."
						});
					";
					
					$html_act = '
						<h3>Filter By:</h3>
						<form action="?action=view_reports&obj=trans_by_date" method="post">
							<dl class="w75">
								<!--dt>Date:<br /><span class="noteTip" title="Enter just a single date to view all transactions for that day, or enter a date range to view all transactions within that range.">Help ?</span></dt><dd class="text-right"><input style="width:186px;" type="text" name="date_prime" id="date_prime" /> - <input style="width:186px;" type="text" name="date_end" id="date_end" /></dd-->
								<dt style="font-size: 14px">Clerk Name:</dt><input style="width:186px;font-size: 16px" type="text" name="clerk_name" id="clerk_name" value="'.$clerk_name.'"/> <br/><br/>
								<dt style="font-size: 14px">Negative Balances Only:</dt><dd><input type="checkbox" name="neg_bal_only" value="1"'.($show_neg_bal_only ? ' checked' : '').'/></dd>
								<dt style="font-size: 14px">Type:</dt><br/>
								<dt style="font-size: 14px">&nbsp;&nbsp;&nbsp;BW</dt><dd><input type="checkbox" name="show_bw" value="1"'.($show_bw ? ' checked' : '').'/></dd>
								<dt style="font-size: 14px">&nbsp;&nbsp;&nbsp;Color</dt><dd><input type="checkbox" name="show_color" value="1"'.($show_color ? ' checked' : '').'/></dd>
								<dt>&nbsp;</dt><dd class="text-right"><input type="submit" value="Filter" style="font-size:16px" /></dd>
							</dl>
							<input type="hidden" name="cmd" value="dateset" />
							<input type="hidden" name="date_prime" value="'.$_POST['date_prime'].'"/>
							<input type="hidden" name="date_end" value="'.$_POST['date_end'].'"/>
						</form>
						';
				}
				else
				{
					$html = '<span class="notice">Invalid date entered.</span>';
				}
			}
			
			else
			{
				$js = "
					var date_prime = new DatePicker($('date_prime'), { pickerClass:'datepicker_vista', format:'m-d-Y', inputOutputFormat: 'm/d/Y' });
					var date_end = new DatePicker($('date_end'), { pickerClass:'datepicker_vista', format:'m-d-Y', inputOutputFormat: 'm/d/Y', allowEmpty:true });
				";
				
				$html = '
					<h2>View Transactions:</h2>
						<h3>Select Date(s):</h3>
							<form action="?action=view_reports&obj=trans_by_date" method="post">
							<dl class="w25">
								<dt>Date:<br /><span class="noteTip" title="Enter just a single date to view all transactions for that day, or enter a date range to view all transactions within that range.">Help ?</span></dt><dd class="text-right"><input style="width:186px;" type="text" name="date_prime" id="date_prime" /> - <input style="width:186px;" type="text" name="date_end" id="date_end" /></dd>
								<dt>&nbsp;</dt><dd class="text-right"><input type="submit" value="View" /></dd>
							</dl>
							<input type="hidden" name="cmd" value="dateset" />
							<input type="hidden" name="show_bw" value="1"/>
							<input type="hidden" name="show_color" value="1"/>
							</form>
				';
			}
		}
		
		///////////////////////////////////////
		// INACTIVE CUSTOMERS SINCE DATE
		///////////////////////////////////////
		else if($obj == 'inactivity')
		{
			if ($cmd == 'dateset')
			{
				// If not explicitly set by the user, $_POST['date'] has no value. This fixes the problem. Defaults to the current day.
				if(!$_POST['date'])
				{
					$_POST['date'] = date("m/d/Y");
				}
			
				$dp = explode('/',$_POST['date']); // Date format should be m/d/Y
				$date = mktime(0,0,0,$dp[0],$dp[1],$dp[2]);
				
				if ($date)
				{
					// Get all accounts that are currently active.
					$query = "SELECT * FROM transactions WHERE trans_timestamp > '".$date."' ORDER BY trans_timestamp ASC";
					$result = mysqli_query($dbconn, $query);
					$active_accounts = array();
					while ($row = mysqli_fetch_array($result))
					{
						static $i = 0;
						$evenodd = ($i % 2) ? 'even' : 'odd';
						
						if(!in_array($row['acct_id'], $active_accounts))
						{
							array_push($active_accounts, $row['acct_id']);
						}
						$i++;
					}
					
					// Build query for getting all inactive customers
					$query = "SELECT * from accounts";
					for($i = 0; $i < count($active_accounts); $i++)
					{
						if($i == 0)
						{
							$query .= " WHERE acct_id <> " . $active_accounts[$i];
						}
						else
						{
							$query .= " AND acct_id <> " . $active_accounts[$i];
						}
					}
					$query .= " ORDER BY account_name";
					
					$result = mysqli_query($dbconn, $query);
					$acctlist = '<table>
							<tr><th>Acct ID:</th><th>Account Name</th><th>BW</th><th>Color</th><th>Status</th>';
					while ($row = mysqli_fetch_array($result))
					{
						static $i = 0;
						$evenodd = ($i % 2) ? 'even' : 'odd';
						$acctlist .= '<tr class="'.$evenodd.'"><td>'.$row['acct_id'].'</td><td><a href="?action=view_account&acct_id='.$row['acct_id'].'">'.$row['account_name'].'</a></td><td class="text-right">'.$row['copies_bw'].'</td><td class="text-right">'.$row['copies_color'].'</td><td>'.$row['status'].'</td></tr>';
						$i++;
					}
					$acctlist .= '</table>';
					
					$html = '
						<h2>Inactive Accounts Since '.date('M jS, Y',$date).':</h2>
							'.$acctlist.'
						';
				}
				else
				{
					$html = '<span class="notice">Invalid date entered.</span>';
				}
			}
			
			else
			{
				$js = "
					var date = new DatePicker($('date'), { pickerClass:'datepicker_vista', format:'m-d-Y', inputOutputFormat: 'm/d/Y' });
				";
				
				$html = '
					<h2>View Inactive Customers:</h2>
						<h3>Select Date:</h3>
							<form action="?action=view_reports&obj=inactivity" method="post">
							<dl class="w25">
								<dt>Date:<br /><span class="noteTip" title="Enter a date to view all customers inactive since that date.">Help ?</span></dt><dd class="text-right"><input style="width:186px;" type="text" name="date" id="date" /></dd>
								<dt>&nbsp;</dt><dd class="text-right"><input type="submit" value="View" /></dd>
							</dl>
							<input type="hidden" name="cmd" value="dateset" />
							</form>
				';
			}
		}
	}
	
	else
	{
		$html = '
	<h2>View Reports</h2>
		<div class="grid_4 alpha">
			<h3>By Account:</h3>
			<ul class="menu3">
				<li><a href="?action=view_reports&obj=all_accounts">View All Accounts</a></li>
				<li><a href="?action=find_account&redirect=view_transactions">View All Transactions by Account</a></li>
				<li><a href="?action=view_reports&obj=view_negative_balances">View All Negative Balances</a></li>
				<!--<li><a href="#">View Transactions by Date</a></li>-->
			</ul>
		</div>
		
		<div class="grid_4 omega">
			<h3>By Date:</h3>
			<ul class="menu3">
				<li><a href="?action=view_reports&obj=all_trans">(Slow) View All Transactions</a></li>
				<!--<li><a href="#">View All Transactions by Day</a></li>-->
				<li><a href="?action=view_reports&obj=trans_by_date">View Transactions by Date</a></li>
				<li><a href="?action=view_reports&obj=inactivity">View Customers Inactive Since Date</a></li>
			</ul>
		</div>
		
		<div class="clear"></div>
		<br /><br />
		
		<div class="grid_4 alpha">
			<h3>By Clerk:</h3>
			<ul class="menu3">
				<li><a href="?action=find_account&redirect=view_transactions&search_clerk=true">View All Transactions by Clerk</a></li>
			</ul>
		</div>
		
		<div class="clear"></div>
		<br /><br />
		';
	}
}

///////////////////////////////////////
// DO TRANSACTION
///////////////////////////////////////
else if ($action == 'do_transaction')
{
	// NOTE: unset clerk_id in all actions except for change_settings for security purposes.
	if(isset($_SESSION['clerk_id'])) unset($_SESSION['clerk_id']);
	
	$postdata['acct_id'] = cleanData($_POST['acct_id']);
	$postdata['bw'] = cleanData($_POST['bw']);
	$postdata['startbal_bw'] = cleanData($_POST['startbal_bw']);
	$postdata['color'] = cleanData($_POST['color']);
	$postdata['startbal_color'] = cleanData($_POST['startbal_color']);
	$postdata['clerk_id'] = cleanData($_POST['clerk_id']);
	$postdata['trans_notes'] = cleanData($_POST['notes']);
	$postdata['trans_type'] = $_POST['trans_type'];
	$timed = time();
	
	$query = "SELECT * FROM accounts WHERE acct_id = '".$postdata['acct_id']."' LIMIT 1";
	$result = mysqli_query( $dbconn, $query);
	if (mysqli_num_rows($result) != 1)
	{
		die ("Data communications integrity has been breached.");
	}
	$row = mysqli_fetch_array($result);
	
	// MIGRATION MODE
	// Modify date to static for entering of previous accounts.
	if (isset($_POST['prevAcct']) || $_SESSION['migration'] == true) { $timed = mktime(0, 0, 0, 9, 22, 2009); }
	
	
			
	if ($postdata['trans_type'] == 'debit')
	{
		$bw_new = (int) $postdata['startbal_bw'] - $postdata['bw'];
		$color_new = (int) $postdata['startbal_color'] - $postdata['color'];
		$authUriAppend = '';
	}
	else if ($postdata['trans_type'] == 'deposit')
	{
		$bw_new = (int) $postdata['startbal_bw'] + $postdata['bw'];
		$color_new = (int) $postdata['startbal_color'] + $postdata['color'];
		$authUriAppend = '&obj=add_copies';
	}
	else
	{
		die("Safeguard Event.");
	}
	
	if (!authClerk($postdata['clerk_id']))
	{
		
		header("Location: ?action=view_account&acct_id=".$postdata['acct_id'].$authUriAppend."&notice=userauth&nbw=".$postdata['bw']."&ncolor=".$postdata['color']);
		die("Authentication verification denial safeguard event.");
	}
	
	// If negative balance, check if it's enabled and allowed for the user (only checked if trans_type is debit).
	if (($postdata['bw'] > 0 && $bw_new < 0 || ($postdata['color'] > 0 && $color_new < 0)) && ($settings->enable_neg_bal == 'false' || !$row['allow_neg_bal']) && $postdata['trans_type'] == 'debit')
	{
		if($settings->enable_neg_bal == 'false')
		{
			header("Location: ?action=view_account&acct_id=".$postdata['acct_id'].$authUriAppend."&notice=negbalnotenabled&nbw=".$postdata['bw']."&ncolor=".$postdata['color']);
			die("Negative balances not enabled.");
		}
		else
		{
			header("Location: ?action=view_account&acct_id=".$postdata['acct_id'].$authUriAppend."&notice=negbalnotallowed&nbw=".$postdata['bw']."&ncolor=".$postdata['color']);
			die("Negative balances not allowed for this account.");
		}
	}

	$sql = "UPDATE accounts
			SET copies_bw = '" . $bw_new . "',
				copies_color = '" . $color_new . "',
				status = 'Active'
			WHERE acct_id = '" . $postdata['acct_id']."' LIMIT 1";

	if (mysqli_query($dbconn, $sql))
	{
		
		$query = "INSERT INTO transactions (acct_id, trans_timestamp, trans_notes, trans_type, copies_bw, copies_color, startbal_bw, startbal_color, oper_id)
						VALUES ('".$postdata['acct_id']."',
								'".$timed."',
								'".$postdata['trans_notes']."',
								'".$postdata['trans_type']."',
								'".$postdata['bw']."',
								'".$postdata['color']."',
								'".$postdata['startbal_bw']."',
								'".$postdata['startbal_color']."',
								'".$postdata['clerk_id']."')";
		mysqli_query($dbconn, $query) or die('Failure');
		
		$query = " SELECT trans_id FROM transactions WHERE acct_id = '".$postdata['acct_id']."' AND trans_timestamp = '".$timed."' AND startbal_bw = '".$postdata['startbal_bw']."' AND startbal_color = '".$postdata['startbal_color']."' LIMIT 1";
		$result = mysqli_query($dbconn, $query);
		$row = mysqli_fetch_array($result);

		header("Location: ?action=view_transactions&trans_id=".$row['trans_id']);
	}
	else 
	{
		echo "<pre>";
		print_r($postdata);
		echo "<hr />";
		print_r($sql);
		echo "</pre>";
	}
		
}

///////////////////////////////////////
// FIND ACCOUNT / VIEW ACCOUNT
///////////////////////////////////////
else if ($action == 'find_account' || $action == 'view_account')
{
	// NOTE: unset clerk_id in all actions except for change_settings for security purposes.
	if(isset($_SESSION['clerk_id'])) unset($_SESSION['clerk_id']);
	
	if ($name || $acct_id)
	{
		// search_clerk is a hackjob for allowing to search for a clerk, but it works (only for redirecting to view_transactions)
		$search_clerk = (isset($_GET['search_clerk']) && $_GET['search_clerk'] == 'true') ? true : false;
		
		if ($name)
		{
			if($search_clerk)
			{
				$query = "SELECT * FROM operators WHERE clerk_name = '".$name."' LIMIT 1";
			}
			// Check if searched by phone number
			else if (strlen($name) == 10 && is_numeric($name))
			{
				$query = "SELECT * FROM accounts WHERE account_phone = '".$name."' LIMIT 1";
			}
			else
			{
				$query = "SELECT * FROM accounts WHERE account_name = '".$name."' LIMIT 1";
			}
			
			$result = mysqli_query( $dbconn, $query);
			$row = mysqli_fetch_array($result);
		}
		else if ($acct_id)
		{
			$query = "SELECT * FROM accounts WHERE acct_id = '".$acct_id."' LIMIT 1";
			$result = mysqli_query( $dbconn, $query);
			$row = mysqli_fetch_array($result);
		}
		
		if (!$search_clerk && !isset($row['acct_id']) || $search_clerk && !isset($row['oper_id']))
		{
			header("Location: ?action=find_account&notice=dne");
		}
		
		if ($_GET['redirect'] == 'view_transactions') { header("Location: ?action=view_transactions&".(($search_clerk) ? "oper_id" : "acct_id")."=".(($search_clerk) ? $row['oper_id'] : $row['acct_id'])); }
		
		$nbw = (is_numeric($_GET['nbw'])) ? $_GET['nbw'] : '';
		$ncolor = (is_numeric($_GET['ncolor'])) ? $_GET['ncolor'] : '';
		
		$js = "
		// cc = confirmation popup confirming # copies to debit
		// cb = confirmation popup remaining balance
		// ff = form # copies to debit
		// fb = form/account current balance
		
		var cc_shell = $('confirmation_shell');		var cc_pop = $('confirmation');
		var ff_bw = $('debit_bw');					var fb_bw = $('fb_bw');				var h_bw = $('h_bw');
		var ff_color = $('debit_color');			var fb_color = $('fb_color');		var h_color = $('h_color');
		var cc_bw = $('confirmDebit_bw');
		var cc_color = $('confirmDebit_color');
		var cb_bw = $('rem_bw');
		var cb_color = $('rem_color');
		var winsz = window.getSize();
		var deb = $('doDebit');
		var ff_notes = $('trans_notes');			var h_notes = $('h_notes');
		var db_type = $('h_type');
		
		ff_bw.focus();
		
	$('confirmDebit').addEvent('click', function(event) {
		event.stop();
		
		// BW
		// Check for empty values, CSS: apply classes based on remaining balance
		if (!$chk(ff_bw.value)) { cc_bw.set('html',0); h_bw.set('value',0); }
		else { cc_bw.set('html',ff_bw.value); h_bw.set('value',ff_bw.value); }
		if (db_type.get('value') == 'debit') { cb_bw.set('text',(fb_bw.get('text').toInt() - ff_bw.get('value').toInt())); }
		else if (db_type.get('value') == 'deposit') { cb_bw.set('text',(fb_bw.get('text').toInt() + ff_bw.get('value').toInt())); }
		if (cb_bw.get('html') < 50 && cb_bw.get('html') >= 0 && db_type.get('value') == 'debit') { cb_bw.addClass('remainingLow'); } else if (cb_bw.get('html') < 0) { cb_bw.addClass('remainingDebt'); cc_bw.addClass('remainingDebt'); }
		
		// Color
		if (!$chk(ff_color.value)) { cc_color.set('html',0); h_color.set('value',0); }
		else { cc_color.set('html',ff_color.value); h_color.set('value',ff_color.value); }
		if (db_type.get('value') == 'debit') { cb_color.set('text',(fb_color.get('text').toInt() - ff_color.get('value').toInt() )); }
		else if (db_type.get('value') == 'deposit') { cb_color.set('text',(fb_color.get('text').toInt() + ff_color.get('value').toInt() )); }
		if (cb_color.get('html') < 25 && cb_color.get('html') >= 0 && db_type.get('value') == 'debit') { cb_color.addClass('remainingLow'); } else if (cb_color.get('html') < 0) { cb_color.addClass('remainingDebt'); cc_color.addClass('remainingDebt'); }

		h_notes.set('value', ff_notes.value);
		
		cc_shell.set('styles', {
						'display': 'block',
						'height': winsz.y
					});
		cc_pop.set('styles', {
						'left': ((winsz.x - 400) / 2),
						'top': ((winsz.y - 500) / 2)
					});
		$('clerk_id').focus();
		cc_pop.addEvent('keydown', function(event){
			if (event.key == 'esc')
			{
				cc_shell.set('styles',{'display':'none'});
				if (cb_color.hasClass('remainingLow')) { cb_color.removeClass('remainingLow'); }
				if (cb_color.hasClass('remainingDebt')) { cb_color.removeClass('remainingDebt'); }
				if (cc_color.hasClass('remainingDebt')) { cc_color.removeClass('remainingDebt'); }
				if (cb_bw.hasClass('remainingLow')) { cb_bw.removeClass('remainingLow'); }
				if (cb_bw.hasClass('remainingDebt')) { cb_bw.removeClass('remainingDebt'); }
				if (cc_bw.hasClass('remainingDebt')) { cc_bw.removeClass('remainingDebt'); }
			}
		});

		window.scrollTo(0);
	});
	
	$('confirmCancel').addEvent('click', function(event) {
		event.stop();
		cc_shell.set('styles',{'display':'none'});
		if (cb_color.hasClass('remainingLow')) { cb_color.removeClass('remainingLow'); }
		if (cb_color.hasClass('remainingDebt')) { cb_color.removeClass('remainingDebt'); }
		if (cb_bw.hasClass('remainingLow')) { cb_bw.removeClass('remainingLow'); }
		if (cb_bw.hasClass('remainingDebt')) { cb_bw.removeClass('remainingDebt'); }
	});
	
	/*
	
	$('doDebit').addEvent('keydown', function(event){ if (event.key == 'enter') { $('doDebit').fireEvent('click'); } });
	$('clerk_id').addEvent('keydown', function(event){ 
		if (event.key == 'enter')
		{
			$('doDebit').fireEvent('click');
			return false;
		} 
	});
	
	
	
	$('doDebit').addEvent('click', function(event) {
			deb.set('disabled',true);
			deb.set('value','Saving...');
	});
	
	
	var asyncDebit = new Request({
			method: 'post', 
			url: 'async.php',
			onRequest: function() { 
				deb.set('html', 'Saving...');
			},
			onSuccess: function(response) { 
				if (response == 'Success')
				{
					deb.set('value', 'Saved!');
					window.location = '?action=view_transaction&trans=last';
				}
				else if (response == 'Failure')
				{
					deb.set('value', 'Couldn\'t Save!');
				}
				else
				{
					deb.set('value', 'Error!');
				}
			},
			onFailure: function() {
				deb.set('value', 'Couldn\'t Save!');
			}
		});

	$('doDebit').addEvent('click', function(event) {
		event.stop();
		
		var strBuild = 'acct_id=' + $('acct_id').get('text') + '&bw='+encodeURIComponent(cc_bw.get('html')) 
			+ '&color=' + encodeURIComponent(cc_color.get('html')) 
			+ '&startbal_bw=' + encodeURIComponent(fb_bw.get('html')) 
			+ '&startbal_color=' + encodeURIComponent(fb_color.get('html')) 
			+ '&clerk_id=' + encodeURIComponent($('clerk_id').value);

		deb.set('disabled',true);
		//deb.set('value','Saving');
		
		asyncDebit.send(strBuild);
	});*/
	
	";
	
	///////////////////////////////////////
	// ADD COPIES
	///////////////////////////////////////
	if ($obj == "add_copies")
	{
		$action_type = 'deposit'; $action_text = 'Deposit';
		$action_line = '<li><a href="?action=view_account&acct_id='.$acct_id.'">Debit Copies</a></li>';
		$action_form = '
	<h2>Add Copies</h2>
	
	'.$notice.'
		
	<div class="grid_4 alpha">
		<h3>Add Notes to Transaction:</h3>
		<textarea id="trans_notes" name="trans_notes"></textarea>
	</div>
		
	<div class="grid_4 omega">
		<h3>Copies to Add:</h3>
		<dl class="w50">
			<dt title="Black and White Copies">B&W</dt><dd><input class="text-right" type="text" size="4" id="debit_bw" name="debit_bw" value="'.$nbw.'" /></dd>
			<dt title="Color Copies">Color</dt><dd><input class="text-right" type="text" size="4" id="debit_color" name="debit_color" value="'.$ncolor.'" /></dd>
			<dt>&nbsp;</dt><dd class="text-right"><input id="confirmDebit" type="submit" value="Add" /></dd>
		</dl>
	</div>';
		
	}
	///////////////////////////////////////
	// DEBIT COPIES
	///////////////////////////////////////
	else
	{
		$action_type = 'debit'; $action_text = 'Debit';
		$action_line = '<li><a href="?action=view_account&acct_id='.$row['acct_id'].'&obj=add_copies">Add Copies</a></li>';
		$action_form = '
	<h2>Debit Copies</h2>
	
	'.$notice.'
		
	<div class="grid_4 alpha">
		<h3>Add Notes to Transaction:</h3>
		<textarea id="trans_notes" name="trans_notes"></textarea>
	</div>
		
	<div class="grid_4 omega">
		<h3>Copies to Debit:</h3>
		<dl class="w50">
			<dt title="Black and White Copies">B&W</dt><dd><input class="text-right" type="text" size="4" id="debit_bw" name="debit_bw" value="'.$nbw.'" /></dd>
			<dt title="Color Copies">Color</dt><dd><input class="text-right" type="text" size="4" id="debit_color" name="debit_color" value="'.$ncolor.'" /></dd>
			<dt>&nbsp;</dt><dd class="text-right"><input id="confirmDebit" type="submit" value="Debit" /></dd>
		</dl>
	</div>';
	}
		
		$html = genAcctHtmlBlock($row['acct_id']).'
		
		<hr />
	
		'.$action_form.'
		
		<hr />
		
		<h2>Frequent Shipper</h2>
		<div class="freq_ship">
			
		</div>
		';
	
	$html_act = '
		<h3>Account Actions:</h3>
			<ul class="menu3">
				<li><a href="?action=view_transactions&acct_id='.$row['acct_id'].'">View All Transactions</a></li>
				'.$action_line.'
				<li><a href="?action=edit_account&acct_id='.$row['acct_id'].'">Edit Account Info</a></li>
			</ul>
	';
	
	$html_ext = '
	
	<div id="confirmation_shell"><div id="confirmation">
			<a href="#" id="confirmCancel">Cancel</a>
		<h2>Confirm '.$action_text.':</h2>
		<dl class="w50 text-huge">
			<dt>B&W:</dt><dd id="confirmDebit_bw" class="text-right"></dd>
			<dt>Color:</dt><dd id="confirmDebit_color" class="text-right"></dd>
		</dl>
		
		<h3>Remaining Balance:</h3>
		<dl class="w50">
			<dt>B&W: <span id="rem_bw"></span></dt><dd>Color: <span id="rem_color"></span></dd>
		</dl>
		
		<h3>Complete Action:</h3>
		<div class="text-center">
			<form action="?action=do_transaction" method="POST">
			<input type="hidden" id="h_bw" name="bw" value="0" /><input type="hidden" id="h_color" name="color" value="0" />
			<input type="hidden" id="h_startbal_bw" name="startbal_bw" value="'.$row['copies_bw'].'" /><input type="hidden" id="h_startbal_color" name="startbal_color" value="'.$row['copies_color'].'" />
			<input type="hidden" id="h_acct_id" name="acct_id" value="'.$row['acct_id'].'" />
			<input type="hidden" id="h_notes" name="notes" value="" />
			<input type="hidden" id="h_type" name="trans_type" value="'.$action_type.'" />
			<span id="trans_info">Process this transaction by typing clerk id and pressing enter.</span><br />
			<input class="text-center" type="password" id="clerk_id" name="clerk_id" size="4" /><input id="doDebit" type="submit" value="Process" />&nbsp;&nbsp;&nbsp;<!--<input type="checkbox" id="prevAcct" name="prevAcct" />-->
			</form>
		</div>
	</div></div>
		
		';
		
	}
	
	else
	{
		if ($_SESSION['quickComplete']) { $delay = 250; } else { $delay = 1000; }
		$js = "
		$('name').focus();
		var inputWord = $('name');
 
		new Autocompleter.Request.JSON(inputWord, ".((isset($_GET['search_clerk']) && $_GET['search_clerk'] == 'true') ? "'autofindclerk.php'" : "'autofind.php'").", {
			'indicatorClass': 'autocompleter-loading',
			'selectMode': 'type-ahead',
			'delay': ".$delay."

		});


	";
	
		$redirect = (isset($_GET['redirect'])) ? '&redirect='.$_GET['redirect'] : '';
		
		// search_clerk is a hackjob for allowing to search for a clerk, but it works (only for redirecting to view_transactions)
		$search_clerk = (isset($_GET['search_clerk'])) ? '&search_clerk='.$_GET['search_clerk'] : '';
		$html = '
	<h2>Find Account</h2>
		'.$notice.'
		<form action="?action=find_account'.$redirect.$search_clerk.'" method="POST">
		<dl class="w25">
			<dt>Name: </dt><dd class="text-right"><input class="full" type="text" id="name" name="name" /></dd>
			<dt>&nbsp;</dt><dd class="text-right"><input type="submit" value="Find" /></dd>
		</dl>
		</form>

		<br /><br /><br /><br /><br /><br />';
	}
}

///////////////////////////////////////
// CHANGE SETTINGS
///////////////////////////////////////
else if ($action == 'change_settings')
{
	$valid_id = false;
	if(!isset($_SESSION['clerk_id']) && $_POST['clerk_id'])
	{
		$valid_id = authClerk($_POST['clerk_id']);
		if(!$valid_id)
		{
			header("Location: ?action=change_settings&notice=userauth");
			die("Invalid clerk ID");
		}
	}
	
	if ($valid_id || isset($_SESSION['clerk_id']))
	{
		if(!isset($_SESSION['clerk_id'])) $_SESSION['clerk_id'] = $_POST['clerk_id'];
		
		// Save settings
		if($cmd == 'save')
		{
			$settings->enable_neg_bal = isset($_POST['enable_neg_bal']) && $_POST['enable_neg_bal'] ? 'true' : 'false';
			$settings->allow_default_neg_bal = isset($_POST['allow_default_neg_bal']) && $_POST['allow_default_neg_bal'] ? 'true' : 'false';
			$settings->asXml("..\\copytrack-src\\settings.xml");
			
			header("Location: ?action=change_settings&notice=savesuccessful");
			die("Save successful");
		}
		
		///////////////////////////////////////
		// FIND CLERK / VIEW CLERK
		///////////////////////////////////////
		if($subaction == 'find_clerk' || $subaction == 'view_clerk')
		{
			if ($name || $oper_id)
			{
				if ($name)
				{
					$query = "SELECT * FROM operators WHERE clerk_name = '".$name."' LIMIT 1";
					$result = mysqli_query( $dbconn, $query);
					if (!$result) {
						die("Error: %s\n" . mysqli_error($dbconn));
					}
					$row = mysqli_fetch_array($result);
				}
				else if ($oper_id)
				{
					$query = "SELECT * FROM operators WHERE oper_id = '".$oper_id."' LIMIT 1";
					$result = mysqli_query( $dbconn, $query);
					$row = mysqli_fetch_array($result);
				}
				
				if (!isset($row['oper_id']))
				{
					header("Location: ?action=change_settings&subaction=find_clerk&notice=dne");
				}
				
				$html = genOperHtmlBlock($row['oper_id']);
				
				$actionline = '<li><a href="?action=change_settings&subaction=edit_clerk&oper_id='.$row['oper_id'].'">Edit Clerk Info</a></li>';
			}
			
			else
			{
				if ($_SESSION['quickComplete']) { $delay = 250; } else { $delay = 1000; }
				$js = "
				$('name').focus();
				var inputWord = $('name');
		 
				new Autocompleter.Request.JSON(inputWord, 'autofindclerk.php', {
					'indicatorClass': 'autocompleter-loading',
					'selectMode': 'type-ahead',
					'delay': ".$delay."
					
				});


			";
			
				$redirect = (isset($_GET['redirect'])) ? '&redirect='.$_GET['redirect'] : '';
				$html = '
				<h2>Change Settings</h2>
				<h3>Find Clerk</h3>
				'.$notice.'
				<form action="?action=change_settings&subaction=find_clerk'.$redirect.'" method="POST">
				<dl class="w25">
					<dt>Name: </dt><dd class="text-right"><input class="full" type="text" id="name" name="name"/></dd>
					<dt>&nbsp;</dt><dd class="text-right"><input type="submit" value="Find" /></dd>
				</dl>
				</form>

				<br /><br /><br /><br /><br /><br />';
			}
		}
		
		///////////////////////////////////////
		// ADD CLERK
		///////////////////////////////////////
		else if($subaction == 'add_clerk')
		{
			$formV = array();
			if ($cmd == 'new_acct')
			{
				$formV['v_name'] = filter($_POST['clerk_name']);
				$formV['v_initials'] = $_POST['clerk_initials'];
				$formV['v_id'] = $_POST['clerk_id'];
				$formV['v_level'] = $_POST['level'];
				$formV['v_status'] = $_POST['status'];
				
				// Clerk name must be filled in
				if (empty($formV['v_name']))
				{
					$formV['notValid'] = true;
					$formV['f_name'] = CC_FIELD_INVALID;
					$formV['t_name'] = ' title="You must enter a clerk name."';
				}
				
				// Clerk ID must be 4 digits
				if (strlen($formV['v_id']) != 4 || !is_numeric($formV['v_id']))
				{
					$formV['notValid'] = true;
					$formV['f_id'] = CC_FIELD_INVALID;
					$formV['t_id'] = ' title="You must enter a clerk ID."';
				}
				
				if (!$formV['notValid'])
				{
					// Get current clerk info to check privileges
					$query = "SELECT * FROM operators WHERE clerk_id = '".$_SESSION['clerk_id']."' LIMIT 1";
					$result = mysqli_query( $dbconn, $query);
					if (!$result) {
						die("Error: %s\n" . mysqli_error($dbconn));
					}
					$r = mysqli_fetch_array($result);
					
					// Verify current clerk has sufficient privileges
					if($r['level'] < 3)
					{
						header("Location: ?action=change_settings&subaction=add_clerk&notice=insufprivileges");
						die("Insufficient privileges");
					}
				
					// Check if the clerk's name already exists
					$sql = "SELECT clerk_name, oper_id FROM operators WHERE clerk_name = '".$formV['v_name']."' LIMIT 1";
					$res = mysqli_query($dbconn, $sql);
					$num = mysqli_num_rows($res);
					if ($num == 1)
					{
						header("Location: ?action=change_settings&subaction=add_clerk&notice=acctexists");
						die("Acct already exists");
					}
					
					// Check if the clerk ID is already in use
					$sql = "SELECT clerk_name, oper_id FROM operators WHERE clerk_id = '".$formV['v_id']."' LIMIT 1";
					$res = mysqli_query($dbconn, $sql);
					$num = mysqli_num_rows($res);
					if ($num == 1)
					{
						header("Location: ?action=change_settings&subaction=add_clerk&notice=idexists");
						die("ID already exists");
					}
					
					$query = "INSERT INTO operators (clerk_id, level, status, clerk_name, clerk_initials)
								VALUES ('".$formV['v_id']."',
										'".$formV['v_level']."',
										'".$formV['v_status']."',
										'".$formV['v_name']."',
										'".$formV['v_initials']."')";
					$res = mysqli_query($dbconn, $query);
					if (!$res) {
						die("Error: %s\n" . mysqli_error($dbconn));
					}
				
					$query = " SELECT oper_id FROM operators WHERE clerk_id = '".$formV['v_id']."' LIMIT 1";
					$result = mysqli_query($dbconn, $query);
					$row = mysqli_fetch_array($result);
					
					header("Location: ?action=change_settings&subaction=view_clerk&oper_id=".$row['oper_id']);
				}
			}
			
			if ($cmd != 'new_acct' || $formV['notValid'] == true)
			{
				$js = '
				//var errorTips = new Tips(\'.errorTip\',{className:\'errorTipShell\'});
				
				$(\'account_name\').focus();

				';
				$html = '
				<h2>Change Settings</h2>
				<h3>Add Clerk</h3>
					'.$notice.'
					<form action="?action=change_settings&subaction=add_clerk" method="POST">
					<dl class="w25">
						<dt>Name: </dt><dd class="text-right"><input class="full'.$formV['f_name'].'" '.$formV['t_name'].' type="text" id="clerk_name" name="clerk_name" value="'.$formV['v_name'].'" /></dd>
						<dt>Initials: </dt><dd class="text-right"><input class="full'.$formV['f_initials'].'" '.$formV['t_initials'].' type="text" name="clerk_initials" value="'.$formV['v_initials'].'" /></dd>
						<dt>ID: </dt><dd class="text-right"><input class="'.$formV['f_id'].'" '.$formV['t_id'].' length="4" size="4" maxlength="4" type="password" name="clerk_id"/></dd>
						<dt>Level: </dt><dd class="text-right">
							<select name="level" style="font-size: 2em">
								<option value="1">New Hire</option>
								<option value="2" selected>Clerk</option>
								<option value="3">Manager</option>
							</select></dd>
						<dt>Status: </dt><dd class="text-right">
							<select name="status" style="font-size: 2em">
								<option value="Active">Active</option>
								<option value="Inactive">Inactive</option>
							</select></dd>
						<dt>&nbsp;</dt><dd class="text-right"><input type="submit" value="Create" /></dd>
					</dl>
					<input type="hidden" name="cmd" value="new_acct" />
					</form>
				';
			}
		}
		
		///////////////////////////////////////
		// EDIT CLERK
		///////////////////////////////////////
		else if($subaction == 'edit_clerk')
		{
			$formV = array();
			if ($cmd == 'edit_acct')
			{
				$formV['oper_id'] = $oper_id;
				$formV['v_name'] = filter($_POST['clerk_name']);
				$formV['v_initials'] = $_POST['clerk_initials'];
				$formV['v_id'] = $_POST['clerk_id'];
				$formV['v_level'] = $_POST['level'];
				$formV['v_status'] = $_POST['status'];
				
				// Clerk name must be filled in
				if (empty($formV['v_name']))
				{
					$formV['notValid'] = true;
					$formV['f_name'] = CC_FIELD_INVALID;
					$formV['t_name'] = ' title="You must enter a clerk name."';
				}
				
				// Clerk ID must be 4 digits
				if (strlen($formV['v_id']) != 4 || !is_numeric($formV['v_id']))
				{
					$formV['notValid'] = true;
					$formV['f_id'] = CC_FIELD_INVALID;
					$formV['t_id'] = ' title="You must enter a clerk ID."';
				}
				
				if (!$formV['notValid'])
				{	
					// Get current clerk info to check privileges
					$query = "SELECT * FROM operators WHERE clerk_id = '".$_SESSION['clerk_id']."' LIMIT 1";
					$result = mysqli_query( $dbconn, $query);
					if (!$result) {
						die("Error: %s\n" . mysqli_error($dbconn));
					}
					$r = mysqli_fetch_array($result);
					
					// Verify current clerk has sufficient privileges
					if($r['level'] < 3)
					{
						header("Location: ?action=change_settings&subaction=edit_clerk&oper_id=".$oper_id."&notice=insufprivileges");
						die("Insufficient privileges");
					}
				
					// Check if the clerk's name already exists
					$sql = "SELECT clerk_name, oper_id FROM operators WHERE clerk_name = '".$formV['v_name']."' LIMIT 1";
					$res = mysqli_query($dbconn, $sql);
					$num = mysqli_num_rows($res);
					$row = mysqli_fetch_array($res);
					if ($num == 1 && $formV['oper_id'] != $row['oper_id'])
					{
						header("Location: ?action=change_settings&subaction=edit_clerk&oper_id=".$oper_id."&notice=acctexists");
						die("Acct already exists");
					}
					
					// Check if the clerk ID is already in use
					$sql = "SELECT clerk_name, oper_id FROM operators WHERE clerk_id = '".$formV['v_id']."' LIMIT 1";
					$res = mysqli_query($dbconn, $sql);
					$num = mysqli_num_rows($res);
					$row = mysqli_fetch_array($res);
					if ($num == 1 && $formV['oper_id'] != $row['oper_id'])
					{
						header("Location: ?action=change_settings&subaction=edit_clerk&oper_id=".$oper_id."&notice=idexists");
						die("ID already exists");
					}
					
					$sql = "UPDATE operators
					SET clerk_id = '" . $formV['v_id'] . "',
						level = '" . $formV['v_level'] . "',
						status = '" . $formV['v_status'] . "',
						clerk_name = '" . $formV['v_name'] . "',
						clerk_initials = '" . $formV['v_initials'] . "'
					WHERE oper_id = '" . $formV['oper_id']."' LIMIT 1";

					mysqli_query($dbconn, $sql) or die('Failure');
					
					$query = " SELECT oper_id FROM operators WHERE oper_id = '".$formV['oper_id']."' LIMIT 1";
					$result = mysqli_query($dbconn, $query);
					$row = mysqli_fetch_array($result);
					
					header("Location: ?action=change_settings&subaction=view_clerk&oper_id=".$row['oper_id']);
				}
			}
			
			if ($cmd != 'edit_acct' || $formV['notValid'] == true)
			{
				if ($formV['notValid'] != true)
				{
					$query = "SELECT * FROM operators WHERE oper_id = '".$oper_id."' LIMIT 1";
					$result = mysqli_query($dbconn, $query);
					$acctrow = mysqli_fetch_array($result);
					
					$formV['v_name'] = $acctrow['clerk_name'];
					$formV['v_initials'] = $acctrow['clerk_initials'];
					$formV['v_id'] = $acctrow['clerk_id'];
					$formV['v_level'] = $acctrow['level'];
					$formV['v_status'] = $acctrow['status'];
				}
				
				$js = '
				//var errorTips = new Tips(\'.errorTip\',{className:\'errorTipShell\'});
				';
				$html = '
				<h2>Change Settings</h2>
				<h3>Edit Account</h3>
					'.$notice.'
					<form action="?action=change_settings&subaction=edit_clerk&oper_id='.$oper_id.'" method="POST">
					<dl class="w25">
						<dt>Name: </dt><dd class="text-right"><input class="full'.$formV['f_name'].'" '.$formV['t_name'].' type="text" id="clerk_name" name="clerk_name" value="'.$formV['v_name'].'" /></dd>
						<dt>Initials: </dt><dd class="text-right"><input class="full'.$formV['f_initials'].'" '.$formV['t_initials'].' type="text" name="clerk_initials" value="'.$formV['v_initials'].'" /></dd>
						<dt>ID: </dt><dd class="text-right"><input class="'.$formV['f_id'].'" '.$formV['t_id'].' length="4" size="4" maxlength="4" type="password" name="clerk_id" value="'.$formV['v_id'].'"/></dd>
						<dt>Level: </dt><dd class="text-right">
							<select name="level" style="font-size: 2em">
								<option value="1"'.(($formV['v_level'] == 1) ? ' selected' : '').'>New Hire</option>
								<option value="2"'.(($formV['v_level'] == 2) ? ' selected' : '').'>Clerk</option>
								<option value="3"'.(($formV['v_level'] == 3) ? ' selected' : '').'>Manager</option>
							</select></dd>
						<dt>Status: </dt><dd class="text-right">
							<select name="status" style="font-size: 2em">
								<option value="Active"' . (($formV['v_status'] == 'Active') ? ' selected' : '') . '>Active</option>
								<option value="Inactive"' . (($formV['v_status'] == 'Inactive') ? ' selected' : '') . '>Inactive</option>
							</select></dd>
						<dt>&nbsp;</dt><dd class="text-right"><input type="submit" value="Edit" /></dd>
					</dl>
					<input type="hidden" name="cmd" value="edit_acct" />
					</form>
				';
			}
		}
		
		///////////////////////////////////////
		// VIEW CLERKS
		///////////////////////////////////////
		else if($subaction == 'view_clerks')
		{
			$sortr = (isset($_GET['reverse'])) ? " DESC" : " ASC";
			switch ($sort)
			{
				case "oper_id":
					$sortsql = 'oper_id';
					$sortoperid = (!isset($_GET['reverse'])) ? '&reverse=1' : '';
					break;
				case "clerk_id":
					$sortsql = 'clerk_id';
					$sortclerkid = (!isset($_GET['reverse'])) ? '&reverse=1' : '';
					break;
				case "level":
					$sortsql = 'level';
					$sortlevel = (!isset($_GET['reverse'])) ? '&reverse=1' : '';
					break;
				case "status":
					$sortsql = 'status';
					$sortstatus = (!isset($_GET['reverse'])) ? '&reverse=1' : '';
					break;
				case "clerk_initials":
					$sortsql = 'clerk_initials';
					$sortinitials = (!isset($_GET['reverse'])) ? '&reverse=1' : '';
					break;
				case "clerk_name":
				default:
					$sortsql = 'clerk_name';
					$sortname = (!isset($_GET['reverse'])) ? '&reverse=1' : '';
					break;
			}
			$sorturl = "?action=change_settings&subaction=view_clerks";
			
			// Get current clerk info to check privileges
			$query = "SELECT * FROM operators WHERE clerk_id = '".$_SESSION['clerk_id']."' LIMIT 1";
			$result = mysqli_query( $dbconn, $query);
			if (!$result) {
				die("Error: %s\n" . mysqli_error($dbconn));
			}
			$r = mysqli_fetch_array($result);
			
			$query = "SELECT oper_id, clerk_id, clerk_name, clerk_initials, level, status FROM operators ORDER BY " . $sortsql . $sortr;
			$result = mysqli_query($dbconn, $query);
			$acctlist = '<table>
							<tr><th><a href="'.$sorturl.'&sort=oper_id'.$sortoperid.'">Oper ID</a></th><th><a href="'.$sorturl.'&sort=clerk_id'.$sortclerkid.'">Clerk ID</a></th><th><a href="'.$sorturl.'&sort=clerk_name'.$sortname.'">Clerk Name</a></th><th><a href="'.$sorturl.'&sort=clerk_initials'.$sortinitials.'">Clerk Initials</a></th><th><a href="'.$sorturl.'&sort=level'.$sortlevel.'">Level</a></th><th><a href="'.$sorturl.'&sort=status'.$sortstatus.'">Status</a></th>';
			while ($row = mysqli_fetch_array($result))
			{
				// Blank out clerk ID if current clerk has insufficient level (less than 3)
				if($r['level'] < 3) $row['clerk_id'] = '-';
		
				static $i = 0;
				$evenodd = ($i % 2) ? 'even' : 'odd';
				$acctlist .= '<tr class="'.$evenodd.'"><td>'.$row['oper_id'].'</td><td class="text-right">'.$row['clerk_id'].'</td><td><a href="?action=change_settings&subaction=view_clerk&oper_id='.$row['oper_id'].'">'.$row['clerk_name'].'</a></td><td class="text-right">'.$row['clerk_initials'].'</td><td class="text-right">'.convertLevel($row['level']).'</td><td class="text-right">'.$row['status'].'</td></tr>';
				$i++;
			}
			$acctlist .= '</table>';
			
			$html = '
			<h2>Change Settings</h2>
			<h3>All Clerks:</h3>
				'.$acctlist.'
			';
		}
		
		else
		{
			$js = '
			var checkbox = document.getElementById("enable_neg_bal");
			document.getElementById("allow_default_neg_bal").disabled = !checkbox.checked;
            if (checkbox.addEventListener) {
                checkbox.addEventListener ("CheckboxStateChange", 
					function(event){
						var checkbox = event.target;
						document.getElementById("allow_default_neg_bal").disabled = !checkbox.checked;
						document.getElementById("allow_default_neg_bal_text").disabled = !checkbox.checked;
					}, false);
            }';
		
			$html = $notice.'
			<h2>Change Settings</h2>
			<p>
				Modify settings for CopyTracker here, and manage users by selecting the options to the right.
			</p>
			
			<h3>Configure Copy Tracker:</h3>
			<form action="?action=change_settings" method="post">
			<dl class="w50">
				<!--dt>Allow Negative Balances?:</dt><dd><input type="checkbox" name="allow_neg_balances"'.($_SESSION['allow_neg_balances'] ? ' checked' : '').' value="1"/></dd-->
				<dt>Enable Negative Balances</dt><dd><input type="checkbox" id="enable_neg_bal" name="enable_neg_bal"'.($settings->enable_neg_bal == 'true' ? ' checked' : '').' value="1"/></dd>
				<dt>Allow Negative Balances by Default?</dt><dd><input type="checkbox" id="allow_default_neg_bal" name="allow_default_neg_bal"'.($settings->allow_default_neg_bal == 'true' ? ' checked' : '').' value="1"/></dd>
				<dt>&nbsp;</dt><dd class="text-right"><input type="submit" value="Save" /></dd>
			</dl>
			<input type="hidden" name="cmd" value="save" />
			</form>
			';
		}
		
		if(!isset($actionline)) $actionline = '';
		
		$html_act = '
		<!--<h3>More Settings:</h3>
			<ul class="menu3">
				<li><a href="?action=change_settings&sub=manage_users">Manage Users</a></li>
			</ul>-->
			<h3>Manage Clerk Accounts:</h3>
			<ul class="menu3">
				<li><a href="?action=change_settings&subaction=find_clerk">Find Clerk</a></li>
				'.$actionline.'
				<li><a href="?action=change_settings&subaction=add_clerk">Add New Clerk</a></li>
				<li><a href="?action=change_settings&subaction=view_clerks">View All Clerks</a></li>
			</ul>
		';
	}
	
	else
	{
		$html = '
			<h2>Change Settings</h2>
			'.$notice.'
			<div class="grid_4 prefix_2 suffix_2 text-center">
				<br /><br /><br />
				<form action="?action=change_settings" method="post">
					Enter Clerk ID to Continue<br />
					<input type="password" length="4" size="4" name="clerk_id" /> <input type="submit" value="Go" />
				</form>
				<br /><br /><br /><br />
			</div>';
	}
}

///////////////////////////////////////
// DEFAULT (HOME PAGE)
///////////////////////////////////////
else if ($action == 'default')
{
	$html = '
	<h2>Welcome</h2>
		<p>
		CopyTracker provides an all-inclusive utility for managing "Copy Cards" in a completely digital and easy-to-use format.
		<br /><br />
		To get started, choose an action on the right.<br />
		</p>

		<br /><br /><br /><br /><br /><br />';
}

$query = "SELECT * FROM transactions ORDER BY trans_timestamp DESC LIMIT 5";
$result = mysqli_query( $dbconn, $query);
$recent_transactions = '';
while ($row = mysqli_fetch_array($result))
{
	$r_bw = ''; $r_cl = ''; $r_ec = '';
	if ($row['copies_bw'] > 0) { $r_bw = $row['copies_bw'] . ' BW'; }
	if ($row['copies_color'] > 0) { $r_cl = $row['copies_color'] . ' C'; }
	if ($r_bw && $r_cl) { $r_ec = $r_bw . ', ' . $r_cl; }
	else { $r_ec = ($r_bw > 0) ? $r_bw : $r_cl; }
	$recent_transactions .= '<a href="?action=view_account&acct_id='.$row['acct_id'].'">'.getAcctNameById($row['acct_id']).'</a> did '.$r_ec.'<br />';
}
//	original Logic above - else { $r_ec = (isset($r_bw)) ? $r_bw : $r_cl; }

if ($recent_transactions == '') { $recent_transactions = '<i>No Recent Transactions.</i>'; }

$recent = $recent_transactions;


$query = "SELECT count(acct_id) as cnt FROM accounts WHERE copies_bw > 0";
$result = mysqli_query( $dbconn, $query);
$row = mysqli_fetch_array($result);
$bw_active_acct_cnt = $row['cnt'];

$query = "SELECT count(acct_id) as cnt FROM accounts WHERE copies_color > 0";
$result = mysqli_query( $dbconn, $query);
$row = mysqli_fetch_array($result);
$cc_active_acct_cnt = $row['cnt'];

$stats = '
			Active BW Accounts: '.$bw_active_acct_cnt.'<br />
			Active Color Accounts: '.$cc_active_acct_cnt.'<br />
			';

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>The UPS Store &bull; Copy Tracker</title>
<link rel="stylesheet" href="css/reset.css" />
<link rel="stylesheet" href="css/960.css" />
<link rel="stylesheet" href="css/text.css" />
<link rel="stylesheet" href="css/site.css" />
<link rel="stylesheet" href="css/Autocompleter.css" />
<link rel="stylesheet" href="css/datepicker_vista.css" />
<link rel="stylesheet" href="css/print.css" media="print" />



<script type="text/javascript" src="js/mootools-yui-compressed-1.5.1.js"></script>
<script type="text/javascript" src="js/Observer.js"></script>
<script type="text/javascript" src="js/Autocompleter.js"></script>
<script type="text/javascript" src="js/Autocompleter.Request.js"></script>
<script type="text/javascript" src="js/datepicker.js"></script>

<style type="text/css">

body {
	background:#fff;
	padding-left:8px;
}


<?php if ($_SESSION['invertColor'])
{
	echo '
	body {
		background:#c0c0c0;
		color:#fff;
	}
	
	#main {
		background:transparent url(o50c000.png) top right repeat;
		margin-bottom:16px;
		padding-top:8px;
	}
	
	h1 {
		color:#fff;
		background:transparent url(img/o87c000.png) repeat;
	}
	
	h2 {
		border-bottom:1px solid #ccc;
	}
	
	hr {
		color:#333;
	}
	
	.menu1 a {
	background:transparent url(img/o87c000.png) repeat;
	border-left:3px solid #0048a3;
	
	}
	
	.menu3 a {
	border:1px solid #00bff3;
	background:transparent url(img/o87c000.png) repeat;
	color:#00bff3;
	}
	
	dd { color:#aaa; } 
	
	input, textarea {
		border:1px solid #444;
		background:transparent url(img/o87c000.png) repeat;
		color:#fff;
	}
	
	tr.even td {
	background:#202020;
	}
	
	.prefoot {
		background:transparent url(img/o87c000.png) repeat;
	}
	
	ul.autocompleter-choices
	{
		background-color:		#222;
	}
	
#confirmation_shell {
	background:transparent url(\'o50cfff.png\') repeat;
}

#confirmation {
	background:#000;
	border:3px solid #00bff3;
	padding:8px;
}
#footer div {
	background:transparent url(o50c000.png) top right repeat;
}
	';
}
?>

</style>

<script type="text/javascript">
window.addEvent('domready', function(){
	<?php echo $js; ?>
	
	//var noteTips = new Tips('.noteTip');
	
	$('modSessionCtrl').addEvent('click', function(event){
		$('modSession').setStyle('display','block');
		this.setStyle('display','none');
		event.stop();
	});
	
	var shortKeys = window.addEvent('keydown', function(event) {
		var addURI = new URI('?action=add_account');
		var findURI = new URI('?action=find_account');
		if (event.key == 'a' && event.alt && event.control) { addURI.go() };
		if (event.key == 'f' && event.alt) { findURI.go() };
	});
});
</script>

</head>

<body>

<div id="shell" class="container_12">
	<div id="header" class="grid_8 alpha"><h1><?php if($_SESSION['migration']) { echo '<span id="sessionNotice">Migration Mode Active <img src="img/png32/Info.png" class="noteTip" title="Migration Mode is Active" /></span>'; } ?>Copy Tracker</h1></div>

	<div class="clear"></div>

	<div id="main" class="grid_8 alpha">
<?php
echo $html;
?>

	</div>

	<div id="actions" class="grid_3 suffix_1 omega">
		<ul class="menu1">
			<li><a href="?action=find_account"><img src="img/png32/Search.png" alt="" /> Find Account</a></li>
			<li><a href="?action=add_account"><img src="img/png32/Add.png" alt="" /> Add Account</a></li>
			<li><a href="?action=view_reports"><img src="img/png32/BarChart.png" alt="" /> View Reports</a></li>
			<li><a href="?action=change_settings"><img src="img/png32/LineChart.png" alt="" /> Change Settings</a></li>
		</ul>
		
		<?php if(!empty($html_act)) { echo '<br /><br />'.$html_act; } ?>
	</div>

	<div class="clear"></div>

	<div class="grid_4 alpha prefoot">
		<div class="inner">
			<h3>Quick Stats:</h3>
			<?php echo $stats; ?>
		</div>
	</div>

	<div class="grid_4 prefoot">
		<div class="inner">
			<h3>Recent Activity:</h3>
			<?php echo $recent; ?>
		</div>
	</div>

	<div id="footer" class="grid_8 alpha">
		<div>
			<a href="#" id="modSessionCtrl">Show Session Settings</a><span id="modSession"><?php if (!$_SESSION['migration']) { echo '<a href="?setMode=migration">Turn on Migration Mode</a>'; } else { echo '<a href="?resetMode=migration">Turn off Migration Mode</a>'; } ?> | <?php if (!$_SESSION['quickComplete']) { echo '<a href="?setMode=quickComplete">Increase Autocompletion Speed</a>'; } else { echo '<a href="?resetMode=quickComplete">Decrease Autocompletion Speed</a>'; } ?> | <?php if (!$_SESSION['invertColor']) { echo '<a href="?setMode=invertColor">Invert Color</a>'; } else { echo '<a href="?resetMode=invertColor">Revert Color</a>'; } ?> |<br/> <?php if (!$_SESSION['searchInactive']) { echo '<a href="?setMode=searchInactive">Search Active Accounts Only</a>'; } else { echo '<a href="?resetMode=searchInactive">Search All Accounts</a>'; } ?> &bull; <a href="?setMode=reset">Reset All</a></span>
		</div>
	</div>
</div>

<?php echo $html_ext; ?>

</body>

</html>
