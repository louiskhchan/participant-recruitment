<?php
// project: expsignup/admin-index
// by: louis
// on: 14sep2010
// last modify: 22sep2010
// version: 2.1
// script-dependency: libcommon sqliteutil (sqliteutil>2.1)
// server-dependency: sqlite or dl
// db-dependency: timeslot.db expt.db expter.db subject.db

chdir("../");
require_once("libcommon.php");

//supplementary functions which were to be moved to a new libaray file for calender
function roundtod5min($timetag){
	$thour=date("G",$timetag);
	$tmin=(int)date("i",$timetag);
	$tmin=$tmin-$tmin%5;
	return ($thour.":".sprintf("%02d",$tmin));
}

function gencalcell($height,$left,$width,$color,$html,$expterid){
	$bordercolor="rgb(".(int)(127+(($color-1/3+1)*128)%128).",".(int)(127+($color)*128).",".(int)(127+(($color+1/3)*128)%128).")";
	$bgcolor="rgb(".(int)(247+(($color-1/3+1)*8)%8).",".(int)(247+($color)*8).",".(int)(247+(($color+1/3)*8)%8).")";
		
	return "<div class=\"calparentdiv\"><div class=\"caldiv\" style=\"height:$height;left:$left;width:$width;border-color:$bordercolor;background-color:$bgcolor;\"><div class=\"calcelldiv\">$html<span style=\"color:$bordercolor;font-weight:bold;\">$expterid</span></div></div></div>";
}

$webin=getwebin();
$gateway=getgateway();

if (isset($gateway['current_expter']) && !sq_query_val("admin/expter.db","SELECT expterid FROM expter WHERE expterid=".sq_quote($gateway['current_expter']))){
	unset($gateway['current_expter']);
	putgateway($gateway);
	}
if (!isset($gateway['current_expter']) && !(isset($webin['module']) && ($webin['module']=="doswitchexpter" || $webin['module']=="doaddexpter" || $webin['module']=="addexpter"))) $webin['module']="switchexpter";

//modules

$hme=isset($gateway['current_expter'])?"<small style=\"color:#faa;\">(<a class=\"inherit\" href=\"?module=switchexpter\">switch</a>/<a class=\"inherit\" href=\"?module=editexpter\">edit</a>/<a class=\"inherit\" href=\"?module=addexpter\">new</a>)</small> <em>".$gateway['current_expter']." </em><p />":"";

if (isset($webin['module']) && $webin['module']=="doeditsubj"){
	if (!checkinput($webin,"subjid","subjname","email","phone_num","subjuid","sex","race","role","validuntilstr")){
		header("Location: ".$_SERVER['HTTP_REFERER']);
		return;
	}
	//allow empty note
	if (!isset($webin['notes'])) $webin['notes']="";
	
	//check if subj exist
	if (!sq_query_val("admin/subj.db","SELECT subjid FROM subj WHERE subjid=".sq_quote($webin['subjid']))){
		$gateway['internal_message']="No such participant.";
		putgateway($gateway);
		header("Location: ".$_SERVER['HTTP_REFERER']);
		return;
	}
	$validuntil=-1;
	if ($webin['validuntilstr']=="Not validated yet"){
		$validuntil=-1;
	} else {
		$validuntil=strtotime($webin['validuntilstr']);
		if ($validuntil==FALSE || $validuntil==-1){
			
			$gateway['internal_message']="No time or time format not recognized.";
			putgateway($gateway);
			header("Location: ".$_SERVER['HTTP_REFERER']);
			return;
		}
	}
	
	//change info
	$updatestr=array();
	$updatestr[]="subjname=".sq_quote($webin['subjname']);
	$updatestr[]="email=".sq_quote($webin['email']);
	$updatestr[]="phone=".$webin['phone_num'];
	$updatestr[]="subjuid=".sq_quote($webin['subjuid']);
	$updatestr[]="sex=".sq_quote($webin['sex']);
	$updatestr[]="race=".sq_quote($webin['race']);
	$updatestr[]="role=".sq_quote($webin['role']);
	$updatestr[]="subscription=".sq_quote($webin['subscription']);
	$updatestr[]="validuntil=".$validuntil;
	$updatestr[]="notes=".sq_quote($webin['notes']);
	
	//check if password is changed
	if (isset($webin['password']) && $webin['password']!="") $updatestr[]="password=".sq_quote(md5(trim($webin['password'])));
	
	//do change
	if (!sq_query_safe("admin/subj.db","UPDATE subj SET ".implode(",",$updatestr)." WHERE subjid=".sq_quote($webin['subjid']))) dieout("edit subj info fail");
	
	$hti="Edit participant information";
	$hme.=<<<ENDHTML
	<a href="#" onclick="window.close();">close window</a><p />
	
ENDHTML;
	$hco="";
	$hco.=<<<ENDHTML
	Participant information changed. <p /><a href="#" onclick="window.close();">Click here to close this window.</a><p />

ENDHTML;

	templateout($hti,$hme,$hco,"../");
	return;
}

if (isset($webin['module']) && $webin['module']=="editsubj"){
	if (!checkinput($webin,"subjid")){
		header("Location: ".$_SERVER['HTTP_REFERER']);
		return;
	}
	//check if data match
	$subj=sq_query_row("admin/subj.db","SELECT * FROM subj WHERE subjid=".sq_quote($webin['subjid']));
	if (!$subj){
		$gateway['internal_message']="No such participant.";
		putgateway($gateway);
		header("Location: ".$_SERVER['HTTP_REFERER']);
		return;
	}
	
	$hti="Edit participant information";
	$hme.=<<<ENDHTML
	<a href="#" onclick="window.close();">close window</a><p />
	
ENDHTML;
	$hco="";
	$validuntilstr="";
	if ($subj['validuntil']<0){
		$validuntilstr="Not validated yet";
	} else {
		$validuntilstr=date("dMY",$subj['validuntil']);
	}
	
	$hco.=<<<ENDHTML
	<h1>Edit participant information</h1>
	
	<form action="index.php" method="post">
	<input type="hidden" name="module" value="doeditsubj" />
	<input type="hidden" name="subjid" value="{$webin['subjid']}" />
	
	University No.: <input type="text" name="subjuid" value="{$subj['subjuid']}" /><p />
	Password: <input type="password" name="password" /><br />
	<small>Leave it blank for no change.</small><p />
	Full Name: <input type="text" name="subjname" value="{$subj['subjname']}" /><p />
	Email: <input type="text" name="email" value="{$subj['email']}" /><p />
	Phone number: <input type="text" name="phone_num" value="{$subj['phone']}" /><p />
	Valid until: <input type="text" name="validuntilstr" value="{$validuntilstr}" /><p />	
	Notes:<br><textarea name="notes" style="vertical-align:top;height:50px;width:300px;"/>{$subj['notes']}</textarea>
	
ENDHTML;

	$hco.="<p />\n\tSex: ";
	foreach($sexlabel as $key=>$val){
		$hco.="<input class=\"radio\" type=\"radio\" name=\"sex\" value=\"".$key."\" ".(($subj['sex']==$key)?"checked":"")." /> ".$val;
	}
	$hco.="<p />\n\tRace: ";
	foreach($racelabel as $key=>$val){
		$hco.="<input class=\"radio\" type=\"radio\" name=\"race\" value=\"".$key."\" ".(($subj['race']==$key)?"checked":"")." /> ".$val;
	}
	$hco.="<p />\n\tIdentity: ";
	foreach($rolelabel as $key=>$val){
		$hco.="<input class=\"radio\" type=\"radio\" name=\"role\" value=\"".$key."\" ".(($subj['role']==$key)?"checked":"")." /> ".$val;
	}
	$hco.="<p />\n\tSubscription: ";
	foreach($subscriptionlabel as $key=>$val){
		$hco.="<input class=\"radio\" type=\"radio\" name=\"subscription\" value=\"".$key."\" ".(($subj['subscription']==$key)?"checked":"")." /> ".$val;
	}
	$hco.=<<<ENDHTML
	<p />
	<input class="submit" type="submit" value="Save" /><p />
	</form>
	
ENDHTML;
	
	templateout($hti,$hme,$hco,"../");
	return;
}

if (isset($webin['module']) && $webin['module']=="dounverify"){
	if (!checkinput($webin,"subjid")){
		header("Location: ".$_SERVER['HTTP_REFERER']);
		return;
	}
	$subj=sq_query_row("admin/subj.db","SELECT * FROM subj WHERE subjid=".sq_quote($webin['subjid']));
	if (!$subj){
		$gateway['internal_message']="No such participant.";
		putgateway($gateway);
		header("Location: ".$_SERVER['HTTP_REFERER']);
		return;
	}
	
	//do change
	if (!sq_query_safe("admin/timeslot.db","UPDATE timeslot SET subjid=NULL WHERE subjid=".sq_quote($webin['subjid']))) dieout("unverify clear fail");
	if (!sq_query_safe("admin/subj.db","DELETE FROM subj WHERE subjid=".sq_quote($webin['subjid']))) dieout("unverify delete fail");
	
	$hti="Verify information (remove participant)";
	$hme.=<<<ENDHTML
	<a href="#" onclick="window.close();">close window</a><p />
	
ENDHTML;
	$hco="";
	$hco.=<<<ENDHTML
	Participant {$subj['subjname']} removed. <p /><a href="#" onclick="window.close();">Click here to close this window.</a><p />

ENDHTML;

	templateout($hti,$hme,$hco,"../");
	return;
}

