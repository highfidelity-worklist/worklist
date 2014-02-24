$(function() {
    if (!errorOut) {
        WorklistProject.repo_type = repo_type;
        WorklistProject.init();
        if (!GitHub.validate()) {
            GitHub.handleUserConnect();
        }        
    } else {
        $('#project-status').html('<h3>Something went wrong! ' + errorOut + '</h3>');
    }
});