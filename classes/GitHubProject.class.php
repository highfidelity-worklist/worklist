<?php
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
                $pullRequestBase = $payload->pull_request->base->label;
                $pullRequestStatus = $payload->pull_request->merged == 'true' 
                    ? "closed and merged"
                    : "closed but not merged";
                $message = "
                    Job #{$jobNumber} - Pull request {$pullRequestNumber}
                    ({$pullRequestURL}) has been {$pullRequestStatus} into {$pullRequestBase}";

                sendJournalNotification($message);
                
                if ($payload->pull_request->merged == 'true') {
                    $journal_message = 
                        "Job #{$jobNumber} has been automatically set to COMPLETED";
                    sendJournalNotification($journal_message);
                    $workItem->setStatus('COMPLETED');
                    $workItem->addFeesToCompletedJob(true);
                    $workItem->save();
                }
            }
        }
    }
}