if (isset($webin['module']) && $webin['module']=="doverify"){
	if (!checkinput($webin,"subjid","subjname","email","phone_num","subjuid","sex","race","role")){
		header("Location: ".$_SERVER['HTTP_REFERER']);
		return;
	}
	//allow empty note
	if (!isset($webin['notes'])) $webin['notes']="";
	
	if (!sq_query_val("admin/subj.db","SELECT subjid FROM subj WHERE subjid=".sq_quote($webin['subjid']))){
		$gateway['internal_message']="No such participant.";
		putgateway($gateway);
		header("Location: ".$_SERVER['HTTP_REFERER']);
		return;
	}
	
	$validuntiltime=FALSE;
	if (isset($webin['validuntilstr'])) { $validuntiltime=strtotime($webin['validuntilstr']); }
	if ($validuntiltime==FALSE || $validuntiltime==-1){
		$gateway['internal_message']="No time or time format not recognized.";
		putgateway($gateway);
		header("Location: ".$_SERVER['HTTP_REFERER']);
		return;
	}
	
	//change info
	$updatestr=array();
	$updatestr[]="subjname=".sq_quote($webin['subjname']);
	$updatestr[]="email=".sq_quote($webin['email']);
	$updatestr[]="phone=".$webin['phone_num'];
	$updatestr[]="subjuid=".sq_quote($webin['subjuid']);
	$updatestr[]="sex=".sq_quote($webin['sex']);
	$updatestr[]="race=".sq_quote($webin['race']);
	$updatestr[]="role=".sq_quote($webin['role']);
	$updatestr[]="notes=".sq_quote($webin['notes']);
	
	//verify 
	$updatestr[]="validuntil=".$validuntiltime;
	
	//do change
	if (!sq_query_safe("admin/subj.db","UPDATE subj SET ".implode(",",$updatestr)." WHERE subjid=".sq_quote($webin['subjid']))) dieout("verify subj info fail");
	
	$hti="Verify information";
	$hme.=<<<ENDHTML
	<a href="#" onclick="window.close();">close window</a><p />
	
ENDHTML;
	$hco="";
	$hco.=<<<ENDHTML
	Verified. <p /><a href="#" onclick="window.close();">Click here to close this window.</a><p />

ENDHTML;

	templateout($hti,$hme,$hco,"../");
	return;
}

if (isset($webin['module']) && $webin['module']=="verify"){
	if (!checkinput($webin,"subjid")){
		header("Location: ".$_SERVER['HTTP_REFERER']);
		return;
	}
	//check if data match
	$subj=sq_query_row("admin/subj.db","SELECT * FROM subj WHERE subjid=".sq_quote($webin['subjid']));
	if (!$subj){
		$gateway['internal_message']="No such participant.";
		putgateway($gateway);
		header("Location: ".$_SERVER['HTTP_REFERER']);
		return;
	}
	if ($subj['validuntil']>time()){ //this should not happen
		$gateway['internal_message']="No need to verify.";
		putgateway($gateway);
		header("Location: ".$_SERVER['HTTP_REFERER']);
		return;
	}
	
	$hti="Verify information";
	$hme.=<<<ENDHTML
	<a href="#" onclick="window.close();">close window</a><p />
	
ENDHTML;
	$hco="";
	
	$hco.=<<<ENDHTML
	<h1>Verify information</h1>
	
	<form action="index.php" method="post">
	<input type="hidden" name="module" value="doverify" />
	<input type="hidden" name="subjid" value="{$webin['subjid']}" />
	
ENDHTML;
	$hco.="University No.: <input type=\"text\" name=\"subjuid\" value=\"".$subj['subjuid']."\">";
	$hco.="<p />\n\tSex: ";
	foreach($sexlabel as $key=>$val){
		$hco.="<input class=\"radio\" type=\"radio\" name=\"sex\" value=\"".$key."\" ".(($subj['sex']==$key)?"checked":"")." /> ".$val;
	}
	$hco.="<p />\n\tRace: ";
	foreach($racelabel as $key=>$val){
		$hco.="<input class=\"radio\" type=\"radio\" name=\"race\" value=\"".$key."\" ".(($subj['race']==$key)?"checked":"")." /> ".$val;
	}
	$hco.="<p />\n\tIdentity: ";
	foreach($rolelabel as $key=>$val){
		$hco.="<input class=\"radio\" type=\"radio\" name=\"role\" value=\"".$key."\" ".(($subj['role']==$key)?"checked":"")." /> ".$val;
	}
	$hco.="<p />\n";
	$hco.=<<<ENDHTML
	
	Full Name: <input type="text" name="subjname" value="{$subj['subjname']}" /><p />
	Email: <input type="text" name="email" value="{$subj['email']}" /><p />
	Phone number: <input type="text" name="phone_num" value="{$subj['phone']}" /><p />
	Valid until: <input type="text" name="validuntilstr" /><p />	
	Notes:<br><textarea name="notes" style="vertical-align:top;height:50px;width:300px;"/>{$subj['notes']}</textarea><br />
	
	<input class="submit" style="background:#cfc;" type="submit" value="Verify" /> 
	</form>
	<form action="index.php" method="post">
	<input type="hidden" name="module" value="dounverify" />
	<input type="hidden" name="subjid" value="{$webin['subjid']}" />
	<input class="submit" style="background:#fcc;" name="verifyfail" type="submit" value="Remove this record" />
	</form>
	
ENDHTML;
	
	templateout($hti,$hme,$hco,"../");
	return;
}

if (isset($webin['module']) && $webin['module']=="doreportbademail"){
	
	if (!checkinput($webin,"content")){
		header("Location: ".$_SERVER['HTTP_REFERER']);
		return;
	}
	//check if no content
	$matches=array();

	if (!preg_match_all("/[-_\\d\\.\\w]+@[-_\\d\\.\\w]+\\.\\w{2,}/i",$webin['content'],$matches)){
		$gateway['contentps']=$webin['content'];
		$gateway['internal_message']="No email detected.";
		putgateway($gateway);
		header("Location: ".$_SERVER['HTTP_REFERER']);
		return;
	}

	//done checking and grabing., put to a properly named array
	$matchedemails=array_unique($matches[0]);
	
	//unsubscribe
	$unsubscribedemails=array();
	sq_open("admin/subj.db");
	foreach($matchedemails as $email){
		//first find the subj, assume no duplicated emails
		$tmpsubjidarr=sq_query_val_array("admin/subj.db","SELECT subjid FROM subj WHERE email=".sq_quote($email));
		foreach ($tmpsubjidarr as $tmpsubjid){
			if (!sq_query_safe("admin/subj.db","UPDATE subj SET subscription='N' WHERE subjid=".sq_quote($tmpsubjid))) dieout("unsubscribe subj fail");
			$unsubscribedemails[$tmpsubjid]=$email;
		}
	}
	sq_close("admin/subj.db");
	
	$hti="Done unsubscribing!";
	$hme.=<<<ENDHTML
	<a href="index.php">back</a><p />
	<hr />
	<a href="../">signup page</a><p />
	
ENDHTML;
	$hco="";
	$hco.=<<<ENDHTML
	<h1>Done unsubscribing!</h1>
	<h3>Results:</h3>
	
ENDHTML;
	$unmatchedemails=array_diff($matchedemails,$unsubscribedemails);
	if (count($unsubscribedemails)>0){
		$hco.="Unsubscribed:<p /><table border=\"0\">";
		$i=0;
		foreach($unsubscribedemails as $tmpsubjid=>$email){
			$hco.="<tr class=\"".(($i%2==0)?"eventr":"oddtr")."\"><td style=\"padding-right:20px;\" style=\"text-align:left;\">$tmpsubjid</td><td>$email</td></tr>";
			$i++;
		}
		$hco.="</table><p />";
	}
	if (count($unmatchedemails)>0){
		$hco.="Unmatched:<p /><table border=\"0\">";
		$i=0;
		foreach($unmatchedemails as $email){
			$hco.="<tr class=\"".(($i%2==0)?"eventr":"oddtr")."\" style=\"text-align:left;\"><td>$email</td></tr>";
			$i++;
		}
		$hco.="</table><p />";
	}

	templateout($hti,$hme,$hco,"../");
	return;	
	
	
}

if (isset($webin['module']) && $webin['module']=="reportbademail"){
	$hti="Report bad emails";
	$hco="";
	//menu
	$hme.=<<<ENDHTML
<a href="index.php">back</a><p />
<hr />
<a href="../">signup page</a><p />

ENDHTML;
	$numsubj=sq_query_numrow("admin/subj.db","SELECT subjid FROM subj");
	$numsubjsub=sq_query_numrow("admin/subj.db","SELECT subjid FROM subj WHERE (validuntil NOT BETWEEN 0 AND ".time().") AND subscription='Y' ");
	if ($numsubjsub<=0){
		$gateway['internal_message']="We don't have registered subject yet.";
		putgateway($gateway);
		header("Location: ".$_SERVER['HTTP_REFERER']);
		return;
	}
	$hco.=<<<ENDHTML
	<h1>Report bad emails</h1>
	Currently, we have $numsubj registered participants, among them $numsubjsub are unexpired and subscribed.
	<form action="index.php" method="post">
	<p />
	<input type="hidden" name="module" value="doreportbademail" />
	In the following box you can paste email addresses with permanent errors and they will be automatically unsubscribed from the lab mailing list.<p />
	<textarea name="content" style="width:80%;height:200px;"></textarea>
	<p />
	<input type="submit" value="Report!" /><p />
	</form>
	
ENDHTML;
	
	templateout($hti,$hme,$hco,"../");
	
	return;
	
}

