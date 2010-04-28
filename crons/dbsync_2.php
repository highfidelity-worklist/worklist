#!/usr/bin/php
<?php

//  Copyright (c) 2009-2010, LoveMachine Inc.
//  All Rights Reserved. 
//  http://www.lovemachineinc.com


/*****************************************************************************
    Syncronize the Users Table between the SENDLOVE and WORKLIST databases
    
    Goals: 
        Self-contained, can run as cronjob or by direct invocation.
        Runs as quickly as possible, my be invoked as part of user account creation.
        Two-way sync of tables, users will typically be created in the WORKLIST first, 
        but they could be created in SENDLOVE first.
        
    Reality:  
        In it's current form it actually uses very little memory and runs really fast. so, take
        the concerns about speed and memory with a grain of salt, those were preliminary guesstimates.
        
    @Caution:  dbMakeSQLValue()  only converts common fields types (text, numbers, date) that are currently  
            being used in the table.  If down the road a blob or some other exotic db field type is added  
            to the table, this will potentially cause a sync failure that may be hard to identify.
            
                    
    Assumptions:
		Any fields with the same name are assumed to contain the same value.  
		e.g.  username in one table is the same as username in the other table 
		(as opposed to username having a totally different meaning).
        
        We have enough memory to hold both table indexes, this provides a huge simplification 
        of the code. I know from experience that php has no problem with million row arrays
        This approach will work fine as long as volumes are moderate -- if this is a 
        facebook scale app then a different approach is needed. 

        The simplifying assumption is that Both Databases are on the same db server
        this makes a big difference to performance and substantially reduces code complexity
        
 
    Limitations:
        The tables turned out to be quite different from each other, we have no way of 
        knowing what the missing values are, we can only copy the values that we have.
        So, the copied records will be incomplete.  unknown fields are set to empty/zero/null
            
       
        
        
    Depends on:  
        db.ini  which defines the connection info and table names for the databases
        a seperate ini was created because the multiple config.php files are in conflict
        with each other.  Also this file contains passwords and should be treated accordingly.  
        I'd suggest that the config.php files ought to pull their info from db.ini, that way 
        it can all be in one place. db.ini contains info that is common to all products, 
        whereas config.php is product specific.
        
    
    Requires: 
        The tables being sync'ed contain the following fields (id, username)
        
        Ability to save a state file in a cache path that is writable by the webserver.
        Nothing confidential or executable is stored in this file
        
    
    Outputs: to an Error Log (/tmp/php.error.log) if any sync problems are detected
    

    Notes:
        To do a full resync can take quite awhile depending upon how many users there are,
        but after that, this runs quickly because it remembers the last id checked.  If
        the state file gets deleted we have to do a full sync.
        
        
        To manually force a full resync, run this program from the shell and specify   
            php -f dbsync.php -- --fullsync
        
        
    
    History:
    07-APR-2010 Created By: codehappy  eriksjobs@emerald-forest.net
    
    11-APR-2010 Made several changes per discussion with Garth....  major rework to match
                on username instead of id.  to gracefully ignore duplicate records
                to allow copying between any two database servers/accounts
                added a bunch of commnad line options to aid debugging
                
                Only sync from the Worklist into Sendlove but not the other way around
                This is due to problematic test accounts in Sendlove.
    
******************************************************************************/

$bEnableDebug = true;   //the debug options can reveal sensitive data, this should be false in production
$msgOptionsAre = ($bEnableDebug) ? 'Enabled' : '@ Disabled @';

$version = '2.0';

$help =<<<TEXT
  
dbsync -- synchronize user table, copies User records from WorkList to SendLove
version $version
  


  -f or --fullsync    forces a full sync of the data instead of just the most recent changes
  
  --help
  
  
  --------------------------------------------
  These are to aid debugging:    Debug Options Are $msgOptionsAre
  
  -p        --pretend  don't actually copy the records
  -r        --records  show the list of records to be copied
  -s        --sql      show the sql that will do the copy
  -h        --host     Force use of the copy method that works when the db is on different servers
  
  -i (filename)   specify an ini file to use instead of db.ini
    
  options must be separated by spaces, they can not be combined
