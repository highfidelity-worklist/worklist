<?php
/**
 * vim:ts=4:et
 * 
 * Copyright (c) 2013, CoffeeandPower Inc.
 * All Rights Reserved. 
 *
 * http://www.worklist.net
 */

// if user is logged in and he is new user - redirect him to
// settings page
$settings_page = "settings.php";
$exploded_url = Explode('/', $_SERVER['PHP_SELF']);
$current_page = $exploded_url[count($exploded_url) - 1];
if(!empty($_SESSION['userid']) && $current_page != $settings_page){
    //stop warning when new_user is not an element of SESSION
    if(!empty($_SESSION['new_user'])){
        header("Location:" . $settings_page);
    }
}