if (isset($webin['module']) && $webin['module']=="domassmail"){
	if (!checkinput($webin,"subject","content","numrecipient")){
		header("Location: ".$_SERVER['HTTP_REFERER']);
		return;
	}
	
	//check if no content
	if (preg_match("/fill in sth here/i",$webin['content'])){
		$gateway['contentps']=$webin['content'];
		$gateway['internal_message']="Your content is not finished yet.";
		putgateway($gateway);
		header("Location: ".$_SERVER['HTTP_REFERER']);
		return;
	}
	//numrecipient can't be zero
	if ($webin['numrecipient']<=0){
		$gateway['internal_message']="Number of Recipient can't be zero.";
		putgateway($gateway);
		header("Location: ".$_SERVER['HTTP_REFERER']);
		return;
	}
	
	//retrieve expter info. must have logged in expter.
	$expter=sq_query_row("admin/expter.db","SELECT * FROM expter WHERE expterid=".sq_quote($gateway['current_expter']));
	
	//sexarr, racearr and rolearr must set

	if (!isset($webin['racearr']) || !is_array($webin['racearr']) || count($webin['racearr'])<1 || !isset($webin['rolearr']) || !is_array($webin['rolearr']) || count($webin['rolearr'])<1 || !isset($webin['sexarr']) || !is_array($webin['sexarr']) || count($webin['sexarr'])<1){
		$gateway['internal_message']="Must select some sexes, races and roles.";
		putgateway($gateway);
		header("Location: ".$_SERVER['HTTP_REFERER']);
		return;
	}
	
	//filter subjects
	$racerolestr=" AND ((".sq_implode(" OR ","race",$webin['racearr']).") AND (".sq_implode(" OR ","role",$webin['rolearr']).") AND (".sq_implode(" OR ","sex",$webin['sexarr']).")) ";
	
	$removeemailstr="";
	if (isset($webin['clasharr']) && is_array($webin['clasharr'])){
		$avoidexptstr=sq_implode(" OR ","exptid",$webin['clasharr']);
		$removesubjarr=sq_query_val_array("admin/timeslot.db","SELECT DISTINCT subjid FROM timeslot WHERE subjid NOTNULL AND (".$avoidexptstr.") ");
		//turn the subj list into an email list
		$removesubjstr=sq_implode(" OR ","subjid",$removesubjarr);
		$removeemailarr=sq_query_val_array("admin/subj.db","SELECT DISTINCT email FROM subj WHERE $removesubjstr");
		$removeemailstr=" AND NOT (".sq_implode(" OR ","email",$removeemailarr).") ";
	}
	
	//get all eligible emails
	$fullemailarr=sq_query_pair_array("admin/subj.db","SELECT DISTINCT email,lastsenttime FROM subj WHERE (validuntil NOT BETWEEN 0 AND ".time().") AND subscription='Y' $racerolestr $removeemailstr ");
	
	//now sort according to last sent time, add random suffix for same lastsenttime
	foreach ($fullemailarr as $key=>$val){
		$fullemailarr[$key]=$val+rand_real();
	}
	asort($fullemailarr);
	$fullemailarr=array_keys($fullemailarr); //i assume array_keys preserves the array order
	
	$fullemailarr=array_slice($fullemailarr,0,$webin['numrecipient']); //this sentence is actually useless
	
	$numemailpertime=200;
	$successstr="";
	$successfulemailarr=array();
	
	//fetch and send to 200 subj at a time
	for ($emailstart=0;$emailstart<min($webin['numrecipient'],count($fullemailarr));$emailstart+=200){
		$emailarr=array_slice($fullemailarr,$emailstart,$numemailpertime);
		$recipients=implode(", ",$emailarr);
		if ($emailstart==0) $recipients.=", ".$expter['email']; //send sender a copy
		$headers=array();
		$headers['From']=$expter['exptername']." (".$expter['labname'].") <".$expter['email'].">";
		$headers['Subject']=$webin['subject'];
		$headers['Bcc']=$recipients;
		$body=$webin['content'];
		@$mail_object=&Mail::factory('smtp', $params);
		if ($mail_object && @$mail_object->send($recipients, $headers, $body)){
			$successstr.="Subject ".($emailstart+1)."-".($emailstart+count($emailarr)).": success!<br />";
			$successfulemailarr=array_merge($successfulemailarr,$emailarr);
		} else {
			$successstr.="Subject ".($emailstart+1)."-".($emailstart+count($emailarr)).": failed!<br />";
		}
	}
	//update timestamp
	$successfulemailstr=sq_implode(" OR ","email=",$successfulemailarr);
	if (!sq_query_safe("admin/subj.db","UPDATE subj SET lastsenttime=".time()." WHERE ".$successfulemailstr)) dieout("update timestamp fail");
	
	$hti="Mass mail sent!";
	$hme.=<<<ENDHTML
	<a href="index.php">back</a><p />
	<hr />
	<a href="../">signup page</a><p />
	
ENDHTML;
	$hco="";
	$hco.=<<<ENDHTML
	<h1>Mass mail sent!</h1>
	<h3>Results:</h3>
	$successstr

ENDHTML;

	templateout($hti,$hme,$hco,"../");
	return;	
	
	
}

if (isset($webin['module']) && $webin['module']=="massmail"){
	$hti="Send mass mail";
	$hco="";
	//menu
	$hme.=<<<ENDHTML
<a href="index.php">back</a><p />
<hr />
<a href="../">signup page</a><p />

ENDHTML;
	$expter=sq_query_row("admin/expter.db","SELECT * FROM expter WHERE expterid=".sq_quote($gateway['current_expter']));
	$numsubj=sq_query_numrow("admin/subj.db","SELECT DISTINCT email FROM subj");
	$numsubjsub=sq_query_numrow("admin/subj.db","SELECT DISTINCT email FROM subj WHERE (validuntil NOT BETWEEN 0 AND ".time().") AND subscription='Y' ");
	if ($numsubjsub<=0){
		$gateway['internal_message']="We don't have registered subject yet.";
		putgateway($gateway);
		header("Location: ".$_SERVER['HTTP_REFERER']);
		return;
	}

	$hco.=<<<ENDHTML
	<h1>Send mass mail</h1>
	<p>Currently, we have $numsubj registered emails, among them $numsubjsub are unexpired and subscribed.</p>
	Recent email records:
	<table border="0" cellspacing="0" cellpadding="0">
	
ENDHTML;

	//display recent email records
	$lastsenttimes=sq_query_val_array("admin/subj.db","SELECT DISTINCT lastsenttime FROM subj WHERE subscription='Y' ");
	sort($lastsenttimes);
	
	//do not display too much!
	foreach($lastsenttimes as $key=>$val){
		if ($key==0) continue;
		if ($val<strtotime("-1 year")) continue;
		$numtimes=sq_query_numrow("admin/subj.db","SELECT DISTINCT email FROM subj WHERE lastsenttime=$val AND subscription='Y' ");
		$hco.="<tr class=\"".(($key%2)?"eventr":"oddtr")."\"><td style=\"padding-right:3em;\">".date("l jS M Y, H:ia",$val).":</td><td style=\"text-align:right;\">$numtimes received</td></tr>";
	}
	
	$hco.=<<<ENDHTML
	</table>
	<p />
	<form action="index.php" method="post">
	<input type="hidden" name="module" value="domassmail" />
	Number of recipients: <input type="text" name="numrecipient" value="0" /><p />

	<h3>Email information</h3>
	Subject:<br />
	<input type="text" name="subject" style="width:80%;" /><p />
	Content:<br />
	<textarea name="content" style="width:80%;height:200px;">
	
ENDHTML;
	if (isset($gateway['contentps'])){
		$hco.=showonce($gateway,"contentps");
	} else {
		
	$hco.=<<<ENDHTML

Dear all,

[fill in sth here]

If you are interested in joining the experiment, please register by following the following hyperlink:

http://viscog.hku.hk/participate/

Should you have any enquiries, you can call me at {$expter['phone']} or email to {$expter['email']}

Thank you very much!

{$expter['exptername']},

{$expter['labname']},
Department of Psychology,
The University of Hong Kong.


ENDHTML;

	}
	
	$hco.=<<<ENDHTML
	</textarea><p />

	Send to: <br />
	<table border="0" cellpadding="0" cellspacing="0" style="width:100%;">
	<tr>
	<td style="width:50%;">
	<div class="checkboxbox" style="margin-bottom:10px;">

ENDHTML;
	$allsexes=array_keys($sexlabel);
	foreach ($allsexes as $sexi){
		$htmlid=uniqid("id");
		$hco.=<<<ENDHTML
	<table class="checkbox"><tr>
	<td><input id="$htmlid" name="sexarr[]" type="checkbox" class="radio" value="$sexi" checked /></td>
	<td class="cbright" onclick="document.getElementById('$htmlid').click();">{$sexlabel[$sexi]}</td>
	</tr></table>
	
ENDHTML;
	}
	
	$hco.=<<<ENDHTML
	</div>
	</td><td style="width:50%;">&nbsp;</td>
	</tr>
	<tr>
	<td style="width:50%;">
	<div class="checkboxbox">

ENDHTML;
	$allraces=array_keys($racelabel);
	foreach ($allraces as $racei){
		$htmlid=uniqid("id");
		$hco.=<<<ENDHTML
	<table class="checkbox"><tr>
	<td><input id="$htmlid" name="racearr[]" type="checkbox" class="radio" value="$racei" checked /></td>
	<td class="cbright" onclick="document.getElementById('$htmlid').click();">{$racelabel[$racei]}</td>
	</tr></table>
	
ENDHTML;
	}
	
	$hco.=<<<ENDHTML
	</div>
	</td>
	<td style="width:50%;">
	<div class="checkboxbox">

ENDHTML;
	$allroles=array_keys($rolelabel);
	
	foreach ($allroles as $rolei){
		$htmlid=uniqid("id");
		$hco.=<<<ENDHTML
	<table class="checkbox"><tr>
	<td><input id="$htmlid" name="rolearr[]" type="checkbox" class="radio" value="$rolei" checked /></td>
	<td class="cbright" onclick="document.getElementById('$htmlid').click();">{$rolelabel[$rolei]}</td>
	</tr></table>
	
ENDHTML;
	}
	
	$hco.=<<<ENDHTML
	</div>
	</td>
	</tr>
	</table>
	<p />
	Avoid participants from:<br />
	<div class="checkboxbox" style="height:100px;overflow:auto;">

ENDHTML;
	$allexptid=sq_query_val_array("admin/expt.db","SELECT exptid FROM expt ORDER BY timetag DESC");
	foreach ($allexptid as $expt){
		$htmlid=uniqid("id");
		$hco.=<<<ENDHTML
	<table class="checkbox"><tr>
	<td><input id="$htmlid" name="clasharr[]" type="checkbox" class="radio" value="$expt" /></td>
	<td class="cbright" onclick="document.getElementById('$htmlid').click();">$expt</td>
	</tr></table>
	
ENDHTML;
	}
	
	$hco.=<<<ENDHTML
	</div>
	<p />

	<input type="submit" value="Send!" /><p />
	PS: A copy of the mail will also be sent to you.
	</form>
	
ENDHTML;
	
	templateout($hti,$hme,$hco,"../");
	
	return;
	
}

