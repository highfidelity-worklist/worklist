var newpassword;
var confirmpassword;

$(function() {
    newpassword = new LiveValidation('newpassword',{ validMessage: "You have an OK password.", onlyOnBlur: true });
    newpassword.add(Validate.Length, { minimum: 5, maximum: 255 } );
    confirmpassword = new LiveValidation('confirmpassword', {validMessage: "Passwords Match."});
    confirmpassword.add(Validate.Confirmation, { match: 'newpassword'} );

})