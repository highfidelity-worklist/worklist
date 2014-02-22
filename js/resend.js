$(function() {
    var username = new LiveValidation('username', {validMessage: "ok"});
    //username.add( Validate.Presence );
    username.add( Validate.Email );
    username.add(Validate.Length, { minimum: 10, maximum: 50 } );    
});