if (isset($webin['module']) && $webin['module']=="doaddexpt"){
	if (!checkinput($webin,"exptid","exptname","exptdesc","numsubj","expterid")){
		header("Location: ".$_SERVER['HTTP_REFERER']);
		return;
	}
	$webin['exptid']=strtoupper($webin['exptid']);
	if (isset($webin['clasharr']) && is_array($webin['clasharr'])) $webin['clasharr']=serialize($webin['clasharr']); else $webin['clasharr']=serialize(array());
	if (isset($webin['racearr']) && is_array($webin['racearr'])) $webin['racearr']=serialize($webin['racearr']); else $webin['racearr']=serialize(array());
	if (isset($webin['rolearr']) && is_array($webin['rolearr'])) $webin['rolearr']=serialize($webin['rolearr']); else $webin['rolearr']=serialize(array());
	if (isset($webin['sexarr']) && is_array($webin['sexarr'])) $webin['sexarr']=serialize($webin['sexarr']); else $webin['sexarr']=serialize(array());

	if (sq_query_row("admin/expt.db","SELECT exptid FROM expt WHERE exptid=".sq_quote($webin['exptid']))){
		$gateway=getgateway();
		$gateway['internal_message']="This experiment ID is already in use. Please choose another one.";
		putgateway($gateway);
		header("Location: ".$_SERVER['HTTP_REFERER']);
		return;
	}
	if (!sq_query_safe("admin/expt.db","INSERT INTO expt(exptid,exptname,exptdesc,numsubj,clasharr,sexarr,racearr,rolearr,timetag,creator,openness) VALUES(".sq_quote($webin['exptid']).",".sq_quote($webin['exptname']).",".sq_quote($webin['exptdesc']).",".$webin['numsubj'].",".sq_quote($webin['clasharr']).",".sq_quote($webin['sexarr']).",".sq_quote($webin['racearr']).",".sq_quote($webin['rolearr']).",".time().",".sq_quote($webin['expterid']).",".sq_quote("new").")")) dieout("add expt exist fail");
	
	$gateway=getgateway();
	$gateway['internal_message']="Experiment successfully added.";
	putgateway($gateway);
	header("Location: index.php");
	return;
}

if (isset($webin['module']) && $webin['module']=="dofulleditexpt"){
	if (!checkinput($webin,"exptid","exptname","exptdesc","numsubj","sexarr","rolearr","racearr")){
		header("Location: ".$_SERVER['HTTP_REFERER']);
		return;
	}
	$webin['exptid']=strtoupper($webin['exptid']);
	if (isset($webin['clasharr']) && is_array($webin['clasharr'])) $webin['clasharr']=serialize($webin['clasharr']); else $webin['clasharr']=serialize(Array());
	if (isset($webin['racearr']) && is_array($webin['racearr'])) $webin['racearr']=serialize($webin['racearr']); else $webin['racearr']=serialize(Array());
	if (isset($webin['rolearr']) && is_array($webin['rolearr'])) $webin['rolearr']=serialize($webin['rolearr']); else $webin['rolearr']=serialize(Array());
	if (isset($webin['sexarr']) && is_array($webin['sexarr'])) $webin['sexarr']=serialize($webin['sexarr']); else $webin['sexarr']=serialize(Array());
	
	if (!sq_query_val("admin/expt.db","SELECT exptid FROM expt WHERE exptid=".sq_quote($webin['exptid']))){
		$gateway=getgateway();
		$gateway['internal_message']="No such exptid!";
		putgateway($gateway);
		header("Location: index.php");
		return;
	}
	
	if (!sq_query_safe("admin/expt.db","UPDATE expt SET numsubj=".$webin['numsubj'].",exptname=".sq_quote($webin['exptname']).",exptdesc=".sq_quote($webin['exptdesc']).",clasharr=".sq_quote($webin['clasharr']).",sexarr=".sq_quote($webin['sexarr']).",racearr=".sq_quote($webin['racearr']).",rolearr=".sq_quote($webin['rolearr'])."WHERE exptid=".sq_quote($webin['exptid']))) dieout("expt update fail");
	
	$gateway=getgateway();
	$gateway['internal_message']=$webin['exptid']." changed.";
	putgateway($gateway);
	header("Location: index.php");
	return;
}

if (isset($webin['module']) && ($webin['module']=="addexpt" || $webin['module']=="fulleditexpt")){
	$hti=($webin['module']=="fulleditexpt")?"Edit experiment":"Add a new experiment";
	$editexptname=$editexptdesc=$editnumsubj=$editclasharr=$editracearr=$editrolearr="";
	if ($webin['module']=="fulleditexpt"){
		@list($editexptname,$editexptdesc,$editnumsubj,$editclasharr,$editracearr,$editrolearr,$editsexarr)=sq_query_row("admin/expt.db","SELECT exptname,exptdesc,numsubj,clasharr,racearr,rolearr,sexarr FROM expt WHERE exptid=".sq_quote($gateway['current_expt']));
		$editclasharr=@unserialize(trim($editclasharr));
		$editracearr=@unserialize(trim($editracearr));
		$editrolearr=@unserialize(trim($editrolearr));
		$editsexarr=@unserialize(trim($editsexarr));
	}
	if (!is_array($editclasharr)) $editclasharr=Array();
	if (!is_array($editracearr)) $editracearr=array_keys($racelabel);
	if (!is_array($editrolearr)) $editrolearr=array_keys($rolelabel);
	if (!is_array($editsexarr)) $editsexarr=array_keys($sexlabel);
//menu
$hme.=<<<ENDHTML
<a href="javascript:history.go(-1);">back</a><p />
<hr />
<a href="../">signup page</a><p />


ENDHTML;

	$hco="";
if ($webin['module']=="fulleditexpt"){
	$hco.=<<<ENDHTML
	<h1>Edit an experiment</h1>
	<form action="index.php" method="post">
	<input type="hidden" name="module" value="dofulleditexpt" />
	Experiment ID: <b>{$gateway['current_expt']}</b><input type="hidden" name="exptid" value="{$gateway['current_expt']}" /><p />
	
ENDHTML;
}	else {
	$hco.=<<<ENDHTML
	<h1>Add a new experiment</h1>
	<form action="index.php" method="post">
	<input type="hidden" name="module" value="doaddexpt" />
	<input type="hidden" name="expterid" value="{$gateway['current_expter']}" />
	Experiment ID: <input type="text" name="exptid" /><p />
	
ENDHTML;
}
	$hco.=<<<ENDHTML
	Experiment name: <input type="text" name="exptname" value="$editexptname"/><p />
	Experiment descriptions:<br />
	<small>Enter descriptions here. E.g., who can/cannot do it, preparation, procedure, purpose, anything special, etc.</small><br />
	<textarea style="width:80%;height:100px;" name="exptdesc" />$editexptdesc</textarea>
	<p />
	Number of subjects: <input type="text" name="numsubj" value="$editnumsubj"/><p />
	Subject groups:<p />
	<table border="0" cellpadding="0" cellspacing="0" style="width:100%;">
	<tr>
	<td style="width:50%;">
	<div class="checkboxbox" style="margin-bottom:10px;">

ENDHTML;
	$allsexes=array_keys($sexlabel);
	
	foreach ($allsexes as $sexi){
		$checked=(in_array($sexi,$editsexarr))?"checked":"";
		$sexistr=$sexlabel[$sexi];
		$htmlid=uniqid("id");
		$hco.=<<<ENDHTML
	<table class="checkbox"><tr>
	<td><input id="$htmlid" name="sexarr[]" type="checkbox" class="radio" value="$sexi" $checked /></td>
	<td class="cbright" onclick="document.getElementById('$htmlid').click();">$sexistr</td>
	</tr></table>
	
ENDHTML;
	}
	
	$hco.=<<<ENDHTML
	</div>
	</td>
	<td style="width:50%;">&nbsp;</td>
	</tr>
	<tr>
	<td style="width:50%;">
	<div class="checkboxbox">

ENDHTML;
	$allraces=array_keys($racelabel);
	
	foreach ($allraces as $racei){
		$checked=(in_array($racei,$editracearr))?"checked":"";
		$raceistr=$racelabel[$racei];
		$htmlid=uniqid("id");
		$hco.=<<<ENDHTML
	<table class="checkbox"><tr>
	<td><input id="$htmlid" name="racearr[]" type="checkbox" class="radio" value="$racei" $checked /></td>
	<td class="cbright" onclick="document.getElementById('$htmlid').click();">$raceistr</td>
	</tr></table>
	
ENDHTML;
	}
	
	$hco.=<<<ENDHTML
	</div>
	</td>
	<td style="width:50%;">
	<div class="checkboxbox">

ENDHTML;
	$allroles=array_keys($rolelabel);
	
	foreach ($allroles as $rolei){
		$checked=(in_array($rolei,$editrolearr))?"checked":"";
		$roleistr=$rolelabel[$rolei];
		$htmlid=uniqid("id");
		$hco.=<<<ENDHTML
	<table class="checkbox"><tr>
	<td><input id="$htmlid" name="rolearr[]" type="checkbox" class="radio" value="$rolei" $checked /></td>
	<td class="cbright" onclick="document.getElementById('$htmlid').click();">$roleistr</td>
	</tr></table>
	
ENDHTML;
	}
	
	$hco.=<<<ENDHTML
	</div>
	</td>
	</tr>
	</table>
	<p />
	Clashing experiments:<br />
	<div class="checkboxbox" style="height:100px;overflow:auto;">

ENDHTML;
	$allexptid=sq_query_val_array("admin/expt.db","SELECT exptid FROM expt ORDER BY timetag DESC");
	if ($webin['module']=="fulleditexpt"){
		$allexptid=array_diff($allexptid,array($gateway['current_expt']));
	}
	$selectedexpt=array_intersect($allexptid,$editclasharr);
	$unselectedexpt=array_diff($allexptid,$editclasharr);
	
	foreach ($selectedexpt as $expt){
		$htmlid=uniqid("id");
		$hco.=<<<ENDHTML
	<table class="checkbox"><tr>
	<td><input id="$htmlid" name="clasharr[]" type="checkbox" class="radio" value="$expt" checked /></td>
	<td class="cbright" onclick="document.getElementById('$htmlid').click();">$expt</td>
	</tr></table>
	
ENDHTML;
	}
	foreach ($unselectedexpt as $expt){
		$htmlid=uniqid("id");
		$hco.=<<<ENDHTML
	<table class="checkbox"><tr>
	<td><input id="$htmlid" name="clasharr[]" type="checkbox" class="radio" value="$expt" /></td>
	<td class="cbright" onclick="document.getElementById('$htmlid').click();">$expt</td>
	</tr></table>
	
ENDHTML;
	}
	
	$hco.=<<<ENDHTML
	</div>
	<p />
	<input class="submit" type="submit" value="Save" />
	
	</form>
	
ENDHTML;
	
	templateout($hti,$hme,$hco,"../");
	return;
}

