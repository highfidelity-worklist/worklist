$(function() {
    WorklistProject.populateProjectListing();
    if (this.repo_type == 'git') {
        WorklistProject.sendEmails();
    } else {
        WorklistProject.createDb();
    }
    WorklistProject.init();
});