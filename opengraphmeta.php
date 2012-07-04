<?php
//  Copyright (c) 2012, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com

$meta_title = 'Worklist - A community of independent software developers';
$meta_image = 'images/worklist_logo_large.png';
$meta_desc = 'Worklist is a software development forum to rapidly prototype and build software and websites using a global network of developers, designers and testers.';

if(is_object($inProject))
{
	$meta_title = 'Worklist Project: ' . $inProject->getName();
	$meta_desc = $inProject->getDescription();
	$min_dimensions = 120; // Minimum image dimension some social networks must have before showing thumb

	if($inProject->getLogo())
	{
		list($check_width, $check_height) = getimagesize(UPLOAD_PATH.$inProject->getLogo());

		if($check_width >= $min_dimensions && $check_height >= $min_dimensions)
		{	// Override default if image is large enough
			$meta_image = 'uploads/'.$inProject->getLogo();
		}
	}
}
?>
<meta property="og:title" content="<?php echo $meta_title; ?>" />
<meta name="description" content="<?php echo $meta_desc; ?>" />
<meta property="og:description" content="<?php echo $meta_desc; ?>" />
<meta property="og:image" content="<?php echo SERVER_URL . $meta_image; ?>" />