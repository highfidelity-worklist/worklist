var GitHub = {
    isConnected: false,
    token: false,
    applicationKey: false,
    
    validate: function() {
        return GitHub.isConnected;
    },
    
    handleUserConnect: function(autoclose) {
        if(typeof(autoclose) === 'undefined') {
            autoclose = true;
        }
        var client_id = GitHub.applicationKey;
        var redirect_uri = encodeURIComponent(worklistUrl + 'GitHub.php?project=' + project_id);

        var gitHubConnectWindow = window.open(
             //https://github.com/login/oauth/authorize
            'https://github.com/login/oauth/authorize?client_id=' + client_id + '&scope=user,repo&redirect_uri=' + redirect_uri,
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
};