if (isset($webin['module']) && $webin['module']=="doeditexpter"){
	if (!checkinput($webin,"expterid","exptername","email","phone_num","labname")){
		header("Location: ".$_SERVER['HTTP_REFERER']);
		return;
	}
	$webin['expterid']=strtolower($webin['expterid']);
	
	if (!sq_query_val("admin/expter.db","SELECT expterid FROM expter WHERE expterid=".sq_quote($webin['expterid']))){
		$gateway=getgateway();
		$gateway['internal_message']="No such experimenter ID.";
		putgateway($gateway);
		header("Location: ".$_SERVER['HTTP_REFERER']);
		return;
	}
	if (!sq_query_safe("admin/expter.db","UPDATE expter SET exptername=".sq_quote($webin['exptername']).",email=".sq_quote($webin['email']).",phone=".$webin['phone_num'].",labname=".sq_quote($webin['labname'])." WHERE expterid=".sq_quote($webin['expterid']))) dieout("update expter fail");
	
	$gateway=getgateway();
	$gateway['internal_message']="Experimenter edited!";
	putgateway($gateway);
	header("Location: index.php");
	return;
}

if (isset($webin['module']) && $webin['module']=="editexpter"){
	$hti="Edit experimenter";
//menu
$hme.=<<<ENDHTML
<a href="javascript:history.go(-1);">back</a><p />
<hr />
<a href="../">signup page</a><p />


ENDHTML;
	$expter=sq_query_row("admin/expter.db","SELECT * FROM expter WHERE expterid=".sq_quote($gateway['current_expter']));

	$hco="";
	$hco.=<<<ENDHTML
	<h1>Edit experimenter</h1>
	<form action="index.php" method="post">
	<input type="hidden" name="module" value="doeditexpter" />
	Experimenter ID: {$gateway['current_expter']}<p />
	<input type="hidden" name="expterid" value="{$gateway['current_expter']}" />
	Name: <input type="text" name="exptername" value="{$expter['exptername']}" /><p />
	Email: <input type="text" name="email" value="{$expter['email']}" /><p />
	Phone: <input type="text" name="phone_num" value="{$expter['phone']}" /><p />
	Lab name: <input type="text" name="labname" value="{$expter['labname']}" /><p />
	<input class="submit" type="submit" value="Save" /><p />
		
	</form>
	
ENDHTML;
	
	templateout($hti,$hme,$hco,"../");
	return;
}

if (isset($webin['module']) && $webin['module']=="doaddexpter"){
	if (!checkinput($webin,"expterid","exptername","email","phone_num","labname")){
		header("Location: ".$_SERVER['HTTP_REFERER']);
		return;
	}
	$webin['expterid']=strtolower($webin['expterid']);
	
	if (sq_query_val("admin/expter.db","SELECT expterid FROM expter WHERE expterid=".sq_quote($webin['expterid']))){
		$gateway=getgateway();
		$gateway['internal_message']="This experimenter ID is already in use. Please choose another one";
		putgateway($gateway);
		header("Location: ".$_SERVER['HTTP_REFERER']);
		return;
	}
	if (!sq_query_safe("admin/expter.db","INSERT INTO expter(expterid,exptername,email,phone,labname) VALUES(".sq_quote($webin['expterid']).",".sq_quote($webin['exptername']).",".sq_quote($webin['email']).",".$webin['phone_num'].",".sq_quote($webin['labname']).")")) dieout("insert expter fail");
	
	$gateway=getgateway();
	$gateway['internal_message']="Experimenter added!";
	putgateway($gateway);
	header("Location: index.php");
	return;
}

if (isset($webin['module']) && $webin['module']=="addexpter"){
	$hti="New experimenter";
//menu
$hme.=<<<ENDHTML
<a href="javascript:history.go(-1);">back</a><p />
<hr />
<a href="../">signup page</a><p />


ENDHTML;

	$hco="";
	$hco.=<<<ENDHTML
	<h1>New experimenter</h1>
	<form action="index.php" method="post">
	<input type="hidden" name="module" value="doaddexpter" />
	Experimenter ID: <input type="text" name="expterid" /><p />
	Name: <input type="text" name="exptername" /><p />
	Email: <input type="text" name="email" /><p />
	Phone: <input type="text" name="phone_num" /><p />
	Lab name: <input type="text" name="labname" /><p />
	<input class="submit" type="submit" value="Submit!" /><p />
		
	</form>
	
ENDHTML;
	
	templateout($hti,$hme,$hco,"../");
	return;
}

if (isset($webin['module']) && $webin['module']=="doswitchexpter"){
	if (!checkinput($webin,"expterid")){
		header("Location: ".$_SERVER['HTTP_REFERER']);
		return;
	}
	$webin['expterid']=strtolower($webin['expterid']);
	
	$exptername=sq_query_val("admin/expter.db","SELECT exptername FROM expter WHERE expterid=".sq_quote($webin['expterid']));
	
	if (!$exptername){
		$gateway=getgateway();
		$gateway['internal_message']="No such expterid!";
		putgateway($gateway);
		header("Location: index.php");
		return;
	}
	
	$gateway=Array(); //when switch an expter, end the current session.
	$gateway['current_expter']=$webin['expterid'];
	putgateway($gateway);
	header("Location: index.php");
	return;
}

if (isset($webin['module']) && $webin['module']=="switchexpter"){
	$hti="Who are you?";
//menu
$hme.=<<<ENDHTML
<a href="?module=addexpter">new experimenter</a><p />
<hr />
<a href="../">signup page</a><p />


ENDHTML;

	$hco="";
	$hco.=<<<ENDHTML
	<h1>Who are you?</h1>
	<form action="index.php" method="post">
	<input type="hidden" name="module" value="doswitchexpter" />
	<table border="0" cellspacing="0" cellpadding="0">
	
ENDHTML;
	
	$expters=sq_query("admin/expter.db","SELECT expterid FROM expter");
	$i=0;
	foreach ($expters as $expter){
		if ($i%4==0){
			if ($i==0){$hco.="<tr>";}
			else { $hco.="</tr><tr>";}
		}
		$hco.=<<<ENDHTML
	<td style="text-align:center;width:25%;"><input class="submit" type="submit" name="expterid" value="{$expter['expterid']}" /><p /></td>
	
ENDHTML;
		$i++;
	}
	$hco.="</tr>";
	
	$hco.=<<<ENDHTML
	</table>

ENDHTML;
	if (count($expters)<=0) $hco.="No registered experimenter yet.";
	
	$hco.=<<<ENDHTML
	</form>
	
ENDHTML;
	
	templateout($hti,$hme,$hco,"../");
	return;
}

if (isset($webin['module']) && $webin['module']=="editexpt"){
	if (!checkinput($webin,"exptid","numsubj","openness")){
		header("Location: ".$_SERVER['HTTP_REFERER']);
		return;
	}
	$webin['exptid']=strtoupper($webin['exptid']);
	
	if (!sq_query_val("admin/expt.db","SELECT exptid FROM expt WHERE exptid=".sq_quote($webin['exptid']))){
		$gateway=getgateway();
		$gateway['internal_message']="No such exptid!";
		putgateway($gateway);
		header("Location: index.php");
		return;
	}
	if (!sq_query_safe("admin/expt.db","UPDATE expt SET numsubj=".$webin['numsubj'].",openness=".sq_quote($webin['openness'])."WHERE exptid=".sq_quote($webin['exptid']))) dieout("update expt fail");
	
	$gateway=getgateway();
	$gateway['internal_message']=$webin['exptid']." changed.";
	putgateway($gateway);
	header("Location: index.php");
	return;
}

if (isset($webin['module']) && $webin['module']=="doaddtimeslot"){	
	//very stupid here
	$gateway=getgateway();
	if (isset($webin['location'])) $gateway['locationps']=$webin['location'];
	if (isset($webin['timegap'])) $gateway['timegapps']=$webin['timegap'];
	if (isset($webin['duration_num'])) $gateway['durationps']=$webin['duration_num'];
	if (isset($webin['pay_num'])) $gateway['payps']=$webin['pay_num'];
	if (isset($webin['multiply_num'])) $gateway['multiplyps']=$webin['multiply_num'];
	if (isset($webin['time'])) $webin['time']=strtotime($webin['time']);
	if (!isset($webin['time']) || $webin['time']==FALSE || $webin['time']==-1){
		$gateway['internal_message']="No time or time format not recognized.";
		putgateway($gateway);
		header("Location: ".$_SERVER['HTTP_REFERER']);
		return;
	} else{
		$gateway['timeps']=$webin['time'];
		putgateway($gateway);
	}
	// stop being stupid here
	if (!checkinput($webin,"exptid","expterid","location","timegap","duration_num","pay_num","multiply_num")){
		header("Location: ".$_SERVER['HTTP_REFERER']);
		return;
	}
	$webin['expterid']=strtolower($webin['expterid']);
	$webin['exptid']=strtoupper($webin['exptid']);
	
	$success=true;
	for ($i=0;$i<$webin['multiply_num'];$i++){
		$success=$success && sq_query_safe("admin/timeslot.db","INSERT INTO timeslot(exptid,expterid,timetag,location,duration,pay) VALUES(".sq_quote($webin['exptid']).",".sq_quote($webin['expterid']).",".$webin['time'].",".sq_quote($webin['location']).",".sq_quote($webin['duration_num']).",".sq_quote($webin['pay_num']).")");
		$webin['time']=$webin['time']+60*($webin['timegap']+$webin['duration_num']);
	}
	
	if (!$success) dieout("addtimeslot fail");

	$gateway=getgateway(); 
	$gateway['internal_message']=$webin['multiply_num']." timeslots added.";
	$gateway['timeps']=$webin['time'];
	putgateway($gateway);
	header("Location: index.php");
	return;
}

