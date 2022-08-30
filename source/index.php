<?php
// project: expsignup/index
// by: louis
// on: 14sep2010
// last modify: 20sep2010
// version: 2.1
// script-dependency: libcommon sqliteutil
// server-dependency: sqlite or dl
// db-dependency: timeslot.db expt.db expter.db subject.db

require_once("./libcommon.php");

$webin=getwebin();
$gateway=getgateway();

//modules


if (isset($webin['module']) && $webin['module']=="docancelts"){
	if (!checkinput($webin,"tsid_num","subjid")){
		header("Location: ".$_SERVER['HTTP_REFERER']);
		return;
	}
	
	//check if it's a hack
	if ($webin['subjid']!=showonce($gateway,"secusubjid") || $webin['secucode']!=showonce($gateway,"secucode")){
		$gateway['internal_message']="Sorry, you have logged off already. please login again.";
		putgateway($gateway);
		header("Location: index.php?module=cancelts");
		return;
	}
	
	if (!sq_query_val("admin/timeslot.db","SELECT timeslotid FROM timeslot WHERE timeslotid=".$webin['tsid_num'])){
		$gateway=getgateway();
		$gateway['internal_message']="No such timeslotid!";
		putgateway($gateway);
		header("Location: index.php");
		return;
	}
	
	if (!sq_query_safe("admin/timeslot.db","UPDATE timeslot SET subjid=NULL WHERE timeslotid=".$webin['tsid_num'])) dieout("update timeslot fail");
	
	$gateway=getgateway();
	$gateway['internal_message']="Appointment cancelled.";
	putgateway($gateway);
	header("Location: index.php");
	return;
}

if (isset($webin['module']) && $webin['module']=="cancelts2"){
	//generate the registration page
	if (!checkinput($webin,"password","subjuid")){
		header("Location: ".$_SERVER['HTTP_REFERER']);
		return;
	}
	//check if data match
	$subj=sq_query_row("admin/subj.db","SELECT * FROM subj WHERE subjuid=".$webin['subjuid']);
	if (!$subj || $subj['password']!=md5($webin['password'])){
		$gateway['internal_message']="Wrong U-number or wrong password.";
		putgateway($gateway);
		header("Location: ".$_SERVER['HTTP_REFERER']);
		return;
	}
	
	$hti="Cancel an appointment";
	$hme=<<<ENDHTML
	<a href="index.php">back</a><p />
	
ENDHTML;
	$hco="";
	$gateway['secucode']=md5(uniqid(rand(), true));
	$gateway['secusubjid']=$subj['subjid'];
	putgateway($gateway);
	
	$hco.=<<<ENDHTML
	<h1>Cancel an appointment</h1>
	
ENDHTML;
	$currentts=sq_query("admin/timeslot.db","SELECT * FROM timeslot WHERE timetag>".time()." AND subjid=".sq_quote($subj['subjid'])." ORDER BY timetag ASC");
	
	if (count($currentts)>0){
		$hco.=<<<ENDHTML
		<form id="tscancelform" action="index.php" method="post">
		<input type="hidden" name="module" value="docancelts" />
		<input type="hidden" name="secucode" value="{$gateway['secucode']}" />
		<input type="hidden" name="subjid" value="{$subj['subjid']}" />
	
ENDHTML;
		$hco.=buttoninit("tscancelform","tsid_num");
		$hco.=<<<ENDHTML
		Your experiment appointments:<p />
		<table>
		<tr class="headtr"><td>Expt ID</td><td>Date/Time</td><td>Location</td><td>Experimenter</td><td></td></tr>
		
ENDHTML;
		sq_open("admin/expter.db");
		$i=0;
		foreach ($currentts as $timeslot){
			$tsexpter=sq_query_val("admin/expter.db","SELECT exptername FROM expter WHERE expterid=".sq_quote($timeslot['expterid']));
			$hco.="<tr class=\"".(($i%2==0)?"unreadeventr":"unreadoddtr")."\"><td>".$timeslot['exptid']."</td><td>".date("D",$timeslot['timetag'])."&nbsp;".date("d/n H:i",$timeslot['timetag'])."</td><td>".$timeslot['location']."</td><td>".$tsexpter."</td><td>".buttontag("tsid_num",$timeslot['timeslotid'],"Cancel")."</td></tr>";
			$i++;
		}
		sq_close("admin/expter.db");
		$hco.=<<<ENDHTML
		</table>
		</form>
		
ENDHTML;
	} else {
		$hco.=<<<ENDHTML
		You do not have any experiment appointments.<p />
		<a href="index.php">back to signup page</a><p />
		
ENDHTML;
	
	}
	

	templateout($hti,$hme,$hco);
	return;
}


