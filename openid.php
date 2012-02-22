<?php
require_once('Zend/OpenId/Consumer.php');
require_once('lib/Agency/OpenId/Consumer.php');
require_once('lib/Agency/OpenId/Extension/Ax.php');

if (isset($_REQUEST['google_identifier']) && !empty($_REQUEST['google_identifier'])) {
    // Redirecting the user to googles login page
	$ax = new Agency_OpenId_Extension_Ax(array(
		'email' => true
	), null, 1.0);
	$consumer = new Agency_OpenId_Consumer();
	if (!$consumer->login($_REQUEST['google_identifier'], null, 'http://' . SERVER_NAME . '/', $ax)) {
		$msg = "Google Authentication failed!";
	}
}

// Perform login
if (isset($_GET['openid_mode'])) {
	$id = null;
	
	$ext = new Agency_OpenId_Extension_Ax(array(
		'email' => true
	), null, 1.0);		
	$consumer = new Agency_OpenId_Consumer();

	// we try to verify the user with the information of the openid provider
	if ($consumer->verify($_GET, $id, $ext)) {
	    // user verified do we have a database entry?
		$result = mysql_query('SELECT * FROM `' . USERS . '` WHERE `openid` = "' . $id . '";');
		if ($result && mysql_num_rows($result) == 1) {
			$row = mysql_fetch_array($result);
			if($row['confirm'] == 1) {
				initSessionData($row);
				if ($_POST['redir']) {
			        header("Location:".urldecode($_POST['redir']));
			        exit();
			    } else { 
			          if (!empty($_POST['reauth'])) {
			              header("Location:".urldecode($_POST['reauth']));
			              exit();
			          } else {
			              if (isset($_GET['redirectto']) && ($_GET['redirectto'] == 'journal')) {
			                  header('Location:journal.php');
			                  exit();
			              } else {
			              	  header("Location:worklist.php");
			              	  exit();
			              }
			          }
			    }
			} else {
				$msg = "Your email is not verified yet. Check your inbox for an email to verify your account first.<br />OR<br /><a href=\"resend.php\">Re-Send Email Confirmation</a>";
			}
		} else {
		    // user needs to sign up
			$data = $ext->getProperties();
			header('Location: signup.php?authtype=openid&id=' . rawurlencode($id) . '&nickname=' . rawurlencode($data['firstname']) . '&email=' . rawurlencode($data['email']) . '&country=' . rawurlencode($data['country']));
			exit();
		}
	} else {
	    // the user couldn't be verified
	    $id = '("' . htmlspecialchars($id) . '")';
		$msg = 'Your ID ' . $id . ' is invalid.<br />';
		$msg .= $consumer->getError();
	}
}
