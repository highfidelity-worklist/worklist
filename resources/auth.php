<?php

include('../class.session_handler.php');
include('../check_session.php');

$url = "https://www.google.com/analytics/feeds/data";

/**
 * Main logic. Take action based on the GET and POST
 * parameters, which reflect whether the user has
 * authenticated and which action they want to perform.
 */
if(array_key_exists('token', $_GET)) {
    showFirstAuthScreen();
} else {
    showIntroPage();
}

/**
 * Exchanges the given single-use token for a session
 * token using AuthSubSessionToken, and returns the result.
 */
function exchangeToken($token) {
  $ch = curl_init();    /* Create a CURL handle. */

  curl_setopt($ch, CURLOPT_URL,
    "https://www.google.com/accounts/AuthSubSessionToken");
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_FAILONERROR, true);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Authorization: AuthSub token="' . $token . '"'
  ));

  $result = curl_exec($ch);  /* Execute the HTTP command. */
  curl_close($ch);

  $splitStr = split("=", $result);
  return trim($splitStr[1]);
}
/**
 * We arrive here when the user first comes to the form. The first step is
 * to have them get a single-use token.
 */
function showIntroPage() {
  global $url;

  $next_url  = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
  $redirect_url = 'https://www.google.com/accounts/AuthSubRequest?session=1';
  $redirect_url .= '&next=';
  $redirect_url .= urlencode($next_url);
  $redirect_url .= "&scope=";
  $redirect_url .= urlencode($url);

  print '<html>' . "\n";
  print '<head><title>API Access</title>' . "\n";
  print '<link rel="stylesheet" type="text/css" href="http://code.google.com/css/dev_docs.css">' . "\n";
  print '</head>' . "\n";
  print '<body><center>' . "\n";
  print '<table style="width:50%;">' . "\n";
  print '<tr>' . "\n";
  print '<th colspan="2" style="text-align:center;">API Access</th>' . "\n";
  print '</tr>' . "\n";
  print '<tr><td>To acquire a new token, please <a href="' . $redirect_url . 
      '">sign in</a> to your personal Google Base account.</td></tr>' . "\n";
  print '</table>' . "\n";
  print '</center></body></html>' . "\n";
}


/**
 * We arrive here after the user first authenticates and we get back
 * a single-use token.
 */
function showFirstAuthScreen() {
  $singleUseToken = $_GET['token'];
  $sessionToken = exchangeToken($singleUseToken);

  if(!$sessionToken) {
    showIntroPage();
  } else {
  print '<html>' . "\n";
  print '<head><title>API Access</title>' . "\n";
  print '<link rel="stylesheet" type="text/css" href="http://code.google.com/css/dev_docs.css">' . "\n";
  print '</head>' . "\n";
  print '<body><center>' . "\n";
  print '<table style="width:50%;">' . "\n";
  print '<tr>' . "\n";
  print '<th colspan="2" style="text-align:center;">API Access</th>' . "\n";
  print '</tr>' . "\n";
  print '<tr><td>Here\'s your <b>single use token:</b> <code>' . $singleUseToken .
      '</code>' . "\n" . '<br>And here\'s the <b>session token:</b> <code>' .
      $sessionToken . '</code>' . "\n";
  print '</center></body></html>' . "\n";
  }
}

?>