if (isset($webin['module']) && $webin['module']=="cancelts"){
	//generate the registration page

	$hti="Cancel an appointment";
	$hme=<<<ENDHTML
	<a href="index.php">back</a><p />
	
ENDHTML;
	$hco="";
	$hco.=<<<ENDHTML
	<h1>Cancel an appointment</h1>
	Please login first:<p />
	<form action="index.php" method="post">
	<input type="hidden" name="module" value="cancelts2" />
	University number: <input type="text" name="subjuid" /><p />
	Password: <input type="password" name="password" /><p />
	
	<input class="submit" type="submit" value="Login" />
	</form>
	
ENDHTML;
	

	templateout($hti,$hme,$hco);
	return;
}

if (isset($webin['module']) && $webin['module']=="dosignupts"){
	if (!checkinput($webin,"tsid_num","password","subjuid")){
		header("Location: ".$_SERVER['HTTP_REFERER']);
		return;
	}
	//check if data match
	$subj=sq_query_row("admin/subj.db","SELECT * FROM subj WHERE subjuid=".$webin['subjuid']);
	if (!$subj){
		$gateway['internal_message']="Wrong U-number or wrong password.";
		putgateway($gateway);
		header("Location: ".$_SERVER['HTTP_REFERER']);
		return;
	}
	if ($subj['password']!=md5($webin['password']) && $masterpassword!=md5($webin['password'])){
		$gateway['internal_message']="Wrong U-number or wrong password.";
		putgateway($gateway);
		header("Location: ".$_SERVER['HTTP_REFERER']);
		return;
	}
	//check if this timeslot have been signed
	$ts=sq_query_row("admin/timeslot.db","SELECT * FROM timeslot WHERE timeslotid=".$webin['tsid_num']);
	if (!$ts){
		$gateway['internal_message']="Sorry, this timeslot is no longer available. Please choose another one.";
		putgateway($gateway);
		header("Location: ".$_SERVER['HTTP_REFERER']);
		return;
	}
	if (!is_null($ts['subjid'])){
		$gateway['internal_message']="Sorry, this timeslot is no longer available. Please choose another one.";
		putgateway($gateway);
		header("Location: ".$_SERVER['HTTP_REFERER']);
		return;
	}	
	//check if this subject had done any of the clashing expts
	$expt=sq_query_row("admin/expt.db","SELECT * FROM expt WHERE exptid=".sq_quote($ts['exptid']));
	$clasharr=unserialize($expt['clasharr']);
	$clasharr=(is_array($clasharr)?$clasharr:Array());
	$clasharr[]=$ts['exptid']; //can't do the same expt twice
	$clasharrstr="";
	foreach($clasharr as $val){
		$clasharrstr.="exptid=".sq_quote($val)." OR ";
	}
	$clasharrstr=substr($clasharrstr,0,strlen($clasharrstr)-3);
	$clashedexpt=sq_query_val("admin/timeslot.db","SELECT exptid FROM timeslot WHERE subjid=".sq_quote($subj['subjid'])." AND ($clasharrstr)");
	if ($clashedexpt){
		//clashed
		$gateway['internal_message']="Sorry. Since you had registered with the experiment $clashedexpt,<br /> before, you cannot participate in this experiment.";
		putgateway($gateway);
		header("Location: ".$_SERVER['HTTP_REFERER']);
		return;
	}
	//check if the subject meet the eligibility of the expt
	//race:
	if (!in_array($subj['race'],unserialize($expt['racearr']))){
		$gateway['internal_message']="Sorry. Since we are looking for ".wordsconnect(array_map2($racelabel,unserialize($expt['racearr'])))." participants only, you are not eligible for this experiment.";
		putgateway($gateway);
		header("Location: ".$_SERVER['HTTP_REFERER']);
		return;
	}
	//sex:
	if (!in_array($subj['sex'],unserialize($expt['sexarr']))){
		$gateway['internal_message']="Sorry. Since we are looking for ".wordsconnect(array_map2($sexlabel1,unserialize($expt['sexarr'])))." participants only, you are not eligible for this experiment.";
		putgateway($gateway);
		header("Location: ".$_SERVER['HTTP_REFERER']);
		return;
	}
	//role:
	if (!in_array($subj['role'],unserialize($expt['rolearr']))){
		$gateway['internal_message']="Sorry. Since we are looking for ".wordsconnect(array_map2($rolelabel1,unserialize($expt['rolearr'])))." participants only, you are not eligible for this experiment.";
		putgateway($gateway);
		header("Location: ".$_SERVER['HTTP_REFERER']);
		return;
	}
	
	//check if the experiment has been full
	if (sq_query_numrow("admin/timeslot.db","SELECT timeslotid FROM timeslot WHERE exptid=".sq_quote($ts['exptid'])." AND subjid NOTNULL")>=$expt['numsubj']){
		$gateway['internal_message']="Sorry, this timeslot is no longer available. Please choose another one.";
		putgateway($gateway);
		header("Location: ".$_SERVER['HTTP_REFERER']);
		return;
	}
	//get experimenter info
	$expter=sq_query_row("admin/expter.db","SELECT * FROM expter WHERE expterid=".sq_quote($ts['expterid']));
	if (!$expter){
		$gateway['internal_message']="There is some internal problem with this timeslot. Please sign up for another one.";
		putgateway($gateway);
		header("Location: ".$_SERVER['HTTP_REFERER']);
		return;
	}
	
	//should be okay now; sign the experiment
	if (!sq_query_safe("admin/timeslot.db","UPDATE timeslot SET subjid=".sq_quote($subj['subjid'])." WHERE timeslotid=".$webin['tsid_num'])){
		dieout("can't sign timeslot".serialize($webin).serialize($ts));
	}
	
	//should have signed. send email
		
	//put mail
	$mdate=date("d M Y (D)",$ts['timetag']);
	$mtime=date("H:i",$ts['timetag']);
	$mco=<<<ENDHTML
	Dear {$subj['subjname']},
	
	Thank you for joining our experiments.
	
	Your experiment appointment:
	
	-- {$expt['exptname']} ({$expt['exptid']}) --
	
	Experimenter:	{$expter['exptername']}
	Date:	$mdate
	Time:	$mtime
	Location:	{$ts['location']}
	Duration:	{$ts['duration']} min
	Pay:	HK$ {$ts['pay']}
	
	Please be punctual, because there may be another experiment session following that use the same room. We may request you to withdraw from the experiment if you are more than 5 minutes late.
	
	In case you can't come, you may cancel the appointment via the participant website http://viscog.hku.hk/participate/ .
	
	Should you have any enquiries, please call me at {$expter['phone']} or email to {$expter['email']}. Thank you very much!
	
	Best regards,
	
	{$expter['exptername']}
	
	{$expter['labname']},
	Department of Psychology,
	The University of Hong Kong.
	
	
ENDHTML;

	$success=FALSE;
	
	$recipients=$subj['email'].", ".$expter['email'];
	
	$headers=array();
	$headers['From']=$expter['exptername']." (".$expter['labname'].") <".$expter['email'].">";
	$headers['To']=$subj['email'];
	$headers['Subject']="Experiment reminder";
	$headers['Bcc']=$expter['email'];
	$body=$mco;
	
	@$mail_object=&Mail::factory('smtp', $params);
	if ($mail_object && @$mail_object->send($recipients, $headers, $body)) $success=TRUE;

	$hti="Sign up successful!";
//	$emailsuccessstr=$success?"PS: A confirmation email has been sent to you.":"PS: We failed to send you a confirmation email. Please make sure you remember the location and time to come.";
	
	$hme=<<<ENDHTML
	<a href="index.php">back</a><p />
	
ENDHTML;
	$timestr=date("D d/n H:i",$ts['timetag']);
	$hco="";
	$hco.=<<<ENDHTML
	<h1>Sign up successful!</h1>
	You have signed up the following timeslot:<p />
	<div class="exptbox" style="padding:5px;">
	<table>
	<tr class="headtr"><td>Expt ID</td><td>Date/Time</td><td>Location</td><td>(min)</td><td>(HK$)</td></tr>
	<tr class="eventr"><td>{$ts['exptid']}</td><td>$timestr</td><td>{$ts['location']}</td><td>{$ts['duration']}</td><td>{$ts['pay']}</td></tr>
	</table>
	</div>
	<p />
	Your experimenter: {$expter['exptername']}
	<p />
	Thank you very much!
	
ENDHTML;
	
	

	templateout($hti,$hme,$hco);
	return;
}

