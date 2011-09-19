<?php

//
//  Copyright (c) 2011, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.below92.com
//

ob_start();
include("config.php");
include("class.session_handler.php");
include("check_new_user.php");
include("functions.php");
require_once("update_status.php");
require_once('classes/Project.class.php');
/*********************************** HTML layout begins here  *************************************/

include("head.html");
?>

<!-- Add page-specific scripts and styles here, see head.html for global scripts and styles  -->
<link type="text/css" href="css/worklist.css" rel="stylesheet" />
<link href="css/projects.css" rel="stylesheet" type="text/css" >

<script type="text/javascript" src="js/worklist.js"></script>
<script type="text/javascript" src="js/utils.js"></script>
<script type="text/javascript" src="js/add-proj-contact.js"></script>

<title>Worklist | Help</title>

</head>
<br/>
<br/>
<body>

<?php include("format.php"); ?>
<br/>
<a name="top"></a>
<h1 class="header">Worklist Frequently Asked Questions</h1> </br></br>


<div id="general-faq" class="faq">

<h1>General FAQs<span class="heading-links" /></h1>

    <ul>     
        <li><a href="#gf1">What is the Worklist?</a></li>
        <li><a href="#gf2">What does "Mechanic" mean?</a></li>
        <li><a href="#gf3">What does "Founder" mean? </a></li>
        <li><a href="#gf4">Why would I add a project to the Worklist?</a></li>
        <li><a href="#gf5">I'm a web programmer, can I work on the Worklist?</a></li>
        <li><a href="#gf6">What is a Bid?</a></li>
        <li><a href="#gf7">What is a Fee?</a></li>
    </ul>
<br/>  
</div>  
<div id="founder-faq" class="faq">
<h1>Founder FAQs<span class="heading-links" /></h1>
    <ul>     
        <li><a href="#ff1">How Do I Know Which Bid to Accept?</a></li>
        <li><a href="#ff2">Can Someone Else Help me Run Jobs?</a></li>
        <li><a href="#ff3">Do I have to Open Source my Code to use the Worklist?</a></li>
        <li><a href="#ff4">I'm not a Founder, but I'd like to be, how do I get my project on the Worklist?</a></li>
        <li><a href="#ff5">How do I manage a project on the Worklist? What do the various project statuses mean?</a></li>
    </ul>
<br/>
</div>
<div id="general-faqlist" class="faq">

<h1>General FAQs<span class="heading-links" /></h1>
    <p><a name="gf1"></a><strong>Q. What is the Worklist? </strong></br >
    <strong>A.</strong> Worklist is a marketplace to rapidly build software and websites using a global network of developers, designers and
    testers.<br/>
    </p>
    <p><a name="gf2"></a><strong>Q. What does "Mechanic" mean? </strong></br>
    <strong>A.</strong> Mechanic refers to our distributed development team. Our global team of mechanics include web developers,
    engineers, designers and UI/UX experts.<br/>
    </p>
    <p><a name="gf3"></a><strong>Q. What does "Founder" mean? </strong></br>
    <strong>A.</strong> Founder refers to the project owner. They are usually the entrepreneur who is funding the project and make the
    final decisions on what goes live.<br/>
    </p>
    <p><a name="gf4"></a><strong>Q. Why would I add a project to the Worklist? </strong></br>
    <strong>A.</strong> Worklist is a marketplace to rapidly prototype and build software and websites using a global community of
    developers, designers and testers. We're currently working on several different <a href ='https://www.worklist.net/worklist/projects.php' target='_blank'>
    projects</a> and are always adding new tasks and projects to the list. If you are an entrepreneur or software engineer who
    wants to develop a new web or phone app, Worklist can help.<br/>
    </p>
    <p><a name="gf5"></a><strong>Q. I'm a web programmer, can I work on the Worklist? </strong></br>    
    <strong>A.</strong> Yes! We're always looking for new web developers join our community. To get started, sign up for an 
    <a href ='http://www.worklist.net/worklist/signup.php' target='_blank'>account</a> or visit our <a href ='http://www.worklist.net/journal' target='_blank'>
    dev chat channel</a> where there are almost always other people around to answer questions and help you get started.<br/>
    </p>
    <p><a name="gf6"></a><strong>Q. What is a Bid? </strong></br>
    <strong>A.</strong> Bids are a key part of our process. When you enter a bid, it is always better to give a brief description of
    how you intend to address the job requirements. Runners are not just looking for the lowest bid; we are looking
    for a considered bid that has some thought behind it and is not being unrealistic in cost.<br/>
    </p>
    <p><a name="gf7"></a><strong>Q. What is a Fee? </strong></br>
    <strong>A.</strong> In addition to the initial bid, you will probably also see one or more fees attached to a job. During the course
    of a job Mechanics may attach fees for code reviews, testing or other help. We encourage Mechanics to use their best judgement when
    fee-ing in. If you are confused about any fee, please don't hesitate to talk to the Mechanic before marking your job as done. As a
    Founder, you are also able to dispute or even delete fees if you wish.  Founders can award bonus payments to recognize a Mechanic's
    job well done or for going above and beyond.<br/>
    </p>
    <br/>
    <a href="#top">BACK TO TOP</a>
    </p>
    </div>
