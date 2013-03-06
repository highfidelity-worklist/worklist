<?php
/**
 * vim:ts=4:et
 * 
 * Copyright (c) 2013, CoffeeandPower Inc.
 * All Rights Reserved. 
 *
 * http://www.worklist.net
 */

ob_start();
include("config.php");
include("class.session_handler.php");
include("functions.php");
include("head.html");
include("opengraphmeta.php");
?>
<!-- Add page-specific scripts and styles here, see head.html for global scripts and styles  -->
<link href="css/worklist.css" rel="stylesheet" type="text/css">
<title>Worklist | Privacy Statement</title>
</head>
<body>
<?php
    require_once('header.php');
    require_once('format.php');
?>
<!-- ---------------------- BEGIN MAIN CONTENT HERE ---------------------- -->
<span id="privacyInfo">
	<h1>Worklist Privacy Statement</h1>
	<h3>What information do we collect?</h3>
	<p>We collect information from you when you register on our site or fill out a form.</p>
	<p>When ordering or registering on our site, as appropriate, you may be asked to enter your: e-mail address. You may, however, visit our site anonymously.</p>
	<h3>What do we use your information for?</h3>
	<p>Any of the information we collect from you may be used in one of the following ways:</p>
	<ul>
		<li>To personalize your experience <br />
		(your information helps us to better respond to your individual needs)</li>
		<li>To improve our website <br />
		(we continually strive to improve our website offerings based on the information and feedback we receive from you)</li>
		<li>To improve customer service<br />
		(your information helps us to more effectively respond to your customer service requests and support needs)</li>
	</ul>

	<h3>How do we protect your information?</h3>
	<p>We implement a variety of security measures to maintain the safety of your personal information when you access your personal information.</p>
	<p>We offer the use of a secure server. All supplied sensitive/credit information is transmitted via Secure Socket Layer (SSL) technology and then encrypted into our Database to be only accessed by those authorized with special access rights to our systems, and are required to keep the information confidential.</p>
	<p>After a transaction, your private information (credit cards, social security numbers, financials, etc.) will not be kept on file for more than 60 days.</p>
	<h3>Do we use cookies?</h3>
	<p>Yes Cookies are small files that a site or its service provider transfers to your computers hard drive through your Web browser (if you allow) that enables the sites or service providers systems to recognize your browser and capture and remember certain information</p>
	<p>We use cookies to understand and save your preferences for future visits.</p>
	<h3>Do we disclose any information to outside parties?</h3>
	<p>We do not sell, trade, or otherwise transfer to outside parties your personally identifiable information. This does not include trusted third parties who assist us in operating our website, conducting our business, or servicing you, so long as those parties agree to keep this information confidential. We may also release your information when we believe release is appropriate to comply with the law, enforce our site policies, or protect ours or others rights, property, or safety. However, non-personally identifiable visitor information may be provided to other parties for marketing, advertising, or other uses.</p>
	<h3>Third party links</h3>
	<p>Occasionally, at our discretion, we may include or offer third party products or services on our website. These third party sites have separate and independent privacy policies. We therefore have no responsibility or liability for the content and activities of these linked sites. Nonetheless, we seek to protect the integrity of our site and welcome any feedback about these sites.</p>
	<h3>Childrens Online Privacy Protection Act Compliance</h3>
	<p>We are in compliance with the requirements of COPPA (Childrens Online Privacy Protection Act), we do not collect any information from anyone under 13 years of age. Our website, products and services are all directed to people who are at least 13 years old or older.</p>
	<h3>Your Consent</h3>
	<p>By using our site, you consent to our web site privacy policy.</p>
	<h3>Changes to our Privacy Policy</h3>
	<p>If we decide to change our privacy policy, we will post those changes on this page, and/or send an email notifying you of any changes.</p>
	<h3>Contacting Us</h3>
	<p>If there are any questions regarding this privacy policy you may contact us using the information below.</p>
	<p><a href="http://www.lovemachineinc.com" title="LoveMachine Home Page" target="_blank">www.lovemachineinc.com</a></p>
	<p>San Francisco, CA 94127, USA</p>
	<p>admin (at) lovemachineinc (dot) com</p>
</span>

<!-- ---------------------- end MAIN CONTENT HERE ---------------------- -->
<?php require_once('footer.php'); ?>
