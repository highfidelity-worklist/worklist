<?php

$emailTemplates = array(

/*
confirmationi
    email address confirmation
    used in: resend.php, signup.php

replacement data:
    url - link to email confirmation
*/

'confirmation' => array(

'subject' =>
"SendLove Registration Confirmation",

'body' =>
"<p>You are only one click away from completing your registration with SendLove!</p>
<p>&nbsp;&nbsp;&nbsp;&nbsp;<a href=\"{url}\">Click here to verify your email address and activate your account.</a></p>
<p>Love,
<br/>The LoveMachine</p>",

'plain' =>
"You are only one click away from completing your registration with SendLove!\n\n
Click the link below or copy into your browser's window to verify your email address and activate your account.\n
    {url}\n\n
Love,\n
The LoveMachine",

),

/*
changed_settings
    notification of changed settings
    used in: settings.php

replacement data:
    app_name - human-reqadable application name
    changes - list of settings changes
*/

'changed_settings' => array(

'subject' =>
"SendLove Account Edit Successful.",

'body' =>
"<p>Congratulations!</p>
<p>You have successfully updated your settings with {app_name}<p/>
<p>{changes}</p>
<p>Love,
<br/>The LoveMachine</p>",
),

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
<p>You have successfully changed your password with {app_name}</p>
<p>Love,
<br/>The LoveMachine</p>",
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
"SendLove Password Recovery",

'body' =>
"<p>Hi,</p>
<p>Please click on the link below or copy and paste the url in browser to reset your password.<br/>
&nbsp;&nbsp;&nbsp;&nbsp;{url}</p>
<p>Love,
<br/>The LoveMachine</p>",
),

/*
love_email_new
    notification about received love for unregistered users
    used in: send_email.php

replacement data:
    sender_nickname
    for - love reason
    url - url to signup with sendlove
*/

'love_email_new' => array(

'subject' =>
"Love from {sender_nickname}.",

'body' =>
"<p>{for}</p>
{stats_html}
<br/><br/><p><a href=\"{url}\">Send your own love!</a></p>",

'plain' =>
"{for}\n\n\n\n
Send your own love!
{stats_plain}
Click the link below to join {sender_nickname} on SendLove and claim your love!:\n
{url}",
),

/*
love_email_old
    notification about received love for registered users
    used in: send_email.php

replacement data:
    sender_nickname
    for - love reason
    url - url to tofor page
*/

'love_email_old' => array(

'subject' =>
"Love from {sender_nickname}.",

'body' =>
"<p>{for}</p>
{stats_html}
<br/><br/><p><a href=\"{url}\">View your love!</a></p>",

'plain' =>
"{for}\n\n\n\n
{stats_plain}
Send your own love!

View your love:\n
{url}",

),

/*
love_email_new_private
    notification about received private love for unregistered users
    used in: send_email.php

replacement data:
    sender_nickname
    for - love reason
    url - url to signup with sendlove
*/

'love_email_new_private' => array(

'subject' =>
"Love from {sender_nickname} (love sent quietly).",

'body' =>
"<p>{for}</p>
{stats_html}
<br/><br/><p><a href=\"{url}\">Send your own love!</a></p>",

'plain' =>
"{for}\n\n\n\n
{stats_plain}
Send your own love!
Click the link below to join {sender_nickname} on SendLove:\n
{url}",
),

/*
love_email_old_private
    notification about received private love for registered users
    used in: send_email.php

replacement data:
    sender_nickname
    for - love reason
    url - url to tofor page
*/

'love_email_old_private' => array(

'subject' =>
"Love from {sender_nickname} (love sent quietly).",

'body' =>
"<p>{for}</p>
{stats_html}
<br/><br/><p><a href=\"{url}\">View your love!</a></p>",

'plain' =>
"{for}\n\n\n\n
{stats_plain}
Send your own love!

View your love:\n
{url}",

),


/*
invite_user
    invitation to join the company on sendlove
    used in: send_email.php

replacement data:
    invitor_email
    invitor nickname
    company_name
    url - url to join the company
*/