if (isset($webin['module']) && $webin['module']=="signupts"){
	//generate the signup page
	if (!checkinput($webin,"tsid_num")){
		header("Location: ".$_SERVER['HTTP_REFERER']);
		return;
	}
	$ts=sq_query_row("admin/timeslot.db","SELECT timeslotid,exptid,expterid,subjid,location,timetag,pay,duration FROM timeslot WHERE timeslotid=".$webin['tsid_num']);
	$expt=sq_query_row("admin/expt.db","SELECT exptid,exptname,exptdesc,clasharr,racearr,rolearr,sexarr FROM expt WHERE exptid=".sq_quote($ts['exptid']));
	$expter=sq_query_row("admin/expter.db","SELECT * FROM expter WHERE expterid=".sq_quote($ts['expterid']));
	$expt['clasharr']=@unserialize(trim($expt['clasharr']));
	$expt['racearr']=@unserialize(trim($expt['racearr']));
	$expt['rolearr']=@unserialize(trim($expt['rolearr']));
	$expt['sexarr']=@unserialize(trim($expt['sexarr']));
	if (!is_array($expt['clasharr'])) $expt['clasharr']=Array();
	if (!is_array($expt['racearr'])) $expt['racearr']=Array();
	if (!is_array($expt['rolearr'])) $expt['rolearr']=Array();
	if (!is_array($expt['sexarr'])) $expt['sexarr']=Array();
	if(!is_null($ts['subjid'])){
		$gateway['internal_message']="This timeslot has just been occupied. Please choose another one.";
		putgateway($gateway);
		header("Location: ".$_SERVER['HTTP_REFERER']);
		return;
	}

	$hti="Sign up for an experiment";
	$hme=<<<ENDHTML
	<a href="index.php">back</a><p />
	
ENDHTML;
	$hco="";
	$timestr=date("D d/n H:i",$ts['timetag']);
	$hco.=<<<ENDHTML
	<h1>Sign up for this experiment</h1>
	Your experiment:<p />
	<div class="exptbox" style="padding:5px;">
		<b>{$expt['exptname']}</b> ({$expt['exptid']})
		<p />
		<div class="exptdesc">
		{$expt['exptdesc']}
		</div>

ENDHTML;
	//if all categories apply
	if (count($racelabel)==count($expt['racearr']) && count($rolelabel)==count($expt['rolearr']) && count($sexlabel)==count($expt['sexarr'])){
		$hco.="";
	} else {
		$hco.="<div class=\"exptdesc\" style=\"color:#777;margin-top:10px;\">";
		//some subj requirement
		$subjtitle="";
		$islasttype="";
		if (count($sexlabel)!=count($expt['sexarr'])) $islasttype="sex";
		if (count($racelabel)!=count($expt['racearr'])) $islasttype="race";
		if (count($rolelabel)!=count($expt['rolearr'])) $islasttype="role";
		if (count($sexlabel)!=count($expt['sexarr'])) $subjtitle.=($islasttype=="sex"?wordsconnect(array_map2($sexlabel2,$expt['sexarr'])):wordsconnect(array_map2($sexlabel1,$expt['sexarr'])))." ";
		if (count($racelabel)!=count($expt['racearr'])) $subjtitle.=($islasttype=="race"?(wordsconnect(array_map2($racelabel,$expt['racearr']))." people"):wordsconnect(array_map2($racelabel,$expt['racearr'])))." ";
		if (count($rolelabel)!=count($expt['rolearr'])) $subjtitle.=wordsconnect(array_map2($rolelabel2,$expt['rolearr']))." ";
		$hco.="* This experiment is open for ".$subjtitle."only";
		$hco.="</div>";
	}
	if (count($expt['clasharr'])>0){
		$hco.="<div class=\"exptdesc\" style=\"color:#777;margin-top:10px;\">";
		$hco.="* Conflicting experiment(s): ";
		foreach($expt['clasharr'] as $expti){
			$hco.="<span class=\"badexpttag\">&times; $expti</span>";
		}
		$hco.="</div>";
	}
	$hco.=<<<ENDHTML
		</div>
	</div>
	<p />
	Your timeslot:
	
ENDHTML;
	$hco.=<<<ENDHTML
	<div class="exptbox">
	<table style="width:100%;">
	<tr class="headtr"><td>Expt ID</td><td>Date/Time</td><td>Location</td><td>(min)</td><td>(HK$)</td></tr>
	<tr class="eventr"><td>{$ts['exptid']}</td><td>$timestr</td><td>{$ts['location']}</td><td>{$ts['duration']}</td><td>{$ts['pay']}</td></tr>
	</table>
	</div>
	<div style="margin:25px;"> </div>
	<form action="index.php" method="post">
	<input type="hidden" name="module" value="dosignupts" />
	<input type="hidden" name="tsid_num" value="{$ts['timeslotid']}" />
	University No.: <input type="text" name="subjuid" /><p />
	Password: <input type="password" name="password" /><br />
	<p />
	<input class="submit" type="submit" value="Sign up!" />
	</form>
	<p /><br />
	<hr style="border-style:dashed;" />
	* Do not have a password? Please <a href="?module=reg">register as a participant</a> first!
	<p />
	
ENDHTML;

	templateout($hti,$hme,$hco);
	return;
}

