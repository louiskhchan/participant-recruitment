<?php
// project: expsignup/libcommon
// by: louis
// on: 14sep2010
// last modify: 14sep2010
// version: 2.1
// script-dependency: sqliteutil
// server-dependency: sqlite or dl; pear mail support
// db-dependency: 

ini_Set("date.timezone", "Asia/Hong_Kong");

include_once('./Mail.php');
include_once("./sqliteutil.php");
session_start();

//start commonfunctions

if (!is_callable("scandir")) {
	function scandir($dir)
	{ //for PHP<5
		$dh  = opendir($dir);
		while (false !== ($filename = readdir($dh))) {
			$files[] = $filename;
		}
		sort($files);
		return $files;
	}
}

function getabsolutepath()
{
	$return = "http://" . $_SERVER['SERVER_NAME'];
	$matches = array();
	preg_match("/(.*)\/(.+)/", $_SERVER['PHP_SELF'], $matches);
	$return .= $matches[1];
	return $return;
}

function checkinput()
{
	$inputs = func_get_args();
	$pac = $inputs[0];
	unset($inputs[0]);
	$fail = 0;
	$im = "";
	foreach ($inputs as $key => $val) {
		if (!isset($pac[$val])) {
			if ($fail != 0) $im .= "<br />";
			$im .= "$val was not entered.";
			$fail++;
			continue;
		}
		$exceptionlist = array("validuntilstr");
		//here add special handlers (e.g. email)
		if (preg_match("/id/i", $val) && !in_array($val, $exceptionlist)) {
			if (!preg_match("/^[_\w\d]+$/", $pac[$val])) {
				$im .= "<br />We only accept digits, alphabets and underscore for Login IDs.";
				$fail++;
			}
		}
		if (preg_match("/uid/i", $val)) {
			if (!preg_match("/(^[\d]{10}$)|(^[\d]{5}$)/", $pac[$val])) {
				$im .= "<br />Please enter your 10-digit University Number.";
				$fail++;
			}
		}
		if (preg_match("/name/i", $val)) {
			if (!preg_match("/^[_\w\d\s]+$/", $pac[$val])) {
				$im .= "<br />We do not accept symbols for Names.";
				$fail++;
			}
		}
		if (preg_match("/time/i", $val) || preg_match("/num/i", $val)) {
			if (!preg_match("/^[\d]+$/", $pac[$val])) {
				$im .= "<br />$val is not a number.";
				$fail++;
			}
		}
		if (preg_match("/email/i", $val)) {
			if (!preg_match("/^[-_\\d\\.\\w]+@[-_\\d\\.\\w]+\\.\\w{2,}$/", $pac[$val])) {
				$im .= "<br />Please enter a valid email.";
				$fail++;
			}
		}
	}
	if ($fail > 0) {
		$session = getgateway();
		$session['internal_message'] = $im;
		putgateway($session);
		return false;
	}
	return true;
}

function getgateway()
{
	return isset($_SESSION['expsignup21_gateway']) ? unserialize(trim($_SESSION['expsignup21_gateway'])) : array();
}

function putgateway(&$gatewaydb)
{
	$_SESSION['expsignup21_gateway'] = serialize($gatewaydb) . "\n";
}

function showonce(&$val, $key)
{
	$tmpval = "";
	if (isset($val[$key])) {
		$tmpval = $val[$key];
		unset($val[$key]);
		putgateway($val);
	}
	return $tmpval;
}

function getwebin()
{
	//read webin.
	$webin = array();
	foreach ($_POST as $key => $val) {
		if (!is_array($val)) {
			if (trim(stripslashes($val)) != "") $webin[$key] = trim(stripslashes($val));
		} else {
			$webin[$key] = $val;
		}
	}
	foreach ($_GET as $key => $val) {
		if (!is_array($val)) {
			if (trim(stripslashes($val)) != "") $webin[$key] = trim(stripslashes($val));
		} else {
			$webin[$key] = $val;
		}
	}
	return $webin;
}

