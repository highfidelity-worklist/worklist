<?php

/**
 *
 * Objects required for the handling of GitHub integration
 * 
 * @author      Leonardo Murillo (leonardo@murillodigital.com)
 * @copyright   2010-2012 Below92 Inc.
 *  
 */

/*
 * 
 */
class GitHubUser extends User
{
    protected $github_connected;
    protected $github_secret;
    
    public function __construct($userID) {
        $this->findUserById((int)$userID);
    }
    
    public function processConnectResponse() {
        $error = isset($_REQUEST['error']) ? true : false;
        $message = $error ? $_REQUEST['error'] : false;
        $data = false;
        if (!$error) {
            // We should have a temporal code, lets verify that and get the actual token
            if (!$error && isset($_REQUEST['code'])) {
                $params = array(
                    'code' => $_REQUEST['code'],
                    'state' => $_REQUEST['state']
                );
                $GitHubProject = new GitHubProject();
                return $GitHubProject->makeApiRequest('login/oauth/access_token', 'POST', false, $params);
            } else {
                return array(
                    'error' => true,
                    'message' => $message,
                    'data' => $data);
            }
        }
    }
    
    public function storeCredentials($GitHubToken) {
        $this->setGithub_connected(true);
        $this->setGithub_token($GitHubToken);
        if ($this->save()) {
            return true;
        } else {
            return false;
        }
    }
    
    public function verifyForkExists($repository) {
        $GitHubProject = new GitHubProject();
        $repoDetails = $GitHubProject->extractOwnerAndNameFromRepoURL($repository);
        $listOfRepos = $this->getListOfReposForUser();
        $userRepos = $listOfRepos['data'];
        $i = 0;
        while ($i < count($userRepos)) {
            if ($userRepos[$i]['name'] == $repoDetails['name'] && $userRepos[$i]['fork'] == '1') {
                return true;
            }
            $i++;
        }
        return false;
    }
    
    public function createForkForUser($repository) {
        $token = $this->getGithub_token();
        $GitHubProject = new GitHubProject();
        $repoDetails = $GitHubProject->extractOwnerAndNameFromRepoURL($repository);
        $path = 'repos/' . $repoDetails['owner'] . '/' . $repoDetails['name'] . '/forks';
        return $GitHubProject->makeApiRequest($path, 'POST', $token, false);
    }
    
    public function getListOfReposForUser() {
         $token = $this->getGithub_token();
         $GitHubProject = new GitHubProject();
         return $GitHubProject->makeApiRequest('user/repos', 'GET', $token, false);
    }
    
    public function getListOfBranchesForUsersRepo($repository) {
        $listOfBranches = array();
        $latestMasterCommit = false;
        $data = array();
        $i = 0;
        $token = $this->getGithub_token();
        $GitHubProject = new GitHubProject();
        $repoDetails = $GitHubProject->extractOwnerAndNameFromRepoURL($repository);
        $userDetails = $this->getGitHubUserDetails();
        $gitHubUsername = $userDetails['data']['login'];
        $path = 'repos/' . $gitHubUsername . '/' . $repoDetails['name'] . '/branches';
        $rawOutput = $GitHubProject->makeApiRequest($path, 'GET', $token, false);
        while ($i < count($rawOutput['data'])) {
            $listOfBranches[] = $rawOutput['data'][$i]['name'];
            $i++;
        }
        $path2 = 'repos/' . $repoDetails['owner'] . '/' . $repoDetails['name'] . '/branches';
        $rawOutput2 = $GitHubProject->makeApiRequest($path2, 'GET', $token, false);
        $ii = 0;
        while ($ii < count($rawOutput2['data'])) {
            if ($rawOutput2['data'][$ii]['name'] == 'master') {
                $latestMasterCommit = $rawOutput2['data'][$ii]['commit']['sha'];
            }
            $ii++;
        }
        $data['branches'] = $listOfBranches;
        $data['latest_master_commit'] = $latestMasterCommit;
        return $data;
    }
    
    public function getGitHubUserDetails() {
        $token = $this->getGithub_token();
        $GitHubProject = new GitHubProject();
        return $GitHubProject->makeApiRequest('user', 'GET', $token, false);
    }
    
    public function createBranchForUser($branch_name, $repository) {
        $branchDetails = array();
        $token = $this->getGithub_token();
        $GitHubProject = new GitHubProject();
        $repoDetails = $GitHubProject->extractOwnerAndNameFromRepoURL($repository);
        $listOfBranches = $this->getListOfBranchesForUsersRepo($repository);
        $latestCommit = $listOfBranches['latest_master_commit'];
        $userDetails = $this->getGitHubUserDetails();
        $gitHubUsername = $userDetails['data']['login'];
        // Verify whether theres a branch named $branch_name already
        if (!in_array($branch_name, $listOfBranches['branches'])) {
            $path = 'repos/' . $gitHubUsername . '/' . $repoDetails['name'] . '/git/refs';
            $params = array(
                'ref' => 'refs/heads/' . $branch_name,
                'sha' => $latestCommit);
            $branchStatus = $GitHubProject->makeApiRequest($path, 'POST', $token, $params, true);
            if (!$branchStatus['error']) {
                $branchDetails['error'] = false;
                $branchDetails['data'] = $branchStatus['data'];
                $branchDetails['branch_url'] = 'https://github.com/' . $gitHubUsername . "/" . $repoDetails['name'] . '/tree/' . $branch_name;
                return $branchDetails;
            }
        }
        return false;
    }
    