\n\n\n
TEXT;
  

//&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&
//&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&


error_reporting(E_ALL | E_STRICT);
set_time_limit(15*60);
date_default_timezone_set('UTC');


//this ought to be enough to handle a huge number of records
//with ~400 records we only used 0.5 megs of peak memory
ini_set('memory_limit', '100M');  

//&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&
//&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&


//===============================================================================
//parse the command line options, if any
//Note: this is a very simple minded parser each option must be separated by spaces, not combined

$CmdIni = $bGrabFile = $bForceSync = $bPretend = $bDebShowCopy = $bShowSQL = false;
 
if (empty($_SERVER['argv'])) {$_SERVER['argv'] = array();}

foreach($_SERVER['argv'] as $flag)
{
	if ($bGrabFile)
	{
		$CmdIni = $flag;  //grabs the filepath
		$bGrabFile = false;
		continue;
	}
	
	$flag = strtolower(trim($flag));
    
	if ($flag === '--help') 
	{
		echo $help;
		exit;
	}
	
	if ($flag === '-f' || $flag === '--fullsync') {$bForceSync = true;}
    
    if ($bEnableDebug)
    {
        if ($flag === '-p' || $flag === '--pretend') {$bPretend = true;}     //don't actually copy the records
    	if ($flag === '-r' || $flag === '--records') {$bDebShowCopy = true;} //show the list of records to be copied   
        if ($flag === '-s' || $flag === '--sql') {$bShowSQL = true;}         //show the sql that will do the copy
        if ($flag === '-h' || $flag === '--host') {$bForceHardCopy = true;}  //see CopyHard()
        
        if ($flag === '-i' || $flag === '--ini') {$bGrabFile = true;}  //specify an ini file
    }
}


//===============================================================================

//Security: The ini Contains Confidential Data

define('kCodeBasePath', dirname(__FILE__));

//The logical place for the config file is /etc/lvm  but we also check the program's path 
//and for a debug override in the parent folder (out of the svn tree)
is_file($PathToIni = $CmdIni)  
  || is_file($PathToIni = dirname(kCodeBasePath)."/$CmdIni")  
  || is_file($PathToIni = dirname(kCodeBasePath).'/db_debug.ini')  
  || is_file($PathToIni = '/etc/lvm/db.ini')
  || is_file($PathToIni = kCodeBasePath.'/db.ini');  

  
if (!is_file($PathToIni) || !is_readable($PathToIni))
{
    //this should never happen
    trigger_error("The product has not been installed properly - Can't find the config file", E_USER_ERROR);
}

$settings = parse_ini_file($PathToIni, true);

  
//&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&

$PathToErrorLog = $settings['dbsync']['ErrorLog'];
$PathToErrorLog = str_replace('kCodeBasePath', kCodeBasePath, $PathToErrorLog);  


//this path MUST BE WRITEABLE by the webserver if invoked as part of account creation
//it would be good if this file persisted after a reboot

$PathToStateFile = $settings['dbsync']['dbSyncStateFile'];
$PathToStateFile = str_replace('kCodeBasePath', kCodeBasePath, $PathToStateFile);  

if (!is_writeable(dirname($PathToStateFile)))
{
    trigger_error("The product has not been installed properly - Can't write to '$PathToStateFile'", E_USER_ERROR);
}

//=============================
//read previous state from disk, skip if fullsync option requested

if (!$bForceSync && is_readable($PathToStateFile))
{
    $state = file($PathToStateFile, FILE_IGNORE_NEW_LINES);
}

if (empty($state) || !is_array($state) || count($state) < 2)  //one line header + value
{
	//init an empty state -- forces a full resync
	$state = array('State File for dbsync.php -- this file is automatically generated do not modify', '0');
}

//&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&
//&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&

//ppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppp
//
// Inputs: an array containing host, password etc (see db.ini)
//         values should be trusted
//
//    DB_SERVER = localhost
//    DB_USER = name
//    DB_PASSWORD =  pass
//
//
// Returns: handle to database server

