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
