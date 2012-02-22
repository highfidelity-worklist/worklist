        <div align="right"  id="closelogindiv"><a href="#" id="closelogin"  onclick="javascript:HideLogin();">close x</a></div>
        <form id="login" action="journal.php" method="post" >
        		<div class="LVspace">
                <label>Username<br />
			  	<input type="text" id="username" onBlur="if ($(this).val() == '') {$(this).val('Email address'); $(this).addClass('watermark');}" onFocus="if ($(this).val() == 'Email address') {$(this).val(''); $(this).removeClass('watermark');}" name="username" value="Email address" class="text-field watermark"  />
                </label>
				<script type="text/javascript">
					var username = new LiveValidation('username',{ validMessage: "Valid email address.", onlyOnBlur: false });
						username.add(SLEmail);
						username.add(Validate.Length, { minimum: 10, maximum: 50 } );
				</script>
	 			</div>
                
	          	<p><label>Password<br />
			      <input type="password" id="password" name="password" onBlur="if ($(this).val() == '') { $(this).addClass('hide');$('#passwordText').removeClass('hide'); }" class="text-field hide" />
			      <input type="text" id="passwordText" name="passwordText" value="Password" onFocus="$(this).addClass('hide');$('#password').removeClass('hide').focus();" class="text-field watermark" />
                  </label></p>
                  
			<div class="LVspace">
				<label>Google Login<br />
					<input type="text" name="google_identifier" class="text-field google" id="google_identifier" value="" />
				</label>
				<script type="text/javascript">
					var openid = new LiveValidation('google_identifier', {validMessage: 'Valid url.', onlyOnBlur: false});
						openid.add(Validate.Format, { pattern: /((http|https)(:\/\/))?([a-zA-Z0-9]+[.]{1}){2}[a-zA-z0-9]+(\/{1}[a-zA-Z0-9]+)*\/?/i, failureMessage: "Must be a valid url!" });
				</script>
			</div>
			
                <p><input type="submit" id="Login" value="Login" name="Login" alt="Login"></p>
                
      </form>
