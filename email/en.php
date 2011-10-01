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
"SendLove Password Changed",

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
"Worklist Password Recovery",

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
           - Get in the Journal to chat with us: <a href=" https://dev.worklist.net/journal">https://dev.worklist.net/journal</a><br/>
           - Bid on a project on our Worklist: <a href="https://dev.worklist.net/worklist">https://dev.worklist.net/worklist</a><br/>
        <br/>
        * Don\'t worry, you\'ll get a custom sandbox and support getting our codebase set up once you\'ve made a bid!<br/>
        Looking for even more info? Check here: <a href="http://www.below92.com/development-process/">http://www.below92.com/development-process/</a><br/>
        Thanks for joining and we hope to see you in the Journal soon!<br/><br/>
        Cheers,<br/><br/>
        Coffee & Power Inc<br/><br/>
        p.s. Follow our continuing adventures...<br/>
        Blog: <a href="http://www.lovemachineinc.com/blog/">http://www.lovemachineinc.com/blog/</a><br/>
        Twitter: <a href="http://twitter.com/lovemachineinc">http://twitter.com/lovemachineinc</a>
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
code-review-finished
    send user a notification email that their w9 was rejected
    used in: workitem.php

replacement data:
    task - the task number followed by the summary
    reviewer - the mechanic who undertook the code review
*/
'code-review-finished' => array(
    'subject' => 'Review Complete: {subj}',
    'body' => '<p>Hello,</p>
               <p>The code review on task {task} has been completed by {reviewer}</p>
               <br>
               <p>Project: '.$project_name . '<br />
               Creator: {creator}<br />
               Runner: {runner}<br />
               Mechanic: {mechanic}</p>
               <p>Notes: '.$workitem->getNotes() . '<br /></p>
               <p>You can view the job <a href='.SERVER_URL.'workitem.php?job_id=' . $itemId . '>here</a>.' . '<br /></p>
               <p> -Worklist.net</p>'
               '
)

); 
