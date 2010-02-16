<?php
//  Copyright (c) 2009, LoveMachine Inc.
//  All Rights Reserved. 
//  http://www.lovemachineinc.com

/**
 * File:            $Id$
 *
 * @lastrevision    $Date$
 * @modifiedby      $LastChangedBy$
 * @lastmodified    $LastChangedDate$
 */

// AJAX request from ourselves to retrieve history

include("config.php");
include("functions.php");
if (!checkReferer()) die;

include("smslist.php");

if (empty($_POST['c'])) {
    die;
}


$provlist = array();
foreach ($smslist[$_POST['c']] as $prov=>$fmt)
{
    $provlist[] = array($prov, $fmt);
}

$json = json_encode($provlist);
echo $json;

?>
