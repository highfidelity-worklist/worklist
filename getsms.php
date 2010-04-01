<?php
//  Copyright (c) 2009-2010, LoveMachine Inc.
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
//if (!checkReferer()) die;

include("smslist.php");
require_once 'lib/Sms.php';

if (empty($_POST['c'])) {
    die;
}

$provlist = Sms_Backend_Email::getProviders($_POST['c']);
$json = json_encode($provlist);
echo $json;

?>
