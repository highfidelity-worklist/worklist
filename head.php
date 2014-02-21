<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="copyright" content="Copyright (c) 2014 HighFidelity inc. All Rights Reserved. http://highfidelity.io" />

    <link rel="shortcut icon" type="image/x-icon" href="images/worklist_favicon.png" />

    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="css/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/jquery/jquery.combobox.css">
    <link rel="stylesheet" href="css/jquery/jquery-ui.css">
    <link rel="stylesheet" href="css/tooltip.css" />
    <link rel="stylesheet" href="css/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="css/common.css">
    <link rel="stylesheet" href="css/menu.css">

    <script type="text/javascript">
        var user_id = <?php echo isset($_SESSION['userid']) ? $_SESSION['userid'] : 0; ?>;
        var worklistUrl = '<?php echo SERVER_URL; ?>';
        var sessionusername = '<?php echo $_SESSION['username']; ?>';
        var ajaxRefresh = <?php echo AJAX_REFRESH * 1000; ?>;
    </script>
    <script type="text/javascript" src="js/jquery/jquery-1.7.1.min.js"></script>
    <script type="text/javascript" src="js/jquery/jquery.class.js"></script>
    <script type="text/javascript" src="js/jquery/jquery-ui-1.8.12.min.js"></script>
    <script type="text/javascript" src="js/jquery/jquery.watermark.min.js"></script>
    <script type="text/javascript" src="js/jquery/jquery.livevalidation.js"></script>
    <script type="text/javascript" src="js/jquery/jquery.scrollTo-min.js"></script>
    <script type="text/javascript" src="js/jquery/jquery.combobox.js"></script>
    <script type="text/javascript" src="js/jquery/jquery.autogrow.js"></script>
    <script type="text/javascript" src="js/bootstrap/bootstrap.min.js"></script>
    <script type="text/javascript" src="js/lightbox-hc.js"></script>
    <script type="text/javascript" src="js/common.js"></script>
    <script type="text/javascript" src="js/jquery/jquery.tooltip.min.js"></script>
    <script type="text/javascript" src="js/utils.js"></script>
    <script type="text/javascript" src="js/userstats.js"></script>
    <script type="text/javascript" src="js/worklist.js"></script>
    <script type="text/javascript" src="js/budget.js"></script>    


    <meta name="viewport" content="width=device-width, initial-scale=1">
