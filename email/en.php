<?php

$emailTemplates = array(

/*
changed_pass
    notification of changed password
    used in: resetpass.php

replacement data:
    app_name - human-reqadable application name
*/

'changed_pass' => array(

'subject' =>
"Worklist Password Changed",

'body' =>
"<p>Change is good!</p>
<p>You have successfully changed your password with {app_name}</p>",
),

/*
recovery
    email for password recovery
    used in: forgot.php

replacement data:
    url - link to reset password
*/

'recovery' => array(

'subject' =>
"Worklist Password Reset",

'body' =>
"<p>Hi,</p>
<p>Please click on the link below or copy and paste the url in browser to reset your password.<br/>
&nbsp;&nbsp;&nbsp;&nbsp;{url}</p>
<p>Worklist</p>",
),

/*
feedback
    feedback received from feedback slidout
    used in: feedback.php

replacement data:
    app_name - human-reqadable application name
    message - message from user
*/

'feedback' => array(

'subject' =>
"Feedback for {app_name}.",

'body' =>
"<p>You received feedback for {app_name} from {sender}!</p>
<p>{message}<p/>",
),

/*
bonus
    inform user they have received a bonus
    used in: pay-bonus.php

replacement data:
    amount - the amount of bonus received
    reason - the reason the bonus was given
*/
'bonus_received' => array(
    'subject' => 'Bonus payment of ${amount}',
    'body' => '
        <p>You received a bonus of ${amount}, and note:</p>
        <p>{reason}</p>'
),

/*
welcome
    send confirmed user a welcome email
    used in: confirmation.php

replacement data:
    nickname - nickname of the receiving user
*/
'welcome' => array(
    'subject' => 'Get Started with Worklist',
    'body' => '
        Thank you for joining Worklist, {nickname}!<br/><br/>
        Worklist offers fast pay for your work, an open codebase,
        and great community. We\'re excited to have you join us as we
        create a new way to get things done.<br/><br/>
        Three things to help you get started:<br/><br/>
           - Browse the source code*: <a href="http://svn.worklist.net">http://svn.worklist.net</a><br/>
          - Get in the Journal to chat with us: <a href="' . JOURNAL_URL . '">' . JOURNAL_URL . '</a><br/>
           - Bid on a project on our Worklist: <a href="' . WORKLIST_URL . '">' . WORKLIST_URL . '</a><br/>
        <br/>
        Don\'t worry, you\'ll get a custom sandbox and support getting our codebase set up once your bid is accepted.
        <br/>
        Thanks for joining and we hope to see you in the Journal soon!<br/><br/>
        Cheers,<br/><br/>
        Coffee & Power Inc<br/><br/>
        p.s. Follow our continuing adventures...<br/>
        Twitter: <a href="http://twitter.com/worklistnet">http://twitter.com/worklistnet</a>
    '
),

/*
trusted
    send approved user a notification email
    used in: userinfo.php
*/
'trusted' => array(
    'subject' => 'You have been Trusted!',
    'body' => '<p>Congrats!</p>
               <p>You have been trusted by one of your peers in the Worklist!</p>
<p></p>
<p>See your User Profile here:<br/>
{link}
</p>
<p>- Worklist.net</p>'
),

/*
w9-approved
    send approved user a notification email
    used in: userinfo.php
*/
'w9-approved' => array(
    'subject' => 'Your W9 has been approved',
    'body' => '<p>Hello,</p>
               <p>Your W9 form has been approved.</p>'
),

/*
w9-rejected
    send user a notification email that their w9 was rejected
    used in: userinfo.php

replacement data:
    reason - the reason that the w9 was rejected, as entered by the user
*/
'w9-rejected' => array(
    'subject' => 'Your W9 has been rejected',
    'body' => '<p>Hello,</p>
               <p>Your W9 form has been rejected because:</p>
               <p>{reason}'
),

/*
 * project-created
 *     send project creator the details associated to the newly created project
 * 
 * replacement data:
 *     nickname: User's nickname
 *     project_name: Name of the project
 *     database_user: Name of mysql username
 */
'project-created-newsb' => array(
    'subject' => 'New project {project_name} added to the Worklist!',
    'body' => '<p>Hi {nickname}!</p>
               <p>Your project {project_name} has been created on Worklist, with a sample \'Hello World\' index page with data from your database.<br/>
               The setup includes:</p>
               <p>1. An SVN repository available at <a href="http://svn.worklist.net/listing.php?repname={project_name}">http://svn.worklist.net/listing.php?repname={project_name}</a></p>
               <p>2. A MySQL database including a sample table. You may access your development environment database using the following details:<br/>
               Host: staging-mysql1.worklist.net<br/>
               Database: dev_{project_name}<br/>
               Username: dev_{database_user}<br/>
               Password: unsecure<p/>
               <p>3. A sandbox development environment.  You will receive another email with all required details to access your Sandbox environment shortly.</p>
               <p>4. A working copy of your site can be viewed at <a href="http://staging.worklist.net/{project_name}">http://staging.worklist.net/{project_name}</a></p>
               <p>5. You can view your development site pointing your web browser to http://dev.worklist.net/~{nickname}/{project_name}</p>
               <p>6. To update details specific to your project, or to modify project roles, go to your <a href="' . WORKLIST_URL . '{project_name}">project page</a>.</p>
               <p>7. Your project is set to allow anybody to code review by default, you can change this setting by <a href="' . WORKLIST_URL . '{project_name}?action=edit">editing your project</a>.</p>
               <p><a href="'.SERVER_URL.'>www.worklist.net</a></p>'
),
    
'project-created-existingsb' => array(
    'subject' => 'New project {project_name} added to the Worklist!',
    'body' => '<p>Hi {nickname}!</p>
               <p>Your project {project_name} has been created on Worklist, with a sample \'Hello World\' index page with data from your database.<br/>
               The setup includes:</p>
               <p>1. An SVN repository available at <a href="http://svn.worklist.net/listing.php?repname={project_name}">http://svn.worklist.net/listing.php?repname={project_name}</a></p>
               <p>2. A MySQL database including a sample table. You may access your development environment database using the following details:<br/>
               Host: staging-mysql1.worklist.net<br/>
               Database: dev_{project_name}<br/>
               Username: dev_{database_user}<br/>
               Password: unsecure<p/>
               <p>3. We have checked out your new project into your existing sandbox. Please use your existing credentials.</p>
               <p>4. A working copy of your site can be viewed at <a href="http://staging.worklist.net/{project_name}">http://staging.worklist.net/{project_name}</a></p>
               <p>5. You can view your development site pointing your web browser to http://dev.worklist.net/~{nickname}/{project_name}</p>
               <p>6. To update details specific to your project, or to modify project roles, go to your <a href="' . WORKLIST_URL . '{project_name}">project page</a>.</p>
               <p>7. Your project is set to allow anybody to code review by default, you can change this setting by <a href="' . WORKLIST_URL . '{project_name}?action=edit">editing your project</a>.</p>
               <p><a href="'.SERVER_URL.'>www.worklist.net</a></p>'
),

'ops-project-created' => array(
    'subject' => 'New project {project_name} added to the Worklist!',
    'body' => '<p>Hi there</p>
               <p>Just wanted to let you know that a new project has been created on the worklist.</p>
               <p>Project Admin: {nickname}<br/>
               Project Name: {project_name}</p>
               <p>You can view the details <a href="' . WORKLIST_URL . '{project_name}">here</a>.'
),
    
'forked-repo' => array(
    'subject' => 'New fork {project_name} created on your GitHub account',
    'body' => '<p>Hi {nickname}!</p>
               <p>A fork of the project {project_name} has been created on your GitHub account. Make sure you keep this fork up to date with our master repository to guarantee you\'re always working on the most recent version of the codebase</p>
               <p>In order for your locally cloned repository to keep up to date with master changes, please follow these instructions:</p>
               <ol>
               <li>Locally clone your fork: git clone {users_fork}</li>
               <li>From your cloned copy, set the master repo as upstream: git remote add upstream {master_repo}</li>
               </ol>
               <p>If you have any questions, look for assistance in our Chat.</p>
               <p>The Worklist</p>'
),
    
'branch-created' => array(
    'subject' => 'New branch {branch_name} created for repo {users_fork} on your GitHub account',
    'body' => '<p>Hi {nickname}!</p>
               <p>We have created a new branch {branch_name} on your {users_fork} repo in your GitHub account.</p>
               <p>Please refer to the following basic commands to work with your new branch:</p>
               <ol>
               <li>Locally clone your fork: git clone {users_fork}</li>
               <li>From your cloned copy, set the master repo as upstream: git remote add upstream {master_repo}</li>
               <li>Make sure you\'re using the latest code: git pull upstream master</li>
               <li>Checkout the correct branch: git checkout {branch_name}</li>
               <li>Make all required changes, commit to your local clone as you advance, and push to your fork whenever ready: git push</li>
               <li>Before code or functional reviews can happen, you must have pushed your progress to your GitHub fork.</li>
               </ol>
               <p>If you have any questions, look for assistance in our Chat.</p>
               <p>The Worklist</p>'
),
'branch-created-sub' => array(
    'body' => '<br />
               <strong>New branch {branch_name} created for repo {users_fork} on your GitHub account</strong>
               <p>We have created a new branch {branch_name} on your {users_fork} repo in your GitHub account.</p>
               <p>Please refer to the following basic commands to work with your new branch:</p>
               <ol>
               <li>Locally clone your fork: git clone {users_fork}</li>
               <li>From your cloned copy, set the master repo as upstream: git remote add upstream {master_repo}</li>
               <li>Make sure you\'re using the latest code: git pull upstream master</li>
               <li>Checkout the correct branch: git checkout {branch_name}</li>
               <li>Make all required changes, commit to your local clone as you advance, and push to your fork whenever ready: git push</li>
               <li>Before code or functional reviews can happen, you must have pushed your progress to your GitHub fork.</li>
               </ol>
               <p>If you have any questions, look for assistance in our Chat.</p>'
),

'functional-howto' => array(
    'subject' => 'Job #{branch_name} is ready for functional review',
    'body' => '<p>Hi {runner}!</p>
               <p>Job #{branch_name} is ready for functional review. Please follow these instructions to checkout and test the work done by the mechanic:</p>
               <ul>
               <li>Clone the mechanics fork: git clone {users_fork}</li>
               <li>Checkout the branch created for this job: git checkout {branch_name}</li>
               <li>Use this branch to make your build</li>
               </ul>
               <p>If you have any questions, please get ahold of an O-Team Member for assistance.</p>'
),
    
'commit-howto' => array(
    
),

'project-runner-added' => array(
    'subject' => 'Added as a Runner to Project',
    'body' =>
        '<p>Hi {nickname}</p>
        <p>Congrats! You have been granted Runner rights for the following project:<br />
        <a href="{projectUrl}">{projectName}</a><br />
        Project Founder: <a href="{projectFounderUrl}">{projectFounder}</a></p>
        <p>Please contact the project founder with any questions.</p>
        <p>- Worklist.net</p>'
),

'project-runner-removed' => array(
    'subject' => 'Runner rights removed for Project',
    'body' => 
        '<p>Hi {nickname}</p>
        <p>Your Runner rights have been removed for the following project:<br />
        <a href="{projectUrl}">{projectName}</a><br />
        Project Founder: <a href="{projectFounderUrl}">{projectFounder}</a></p>
        <p>Please contact the project founder with any questions.</p>
        <p>- Worklist.net</p>'
)

);
