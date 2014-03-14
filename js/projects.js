var WorklistProject = {
    repo_type: false,

    init: function() {
        WorklistProject.populateProjectListing();
        if (this.repo_type == 'git') {
            WorklistProject.sendEmails();
        } else {
            WorklistProject.createDb();
        }
    },

    createDb: function() {
        WorklistProject.apiCall('createDatabaseNewProject', 'project=' + projectName + '&username=' + dbuser, function(response) {
            if (response && response['success']) {
                $('#db-status').html("Database created <span class='success'>✔</span>");
            } else {
                $('#db-status').html("Error occurred while creating database <span class='error'>✖</span>");
            }
            setTimeout(function() {
                WorklistProject.createRepo();
            }, 5000);
        });
    },

    createRepo: function() {
        var data = 'project=' + projectName + '&username' + username + '&nickname=' + nickname + '&unixusername=' + unixname;
        WorklistProject.apiCall('createRepo', data, function(response) {
            if (response && response['success']) {
                $('#repo-status').html("Repository created <span class='success'>✔</span>");
            } else {
                $('#repo-status').html("Error occurred while creating repository <span class='error'>✖</span>");
            }
            setTimeout(function() {
                WorklistProject.addPostCommitHook();
                WorklistProject.deployStagingSite();
                WorklistProject.createSandbox();
            }, 5000);
        });
    },

    createSandbox: function() {
        WorklistProject.apiCall('createSandbox', 
                                'projectname=' + projectName + '&username=' + username + '&nickname=' + nickname + '&unixusername=' + unixname + '&newuser=' + newuser + '&dbuser=' + dbuser, 
                                function(response) {
            if (response && response['success']) {
                $('#sandbox-status').html("Sandbox created <span class='success'>✔</span>");
            } else {
                $('#sandbox-status').html("Error occurred while creating sandbox <span class='error'>✖</span>");
            }
            setTimeout(function() {
                WorklistProject.modifyConfigFile();
                WorklistProject.sendEmails();
            }, 5000)
        });
    },

    sendEmails: function() {
        WorklistProject.apiCall('sendNewProjectEmails', 
                                'projectname=' + projectName + 
                                '&username=' + username + 
                                '&nickname=' + nickname + 
                                '&unixusername=' + unixname + 
                                '&template=' + template + 
                                '&dbuser=' + dbuser + 
                                '&repo_type=' + this.repo_type +
                                '&github_repo_url=' + github_repo_url, 
                                function(response) {
            if (response && response['success']) {
                $('#emails-status').html("Emails sent <span class='success'>✔</span>");
            } else {
                $('#emails-status').html("Error occurred while sending emails <span class='error'>✖</span>");
            }
            $('#project-completed').show();
        });
    },
    
    modifyConfigFile: function() {
        WorklistProject.apiCall('modifyConfigFile', 'projectname=' + projectName + '&username=' + username + '&nickname=' + nickname + '&unixusername=' + unixname + '&template=' + template + '&dbuser=' + dbuser, 
                                function(response) {
            if (response && response['success']) {
                return true;
            } else {
                return false;
            }
        });
    },
    
    addPostCommitHook: function() {
        WorklistProject.apiCall('addPostCommitHook', 'repo=' + projectName, function(response) {
            if (response && response['success']) {
                return true;
            } else {
                return false;
            }                                  
        })
    },
    
    deployStagingSite: function() {
        WorklistProject.apiCall('deployStagingSite', 'repo=' + projectName, function(response) {
            if (response && response['success']) {
                return true;
            } else {
                return false;
            }                                  
        })
    },

    apiCall: function(api, args, callback) {
        $.ajax({
            url: 'api.php?action=' + api + '&' + args,
            type: "GET",
            dataType: 'json',
            success: function(json) {
                if (callback && typeof callback  === 'function') {
                    callback(json);
                }
            },
            error: function() {
                if (callback && typeof callback  === 'function') {
                    callback(false);
                }
            }
        });
    },

    populateProjectListing: function() {
        $.ajax({
            type: "GET",
            url: 'api.php?action=getProjects',
            dataType: 'json',
            success: function(json) {

                // Clear all contents on screen
                $('#projects').empty();

                for (var i = 0; i < json.length; i++) {
                    WorklistProject.addProjectDetails(json[i]);
                }

                setTimeout(function() {
                    $('#projects').infinitescroll({
                        animate: true,
                        dataType: 'json',
                        debug: true,
                        appendCallback: false,
                        navSelector: '#page-nav',
                        nextSelector: '#page-nav a',
                        itemSelector: '#projects article',
                        extraScrollPx: 350,
                        loading: {
                            msgText: 'Loading the next set of projects...',
                            finishedMsg: 'No more pages to load'
                        }
                    }, function(json, opts) {
                        for (var i = 0; i < json.length; i++) {
                            WorklistProject.addProjectDetails(json[i]);
                        }
                    });

                    // kill scroll binding
                    $(window).unbind('.infscr');

                    // hook up the manual click guy.
                    $('#all-projects').on('click', function(event) {
                        event.preventDefault();
                        $('#projects').infinitescroll('retrieve');
                        $(this).text('More');
                        return false;
                    });
                }, 1);

                // remove the paginator when we're done.
                /*
                $(document).ajaxError(function(e, xhr, opt){
                    if (xhr.status == 404) {
                        $('a#next').remove();
                    }
                });
                */

            },
            error: function() {
                alert("error in populateProjectListing");
            }
        })
    },

    addProjectDetails: function(json) {
        var project = '';
        var image_filename;
        var description = '';
        var link = '';

        link = encodeURIComponent(json.name);

        if (json.logo === null || json.logo === "") {
            image_filename = 'images/emptyLogo.png';
        } else {
            image_filename = 'uploads/' + json.logo;
        }

        if (json.description.length > 500) {
            description = json.description.substring(0, 500) + '... <a href="' + link + '">[ read more ]</a>';
        } else {
            description = json.description;
        }

        project += '<article id="project-' + json.project_id + '">';
        project += '<a href="' + link + '"><img src="' + image_filename + '" border="1" width="48" height="48"  title="Last commit: ' + json.last_commit + '" /></a>';

        project += '<h2><div><a href="' + link + '">' + json.name + '</a><span class="fading">&nbsp;</span></div></h2>';
        project += '<section class="description">' + description + '</section>';
        project += '<ul class="stats">';
        project += '<li><a href="./jobs?status=bidding&project=' + json.name + '"><strong>' + json.bCount + ' jobs in bidding</strong></a></li>';
        project += '<li><a href="./jobs?status=completed&project=' + json.name + '">' + json.cCount+ ' jobs completed</a></li>';
        project += '<li>$' + json.feesCount + ' spent</li>';
        project += '</ul>';
        project += '</article>';

        $('#projects').append(project);
    }
};

$(function() {
    WorklistProject.init();
});