function buttoninit($formid, $inputname)
{
	if (!preg_match("/MSIE\s(\d)/i", $_SERVER['HTTP_USER_AGENT'], $matches) || $matches[1] >= 8) {
		//non ie
		return "";
	} else {
		//ie
		$out = <<<ENDHTML
		<div style="display:none;">
			<input id="{$inputname}handle" name="{$inputname}" value="" />
		</div>
		<script type="text/javascript">
		function click{$inputname}(val){
			document.getElementById('{$inputname}handle').value=val;
			document.getElementById('{$formid}').submit();
		}
		</script>
		
ENDHTML;
		return $out;
	}
}
function buttontag($inputname, $val, $label)
{
	$out = "";
	if (!preg_match("/MSIE\s(\d)/i", $_SERVER['HTTP_USER_AGENT'], $matches) || $matches[1] >= 8) {
		//non ie
		$out = <<<ENDHTML
		<button class="smallsubmit" name="{$inputname}" type="submit" value="{$val}">{$label}</button>
		
ENDHTML;
	} else {
		//ie
		$out = <<<ENDHTML
		<input class="smallsubmit" type="button" onclick="click{$inputname}('{$val}');" value="{$label}" />
		
ENDHTML;
	}
	return $out;
}

//end commonfunctions

if (!is_callable("file_get_contents")) {
	function file_get_contents($fnstr)
	{
		$returnstr = implode('', file($fnstr));
		return $returnstr;
	}
}

function templateout($htmltitle, $htmlmenu, $htmlcontent, $relurl = "")
{
	$websiteurl = $relurl . "../";
	$htmltitle = trim($htmltitle);
	$htmlmenu = trim($htmlmenu);
	$htmlcontent = trim($htmlcontent);
	$gateway = getgateway();
	header("content-type:text/html; charset=UTF-8\n");
	echo "<?xml version=\"1.0\"?>\n";
	if (!(preg_match("/MSIE (\d)/i", $_SERVER['HTTP_USER_AGENT'], $matches) && $matches[1] <= 5)) { ?>
		<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
	<?php } ?>
	<html xmlns="http://www.w3.org/1999/xhtml" lang="zh" xml:lang="zh">

	<head>
		<title>HKU Vision Laboratories participant website<?= $htmltitle == "" ? "" : " - " . $htmltitle; ?></title>
		<link rel="stylesheet" type="text/css" media="screen" href="<?= $relurl; ?>style.css" />
		<style type="text/css">
		</style>
		<link rel="shortcut icon" href="favicon.ico">
	</head>

	<body style="margin:0px;background:#fff;">
		<table style="border:0px;width:100%;height:100%;" cellpadding="0" cellspacing="0">
			<tr>
				<td align="center">
					<div style="margin:0px;padding:10px;width:800px;height:100%;background:#fff;">
						<div style="height:20px;">&nbsp;</div>
						<table border="0" style="width:100%;">
							<tr>
								<td align="right" valign="middle" style="padding:15px;padding-bottom:0px;">
									<img src="<?= $websiteurl; ?>images/bannervision.png" style="border:0px;" />
								</td>
							</tr>
						</table>
						<table border="0" style="width:100%;">
							<tr>
								<td align="center">
									<table border="0" cellpadding="20px" style="width:100%;">
										<tr>
											<td align="right" valign="top" style="width:25%;overflow:auto;">
												<!-- menu -->
												<?= $htmlmenu; ?>
												<p />
												&nbsp;
											</td>
											<td align="left" valign="top" style="width:80%;">
												<div style="overflow:visible;width:auto;">
													<!-- notice -->
													<?= isset($gateway['internal_message']) ? "<em>" . showonce($gateway, "internal_message") . "</em>" : ""; ?>
													<!-- content -->
													<?= $htmlcontent; ?>
													<br />
												</div>
											</td>
										</tr>
									</table>
								</td>
							</tr>
						</table>
					</div>
				</td>
			</tr>
			<tr>
				<td align="center">
					<div style="width:700px;padding:10px;height:10px;background:#fff;">
						&nbsp;
					</div>
				</td>
			</tr>
		</table>
	</body>

	</html>



<?php
	//    $htmlout=ob_get_contents();
	//    ob_end_clean();
	//    return $htmlout;
	return;
}

function dieout($errstr)
{

	@rename("_index.htm", "index.htm");

	@mail(
		"clouis@graduate.hku.hk", //to
		"serverdown", //subj
		$errstr, //content
		"MIME-Version: 1.0\r\n" . //other headers
			"Content-type: text/plain; charset=UTF-8\r\n" .
			"From: Server GUAM (Visual Cognition Laboratory)" .
			" <clouis@graduate.hku.hk>" .
			"\r\n"
	);

	$hti = "Error!";
	$hme = <<<ENDHTML
	<a href="/">back to lab webpage</a>
ENDHTML;
	$hco = <<<ENDHTML
	<h1>Server down</h1>
	Sorry, but our system encountered an unexpected error. We are trying to fix the system. Please come back later.
ENDHTML;

	templateout($hti, $hme, $hco);

	exit(0);
	return;
}

