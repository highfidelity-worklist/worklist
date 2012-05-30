<?php
include("config.php");
include("class.session_handler.php");
include("functions.php");

$query = "SELECT id, code_reviewer_id, code_review_started, code_review_completed FROM " . WORKLIST . " WHERE id = '" . $_REQUEST['workitemid'] . "'" ;
	
$result = mysql_query($query);
	
$data = array();
while ($result && $row=mysql_fetch_assoc($result)) {
    $data[] = $row;
}
	
echo json_encode($data);
?>