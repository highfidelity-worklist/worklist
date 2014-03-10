<?php

/**
 *
 * Objects required for the handling of GitHub integration
 * 
 * @author      Leonardo Murillo (leonardo@murillodigital.com)
 * @copyright   2010-2012 High Fidelity Inc.
 *  
 */

/*
 * 
 */
class GitHubUser extends User
{
    public function __construct($userID) {
        $this->findUserById((int)$userID);
    }
    
    public function processConnectResponse($gitHubId, $gitHubSecret) {
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
                $GitHubProject = new GitHubProject($gitHubId, $gitHubSecret);
                return $GitHubProject->makeApiRequest('login/oauth/access_token', 'POST', false, $params);
            } else {
                return array(
                    'error' => true,
                    'message' => $message,
                    'data' => $data);
            }
        }
    }
    
    public function storeCredentials($gitHubToken, $gitHubId) {
        $sql = "INSERT INTO `" . USERS_AUTH_TOKENS . "` (`user_id`, `github_id`, `auth_token`)
            VALUES ('" . (int)$this->id . "',
            '" . mysql_real_escape_string($gitHubId) . "',
            '" . mysql_real_escape_string($gitHubToken) . "')";
        $result = mysql_query($sql);
        if ($result) {
            return true;
        }

        // token already exists - update it
        $sql = "UPDATE `" . USERS_AUTH_TOKENS . "`
            SET `auth_token` = '" . mysql_real_escape_string($gitHubToken) . "'
            WHERE `user_id` = " . (int)$this->id . " AND `github_id` = '" . mysql_real_escape_string($gitHubId) . "'";
        mysql_query($sql);
        return false;
    }
    
    public function verifyForkExists(Project $project) {
        //
        $GitHubProject = new GitHubProject();
        $repoDetails = $GitHubProject->extractOwnerAndNameFromRepoURL($project->getRepository());
        $listOfRepos = $this->getListOfReposForUser($project);
        if ($listOfRepos === null) {
            return false;
        }
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
    
    public function createForkForUser(Project $project) {
        $token = $this->authTokenForGitHubId($project->getGithubId());
        $GitHubProject = new GitHubProject($project->getGithubId(), $project->getGithubSecret());
        $repoDetails = $GitHubProject->extractOwnerAndNameFromRepoURL($project->getRepository());
        $path = 'repos/' . $repoDetails['owner'] . '/' . $repoDetails['name'] . '/forks';
        return $GitHubProject->makeApiRequest($path, 'POST', $token, false);
    }
    
    public function getListOfReposForUser(Project $project) {
        $token = $this->authTokenForGitHubId($project->getGithubId());
        if ($token == null) {
            return null;
        }
        $GitHubProject = new GitHubProject($project->getGithubId(), $project->getGithubSecret());
        return $GitHubProject->makeApiRequest('user/repos', 'GET', $token, false);
    }
    
    public function getListOfBranchesForUsersRepo(Project $project) {
        $listOfBranches = array();
        $latestMasterCommit = false;
        $data = array();
        $i = 0;
        $token = $this->authTokenForGitHubId($project->getGithubId());
        $GitHubProject = new GitHubProject($project->getGithubId(), $project->getGithubSecret());
        $repoDetails = $GitHubProject->extractOwnerAndNameFromRepoURL($project->getRepository());
        $userDetails = $this->getGitHubUserDetails($project);
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
    
    public function getGitHubUserDetails(Project $project) {
        $token = $this->authTokenForGitHubId($project->getGithubId());
        $GitHubProject = new GitHubProject($project->getGithubId(), $project->getGithubSecret());
        return $GitHubProject->makeApiRequest('user', 'GET', $token, false);
    }
    
    public function createBranchForUser($branch_name, Project $project) {
        $branchDetails = array();
        $token = $this->authTokenForGitHubId($project->getGithubId());
        $GitHubProject = new GitHubProject($project->getGithubId(), $project->getGithubSecret());
        $repoDetails = $GitHubProject->extractOwnerAndNameFromRepoURL($project->getRepository());
        $listOfBranches = $this->getListOfBranchesForUsersRepo($project);
        $latestCommit = $listOfBranches['latest_master_commit'];
        $userDetails = $this->getGitHubUserDetails($project);
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
    
    public function createPullRequest($branch_name, Project $project) {
        $token = $this->authTokenForGitHubId($project->getGithubId());
        $GitHubProject = new GitHubProject($project->getGithubId(), $project->getGithubSecret());
        $repoDetails = $GitHubProject->extractOwnerAndNameFromRepoURL($project->getRepository());
        $userDetails = $this->getGitHubUserDetails($project);
        $gitHubUsername = $userDetails['data']['login'];
        $path = 'repos/' . $repoDetails['owner'] . '/' . $repoDetails['name'] . '/pulls';
        $params = array(
            'title' => 'Code Review for Job #' . $branch_name,
            'body' => 'Code Review for Job #' . $branch_name . " - Workitem available at https://www.worklist.net/" . $branch_name,
            'head' => $gitHubUsername . ':' . $branch_name,
            'base' => 'master'
        );
        $pullRequestStatus = $GitHubProject->makeApiRequest($path, 'POST', $token, $params, true);
        return $pullRequestStatus;
    }
}
