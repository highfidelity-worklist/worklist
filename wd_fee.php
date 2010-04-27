<?php

include("config.php");

//open db connection
$db = @mysql_connect(DB_SERVER, DB_USER, DB_PASSWORD) or die ('I cannot connect to the database because: ' . mysql_error());
$db = @mysql_select_db(DB_NAME);


$fee_id = $_GET["wd_fee_id"];

$fee_update_sql = 'UPDATE '.FEES.' SET withdrawn = \'1\' WHERE id = '.$fee_id;
$fee_update = mysql_query($fee_update_sql);

if ($fee_update) { 
    echo 'Update Successful!'; 
} else { 
    echo 'Update Failed: '.mysql_error(); 
}

?>
