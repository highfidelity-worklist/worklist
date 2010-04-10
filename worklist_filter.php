<?php
require_once 'lib/Worklist/Filter.php';
$WorklistFilter = new Worklist_Filter(array(
    Worklist_Filter::CONFIG_COOKIE_EXPIRY => (60 * 60 * 24 * 30),
    Worklist_Filter::CONFIG_COOKIE_PATH   => '/' . APP_BASE
));
?>