//create necessary files
//timeslotdb
if (!sq_query_val("admin/timeslot.db", "SELECT name FROM sqlite_master WHERE type='table' AND name='timeslot'")) {
	if (!sq_query_safe("admin/timeslot.db", "CREATE TABLE timeslot(timeslotid INTEGER PRIMARY KEY,exptid,expterid,subjid,timetag,location,duration,pay)")) dieout("can't create timeslot table");
	if (!sq_query_safe("admin/timeslot.db", "CREATE INDEX timeslotindex1 ON timeslot(timeslotid,exptid,expterid,subjid)")) dieout("can't create timeslot index");
}
//exptdb
if (!sq_query_val("admin/expt.db", "SELECT name FROM sqlite_master WHERE type='table' AND name='expt'")) {
	if (!sq_query_safe("admin/expt.db", "CREATE TABLE expt(exptid PRIMARY KEY,exptname,exptdesc,numsubj,openness,clasharr,timetag,creator,racearr,rolearr,sexarr)")) dieout("can't create expt table");
	if (!sq_query_safe("admin/expt.db", "CREATE INDEX exptindex1 ON expt(exptid,openness,timetag)")) dieout("can't create expt index");
}
//expterdb
if (!sq_query_val("admin/expter.db", "SELECT name FROM sqlite_master WHERE type='table' AND name='expter'")) {
	if (!sq_query_safe("admin/expter.db", "CREATE TABLE expter(expterid PRIMARY KEY,exptername,email,phone,labname)")) dieout("can't create expter table");
	if (!sq_query_safe("admin/expter.db", "CREATE INDEX expterindex1 ON expter(expterid)")) dieout("can't create expter index");
}
//subjdb
if (!sq_query_val("admin/subj.db", "SELECT name FROM sqlite_master WHERE type='table' AND name='subj'")) {
	if (!sq_query_safe("admin/subj.db", "CREATE TABLE subj(subjid INTEGER PRIMARY KEY,subjuid,password,subjname,email,phone,sex,race,role,subscription DEFAULT 'Y',validuntil DEFAULT -1,lastsenttime DEFAULT 0,notes)")) dieout("can't create subj table");
	if (!sq_query_safe("admin/subj.db", "CREATE INDEX subjindex1 ON subj(subjid,subjuid,email,race,role,subscription,lastsenttime)")) dieout("can't create subj index");
}

function rand_real()
{
	return mt_rand() / mt_getrandmax();
}

function wordsconnect($wordarr)
{
	if (count($wordarr) < 1) {
		return "";
	} else if (count($wordarr) < 2) {
		return trim($wordarr[0]);
	} else {
		$out = "";
		for ($i = 0; $i < (count($wordarr) - 2); $i++) {
			$out .= $wordarr[$i] . ", ";
		}
		$out .= $wordarr[count($wordarr) - 2] . " and " . $wordarr[count($wordarr) - 1];
		return $out;
	}
}

function array_map2($mainarray, $keyarray)
{
	$out = array();
	foreach ($keyarray as $key) {
		$out[] = $mainarray[$key];
	}
	return $out;
}

//mail configuration
$params = array();
$params['host'] = 'mail.hku.hk';
$params['persist'] = TRUE;

//global constant
$subscriptionlabel = array("Y" => "Subscribed", "N" => "Unsubscribed");
$subscriptionlabel1 = array("Y" => "Yes", "N" => "No");
$sexlabel = array("M" => "Male", "F" => "Female");
$sexlabel1 = array("M" => "male", "F" => "female");
$sexlabel2 = array("M" => "males", "F" => "females");
$racelabel = array("chi" => "Chinese", "cau" => "Caucasian", "eaa" => "East-Asian", "oth" => "Other",);
$rolelabel = array("und" => "Undergraduate", "gra" => "Postgraduate", "sta" => "Staff", "oth" => "Other",);
$rolelabel1 = array("und" => "undergraduate", "gra" => "postgraduate", "sta" => "staff", "oth" => "other",);
$rolelabel2 = array("und" => "undergraduates", "gra" => "postgraduates", "sta" => "staff", "oth" => "others",);
$rolelabel3 = array("und" => "Undergraduate student", "gra" => "Graduate student", "sta" => "Staff", "oth" => "Other",);

$masterpassword = 'ENTER THE MASTER PASSWORD HASH HERE';

?>