if (isset($webin['module']) && $webin['module']=="doreg"){
	if (!checkinput($webin,"subjuid","password","subjname","email","phone_num","sex","race","role")){
		header("Location: ".$_SERVER['HTTP_REFERER']);
		return;
	}
	
	if (sq_query_val("admin/subj.db","SELECT subjuid FROM subj WHERE subjuid=".$webin['subjuid'])){
		$gateway=getgateway();
		$gateway['internal_message']="This U-number is already registered. You cannot register more than one account.";
		putgateway($gateway);
		header("Location: ".$_SERVER['HTTP_REFERER']);
		return;
	}
	
	if (!sq_query_safe("admin/subj.db","INSERT INTO subj(subjuid,password,subjname,email,phone,sex,race,role) VALUES(".$webin['subjuid'].",".sq_quote(md5($webin['password'])).",".sq_quote($webin['subjname']).",".sq_quote($webin['email']).",".sq_quote($webin['phone_num']).",".sq_quote($webin['sex']).",".sq_quote($webin['race']).",".sq_quote($webin['role']).")")) dieout("sqsafe die");
	
	$gateway=getgateway();
	$gateway['internal_message']="Successfully registered!";
	putgateway($gateway);
	header("Location: index.php");
	return;
}

if (isset($webin['module']) && $webin['module']=="reg"){
	//generate the registration page

	$hti="Register as a participant";
	$hme=<<<ENDHTML
	<a href="index.php">back</a><p />
	
ENDHTML;
	$hco="";
	$hco.=<<<ENDHTML
	<h1>Register as a participant</h1>
	Welcome!<p />
	To join our experiments, you need to register as a participant here. Kindly please fill in the following form.<p />
	<hr />
	<br />
	<form action="index.php" method="post">
	<input type="hidden" name="module" value="doreg" />
	University No.: <input type="text" name="subjuid" /><br />
	<small>Please enter your 10-digit HKU University No.<br />For staff, please enter your 5-digit HKU Staff No.</small><p />
	Password: <input type="password" name="password" /><br />
	<small>Please choose a password.<br />You need to key in this password when you sign up for our experiments later.</small><p />
	Full Name: <input type="text" name="subjname" /><p />
	Email: <input type="text" name="email" /><p />
	Phone number: <input type="text" name="phone_num" /><p />
	
ENDHTML;
	
		$hco.="Sex: ";
		foreach($sexlabel as $key=>$val){
			$hco.=" <input class=\"radio\" type=\"radio\" name=\"sex\" value=\"".$key."\" /> ".$val;
		}
		$hco.="<p />\n\tRace: ";
		foreach($racelabel as $key=>$val){
			$hco.=" <input class=\"radio\" type=\"radio\" name=\"race\" value=\"".$key."\" /> ".$val;
		}
		$hco.="<br /><small>We need to know your race because we do face recognition research.</small>";
		$hco.="<p />\n\tYou are a: <br />";
		foreach($rolelabel3 as $key=>$val){
			$hco.=" <input class=\"radio\" type=\"radio\" name=\"role\" value=\"".$key."\" /> ".$val;
		}
		
	$hco.=<<<ENDHTML
	<p />
	<input class="submit" type="submit" value="Register!" />
	</form><p />
	<p />
	
ENDHTML;
	
	

	templateout($hti,$hme,$hco);
	return;
}