function dbConnect($aInfo, $bIgnoreErr = false)
{
    $host = (empty($aInfo['DB_SERVER'])) ? 'localhost' : $aInfo['DB_SERVER'];
    $host .= (empty($aInfo['DB_PORT'])) ? '' : ':'.$aInfo['DB_PORT'];
    
    $hsvr = mysql_connect($host, $aInfo['DB_USER'], $aInfo['DB_PASSWORD']);

    if (empty($hsvr) && !$bIgnoreErr)
    {
        trigger_error("Failed to connect to '$host' for User '{$aInfo['DB_USER']}'", E_USER_ERROR);
    }
    
    return $hsvr;
}


//ppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppp
//
//Gets the list of fields in a table
//
//looks something like this
//
//	Array(
//	   [Field] => id
//	   [Type] => int(7)
//	   [Null] =>  
//	   [Key] => PRI
//	   [Default] =>
//	   [Extra] => auto_increment
//	)
//	Array
//	(
//	   [Field] => somename
//	   [Type] => varchar(100)
//	   [Null] =>
//	   [Key] =>
//	   [Default] =>
//	   [Extra] =>
//	)

function dbGetFields($hsvr, $Table)
{
	$hres = mysql_query("SHOW COLUMNS FROM $Table", $hsvr);
	if (empty($hres))
    {
    	trigger_error("Failed to fetch fields from $Table", E_USER_ERROR);
    }
	
	$fields = array();
	while ($row = mysql_fetch_assoc($hres)) 
	{
		$fields[$row['Field']] = $row;
	}	
	
	mysql_free_result($hres);
	return $fields;
}


//ppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppp
//
// For fields that lack a defined default value
// and for which the source table has no value
// we have to make up something...
//
// in general, we go for the empty value for that data type
// but we also are told the table name so that potentially we could
// provide a specific value
//
// Inputs:
//  
//  $field is a single item as returned from dbGetFields
//  $sqlTable1 is the full table specifier
//
// Returns: a single value properly formatted for embedding in a sql statement
//
// -------------------
//  $field = Array
//	  (
//	     [Field] => somename
//	     [Type] => varchar(100)
//	     [Null] => YES
//	     [Key] =>
//	     [Default] =>
//	     [Extra] =>
//	  )


function dbMakeDefault($field, $sqlTable1)
{
    //basically we simplify to 3 cases
    //if it allows null we return sql(null) === text which says null
	//if it's text we return an empty string
    //for eveything else we return 0
    
	
	if (strtoupper($field['Null']) === 'YES') 
	{
		$out = 'NULL';
	}
	elseif (stripos($field['Type'], 'char') !== false || stripos($field['Type'], 'text') !== false
//	        || stripos($field['Type'], 'ASCII') !== false || stripos($field['Type'], 'UNICODE') !== false
	       )
	{
		$out = "''";
	} 
	else
	{
		$out = 0;
	}
	
	return $out;
}


//ppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppp
//
// Convert a value into something that is ready to insert via sql
//
//  we have to convert the values that we read from one table into values that can be
//  written into another table.  this is actually a very tricky business, there are a 
//  lot of different data types and many of them require special formatting...  
//  a full blown converter that takes all of this into account is pretty big and complicated.
//  
//  however, since this program is (currently) intended for one specific purpose
//  I am going to make some major simplyfying assumptions.
//
//  1) We basically trust this data.  It was read from a table so it must be safe to 
//      put it back into a table
//
//  2) After viewing the schemas for the tables, I see that they only use a few basic data types
//      nothing exotic.  So I am going to skip support for types that we don't actually need.
//      such as blobs and sets which are a pain to deal with.
//      so this program won't support them....   for now....  :-)
//
// @Caution: if down the road the table schema is changed and a new data type is added such as
//           a blob, then the sync will fail until this function is updated to handle that data type
//
//
// @Security: We trust the values to not be malicious.  It is assumed that they are
//            being read from a table and thus have already been sanitized
//            if you want to repurpose this code, make certain you review this
//
//  Assumption: the value is already unescaped
//
//  The point of providing the table name is so that we could do selective remapping of fields
//  but currently this is not used.
 