<br/>    
<div id="founder-faq" class="faq">
    <tr>
        <td align="left"><h1>Founder FAQ<span class="heading-links" /></h1></td>
    </tr>
    <p><a name="ff1"></a><strong>Q. How Do I Know Which Bid to Accept? </strong></br>
    <strong>A.</strong> Everyone has a slightly different philosophy for which bid to accept, but as a project Founder you have several data
    points available to help you make a decision. First, most developers will include a description for how they would do the job, it's a
    great place to start. <br/>
    <br/>
    Another place to look is the Mechanic's profile, just click on their name in the Worklist to view it. From the profile, you can browse all of
    the jobs they've done, how many active jobs they have currently. Also, as Founder, you have access to "Reviews" from other project Founders
    and Runners - don't hesitate to reach out to them if you need more information about their experience with the Mechanic.<br/>
    </p>
    <p><a name="ff2"></a><strong>Q. Can Someone Else Help me Run Jobs? </strong></br>
    <strong>A.</strong> If you would like to bring someone into the Worklist to help you run jobs, please let us know and we'll help you set
    it up. Also, we have several project managers on the Worklist who have the ability to operate as Runners for your project. Email
    contact@worklist.net for more information on how to set this up.<br/>
    </p>
    <p><a name="ff3"></a><strong>Q. Do I have to Open Source my Code to use the Worklist? </strong></br>
    <strong>A.</strong> In short, no. Open Source refers to a number of concepts, ranging from publicly available source code to a free 
    license to use that code for any purpose. For our own projects on the Worklist, we allow our code to be publicly visible because this 
    allows us the fastest product iteration. These benefits outweigh the perceived negatives, namely that competition might steal our code
    or that hackers will find exploits. Our code is not freely licensed and any unlawful use would result in the same legal action as any
    other act of theft.<br/>
    <br/>
    You are welcome to use any visibility level for your project. The trade-off for erecting tighter controls around your code is a slower
    iteration cycle. Whichever side of the spectrum you choose to go, the Worklist can accommodate. We're happy to talk to you to find a
    solution that works best.<br/>
    </p>
    <!-- Popup for add project info-->
        <?php include('dialogs/add-proj-contact.inc'); ?>
    <p><a name="ff4"></a><strong>Q. I'm not a Founder, but I'd like to be, how do I get my project on the Worklist? </strong></br>
    <strong>A.</strong> Please send us some information via this <a id="add-projects" href="#">link</a> and we'll
    help you get started!<br/>
    </p>
    <p><a name="ff5"></a><strong>Q. How do I manage a project on the Worklist? What do the various project statuses mean? </strong></br>
    <strong>A.</strong> Tasks on the Worklist go through various statuses from preparation through release. Once your project is up and
    funded, you might need this cheat sheet to help you out until you get the knack of our work flow.<br/>
    <p><strong>Stage One: Preparation > Setting up your jobs</strong></p> 
    <blockquote>
        <li><strong>SUGGESTED -</strong> The Worklist allows anyone to make suggestions for improvements to any active project. When SUGGESTED tasks
        appear on your project, you can review and decide whether or not you'd like to move them into bidding.</li> 
        <li><strong>SUGGESTEDwithBID -</strong> Sometimes when a Mechanic has a suggestion, they will also have a particular solution in mind
        and be willing to do the work. Don't worry, as project Founder, you always get to decide whether or not a suggested job moves ahead and
        who works on it.</li> 
        <li><strong>BIDDING -</strong> As a project Founder, every new job you submit will automatically be set to BIDDING. You can also move
        SUGGESTED jobs you'd like to get done into BIDDING status. Once a job is set to BIDDING, any Mechanic can put in their offer. For help
        in selecting which bid you want to accept, check the <a href="#ff1">FAQ</a> for tips.</li>
        <br/>
    </blockquote>
        <p><strong>Stage Two: In Progress > Getting your jobs done</strong></p>
    <blockquote>
        <li><strong>WORKING -</strong> Once you've selected a bid, your job will automatically set to WORKING status. It will remain here until the
        Mechanic is done with the work.</li> 
        <li><strong>FUNCTIONAL -</strong> When the Mechanic is ready for their work to be reviewed, they will set the job to FUNCTIONAL. At this
        step, your job as Founder is to make sure everything works according to your design. If anything looks wrong, leave a comment for the
        Mechanic to fix. If the job look good, set it to REVIEW.</li>
        <li><strong>REVIEW -</strong> REVIEW means the new code is ready to be reviewed by another Mechanic. If the reviewer finds any issues,
        they are either worked out in real-time in the Journal or via comments added to the job ticket. Once all issues have been resolved, the
        Mechanic commits the code and sets the job to COMPLETED.</li> 
        <br/>
    </blockquote>
        <p><strong>Stage Three: Finished > Your job is done</strong></p>
    <blockquote>
        <li><strong>COMPLETED -</strong> Once your job to set to COMPLETED, the Mechanic's work should be finished. Depending on how your project is
        set up, committed code gets deployed to a staging site or even directly to the production site or both. As the Founder, you should
        double-check to make sure everything looks correct. If anything is wrong, contact the Mechanic ASAP. This stage allows Founders
        additional time once the job is live to verify the new functionality in production. If things look good, move to the final status: DONE!</li> 
        <li><strong>DONE -</strong> The final Worklist status. Your Mechanic will not be paid until you move your job to the DONE status. As
        Founder, your DONE jobs will be picked up in our payment system during the next payment run via Paypal, currently twice a week. Funding
        for jobs under your project will come from your budget. Please email finance@worklist.net if you have any specific questions about
        budgeting or payment.</li></br>
        </p>
    </blockquote>
    <a href="#top">BACK TO TOP</a>
</div>
   
<?php
include("footer.php");
?> 