if (isset($webin['module']) && $webin['module']=="doeditreg"){
	if (!checkinput($webin,"subjid","subjname","email","phone_num","subscription")){
		header("Location: ".$_SERVER['HTTP_REFERER']);
		return;
	}
	
	//check if it's a hack
	if ($webin['subjid']!=showonce($gateway,"secusubjid") || $webin['secucode']!=showonce($gateway,"secucode")){
		$gateway['internal_message']="Sorry, you have logged off already. please login again.";
		putgateway($gateway);
		header("Location: index.php?module=editreg");
		return;
	}
	
	//retrieve subj data
	$subj=sq_query_row("admin/subj.db","SELECT validuntil FROM subj WHERE subjid=".sq_quote($webin['subjid']));
	if (!$subj){
		$gateway=getgateway();
		$gateway['internal_message']="University number not found. Are you registered?";
		putgateway($gateway);
		header("Location: ".$_SERVER['HTTP_REFERER']);
		return;
	}
	
	//change info
	$updatestr=array();
	$updatestr[]="subjname=".sq_quote($webin['subjname']);
	$updatestr[]="email=".sq_quote($webin['email']);
	$updatestr[]="phone=".$webin['phone_num'];
	$updatestr[]="subscription=".sq_quote($webin['subscription']);
	
	//check if password is changed
	if (isset($webin['password']) && $webin['password']!="") $updatestr[]="password=".sq_quote(md5(trim($webin['password'])));
	
	//if expired, allow change more info
	if ($subj['validuntil']<time()){
		if (!checkinput($webin,"subjuid","sex","race","role")){
			header("Location: ".$_SERVER['HTTP_REFERER']);
			return;
		}
		$updatestr[]="subjuid=".sq_quote($webin['subjuid']);
		$updatestr[]="sex=".sq_quote($webin['sex']);
		$updatestr[]="race=".sq_quote($webin['race']);
		$updatestr[]="role=".sq_quote($webin['role']);
	}
	
	//do change
	if (!sq_query_safe("admin/subj.db","UPDATE subj SET ".implode(",",$updatestr)." WHERE subjid=".sq_quote($webin['subjid']))) dieout("update subj info fail");

	$gateway=getgateway();
	$gateway['internal_message']="Information updated!";
	putgateway($gateway);
	header("Location: index.php");
	return;
}

