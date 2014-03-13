$(function() {
    $('#country, #timezone').chosen();

    var username = new LiveValidation('username', {validMessage: ' '});
    username.add( Validate.Email );
    username.add(Validate.Length, { minimum: 4, maximum: 50 } );
    var password = new LiveValidation('password',{ validMessage: ' ' });
    password.add(Validate.Length, { minimum: 5, maximum: 255 } );
    var confirmpassword = new LiveValidation('confirmpassword', {validMessage: ' '});
    confirmpassword.add(Validate.Custom1, { match: 'password'} );
    var about = new LiveValidation('about');
    about.add(Validate.Length, { minimum: 0, maximum: 150 } );

    if (errorFlag) {
        openbox('Signup Confirmation');
    }
});

function openbox(formtitle) {
    $('#filter').css({display: 'block'});
    $('#box').css({display: 'block'});
}

function closebox() {
    $('#filter').css({display: 'none'});
    $('#box').css({display: 'none'});
    window.location = '.*/'
}
