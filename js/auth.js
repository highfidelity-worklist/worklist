var Auth = {
    init: function() {
        var submitIsRunning = false;

        $('form#auth').submit(function(event) {
            event.preventDefault();
            if (submitIsRunning) {
                return false;
            }
            submitIsRunning = true;

            var email = new LiveValidation('username', {onlyOnSubmit: true});
            email.add(Validate.Email, {failureMessage: "You must enter a valid e-mail address!"});
            massValidation = LiveValidation.massValidate([email]);

            if (!massValidation) {
                submitIsRunning = false;
                return false;
            }

            var username = $('form#auth input[name="username"]').val();
            $.ajax({
                url: './user/exists/' + username,
                dataType: 'json',
                type: 'GET',
                error: function() {
                    submitIsRunning = false;
                },
                success: function(data) {
                    if (!data.success) {
                        submitIsRunning = false;
                        return;
                    }
                    var content, title;
                    if (data.exists) {
                        action = './github/authorize';
                        title = 'Please enter your Worklist password';
                        content = 
                            '<p>' +
                            '  Please enter the password you\'ve been using to sign in the Worklist' +
                            '  so far. This might be the last time you have to use it, but you should ' +
                            '  still remember it as your safe-mode way.' +
                            '</p>' +
                            '<div class="row">' +
                            '  <div class="col-md-4"><label for="pass1">Password</label></div>' +
                            '  <div class="col-md-8">' +
                            '    <input id="pass1" type="password" name="password" class="form-control" placeholder="Enter your password">' +
                            '  </div>' +
                            '</div>';
                    } else {
                        action = './github/signup';
                        title = 'Safe-mode password';
                        content = 
                            '<p>' +
                            '  Please choose a safe-mode password that will be useful' +
                            '  in cases of credentials problems.' +
                            '</p>' +
                            '<div class="row">' +
                            '  <div class="col-md-4"><label for="pass1">Password</label></div>' +
                            '  <div class="col-md-8">' +
                            '    <input id="pass1" type="password" name="password" class="form-control" placeholder="Choose a password">' +
                            '  </div>' +
                            '</div>' +
                            '<div class="row">' +
                            '  <div class="col-md-4"><label for="pass2">Repeat password</label></div>' +
                            '  <div class="col-md-8">' +
                            '    <input id="pass2" type="password" name="password2" class="form-control" placeholder="Enter password again">' +
                            '  </div>' +
                            '</div>';
                    }

                    Utils.emptyFormModal({
                        action: action,
                        title: title,
                        content: content,
                        buttons: [
                            {
                                content: 'Cancel',
                                className: 'btn-default',
                                dismiss: true
                            },
                            {
                                content: 'Ok',
                                className: 'btn-primary',
                                dismiss: false
                            }
                        ],
                        open: function(dialog) {
                            $('form .btn-primary', dialog).click(function() {
                                $('form', dialog).submit();
                            });
                            $('form', dialog).submit(function(event) {
                                return data.exists ? Auth.authorize(dialog) : Auth.signup(dialog);
                            });
                        },
                        close: function(dialog) {
                            submitIsRunning = false;
                        }
                    });
                }
            });
        });
    },

    authorize: function(dialog) {
        $.ajax({
            url: './github/authorize',
            dataType: 'json',
            type: 'POST',
            data: {
                access_token: $('form#auth input[name="access_token"]').val(),
                username: $('form#auth input[name="username"]').val(),
                password: $('input[name="password"]', dialog).val(),
            }, 
            success: function(data) {
                var password = new LiveValidation('pass1', {onlyOnSubmit: true});
                password.add(Validate.Custom, {
                    failureMessage: "Authentication failed! Please try again.",
                    against: function() {
                        return (data.success);
                    }
                });
                massValidation = LiveValidation.massValidate([password]);
                if (massValidation) {
                    // success log in, let's take it to the worklist
                    window.location.href = './';
                }

            }
        });
        return false;
    },

    signup: function(dialog) {
        var password = new LiveValidation('pass1', {onlyOnSubmit: true});
        password.add(Validate.Presence);
        password.add(Validate.Length, { minimum: 5, maximum: 255 } );
        var pass2 = new LiveValidation('pass2', {onlyOnSubmit: true});
        pass2.add(Validate.Presence);
        pass2.add(Validate.Length, { minimum: 5, maximum: 255 } );
        pass2.add(Validate.Confirmation, {match: 'pass1'} );

        if (LiveValidation.massValidate([password, pass2])) {
            $.ajax({
                url: './github/signup',
                dataType: 'json',
                type: 'POST',
                data: {
                    access_token: $('form#auth input[name="access_token"]').val(),
                    username: $('form#auth input[name="username"]').val(),
                    password: $('input[name="password"]', dialog).val(),
                    password2: $('input[name="password2"]', dialog).val(),
                }, 
                success: function(data) {
                    if (!data.success) {
                        alert(data.msg);
                        return;
                    }
                    $(dialog).modal('hide');
                    Utils.emptyModal({
                        title: 'Email confirmation', 
                        content: data.msg, 
                        close: function() {
                            window.location.href = './';
                        }
                    });
                }
            });
        }
        return false;
    }
};

