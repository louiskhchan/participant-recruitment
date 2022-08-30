<?php

ini_set("max_execution_time","600");

include_once("sqliteutil.php");

function dieout($errstr){
	echo "oops\n";
	exit(0);
	return;
}
//read the entiredb

$dbs=array("subj");
foreach($dbs as $dbi){
	if (file_exists("admin_new/".$dbi.".db")) unlink("admin_new/".$dbi.".db");
}

//create new db
//create necessary files

//subjdb
if (!sq_query_val("admin_new/subj.db","SELECT name FROM sqlite_master WHERE type='table' AND name='subj'")){
    if (!sq_query_safe("admin_new/subj.db","CREATE TABLE subj(subjid INTEGER PRIMARY KEY,subjuid,password,subjname,email,phone,sex,race,role,subscription DEFAULT 'Y',validuntil DEFAULT -1,lastsenttime DEFAULT 0,notes)")) dieout("can't create subj table");
    if (!sq_query_safe("admin_new/subj.db","CREATE INDEX subjindex1 ON subj(subjid,subjuid,email,race,role,subscription,lastsenttime)")) dieout("can't create subj index");
}

foreach($dbs as $dbname){
	echo "processing ".$dbname."\n<br>";
	//put db
	$sq_db=sqlite_open("admin/".$dbname.".db");
	$curdb=sqlite_array_query($sq_db,"SELECT * FROM ".$dbname);
	sqlite_close($sq_db);

	$sq_db=sqlite_open("admin_new/".$dbname.".db");

	$batchinsertstr="BEGIN;";
	foreach ($curdb as $entry){
		$keyarr=array();
		foreach ($entry as $key=>$val){
			if (is_int($key)) continue;
			if (is_null($val)) continue;
			$keyarr[]=$key;
		}
		$valarr=array();
		foreach ($entry as $key=>$val){
			if (is_int($key)) continue;
			if (is_null($val)) continue;
			$valarr[]=is_int($val)?$val:sq_quote($val);
		}
		//add default value for new column
		if ($dbname=="expt" && !in_array("sexarr",$keyarr)){
			$keyarr[]="sexarr";
			$valarr[]=sq_quote(serialize(array("M","F")));
		}
		$insertstr="INSERT INTO ".$dbname." (";
		$insertstr.=implode(",",$keyarr);
		$insertstr.=") VALUES (";
		$insertstr.=implode(",",$valarr);
		$insertstr.=");";
		$batchinsertstr.=$insertstr;
		echo ".";
	}
	sqlite_exec($sq_db,$batchinsertstr);
	sqlite_exec($sq_db,"COMMIT;");
	sqlite_close($sq_db);
	
}


?>