function dbMakeSQLValue($v, $field, $sqlTable = false)
{
    
	$t = strtolower(trim($field['Type']));
	
    if (is_null($v) && strtoupper($field['Null']) === 'YES') 
    {   
        return 'NULL';  //only return null if the field itself allows that otherwise deal with below
    }
    
    if ($v === false) {return 0;}
    if ($v === true) {return 1;}
    
    
    if (strpos($t, 'char') !== false || strpos($t, 'text') !== false)
    {
        return "'".mysql_escape_string($v)."'"; //ordinary and large text -- with quotes
    } 

    if ($t === 'datetime')
    {
    	return "'".mysql_escape_string($v)."'";  //happily we don't have to reformat it we just have to stringify it  
    }    
    
    //===================================
    //otherwise it's a number
    //at this point we could decide if it's a float or not... and force it's type
    //but since there are a lot of different number types (BCD is problematic)
    //and since we trust the value to not be malicious
    //we are going to leave it as is.
        
    return (is_null($v) || $v === false) ? 0 : $v;
}

//ppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppp
//
// Inputs: 
//      an array of zero or more rows
//      an array of fields  (we need the types)
//      an optional Name of the $sqlTable...  in future this could be used for remapping specific fields
//
//  Output:  a sql string of value statements  (a1, b1, c1), (a2, b2, c2)  etc.

function dbMakeSQLValues($aRows, $fields, $sqlTable = false)
{
    if (empty($aRows)) {return '';}
    
    $arr = array();
    
    foreach($aRows as $row)
    {
    	if (empty($row)) {continue;}
    	
    	$arr[] = "(";
    	foreach($row as $idx=>$v)
    	{
    		$arr[] = dbMakeSQLValue($v, $fields[$idx], $sqlTable);
    		$arr[] = ', ';
    	}
    	array_pop($arr); //drop the last comma
    	$arr[] = ')';
    	$arr[] = ",\n";
    }
    array_pop($arr); //drop the last comma

    return implode('', $arr);
}

//ppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppp
//
//  Map the fields between two tables
//
//  It turns out that the tables being synced are quite different from each other
//
//  @Review: Simplifying Assumption:  Any fields with the same name 
//  are assumed to contain the same value.  e.g.  username in one table is the same 
//  as username in the other table (as opposed to username having a totally different meaning)
//  (optionally we could verify that the field Type matches as well, but for this goal in which we
//  already know that the tables are compatible, that seems like wasted cycles) 
// 
//  This gets pretty hariy because we also have to provide default values without being 
//  able to know what they are.  So we set them to zero or empty.
//
//  We are effectivly doing a merge of Table2 into Table1,  so Table1 is the target...

function dbMapFields($hsvr1, $sqlTable1, $hsvr2, $sqlTable2)
{

	$af1 = dbGetFields($hsvr1, $sqlTable1);  //array of fields
    $af2 = dbGetFields($hsvr2, $sqlTable2);
    
    $afc = $afdef = array();
    
    foreach($af1 as $idx=>$field)
    {
    	
    	if (!empty($af2[$idx])) 
    	{
    		$afc[$idx] = $field;  //fields in common
    	}
    	else
    	{
    	   if (empty($field['Default']))	
    	   {
    	   	   //the field lacks a default so we have to create one for it
    	   	   //otherwise we ignore the field altogether and mysql will provide the default
    	       $afdef[$idx] = dbMakeDefault($field, $sqlTable1);
    	   }
    	}
    }
    

    //now we create the sql field lists
    //assumes that the tables have at least one field in common
    
    //$out is the list of fields to be inserted
    //$in is the list of fields and constants to select
    
    $out = $in = implode('`, `', array_keys($afc)); 
    $out .= '`, `'. implode('`, `', array_keys($afdef));
    $out = '`'.trim($out, ', `').'`';

    $in = '`'.trim($in, ', `').'`';
    
    foreach($afdef as $idx=>$v)
    {
        $in .= ", $v as `$idx`";  //create default selects for each missing field
    }
    
    
    return array('out'=>$out, 'in'=>$in, 'common'=>$afc, 'default'=>$afdef, 'tgtFields'=>$af1);
}



//ppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppp
//    copy the records from one Host table to the other
//    
//    The Databases or user accounts are different, so we have to copy each row 
//    field by field and deal with data formats etc.  (slow and complicated).

function CopyHard($aCopyMe, $hsvr1, $sqlTable1, $hsvr2, $sqlTable2)
{    
    global $PathToErrorLog, $bPretend, $bDebShowCopy, $bShowSQL;
    
    $bSuccess = true;  
    $afields = dbMapFields($hsvr1, $sqlTable1, $hsvr2, $sqlTable2);

    //id is an autoinc so we must force it to zero, we could do this in remap but it seems cleaner here
    $fieldsIN = str_replace('`id`', '0 as `id`', $afields['in']);  

    
    //-------------------------------    
    //we can use "select IGNORE " to supress the duplicate record error but that does not solve the problem
    //better to have it alert us about the skipped records

    
$sqlHeadIN =<<<SQL
select 
{$fieldsIN} 

from $sqlTable2
where 

SQL;

    //-------------------------------    


$sqlHeadOUT =<<<SQL
insert  into $sqlTable1 
({$afields['out']}) 

VALUES

SQL;

    // we are going to do this in batches to minimize potential problems
    $RowsPerBatch = 50;  
    
    while(!empty($aCopyMe))
    {
        $sql = '';
        $rows = array();
        
        for($i=0; $i < $RowsPerBatch; $i++)
        {
            if (empty($aCopyMe)) {break;}
            
            $item = array_pop($aCopyMe);
            $id = $item['id'];
            
            $sql .= (($i) ? ' or ':'') . "id = '$id'";
        }
        
        if (!empty($bShowSQL)) {echo "Fetch = \n$sqlHeadIN$sql\n\n";}  //@@@DEBUGONLY

        $hres = mysql_query("$sqlHeadIN$sql", $hsvr2);
        if (empty($hres))
        {
            file_put_contents($PathToErrorLog, "\ndbsync Failed while fetching records -- ". mysql_error()."\n$sqlHeadIN$sql\n", FILE_APPEND);
            $bSuccess = false;
            continue;
        }
        
        //====================================================
        //read each row and convert to sql VALUES
        
        while($rows[] = mysql_fetch_assoc($hres)){};
        mysql_free_result($hres);
        
        $Values = dbMakeSQLValues($rows, $afields['tgtFields'], $sqlTable2);
        
        //====================================================
        //write the records

        $request = "$sqlHeadOUT$Values";
        
        if (!empty($bShowSQL)) {echo "OUT = \n$request\n\n";}
        if (!empty($bPretend)) {echo "\nPretend requested Skipping Write\n"; continue;}

        $bRes = mysql_query("$request", $hsvr1);
        if (empty($bRes))
        {
            file_put_contents($PathToErrorLog, "\ndbsync Failed to write records -- ". mysql_error()."\n$request\n", FILE_APPEND);
            $bSuccess = false;
            continue;
        }

    }
    
    return $bSuccess;
    
}


//ppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppp
//    copy the records from one table to the other
//    
//    Both Databases are on the same db server and account
//    so that we can do a ~simple~  select/insert   otherwise see CopyHard()

