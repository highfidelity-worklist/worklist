        <div align="right"  id="closelogindiv"><a href="#" id="closelogin"  onclick="javascript:HideLogin();">close x</a></div>
        <form id="login" action="journal.php" method="post" >
        		<div class="LVspace">
			    <label>E-mail<br />
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
                  
                <p><input type="submit" id="Login" value="Login" name="Login" alt="Login"></p>
                
      </form>