if (isset($webin['module']) && $webin['module']=="docleardelts"){
	if (!checkinput($webin,"tsid")){
		header("Location: ".$_SERVER['HTTP_REFERER']);
		return;
	}
	$tsid=substr($webin['tsid'],1);
	$action=substr($webin['tsid'],0,1);
	
	if (!preg_match("/\d+/",$tsid) || !sq_query_val("admin/timeslot.db","SELECT timeslotid FROM timeslot WHERE timeslotid=".$tsid)){
		$gateway=getgateway();
		$gateway['internal_message']="No such timeslotid!";
		putgateway($gateway);
		header("Location: index.php");
		return;
	}
	
	if ($action=="c"){
		if (!sq_query_safe("admin/timeslot.db","UPDATE timeslot SET subjid=NULL WHERE timeslotid=".$tsid)) dieout("update timeslot fail");
		
		$gateway=getgateway();
		$gateway['internal_message']="1 timeslot cleared.";
		putgateway($gateway);
		header("Location: index.php");
		return;
	
	} else if ($action="d"){
		if (!sq_query_safe("admin/timeslot.db","DELETE FROM timeslot WHERE timeslotid=".$tsid)) dieout("delete timeslot fail");
		
		$gateway=getgateway();
		$gateway['internal_message']="1 timeslot deleted.";
		putgateway($gateway);
		header("Location: index.php");
		return;
	} else {
		$gateway=getgateway();
		$gateway['internal_message']="no action. clear or delete?";
		putgateway($gateway);
		header("Location: index.php");
		return;
	}
	
}

if (isset($webin['module']) && $webin['module']=="doviewsubjectinfo"){
	$hti="Participants information";
	//menu
	$hme.=<<<ENDHTML
	<a href="index.php?module=viewsubjectinfo">search again</a><p />
	<a href="index.php">back</a><p />
	<hr />
	<a href="../">signup page</a><p />

ENDHTML;

	$hco="";

	//first formulate the query string
	$querystr="";
	if (isset($webin['querystr'])){
		$querystr=$webin['querystr'];
	} else {
		$queryarr=array();
		if (isset($webin['subjid']) && checkinput($webin,"subjid")) $queryarr[]="subjid=".sq_quote($webin['subjid']);
		if (isset($webin['subjuid']) && checkinput($webin,"subjuid")) $queryarr[]="subjuid=".$webin['subjuid'];
		if (isset($webin['subjname'])) $queryarr[]="subjname LIKE ".sq_quote("%".$webin['subjname']."%");
		if (isset($webin['email'])) $queryarr[]="email LIKE ".sq_quote("%".$webin['email']."%");
		if (isset($webin['phone_num']) && checkinput($webin,"phone_num")) $queryarr[]="phone=".$webin['phone_num'];
		if (isset($webin['sex'])) $queryarr[]="sex=".sq_quote($webin['sex']);
		if (isset($webin['race'])) $queryarr[]="race=".sq_quote($webin['race']);
		if (isset($webin['role'])) $queryarr[]="role=".sq_quote($webin['role']);
		if (isset($webin['subscription'])) $queryarr[]="subscription=".sq_quote($webin['subscription']);
		$querystr=trim(implode(" AND ",$queryarr));
		if ($querystr!="") $querystr=" WHERE ".$querystr;
		
		$gateway=getgateway();
		if (isset($gateway['internal_message']) && $gateway['internal_message']!=""){
			header("Location: index.php?module=viewsubjectinfo");
			return;
			}
	}

	if (!isset($webin['page'])) $webin['page']=1;
	
	$numperpage=20;
	
	$skip=($webin['page']-1)*$numperpage;

	$subjdb=sq_query("admin/subj.db","SELECT * FROM subj $querystr LIMIT $numperpage OFFSET $skip");
	$numresults=sq_query_numrow("admin/subj.db","SELECT subjid FROM subj $querystr");
	$numpage=(int)($numresults/$numperpage)+1;
	$linkstr="";
	for ($i=1;$i<=$numpage;$i++){
		if ($i==$webin['page']){
			$linkstr.=" <span class=\"current\">$i</span>";
		} else {
			$linkstr.=" <a href=\"?module=doviewsubjectinfo&amp;querystr=".urlencode($querystr)."&amp;page=$i\">$i</a>";
		}
	}

	if (count($subjdb)==0){
		$hco.=<<<ENDHTML
	<h1>Search results</h1>
	No matching participants.
	<p />
	<a href="index.php?module=viewsubjectinfo">search again</a><p />
	
ENDHTML;
	} else {
		$hco.=<<<ENDHTML
	<h1>Search results -- $numresults matches</h1>
	Page: $linkstr<p />
	<table>
	<tr class="headtr" ><td>ID</td><td>U No.</td><td>Name</td><td>E-mail</td><td>Phone</td><td>Edit</td></tr>
		
ENDHTML;

		$i=0;
		foreach ($subjdb as $subj){
			$hco.="<tr class=\"".(($i%2==0)?"eventr":"oddtr")."\"><td>{$subj['subjid']}</td><td>{$subj['subjuid']}</td><td>{$subj['subjname']}</td><td>{$subj['email']}</td><td>{$subj['phone']}</td>";
			$hco.="<td><a href=\"?module=editsubj&amp;subjid=".$subj['subjid']."\" target=\"_blank\" >edit</a></td>";
			$hco.="</tr>\n";
			$i++;
		}
		$hco.="</table><p /><br />";
	}
	
	templateout($hti,$hme,$hco,"../");
	return;
}

if (isset($webin['module']) && $webin['module']=="viewsubjectinfo"){

	$hti="Participants information";
	//menu
	$hme.=<<<ENDHTML
	<a href="index.php">back</a><p />
	<hr />
	<a href="../">signup page</a><p />

ENDHTML;

	$hco="";

	$hco.=<<<ENDHTML
	<h1>Search participants</h1>
	<form action="index.php" method="get">
	<input type="hidden" name="module" value="doviewsubjectinfo" />
	<!-- Participant ID= <input type="text" name="subjid" /><p /> -->
	University No.= <input type="text" name="subjuid" /><p />
	Full Name= <input type="text" name="subjname" /><p />
	Email= <input type="text" name="email" /><p />
	Phone number= <input type="text" name="phone_num" /><p />

ENDHTML;
	$hco.="Sex=";
	foreach($sexlabel as $key=>$val){
		$hco.=<<<ENDHTML
	<input class="radio" type="radio" name="sex" value="$key" />$val
	
ENDHTML;
	}
	$hco.="<p />Race=";
	foreach($racelabel as $key=>$val){
		$hco.=<<<ENDHTML
	<input class="radio" type="radio" name="race" value="$key" />$val
	
ENDHTML;
	}
	$hco.="<p />Identity=";
	foreach($rolelabel as $key=>$val){
		$hco.=<<<ENDHTML
	<input class="radio" type="radio" name="role" value="$key" />$val
	
ENDHTML;
	}
	/*
	$hco.="<p />Subscription=";
	foreach($subscriptionlabel as $key=>$val){
		$hco.=<<<ENDHTML
	<input class="radio" type="radio" name="subscription" value="$key" />$val
	
ENDHTML;
	}
	*/
	$hco.=<<<ENDHTML
	<p />
	<input class="submit" type="submit" value="Query" />
	</form><p />
	
ENDHTML;
	
	templateout($hti,$hme,$hco,"../");
	return;
}

