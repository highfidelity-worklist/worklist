<?php
/**
 * vim:ts=4:et
 * 
 * Copyright (c) 2013, CoffeeandPower Inc.
 * All Rights Reserved. 
 *
 * http://www.worklist.net

 * Generate JSON data for projects.php
 *
 * Development History:
 * 2011-07-30   #14907      Leo
 *
 */

require_once('config.php');
require_once('class.session_handler.php');
require_once('classes/Project.class.php');

/**
 *sort items by Bidding count, then by Total Fees
 */
function sortProjects($a, $b) { 
    if ( $b["bCount"] < $a["bCount"] ) return -1;
    if ( $b["bCount"] > $a["bCount"] ) return 1;
    if ( $b["cCount"] < $a["cCount"] ) return -1;
    if ( $b["cCount"] > $a["cCount"] ) return 1; 
    if ( $b["feesCount"] > $a["feesCount"] ) return -1;
    if ( $b["feesCount"] < $a["feesCount"] ) return 1;
     
    return 0; 
}

// Create project object
$projectHandler = new Project();

// page 1 is "all active projects"
$page = isset($_REQUEST['page']) ? (int) $_REQUEST['page'] : 1;

// for subsequent pages, which will be inactive projects, return 10 at a time
if ($page > 1) {
    // Define values for sorting a display
    $limit = 10;
    // Get listing of all inactive projects
    $projectListing = $projectHandler->getProjects(false, array(), true);

    // Create content for each page
    // Select projects that match the letter chosen and construct the array for
    // the selected page
    $pageFinish = $page * $limit;
    $pageStart = $pageFinish - ($limit - 1);

    // leaving 'letter' filter in place for the time being although the UI is not supporting it
    $letter = isset($_REQUEST["letter"]) ? trim($_REQUEST["letter"]) : "all";
    if($letter == "all") {
        $letter = ".*";
    } else if ($letter == "_") { //numbers
        $letter = "[^A-Za-z]";
    }

    // Count total number of active projects
    $activeProjectsCount = count($projectListing);

    if ($projectListing != null) {
        foreach ($projectListing as $key => $value) {
            if (preg_match("/^$letter/i", $value["name"])) {
                $selectedProjects[] = $value;
            }
        }

        // Count number of projects to display
        $projectsToDisplay = count($selectedProjects);
        // Determine total number of pages
        $displayPages = ceil($projectsToDisplay / $limit);
        // Construct json for pagination
        // $projectsOnPage = array(array($projectsToDisplay, $page, $displayPages));
        $projectsOnPage = array();

        // Select projects for current page
        $i = $pageStart - 1;
        while ($i < $pageFinish) {
            if (isset($selectedProjects[$i])) {
                $projectsOnPage[] = $selectedProjects[$i];
            }
            $i++;
        }
    }

} else {
    // Get listing of active projects
    $projectsOnPage = $projectHandler->getProjects(true);
    usort($projectsOnPage, "sortProjects");
}

// Prepare data for printing in projects.php
$json = json_encode($projectsOnPage);
echo $json;