if (isset($webin['module']) && $webin['module']=="editreg2"){
	//generate the registration page
	if (!checkinput($webin,"password","subjuid")){
		header("Location: ".$_SERVER['HTTP_REFERER']);
		return;
	}
	//check if data match
	$subj=sq_query_row("admin/subj.db","SELECT * FROM subj WHERE subjuid=".$webin['subjuid']);
	if (!$subj){
		$gateway['internal_message']="Wrong U-number or wrong password.";
		putgateway($gateway);
		header("Location: ".$_SERVER['HTTP_REFERER']);
		return;
	}
	if ($subj['password']!=md5($webin['password'])){
		$gateway['internal_message']="Wrong U-number or wrong password.";
		putgateway($gateway);
		header("Location: ".$_SERVER['HTTP_REFERER']);
		return;
	}
	
	
	$hti="Update information";
	$hme=<<<ENDHTML
	<a href="index.php">back</a><p />
	
ENDHTML;
	$hco="";
	$gateway['secucode']=md5(uniqid(rand(), true));
	$gateway['secusubjid']=$subj['subjid'];
	putgateway($gateway);
	
	$hco.=<<<ENDHTML
	<h1>Update information</h1>
	
	<form action="index.php" method="post">
	<input type="hidden" name="module" value="doeditreg" />
	<input type="hidden" name="secucode" value="{$gateway['secucode']}" />
	<input type="hidden" name="subjid" value="{$subj['subjid']}" />
	
ENDHTML;
	if ($subj['validuntil']>time()){ //change these info only if expired
		$hco.=<<<ENDHTML
	University No.: {$subj['subjuid']}<p />
	Sex: {$sexlabel[$subj['sex']]}<p />
	Race: {$racelabel[$subj['race']]}<p />
	Identity: {$rolelabel[$subj['role']]}<p />
	
ENDHTML;
	} else {
		$hco.="University No.: <input type=\"text\" name=\"subjuid\" value=\"".$subj['subjuid']."\">";
		$hco.="<p />\n\tSex: ";
		foreach($sexlabel as $key=>$val){
			$hco.=" <input class=\"radio\" type=\"radio\" name=\"sex\" value=\"".$key."\" ".(($subj['sex']==$key)?"checked":"")." /> ".$val;
		}
		$hco.="<p />\n\tRace: ";
		foreach($racelabel as $key=>$val){
			$hco.=" <input class=\"radio\" type=\"radio\" name=\"race\" value=\"".$key."\" ".(($subj['race']==$key)?"checked":"")." /> ".$val;
		}
		$hco.="<p />\n\tIdentity: ";
		foreach($rolelabel as $key=>$val){
			$hco.=" <input class=\"radio\" type=\"radio\" name=\"role\" value=\"".$key."\" ".(($subj['role']==$key)?"checked":"")." /> ".$val;
		}
		$hco.="<p />\n";
	}
	$hco.=<<<ENDHTML
	
	Password: <input type="password" name="password" /><br />
	<small>Leave it blank for no change.</small><p />
	Full Name: <input type="text" name="subjname" value="{$subj['subjname']}" /><p />
	Email: <input type="text" name="email" value="{$subj['email']}" /><p />
	Phone number: <input type="text" name="phone_num" value="{$subj['phone']}" /><p />
	Send me email for new experiments:
	
ENDHTML;
	foreach($subscriptionlabel1 as $key=>$val){
		$hco.=" <input class=\"radio\" type=\"radio\" name=\"subscription\" value=\"".$key."\" ".(($subj['subscription']==$key)?"checked":"")." /> ".$val;
	}
	$hco.=<<<ENDHTML
	<p />
	<input class="submit" type="submit" value="Save" />
	</form>
	
ENDHTML;
	
	

	templateout($hti,$hme,$hco);
	return;
}