if (isset($webin['module']) && $webin['module']=="viewcalender"){

	if (isset($webin['weekselection'])){
		$curselday=strtotime($webin['weekselection']);
		if (!$curselday) $curselday=strtotime("now");
		$curselweekts=strtotime("last sunday",strtotime($webin['weekselection']." + 1 day"));
	} else {
		$curselday=strtotime("now");
		$curselweekts=strtotime("last sunday",strtotime("now + 1 day"));
	}
	
	$hti="Lab calender";
	//menu
	$tclastmonth=date("j F Y",strtotime("1 ".date("F Y",$curselday)." -1 week"));
	$hme.=<<<ENDHTML
		<div onclick="document.getElementById('calenderformlastmonth').submit();">
			<form id="calenderformlastmonth" action="index.php" method="get">
				<input type="hidden" name="module" value="viewcalender" />
				<input type="hidden" name="weekselection" value="$tclastmonth" />
				
ENDHTML;
	$hme.=date("F",strtotime("1 ".date("F Y",$curselday)." -1 week"))."<br />";
	$hme.=<<<ENDHTML
			</form>
		</div>
		
ENDHTML;
	$hme.="<b>".date("F Y",$curselday)."</b><br />";
	$hme.=<<<ENDHTML
	<div style="width:100%;float:left;">
	<table cellpadding="1" cellspacing="1" border="0" style="float:right;">
		<tr class="headtr"><td>S</td><td>M</td><td>T</td><td>W</td><td>T</td><td>F</td><td>S</td></tr>
	
ENDHTML;
	//fill in calender
	$numdayofmonth=(int)date("t",$curselday);
	$tcday=date("w",strtotime("1 ".date("F Y",$curselday)));
	$tcweek=0;
	$calender=array();
	$calender[$tcweek]=array();
	for ($day=1;$day<=$numdayofmonth;$day++){
		if ($tcday>6){
			$tcweek++;
			$tcday=0;
			$calender[$tcweek]=array();
		}
		$calender[$tcweek][$tcday]=$day;
		$tcday++;
	}
	
	for ($tcweek=0;$tcweek<count($calender);$tcweek++){
		$tcweekfirstday=array_values($calender[$tcweek]);
		$tcweekfirstday=$tcweekfirstday[0].date(" F Y",$curselday);
		$hme.=<<<ENDHTML
		<tr onclick="document.getElementById('calenderform$tcweek').submit();">
		<form id="calenderform$tcweek" action="index.php" method="get">
			<input type="hidden" name="module" value="viewcalender" />
			<input type="hidden" name="weekselection" value="$tcweekfirstday" />
			
ENDHTML;

		for ($tcday=0;$tcday<7;$tcday++){
			if (!isset($calender[$tcweek][$tcday])){
				$hme.="<td class=\"".(($tcday%2==0)?"eventr":"oddtr")."\"></td>";
			} else {
				$todaystr="";
				if (date("n",$curselday)==date("n") && $calender[$tcweek][$tcday]==date("j")) $todaystr=" style=\"font-weight:bold;\"";
				$hme.="<td class=\"".(($tcday%2==0)?"eventr":"oddtr")."\"$todaystr>".$calender[$tcweek][$tcday]."</td>";
			}
		}
		$hme.="</form></tr>";
	}
	
	$hme.=<<<ENDHTML
	</table>
	</div>
	<br />
	
ENDHTML;
	
	$tcnextmonth=date("j F Y",strtotime("$numdayofmonth ".date("F Y",$curselday)." +1 week"));
	$hme.=<<<ENDHTML
		<div onclick="document.getElementById('calenderformnextmonth').submit();">
			<form id="calenderformnextmonth" action="index.php" method="get">
				<input type="hidden" name="module" value="viewcalender" />
				<input type="hidden" name="weekselection" value="$tcnextmonth" />
				
ENDHTML;
	$hme.=date("F",strtotime("$numdayofmonth ".date("F Y",$curselday)." +1 week"))."<br />";
	$hme.=<<<ENDHTML
			</form>
		</div>
		
ENDHTML;
	
	$hme.=<<<ENDHTML
	<hr />
	<a href="index.php">back</a><p />
	<hr />
	<a href="../">signup page</a><p />
	
ENDHTML;


	$hco="";
	
	$mergetimegap=20;
	
	$currenttimeslotdb=sq_query("admin/timeslot.db","SELECT timeslotid,timetag,location,duration,expterid,exptid FROM timeslot WHERE timetag>".$curselweekts." AND timetag<".($curselweekts+604800)." ORDER BY timetag ASC");
	
	$intcal=array();
	$locations=array();
	$expters=array();
	
	foreach ($currenttimeslotdb as $timeslot){
		if (!array_key_exists($timeslot['location'],$locations)) $locations[$timeslot['location']]=array();
		if (!array_key_exists($timeslot['expterid'],$expters)) $expters[$timeslot['expterid']]=array();
		
		//get day and time
		$day=date("w",$timeslot['timetag']);
		$tod=roundtod5min($timeslot['timetag']);
		//other important information includes location, duration, and expterid
		//check if we can live by extending a intcal box, or else create a new one
		if (!isset($intcal[$day])) $intcal[$day]=array();
		ksort($intcal[$day]);
		$lasttod=false;
		foreach ($intcal[$day] as $curtod=>$curtodarr){
			foreach($curtodarr as $curtoditem){
				if (($curtoditem['timetag']<$timeslot['timetag']) && $curtoditem['location']==$timeslot['location'] && $curtoditem['expterid']==$timeslot['expterid']) $lasttod=$curtod;
			}
		}
		//if last tod exist, check if the current timeslot can be merged with any one at the last time
		$merged=false;
		if ($lasttod){
			foreach($intcal[$day][$lasttod] as $toditemid=>$curtoditem){
				if ($curtoditem['location']==$timeslot['location'] && $curtoditem['expterid']==$timeslot['expterid'] && (($curtoditem['timetag']+($curtoditem['duration']+$mergetimegap)*60)>=$timeslot['timetag'])){
					$intcal[$day][$lasttod][$toditemid]['duration']=(int)(($timeslot['timetag']-$curtoditem['timetag'])/60+$timeslot['duration']);
					$merged=true;
					break;
				}
			}
		}
		
		if (!$merged){
		// if not merged, create a new tod item
			if (!isset($intcal[$day][$tod])) $intcal[$day][$tod]=array();
			$toditem=array();
			$toditem['location']=$timeslot['location'];
			$toditem['duration']=$timeslot['duration'];
			$toditem['expterid']=$timeslot['expterid'];
			$toditem['timetag']=$timeslot['timetag'];
			$toditem['exptid']=$timeslot['exptid'];
			$intcal[$day][$tod][]=$toditem;
		}
	}
	
	//determine locations color
	$i=0;
	foreach($locations as $location=>$arr){
		$locations[$location]=array("index"=>$i);
		$i++;
	}
	$i=0;
	foreach($expters as $expter=>$arr){
		$expters[$expter]=array("index"=>$i);
		$i++;
	}
	
	$hco.=<<<ENDHTML
	<h1>Lab calender</h1>
	
	<table width="100%" cellspacing="0" cellpadding="0" border="0">
		<tr class="headtr">
		
ENDHTML;
	$hco.="<td width=\"12.5%\">&nbsp;</td>";
	for ($i=0;$i<7;$i++){
		$hco.="<td width=\"12.5%\">".date("D",strtotime("now + $i day",$curselweekts))."<br />".date("j",strtotime("now + $i day",$curselweekts))."</td>";
	}
	$hco.=<<<ENDHTML
		</tr>
		
ENDHTML;
	//first round
	for ($day=0;$day<7;$day++){
		if (isset($intcal[$day])) foreach($intcal[$day] as $curcelltod=>$curcelltodarr){
			foreach($intcal[$day][$curcelltod] as $curtodindex=>$curtoditem){
				//now check other events to determine how to position the current event
				$cellindexarr=array();
				foreach ($intcal[$day] as $todarrid=>$todarr){
					foreach($intcal[$day][$todarrid] as $toditemid=>$toditem){
						if (($toditem['timetag']>=$curtoditem['timetag'] && $toditem['timetag']<=($curtoditem['timetag']+60*$curtoditem['duration'])) || ($curtoditem['timetag']>=$toditem['timetag'] && $curtoditem['timetag']<=($toditem['timetag']+60*$toditem['duration']))){
							if (isset($toditem['cellindex'])) $cellindexarr[]=$toditem['cellindex'];
						}
					}
				}
				$curcellindex=0;
				while (1){
					if (!in_array($curcellindex,$cellindexarr)) break;
					$curcellindex++;
				}
				//position determined, put
				$intcal[$day][$curcelltod][$curtodindex]['cellindex']=$curcellindex;
			}
		}
	}
	//second round
	for ($hour=9;$hour<21;$hour++){
		for ($min=0;$min<60;$min+=5){
			$hco.="<tr>";
			$hco.=($min==0 || $min==30)?"<td class=\"caleven30mintd\">":"<td class=\"caleventd\">";
			if ($min==0 || $min==30){
				$hco.="<div class=\"calparentdiv\"><div class=\"caltsdiv\">$hour:".sprintf("%02d",$min)."</div></div>";
			}
			$hco.="</td>";
			for ($day=0;$day<7;$day++){
				$hco.=($day%2)?(($min==0 || $min==30)?"<td class=\"caleven30mintd\">":"<td class=\"caleventd\">"):(($min==0 || $min==30)?"<td class=\"calodd30mintd\">":"<td class=\"caloddtd\">");
//				$hco.=$cpdstart;
				$curcelltod=$hour.":".sprintf("%02d",$min);
				if (isset($intcal[$day][$curcelltod])){
					foreach($intcal[$day][$curcelltod] as $curtodindex=>$curtoditem){
						//now check other events to determine how to position the current event
						$total=0;
						$cellindexarr=array();
						foreach ($intcal[$day] as $todarrid=>$todarr){
							foreach($intcal[$day][$todarrid] as $toditemid=>$toditem){
								if (($toditem['timetag']>=$curtoditem['timetag'] && $toditem['timetag']<=($curtoditem['timetag']+60*$curtoditem['duration'])) || ($curtoditem['timetag']>=$toditem['timetag'] && $curtoditem['timetag']<=($toditem['timetag']+60*$toditem['duration']))){
									//update total, find my position
									if (isset($toditem['cellindex'])) $total=($toditem['cellindex']>$total)?$toditem['cellindex']:$total;
								}
							}
						}
						$curcellindex=$intcal[$day][$curcelltod][$curtodindex]['cellindex'];
						$total+=1;
						
						$infohtml=$curtoditem['exptid']."<br />".$curtoditem['location']."<br />";
						if ($total==1){
							$hco.=gencalcell($curtoditem['duration']."px","0%","95%",$expters[$curtoditem['expterid']]['index']/count($expters),$infohtml,$curtoditem['expterid']);
						} else {
							$hco.=gencalcell($curtoditem['duration']."px",((int)($curcellindex*(97-(110/$total))/($total-1)))."%",((int)(110/$total))."%",$expters[$curtoditem['expterid']]['index']/count($expters),$infohtml,$curtoditem['expterid']);
						}
					}
				}
				$hco.=($min==0 || $min==30)?"<div class=\"calparentdiv\"><div></div></div></td>":"</td>";
			}
			$hco.="</tr>";
		}
	}

	$hco.=<<<ENDHTML
	
	</table>
	
ENDHTML;
	
	templateout($hti,$hme,$hco,"../");
	return;
}

//htmlout

$hti="Experiment admin page";
$hco="";

