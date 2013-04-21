var GitHub = {
    isConnected: false,
    token: false,
    applicationKey: false,
    
    validate: function() {
        return WorklistProject.repo_type == 'git' 
               && !GitHub.isConnected ? false : true;
    },
    
    handleUserConnect: function(autoclose) {
        if(typeof(autoclose) === 'undefined') autoclose = true;
        var gitHubConnectWindow = window.open(
            'https://github.com/login/oauth/authorize?client_id=' + GitHub.applicationKey + '&scope=user,repo',
            'GitHubConnect',
            'width=980,height=450,resizable=no,scrollbars=no,toolbar=no,location=no'
        );
        // Wait for the popup to close, with a maximum number of seconds for interval to time out :P
        
        if (autoclose) {
            var counter = 0;
            var gitHubConnectTimer = setInterval(function() {
                counter++;
                if (counter > 60 || gitHubConnectWindow.closed) {
                    clearInterval(gitHubConnectTimer);
                    window.location.reload();
                }
            }, 1000)
        }
    }
    
}