function CopyEasy($aCopyMe, $hsvr1, $sqlTable1, $hsvr2, $sqlTable2)
{    
    global $PathToErrorLog, $bPretend, $bDebShowCopy, $bShowSQL;

    $bSuccess = true;  
    $afields = dbMapFields($hsvr1, $sqlTable1, $hsvr2, $sqlTable2);

    //id is an autoinc so we must force it to zero, we could do this in remap but it seems cleaner here
    $fieldsIN = str_replace('`id`', '0 as `id`', $afields['in']);  

    
	//we can use "select IGNORE " to supress the duplicate record error but that does not solve the problem
	//better to have it alert us about the skipped records

$sqlHead =<<<SQL
insert  into $sqlTable1 
({$afields['out']}) 

select 
{$fieldsIN} 

from $sqlTable2
where 

SQL;

    // we are going to do this in batches to minimize potential problems
    $RowsPerBatch = 100;  
    
    while(!empty($aCopyMe))
    {
        $sql = '';
        
        for($i=0; $i < $RowsPerBatch; $i++)
        {
            if (empty($aCopyMe)) {break;}
            
            $item = array_pop($aCopyMe);
            $id = $item['id'];
            
            $sql .= (($i) ? ' or ':'') . "id = '$id'";
        }
        
	    if (!empty($bShowSQL)) {echo "$sqlHead$sql\n\n";}  //@@@DEBUGONLY
        if (!empty($bPretend)) {echo "\nPretend requested Skipping Write\n"; continue;}
        
        $bRes = mysql_query("$sqlHead$sql", $hsvr1);
        if ($bRes !== true)
        {
          file_put_contents($PathToErrorLog, "\ndbsync Failed while copying records -- ". mysql_error()."$sqlHead$sql\n", FILE_APPEND);
          $bSuccess = false;
        }
    }
    
    return $bSuccess;
        
}


//ppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppppp
//
//  Goal: 
//		Compares records in table2 to records in table1
//		Copies any records from table2 into table1 if they do not already exist
//      silently ignores duplicate records, uses latest version.
//
//  We are doing a merge of Table2 into Table1,  so Table1 is the target...
//
//  
//  Inputs: 
//      Credentials for database server login and the table names to use
//      The id at which to begin checking for new records
//      assumes that id always increments (newer records id > older record id)
//
//
//  Returns:
//      Success == true  and  the $lastId gets set to current value
//      Error == false
//
//
//  11-APR-2010: The big change here is that we are going to match records by user name only
//  But we still use id # as a filter to decide what records to look at
//  the orginal concept was to use date but that gets ugly with unreliable clocks, DST, etc.
//
//  The requirement is that newer records always have a higher id # than older records 
//  (which is true for mysql autoinc, but may not be true if this is ported to another db)
//
//  The other big change is that we now support the source and destination to be totally
//  separate from each other (different accounts/computers/continents ;-)