//menu
if (isset($webin['currentexpt']) && sq_query_val("admin/expt.db","SELECT exptid FROM expt WHERE exptid=".sq_quote($webin['currentexpt']))){
	$gateway=getgateway();
	$gateway['current_expt']=$webin['currentexpt'];
	putgateway($gateway);
} else if (!isset($gateway['current_expt'])) {
	$gateway['current_expt']=sq_query_val("admin/expt.db","SELECT exptid FROM expt WHERE creator=".sq_quote($gateway['current_expter'])." AND (openness=".sq_quote("open")." OR openness=".sq_quote("restricted")." OR openness=".sq_quote("new").") ORDER BY timetag DESC");
	if (!$gateway['current_expt']) unset($gateway['current_expt']);
	putgateway($gateway);
}
//check if set gateway is real
if (isset($gateway['current_expt']) && !sq_query_val("admin/expt.db","SELECT exptid FROM expt WHERE exptid=".sq_quote($gateway['current_expt']))) unset($gateway['current_expt']);
$interestingexpts=Array();
if (isset($gateway['current_expt'])){
	$interestingexpts=sq_query("admin/expt.db","SELECT exptid FROM expt WHERE exptid!=".sq_quote($gateway['current_expt'])." AND (creator=".sq_quote($gateway['current_expter'])." AND (openness=".sq_quote("open")." OR openness=".sq_quote("restricted")." OR openness=".sq_quote("new").")) ORDER BY timetag DESC");
	foreach ($interestingexpts as $expt){
	$hme.=<<<ENDHTML
		<a href="?currentexpt={$expt['exptid']}">{$expt['exptid']}</a><p />
		
ENDHTML;
	}
	list($currexptid,$currexptname,$currexptopenness,$currexptnumsubj)=sq_query_row("admin/expt.db","SELECT exptid,exptname,openness,numsubj FROM expt WHERE exptid=".sq_quote($gateway['current_expt']));
}
$nocurrexptstr=(isset($gateway['current_expt']))?"exptid=".sq_quote($gateway['current_expt'])." OR ":"";
$boringexpts=sq_query("admin/expt.db","SELECT exptid FROM expt WHERE NOT ($nocurrexptstr (creator=".sq_quote($gateway['current_expter'])." AND (openness=".sq_quote("open")." OR openness=".sq_quote("restricted")." OR openness=".sq_quote("new")."))) ORDER BY timetag DESC");

if (count($boringexpts)>0) $hme.="<select style=\"width:200px;\" onchange=\"location.href='?currentexpt='+this.value;\" ><option>Other experiments</option>";

foreach ($boringexpts as $expt){
$hme.=<<<ENDHTML
	<option value="{$expt['exptid']}">{$expt['exptid']}</option>
	
ENDHTML;
}
if (count($boringexpts)>0) $hme.="</select>";


if (count($interestingexpts)>0 || count($boringexpts)>0) $hme.="<hr />";


$hme.=<<<ENDHTML
<a href="?module=viewcalender">view calender</a><p />
<a href="?module=viewsubjectinfo">view participants info</a><p />
<a href="?module=massmail">send mass mail</a><p />
<a href="?module=reportbademail">report bad emails</a><p />
<a href="?module=addexpt">new experiment</a><p />
<hr />
<a href="../">signup page</a><p />

ENDHTML;
//experiment openings
if (!isset($gateway['current_expt'])){
	$hco.="You have no experiment yet.<p />Open some new ones or you can work with other experimenters' experiments.";	
} else {
	
$newps=($currexptopenness=="new")?"selected=\"selected\"":"";
$openps=($currexptopenness=="open")?"selected=\"selected\"":"";
$restrictedps=($currexptopenness=="restricted")?"selected=\"selected\"":"";
$closedps=($currexptopenness=="closed")?"selected=\"selected\"":"";
$hco.=<<<ENDHTML
<h1>$currexptname ($currexptid) <a href="?module=fulleditexpt">edit</a></h1>
<form id="changeopennessform" action="index.php" method="post">
<input type="hidden" name="module" value="editexpt" />
<input type="hidden" name="exptid" value="$currexptid" />
Number of subject: <input style="width:3em;" type="text" name="numsubj" value="$currexptnumsubj" />
&nbsp;&nbsp;
Status: 
<select onchange="document.getElementById('changeopennessform').submit();" name="openness">
	<option value="new" $newps/>Not ready
	<option value="open" $openps/>Active
	<option value="restricted" $restrictedps/>Restricted
	<option value="closed" $closedps/>Closed
</select>
&nbsp;&nbsp;
</form>
<br />

ENDHTML;

$currenttimeslotdb=sq_query("admin/timeslot.db","SELECT timeslotid,subjid,timetag,location,duration,pay,expterid FROM timeslot WHERE exptid=".sq_quote($currexptid)." ORDER BY timetag DESC");

$hco.=<<<ENDHTML
<table border="0" cellpadding="0" cellspacing="0" width="100%">
<tr>
<td style="width:100%;">
<h3>Timeslots:</h3>
</td><td style="white-space:nowrap;" align="right">
<div class="hintbg">
&nbsp;

ENDHTML;
if (count($currenttimeslotdb)>0 && sq_query_numrow("admin/timeslot.db","SELECT timeslotid FROM timeslot WHERE exptid=".sq_quote($currexptid)." AND subjid NOTNULL")>=$currexptnumsubj) $hco.="<em>QUOTA FULL!</em> ";

$hco.="Total: ".sq_query_numrow("admin/timeslot.db","SELECT timeslotid FROM timeslot WHERE exptid=".sq_quote($currexptid)." AND subjid NOTNULL")." Done: ".sq_query_numrow("admin/timeslot.db","SELECT timeslotid FROM timeslot WHERE exptid=".sq_quote($currexptid)." AND subjid NOTNULL AND (timetag+duration*60)<".time())." Working: ".sq_query_numrow("admin/timeslot.db","SELECT timeslotid FROM timeslot WHERE exptid=".sq_quote($currexptid)." AND subjid NOTNULL AND (timetag+duration*60)>".time()." AND timetag<".time());

$hco.=<<<ENDHTML
&nbsp;
</div>
</td>
</tr>
</table>

ENDHTML;

if (count($currenttimeslotdb)>0){
	$hco.=<<<ENDHTML
	<form id="tscleardelform" action="index.php" method="post">
	<input type="hidden" name="module" value="docleardelts" />
	<table>
	<tr class="headtr"><td style="width:140px;">Subject</td><td>UID</td><td>Time</td><td>Loc.</td><td>Dur.</td><td>Pay</td><td></td><td></td><td></td></tr>
	
ENDHTML;
	$hco.=buttoninit("tscleardelform","tsid");
	$i=0;
	sq_open("admin/subj.db");
	
	$lastreadstr="";
	foreach ($currenttimeslotdb as $timeslot){
		$subj=false;
		if (isset($timeslot['subjid'])){
			//first get subj name and phone
			$subj=sq_query_row("admin/subj.db","SELECT * FROM subj WHERE subjid=".sq_quote($timeslot['subjid']));
		}
		//determine whether it's unread, reading or read.
		$readstr="";
		if ($timeslot['timetag']>time()) $readstr="unread";
		else if (($timeslot['timetag']+$timeslot['duration']*60)<time()) $readstr="read";
		else $readstr="reading";
		if ($lastreadstr=="unread" && $readstr=="read"){
			$hco.="<tr class=\"readingeventr\"><td colspan=\"9\"></td></tr>";
		}
		$lastreadstr=$readstr;
		
		if ($subj){
			//if subj needs validate, give the link
			$subjnamestr="";
			if ($subj['validuntil']<time()){
				$subjnamestr="<br /><a target=\"_blank\" href=\"?module=verify&amp;subjid=".$subj['subjid']."\">Validate</a>";
			}
			$phonetag="phone";
			if (isset($webin['replacephonetag'])) $phonetag=$webin['replacephonetag'];
			
			$hco.="<tr class=\"".$readstr.(($i%2==0)?"eventr":"oddtr")."\"><td><a href=\"?module=editsubj&amp;subjid=".$subj['subjid']."\" target=\"_blank\" >".$subj['subjname']."</a> (".$subj['sex'].")</td><td>".$subj['subjuid'].$subjnamestr."</td><td>".date("D",$timeslot['timetag'])."&nbsp;".date("d/n H:i",$timeslot['timetag'])."</td><td>".$timeslot['location']."</td><td>".$timeslot['duration']."</td><td>".$timeslot['pay']."</td>\n";
		} else {
			$hco.="<tr class=\"".$readstr.(($i%2==0)?"eventr":"oddtr")."\"><td> </td><td> </td><td>".date("D",$timeslot['timetag'])."&nbsp;".date("d/n H:i",$timeslot['timetag'])."</td><td>".$timeslot['location']."</td><td>".$timeslot['duration']."</td><td>".$timeslot['pay']."</td>\n";
		}
		$hco.="<td>".buttontag("tsid","c".$timeslot['timeslotid'],"Clr")."</td><td>".buttontag("tsid","d".$timeslot['timeslotid'],"Del")."</td>";
		$hco.="<td>";
		if ($timeslot['expterid']!=$gateway['current_expter']) $hco.="<em>by ".$timeslot['expterid']."</em>";
		$hco.=<<<ENDHTML
		</td></tr>
		
ENDHTML;
		$i++;
	}
	sq_close("admin/subj.db");
	
	$hco.="</table>\n</form>\n";
} else {
	$hco.="No timeslot.\n";
}
$timeps=showonce($gateway,"timeps");
$timeps=date("dMY H:i",($timeps=="")?time():$timeps);
$locationps=showonce($gateway,"locationps");
$locationps=($locationps=="")?"The Jockey Club Tower 610":$locationps;
$timegapps=showonce($gateway,"timegapps");
$durationps=showonce($gateway,"durationps");
$payps=showonce($gateway,"payps");
$multiplyps=showonce($gateway,"multiplyps");
$multiplyps=($multiplyps=="")?"1":$multiplyps;

$hco.=<<<ENDHTML
<h3>Add timeslot: <input class="smallsubmit" type="button" onclick="document.getElementById('addtsdiv').style.display='block';" value="expand" /></h3>
<div style="display:none;" id="addtsdiv">
<form action="index.php" method="post">
<input type="hidden" name="module" value="doaddtimeslot" />
<input type="hidden" name="exptid" value="{$gateway['current_expt']}" />
<input type="hidden" name="expterid" value="{$gateway['current_expter']}" />
Time: <input type="text" name="time" value="$timeps" />
<br />
Location: <input type="text" name="location" value="$locationps" />
<br />
Duration (min): <input type="text" name="duration_num" value="$durationps"/>
<br />
timegap (min): <input type="text" name="timegap" value="$timegapps"/>
<br />
Pay: HK$ <input type="text" name="pay_num" value="$payps"/>
<br />
How many slots? <input type="text" name="multiply_num" value="$multiplyps"/>
<p />
<input type="submit" value="Add" />
</form>
</div>

ENDHTML;


$hco.=<<<ENDHTML
<p />
</form>

ENDHTML;
}



templateout($hti,$hme,$hco,"../");


?>

