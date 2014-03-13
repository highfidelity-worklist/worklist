
$(function() {
    var oldpasword = new LiveValidation('oldpassword', {validMessage: ' '});
    summary.add(Validate.Presence, {failureMessage: "You must enter the job title!"});
    var newpassword = new LiveValidation('newpassword',{ validMessage: ' ', onlyOnBlur: true });
    newpassword.add(Validate.Length, { minimum: 5, maximum: 255 } );
    var confirmpassword = new LiveValidation('confirmpassword', {validMessage: ' '});
    confirmpassword.add(Validate.Confirmation, { match: 'newpassword'} );

    $('form#password').submit(function(event) {
        console.log(LiveValidation.massValidate([oldpassword, newpassword, confirmpassword]));
        return false;
    })
})