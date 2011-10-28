<?php
require_once('../config.php');
require_once('../models/DataObject.php');
require_once('../models/Timeline.php');
$timeline = new Timeline();
if ($_POST["action"] == "getHistoricalData") {
    if (isset($_POST["project"])) {
        $project = $_POST["project"];
    }
    if ($project) {
        $objectData = $timeline->getHistoricalData($project);
    } else {
        $objectData = $timeline->getHistoricalData();
    }
    echo json_encode($objectData);
} else if ($_POST["action"] == "getDistinctLocations") {
    $objectData = $timeline->getDistinctLocations();
    echo json_encode($objectData);
} else if ($_POST["action"] == "storeLatLong") {
    $location = $_POST["location"];
    $latlong = $_POST["latlong"];
    $timeline->insertLocationData($location, $latlong);
} else if ($_REQUEST["action"] == "getLatLong") {
    $objectData = $timeline->getLocationData();
    echo json_encode($objectData);
} else if ($_POST["action"] == "getListOfMonths"){
    $months = $timeline->getListOfMonths();
    echo json_encode($months);
}
?>