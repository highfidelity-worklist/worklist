<?php
  $feedback_email = isset($_SESSION['username']) ? $_SESSION['username'] : '';
?>
<div class="slide-out-div">
    <a class="handle">Content</a>
    <h3>Leave Feedback</h3>
    <form>
      <p>
	<label for = "feedback-email">Email:</label><br />
	<input type = "text" id = "feedback-email" name = "email" value = "<?php echo $feedback_email; ?>" size = "30" />
      </p>
            <script type="text/javascript">
	      var username = new LiveValidation('feedback-email', {validMessage: "Valid email address."});
	      username.add( Validate.Email );
	      username.add(Validate.Length, { minimum: 10, maximum: 50 } );
	    </script>
      <p>
	<label for = "feedback-message">Message:</label><br />
	<textarea id = "feedback-message" name = "message" cols = "30" rows = "8"></textarea>
      </p>
      <p class = "last">
	<input type = "submit" name = "feedback-submit" id = "feedback-submit" value = "Submit" />
      </p>
    </form>
</div>
 