function Sync2Tables($aHost1, $table1, $aHost2, $table2, &$lastId)
{
	global $PathToErrorLog, $bForceHardCopy, $bPretend, $bDebShowCopy, $bShowSQL;
    
    $bSuccess = true;  
	$hsvr1 = dbConnect($aHost1);
	$hsvr2 = dbConnect($aHost2);
	
	//to guard against race conditions we always recheck the previous n entries
    $lastId = max((int)$lastId - 2, 0);


    $sqlTable1 = "`{$aHost1['DB_NAME']}`.`$table1`";
    $sqlTable2 = "`{$aHost2['DB_NAME']}`.`$table2`";
    

	//11-apr-2010: this is upsidedown from the previous approach, it's a lot more overhead and going 
	//to be slower, but on the other hand, this scales really well to large numbers of records
	//and typically we only need to do a few loops so the speed is not going to be a problem
    //
    //What we do is get the list of records from table 2, that need to be synced
    //and then we do a lookup (in table 1) for each record to see if it already exists.
    //if it does we are done, otherwise we copy it from table 2 to table 1
    
$sql = <<<SQL
select id, username from $sqlTable2
where id >= '$lastId'
order by username, id

SQL;
    
    $hr2 = mysql_query($sql, $hsvr2);
    if (empty($hr2))
    {
    	file_put_contents($PathToErrorLog, 
            "\ndbsync Failed when requesting records from $sqlTable2 -- ". mysql_error()
    	   , FILE_APPEND
        );

	    mysql_close($hsvr1);
	    mysql_close($hsvr2);
        return false;
    }
    
    //=====================================================
    //now we check each row in table 2 against table 1
    //and create a list of the rows that need to be copied
    //
    //one way we could optimize the speed is by batching these lookup requests
    //but that would add substantial complexity for a probably ~small~ gain
    
    $aCopyMe = array();
    
    while ($row2 = mysql_fetch_assoc($hr2)) 
    {

    	//=====================================================================
    	//filter for account names to be ignored goes here....
    	//we could also add other fields to be filtered, just need to modify the select, above
    	
    	if (empty($row2['username']) || strpos($row2['username'], '@') === false) {continue;}

    	
        //=====================================================================
    	//test for existance and skip it if it does
    	
    	$match = mysql_escape_string($row2['username']);
    	
$sql = <<<SQL
select count(*) from $sqlTable1
where username = '$match'

SQL;

	    $hr1 = mysql_query($sql, $hsvr1);
	    if (empty($hr1))
	    {
	        file_put_contents($PathToErrorLog, 
	            "\ndbsync Failed when requesting records ({$row2['username']}) from $sqlTable1 -- ". mysql_error()
	            , FILE_APPEND
	        );
	
	        mysql_free_result($hr1);
	        mysql_free_result($hr2);
	        mysql_close($hsvr1);
	        mysql_close($hsvr2);
	        return false;
	    }

	    //-------------------
        //Note: we now silently ignore/skip duplicate records, previously we warned about it
	    
	    $aMatchCnt = mysql_fetch_row($hr1); mysql_free_result($hr1);
	    
    	if (!empty($aMatchCnt[0]))
    	{
			//(matched) nothing to see here, move along...
    	}
    	else
    	{
    		//in the case of dups we only keep the last/most recent record
    		$aCopyMe[$row2['username']] = $row2; //(id, username) of record to be copied from table2
    	}
    	
    	
		//keep latest id that we checked -- this is the point we start from when next we check for sync
        if ($row2['id'] > $lastId) {$lastId = $row2['id'];}
		
    }
    
    mysql_free_result($hr2);
    

    if (!empty($bDebShowCopy)) 
    {
        echo "CopyMe = "; print_r($aCopyMe); echo "Count = ".count($aCopyMe)."\n\n";  //@@@DEBUGONLY
    }
    
    
    if (!$bForceHardCopy && $aHost1['DB_SERVER'] === $aHost2['DB_SERVER'] && $aHost1['DB_USER'] === $aHost2['DB_USER'])
    {
        //when account is the same we can do a fast and easy copy
    	$bSuccess = CopyEasy($aCopyMe, $hsvr1, $sqlTable1, $hsvr2, $sqlTable2);
    }
    else
    {
    	//db servers are not related, must do a field by field copy, this is slow and complex
        $bSuccess = CopyHard($aCopyMe, $hsvr1, $sqlTable1, $hsvr2, $sqlTable2);
    }
    
     
    mysql_close($hsvr1);
    mysql_close($hsvr2);

    return $bSuccess;
}


//&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&
//&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&



$wl_host  = $settings['worklist'];
$wl_table = $settings['worklist.tables']['USERS'];
$sl_host  = $settings['sendlove'];
$sl_table = $settings['sendlove.tables']['USERS'];

$lastId  = (int)$state[1];  //Sanitize: the config file is only semi-trusted


//first sync WorkList into SendLove
$bSuccess = Sync2Tables($sl_host, $sl_table, $wl_host, $wl_table, $lastId);

//11-apr-2010  do not do a reverse sync
//	if ($bSuccess) 
//	{
//		//now sync SendLove into WorkList
//		$lastId  = (int)$state[1];  //must reset
//		$bSuccess = Sync2Tables($wl_host, $wl_table, $sl_host, $sl_table, $lastId);
//	}


if ($bSuccess)
{
	//save the id that we last processed
	$state[1] = $lastId;
	chmod($PathToStateFile, 0755);
    file_put_contents($PathToStateFile, implode("\n", $state));
}


//////////////////////////////////////////////////////////
//this section is for debug/review only
//
//	//print_r($settings);
//	echo "Latest State = "; print_r($state);
//	//print_r($_SERVER['argv']);
//	//var_dump($bForceSync);
//	
//	echo "Peak Memory = ".memory_get_peak_usage(true), "\n";
//	echo "Result of Sync = ";var_dump($bSuccess);
//
//////////////////////////////////////////////////////////


//output a status suitable for logging

echo "dbsync: status = ".($bSuccess ? 'Success' : 'Error - check the log'). '  at '.gmdate('d-M-Y H:i:s T')."\n";

?>