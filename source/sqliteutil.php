<?php
// project: expsignup/sqliteutil
// by: louis
// on: 21jul2005
// last modify: 18apr2006, 13jun2006
// version: 2.1
// script-dependency: none
// server-dependency: sqlite or dl
// db-dependency: 


if (!extension_loaded('sqlite')) {
	dl('./sqlite_'.strtolower(PHP_OS).'.so');
}

$sq_openeddbhandles=array();

function sq_open($db){
	global $sq_openeddbhandles;
	if (!isset($sq_openeddbhandles[$db])) $sq_openeddbhandles[$db]=sqlite_open($db);
	return;
}

function sq_close($db){
	global $sq_openeddbhandles;
    if (isset($sq_openeddbhandles[$db])) sqlite_close($sq_openeddbhandles[$db]);
    unset($sq_openeddbhandles[$db]);
    return;
}

function sqi_open($db){
	global $sq_openeddbhandles;
	if (array_key_exists($db,$sq_openeddbhandles)){
		return $sq_openeddbhandles[$db];
	} else {
		return sqlite_open($db);
	}
}

function sqi_close($sq_db){
	global $sq_openeddbhandles;
	if (!in_array($sq_db,$sq_openeddbhandles)) sqlite_close($sq_db);
}

function sq_query($db,$query){
    $sq_db=sqi_open($db);
    $return=sqlite_array_query($sq_db,$query);
    sqi_close($sq_db);
    return $return;
}

function sq_query_val_array($db,$query){
    $sq_db=sqi_open($db);
    $sq_result=array();
    $tmp_result=true;
    $handle=sqlite_query($sq_db,$query);
    while ($tmp_result){
	    $tmp_result=sqlite_fetch_array($handle);
	    if ($tmp_result) $sq_result[]=$tmp_result[0];
	}
    sqi_close($sq_db);
    
    return $sq_result;
}

function sq_query_val($db,$query){
    $sq_db=sqi_open($db);
    $sq_result=sqlite_fetch_array(sqlite_query($sq_db,$query));
    sqi_close($sq_db);
    
    //$sq_result WILL be false if no match. also the sqlite_fetch_array WON'T return error.
    if ($sq_result) return $sq_result[0];
    
    return $sq_result;
}

function sq_query_pair_array($db,$query){
    $sq_db=sqi_open($db);
    $sq_result=array();
    $tmp_result=true;
    $handle=sqlite_query($sq_db,$query);
    while ($tmp_result){
	    $tmp_result=sqlite_fetch_array($handle);
	    if ($tmp_result) $sq_result[$tmp_result[0]]=$tmp_result[1];
	}
    sqi_close($sq_db);
    
    return $sq_result;
}

function sq_query_row($db,$query){
    $sq_db=sqi_open($db);
    $sq_result=sqlite_fetch_array(sqlite_query($sq_db,$query));
    sqi_close($sq_db);
    
    //$sq_result WILL be false if no match. also the sqlite_fetch_array WON'T return error.
    if ($sq_result) return $sq_result;
    
    return $sq_result;
}

function sq_query_numrow($db,$query){
    $sq_db=sqi_open($db);
    $sq_result=sqlite_num_rows(sqlite_query($sq_db,$query));
    sqi_close($sq_db);
    
    //$sq_result WILL be false if no match. also the sqlite_fetch_array WON'T return error.
    if ($sq_result) return $sq_result;
    
    return $sq_result;
}

function sq_query_safe($db,$query){
    $sq_db=sqi_open($db);
    $sq_result=@sqlite_query($sq_db,$query);
//    $sq_result=sqlite_query($sq_db,$query);
//	$sq_result=false;
    sqi_close($sq_db);
    
    return $sq_result;
}

function sq_quote($instr){
    return "'".sqlite_escape_string($instr)."'";
}

function sq_encode(&$instr){
    //compress $instr into sq query compatible form
    // first compress, then do base64encode.
//    return base64_encode(bzcompress($instr)); //not include compress for diary search support.
    return base64_encode($instr);
}

function sq_decode(&$instr){
    //decode sq_encode
//    return bzdecompress(base64_decode($instr));
    return base64_decode($instr);
}

function sq_implode($join,$pre,$arrayi){
	$outstrarr="";
	foreach ($arrayi as $tmpi){
		$outstrarr[]=$pre."=".sq_quote($tmpi);
	}
	return implode($join,$outstrarr);
}

1;

?>