if (isset($webin['module']) && $webin['module']=="editreg"){
	//generate the registration page

	$hti="Update information";
	$hme=<<<ENDHTML
	<a href="index.php">back</a><p />
	
ENDHTML;
	$hco="";
	$hco.=<<<ENDHTML
	<h1>Update information</h1>
	Please login first:<p />
	<form action="index.php" method="post">
	<input type="hidden" name="module" value="editreg2" />
	University number: <input type="text" name="subjuid" /><p />
	Password: <input type="password" name="password" /><p />
	
	<input class="submit" type="submit" value="Login" />
	</form>
	
ENDHTML;
	

	templateout($hti,$hme,$hco);
	return;
}



//htmlout

$hti="Experiment openings";
$hme="";
$hco="";

//menu
$hme.=<<<ENDHTML
<small style="color:#888;">
Welcome to the <span style="font-weight:bold;"><span style="color:#4bf;">HKU</span> <span style="color:#f4b;">Vision</span> <span style="color:#7d2;">Laboratories</span></span> <span style="color:#f84;">participant website</span>!
</small>
<p />
<a href="?module=reg">register as a participant</a><p />
<hr style="margin-top:20px;margin-bottom:15px;" />
<a href="?module=editreg">update&nbsp;your&nbsp;information</a><p />
<a href="?module=cancelts">cancel an appointment</a><p />
<br />
<small>
<a class="inherit" href="admin/">admin login</a>
</small>
<p />