    public function createPullRequest($branch_name, $repository) {
        $token = $this->getGithub_token();
        $GitHubProject = new GitHubProject();
        $repoDetails = $GitHubProject->extractOwnerAndNameFromRepoURL($repository);
        $userDetails = $this->getGitHubUserDetails();
        $gitHubUsername = $userDetails['data']['login'];
        $path = 'repos/' . $repoDetails['owner'] . '/' . $repoDetails['name'] . '/pulls';
        $params = array(
            'title' => 'Code Review for Job #' . $branch_name,
            'body' => 'Code Review for Job #' . $branch_name . " - Workitem available at https://www.worklist.net/worklist/workitem.php?job_id=" . $branch_name . "&action=view",
            'head' => $gitHubUsername . ':' . $branch_name,
            'base' => 'master'
        );
        $pullRequestStatus = $GitHubProject->makeApiRequest($path, 'POST', $token, $params, true);
        return $pullRequestStatus;
    }
    
}

/*
 * 
 */
class GitHubProject
{
    public function makeApiRequest(
                    $path,
                    $method,
                    $token,
                    $params = array(),
                    $json = false) {
        // Define response defaults
        $error = false;
        $message = false;
        $data = false;
        $postArray = array();
        $postString = '';
        $headers = array('Accept: application/json');
        
        // Define variables required for API
        if ($path == 'login/oauth/access_token') {
            $apiURL = 'https://github.com/' . $path;
        } else {
            $apiURL = GITHUB_API_URL . $path;
        }
        $github_id = GITHUB_ID;
        $github_secret = GITHUB_SECRET;
        $credentials = array(
            'client_id' => urlencode($github_id),
            'client_secret' => urlencode($github_secret)
        );
        
        $postArray = array_merge($params, $credentials);
        if ($json) {
            $postString = json_encode($params);
        } else {
            foreach ($postArray as $key => $value) {
                $postString .= $key . '=' . $value . '&';
            }
            rtrim($postString,'&');
        }
        
        // Initialize cURL
        $curl = curl_init();
        
        if ($method == 'POST') {
            if ($token && $path != 'login/oauth/access_token') {
                $headers[] = 'Authorization: token ' . $token;
            }
            curl_setopt($curl, CURLOPT_POST, count($postArray));
            curl_setopt($curl, CURLOPT_POSTFIELDS, $postString);
        } else if ($method == 'GET') {
            $apiURL .= '?' . $postString;
            if ($token && $path != 'login/oauth/access_token') {
                $apiURL .= (!empty($postString) ? '&' : '') . 'access_token=' . $token;
            }
        }
        
        //set the url, number of POST vars, POST data
        curl_setopt($curl, CURLOPT_URL, $apiURL);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        
        try {
            $apiResponse = curl_exec($curl);
            if ($apiResponse && !curl_errno($curl)) {
                $error = false;
                $message = "API Call executed successfuly";
                $data = json_decode($apiResponse, true);
            } elseif (curl_errno($curl)) {
                $error = true;
                $curlError = curl_error($curl);
                $message = "There was an error processing your request - ERR " . $curlError;
                $data = array(
                    'error' => $curlError);
            }
        } catch(Exception $ex) {
            $error = true;
            $message = $ex;
            $data = array(
                'error' => $ex
            );
        };
        
        return array(
            'error' => $error,
            'message' => $message,
            'data' => $data);
    }
    
    public function extractOwnerAndNameFromRepoURL($repoURL) {
        $repoDetails = array();
        // Get rid of protocol, domain and .git extension
        $removeFromString = array(
            'http://',
            'https://',
            'github.com',
            'www.github.com',
            '.git');
        $cleanedRepoURL = str_replace($removeFromString, '', $repoURL);
        $explodedRepoURL = explode('/', $cleanedRepoURL);
        $repoDetails['owner'] = $explodedRepoURL[1];
        $repoDetails['name'] = $explodedRepoURL[2];
        return $repoDetails;
    }
    
    public function pull_request($payload) {
        $headLabel = $payload->pull_request->head->label;
        $labelComponents = explode(':', $headLabel);
        $jobNumber = trim($labelComponents[1]);
        // Try to extract job number from head repository label
        if (preg_match('/^[0-9]{3,}$/', $labelComponents[1])) {
            $workItem = new WorkItem();
            // We have what looks like a workitem number, see if it exists
            // and if it does, we set job to completed and post comment to
            // journal
            if ($workItem->idExists($jobNumber)
                && $payload->pull_request->state == 'closed') {
                
                $workItem->loadById($jobNumber);
                $pullRequestNumber = $payload->pull_request->number;
                $pullRequestURL = $payload->pull_request->html_url;
                $pullRequestLink = '<a href=" ' 
                    . $pullRequestURL . '" target="_blank">' 
                    . $pullRequestNumber . '</a>';
                $pullRequestBase = $payload->pull_request->base->label;
                $pullRequestStatus = $payload->pull_request->merged == 'true' 
                    ? "closed and merged"
                    : "closed but not merged";
                $message = "
                    Job #{$jobNumber} - Pull request {$pullRequestLink} has
                    been {$pullRequestStatus} into {$pullRequestBase}";

                $data = array(
                        'user'      => JOURNAL_API_USER,
                        'pwd'       => JOURNAL_API_PWD,
                        'message'   => $message );

                postRequest(JOURNAL_API_URL, $data);
                
                $workItem->setStatus('COMPLETED');
                $workItem->setJobCompletedFees();
                $workItem->save();
                
            }
        }
    }
    
}

?>