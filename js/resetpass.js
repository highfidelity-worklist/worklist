
var password;
var confirmpassword;

$(function() {
    password = new LiveValidation('password',{ onlyOnBlur: true });
    password.add(Validate.Length, { minimum: 5, maximum: 255 } );

    confirmpassword = new LiveValidation('confirmpassword');
    //confirmpassword.add(Validate.Length, { minimum: 5, maximum: 12 } );
    confirmpassword.add(Validate.Confirmation, { match: 'password'} );

})

// @TODO: Why have we got custom validation here when we are using LiveValidation further down?
function validate() {

    if (document.frmlogin.username.value=="") {
        alert("Please enter your email");
        document.frmlogin.username.focus();
        return false;
    }
    else if (!(/^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})+$/.test(document.frmlogin.username.value))){
        alert("Invalid email address! please re-enter");
        document.frmlogin.username.focus();
        return false;
    }
    else if (document.frmlogin.password.value=="") {
        alert("Please enter your password");
        document.frmlogin.password.focus();
        return false;
    }
    else if (document.frmlogin.password.value!=document.frmlogin.confirmpassword.value) {
        alert("Your passwords don't match");
        document.frmlogin.confirmpassword.focus();
        return false;
    }
    else
        return true;
}