ENDHTML;

//experiment openings
$hco.=<<<ENDHTML
<h1>Experiment openings</h1>

ENDHTML;

//get currently opened timeslots
$openedexpts=sq_query("admin/expt.db","SELECT exptid,numsubj FROM expt WHERE openness=".sq_quote("open")."");

//get restricted timeslots
$restrictedexpts=sq_query("admin/expt.db","SELECT exptid,numsubj FROM expt WHERE openness=".sq_quote("restricted")."");
foreach($restrictedexpts as $expt){
	if (isset($webin['restrictionkey']) && trim($webin['restrictionkey'])==md5("viscoglalala".$expt['exptid'])) $openedexpts[]=$expt;
}

$openedexptstr="";
$fulledexpts=Array();
foreach($openedexpts as $expt){
	//if the experiment is not full yet, open it.
	if (sq_query_numrow("admin/timeslot.db","SELECT timeslotid FROM timeslot WHERE exptid=".sq_quote($expt['exptid'])." AND subjid NOTNULL")<$expt['numsubj']){
		$openedexptstr.="exptid=".sq_quote($expt['exptid'])." OR ";
	} else {
		$fulledexpts[]=$expt['exptid'];
	}
}
$openedts=array();
if ($openedexptstr!=""){
	$openedexptstr=substr($openedexptstr,0,strlen($openedexptstr)-3);
	$openedts=sq_query("admin/timeslot.db","SELECT timeslotid,exptid,timetag,location,duration,pay FROM timeslot WHERE timetag>".time()." AND subjid ISNULL AND ($openedexptstr) ORDER BY timetag ASC");
}
if (count($openedts)>0){
	
	$hco.=<<<ENDHTML
	<form id="tssignupform" action="index.php" method="get">
	<input type="hidden" name="module" value="signupts" />
	<table>
	<tr class="headtr"><td>Expt ID</td><td>Date/Time</td><td>Location</td><td>(min)</td><td>(HK$)</td><td> </td></tr>
	
ENDHTML;
	$hco.=buttoninit("tssignupform","tsid_num");
	$i=0;
	foreach($openedts as $ts){
		$hco.="<tr class=\"".(($i%2==0)?"eventr":"oddtr")."\"><td>{$ts['exptid']}</td><td>".date("D d/n H:i",$ts['timetag'])."</td><td>{$ts['location']}</td><td>{$ts['duration']}</td><td>{$ts['pay']}</td><td>".buttontag("tsid_num",$ts['timeslotid'],"Sign&nbsp;up")."</td></tr>";
		$i++;
	}
	$hco.=<<<ENDHTML
	</table>
	</form>
	
ENDHTML;
} else {
	// if (count($fulledexpts)>0) $hco.="The timeslots of experiment(s) ".implode(", ",$fulledexpts)." have been fully occupied.<p />";
	$hco.=<<<ENDHTML
	Thank you for visiting this website. However, we have recruited enough participants for now.<p />
	When we need more participants, we will let you know by e-mail.
	<hr />
	<p />
	Not registered yet? You can join our experiments (and earn money) by first <a href="?module=reg">registering as a participant</a> here.<p />
	<p />
	
ENDHTML;
}






templateout($hti,$hme,$hco);
