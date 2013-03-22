<?php
//  Copyright (c) 2010, LoveMachine Inc.  
//  All Rights Reserved.  
//  http://www.lovemachineinc.com

include("config.php");
include("class.session_handler.php");
include("functions.php");
include("classes/Project.class.php");

if (! $_REQUEST['project_id']) {
    echo json_encode(array(
        "error" => "There is no project ID."
    ));
    return;
}

$userId = getSessionUserId();

$project = new Project();
$project->loadById( mysql_real_escape_string($_REQUEST['project_id']) );
$testFlightTeamToken = $project->getTestFlightTeamToken();

if ($project->getTestFlightEnabled()) {
    if ($testFlightTeamToken == "") {
        echo json_encode(array(
            "error" => "TestFlight Team Token is empty."
        ));
    } else if ($project->isOwner($userId)) {
        $ipaFile = mysql_real_escape_string($_REQUEST['ipa_file']);
        $svnUrl = $config['websvn']['baseUrl'] . "/svn/repos/" . $project->getRepository();
    
        if ($ipaFile == "") {
            $ipaFiles = array();
            $svnMessage = array();
            exec("svn list --config-dir /tmp -R " . $svnUrl ." | grep .ipa", $ipaFiles);
            exec("svn log --config-dir /tmp -r HEAD " . $svnUrl, $svnMessage);
        
            if (count($ipaFiles) > 0) {
                echo json_encode(array(
                    "ipaFiles" => $ipaFiles,
                    "message" => $svnMessage[3]
                ));
            } else {
                echo json_encode(array(
                    "error" => "No .ipa files in repository."
                ));
            }
        } else {
            $fileName = "/tmp/" . md5( time().rand() );
            exec("svn export --config-dir /tmp " . $svnUrl . "/" . $ipaFile . " " . $fileName);
        
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_VERBOSE, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
            curl_setopt($ch, CURLOPT_URL, "http://testflightapp.com/api/builds.json");
            curl_setopt($ch, CURLOPT_POST, true);
            $post = array(
                "api_token" => TESTFLIGHT_API_TOKEN,
                "team_token" => $testFlightTeamToken,
                "file" => "@" . $fileName,
                "notes" => mysql_real_escape_string($_REQUEST['message']),
                "notify" => mysql_real_escape_string($_REQUEST['notify'])
            );
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post); 
            $out = curl_exec($ch);
        
            if (json_decode($out) != $out) {
                echo $out;
            } else {
                echo json_encode(array(
                    "error" => $out
                ));
            }
        }
    } else {
        echo json_encode(array(
            "error" => "You don't have the permissions."
        ));
    }
}

?>
