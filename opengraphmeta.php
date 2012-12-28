<?php
//  Copyright (c) 2012, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com

if (! isset($meta_title)) {
    $meta_title = 'Worklist - A community of independent software developers';
}
if (! isset($meta_image)) {
    $meta_image = 'images/worklist_logo_large.png';
}
if (! isset($meta_desc)) {
    $meta_desc = 'Worklist is a software development forum to rapidly prototype and build software and websites using a global network of developers, designers and testers.';
}
?>
<meta property="og:title" content="<?php echo $meta_title; ?>" />
<meta name="description" content="<?php echo $meta_desc; ?>" />
<meta property="og:description" content="<?php echo $meta_desc; ?>" />
<meta property="og:image" content="<?php echo SERVER_URL . $meta_image; ?>" />