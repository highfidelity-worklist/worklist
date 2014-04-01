$(function() {
    $('#country, #timezone').chosen();

    var username = new LiveValidation('username');
    username.add( Validate.Email );
    username.add(Validate.Length, { minimum: 4, maximum: 50 } );
    var password = new LiveValidation('password');
    password.add(Validate.Length, { minimum: 5, maximum: 255 } );
    var confirmpassword = new LiveValidation('confirmpassword');
    confirmpassword.add(Validate.Custom1, { match: 'password'} );
    var about = new LiveValidation('about');
    about.add(Validate.Length, { minimum: 0, maximum: 150 } );

    if (confirmTxt.length > 0) {
        $('#empty-modal .modal-title').text('Email confirmation');
        $('#empty-modal .modal-body').html(confirmTxt);
        $('#empty-modal .modal-footer > button:last-child').click(function() {
            window.location = './';
        });
        $('#empty-modal').modal('show');

    }
});