'invite_user' => array(

'subject' =>
"You are invited to join {company_name} on SendLove!",

'body' =>
"<p>{invitor_nickname} ({invitor_email}) has invited you to join {company_name} on SendLove.</p>
<p><a href=\"{url}\">Accept this invitation.</a></p>
<p>Love,
<br/>The LoveMachine</p>",

'plain' =>
"{invitor_nickname} ({invitor_email}) has invited you to join {company_name} on SendLove.\n
To accept this invitation click the link below:\n
    {url}\n\n
Love,\n
The LoveMachine\n",

),

/*
invite_switch
    invitation to join another company on sendlove
    used in: send_email.php

replacement data:
    invitor_email
    invitor nickname
    company_name
    url - url to join the company
*/

'invite_switch' => array(

'subject' =>
"You are invited to join {company_name} on SendLove!",

'body' =>
"<p>{invitor_nickname} ({invitor_email}) has invited you to join {company_name} on SendLove.<br />
You are already a member of another company. If you accept this invitation you will leave your current company and switch to {company_name}.<p/>
<p><a href=\"{url}\">Accept this invitation.</a></p>
<p>Love,
<br/>The LoveMachine</p>",

'plain' =>
"{invitor_nickname} ({invitor_email}) has invited you to join {company_name} on SendLove.\n
You are already a member of another company. If you accept this invitation you will leave your current company and switch to {company_name}.\n\n
To accept this invitation click the link below:\n
    {url}\n\n
Love,\n
The LoveMachine\n",

),

/*
invite_admin
    invitation to become admin of the company on sendlove
    used in: send_email.php

replacement data:
    invitor_email
    invitor nickname
    company_name
    url - url to join the company
*/

'invite_admin' => array(

'subject' =>
"You are invited to join {company_name} on SendLove!",

'body' =>
"<p>{invitor_nickname} ({invitor_email}) has invited you to become an administrator for {company_name} on SendLove.</p>
<p><a href=\"{url}\">Accept this invitation.</a></p>
<p>Love,
<br/>The LoveMachine</p>",

'plain' =>
"{invitor_nickname} ({invitor_email}) has invited you to become an administrator for {company_name} on SendLove.\n
To accept this invitation click the link below:\n
   {url}\n\n
Love,\n
The LoveMachine\n",

),

/*
join_request
    sent to company admins with user request to join the company
    used in: send_email.php

replacement data:
    sender_nickname
    company_name
    url - url to join the company
*/

'join_request' => array(

'subject' =>
"Company join request from {sender_nickname}.",

'body' =>
"<p>The user {sender_nickname} would like to join {company_name}<br />
<a href=\"{url}\">Approve this request.</a></p>
<p>Love,
<br/>The LoveMachine</p>",

'plain' =>
"The user {sender_nickname} would like to join {company_name}.\n
To approve this request please click the link below:\n
{url}\n\n
Love,\n
The LoveMachine",

),


/*
love_value
    changing value of love notification
    used in: admin.php

replacement data:
    company_name
    old_multiplier
    new_multiplier
*/

'love_value' => array(

'subject' =>
"Love value changed.",

'body' =>
"<p>Change is good! :)</p>
<p>The value of love messages within '{company_name}'
has been changed from {old_multiplier} to {new_multiplier}.
</p><p>Love,<br/>The LoveMachine</p>",

'plain' =>
"Change is good! :)\n
The value of love messages within '{company_name}'
has been changed from {old_multiplier} to {new_multiplier}.\n\n
Love,\n
The LoveMachine",

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
<p>{message}<p/>
<p>Love,
<br/>The LoveMachine</p>",
),

/*
Weekly Updates
	used in: weeklyupdates.php

replacement data:
	app_name - human-readable application name
	table - the table with the love sent
*/
'weeklyupdates' => array(
	'subject' => 'Weekly Updates for {app_name}',
	'body' => '<p>This is the love you and your colleagues shared this week:</p>
	{table}
	<p>Love,
	<br />The LoveMachine</p>',
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

replacement date:
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
           - Get in the Journal to chat with us: <a href=" http://dev.sendlove.us/journal">http://dev.sendlove.us/journal</a><br/>
           - Bid on a project on our Worklist: <a href="http://dev.sendlove.us/worklist">http://dev.sendlove.us/worklist</a><br/>
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
)

); 
