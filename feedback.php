<?php
  include("config.php");

  if(isset($_POST['message'])){
    //sending feedback email
    $subject = "Feedback for ".APP_NAME;
    $body = strip_tags($_POST['message']);
    $email = isValidEmail(trim($_POST['email'])) ? trim($_POST['email']) : FEEDBACK_EMAIL; 
    $headers = "From: ".APP_NAME." Feedback <".$email.">\n"."X-Mailer: php";;

	//This is not using the mail mechanism and may not work in all cases
    if(mail(FEEDBACK_EMAIL,$subject,$body,$headers)){
      echo "Feedback sent!";
    }
  }

function isValidEmail($email){
    return eregi("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$", $email);
}
?> 
