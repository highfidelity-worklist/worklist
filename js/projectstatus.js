$(function() {
    if (!errorOut) {
        if (!GitHub.validate()) {
            GitHub.handleUserConnect();
        } else {
            WorklistProject.sendEmails();
        }
    } else {
        $('#project-status').html('<h3>Something went wrong! ' + errorOut + '</h3>');
    }
});
