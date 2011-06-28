<?php 
//  Copyright (c) 2010, LoveMachine Inc.
//  All Rights Reserved. 
//  http://www.lovemachineinc.com

//  This class handles a Json Requests if you need more functionality don't hesitate to add it.
//  But please be as fair as you comment your methods - maybe another developer needs them too.

class JsonServer
{
    
    protected $output;
    protected $user;
    protected $request;
    
    /**
     * If an action is passed and the method exists it will call this method.
     * 
     * @param (string) $action
     * @param (mixed) $arguments
     */
    public function __construct()
    {

    }
    
    /**
     * Get the output
     *
     * @return (string) $this->output
     /*/    
    public function getOutput()
    {
        if (null === $this->output) {
            $this->setOutput(array(
                'success' => false,
                'message' => 'No output!'
            ));
        }
        return $this->output;
    }
    
    /**
     * Sets the output property and json_encodes it.
     * 
     * @param (array) $output
     * @return JsonServer $this
     */
    public function setOutput(array $output)
    {
        $this->output = json_encode($output);
    }
    
    public function run()
    {
        $method = 'action' . ucfirst($this->getAction());
        if (!method_exists($this, $method)) {
            throw new Exception('Action does not exit!');
        }
        
        $this->$method();
    }
    
    /**
     * @return the $action
     */
    public function getAction()
    {
        return $this->getRequest()->getActionName();
    }

    /**
     * @return the $user
     */
    public function getUser()
    {
        if (null === $this->user) {
            $this->setUser();
        }
        return $this->user;
    }

    /**
     * @return the $request
     */
    public function getRequest()
    {
        if (null === $this->request) {
            $this->setRequest();
        }
        return $this->request;
    }

    /**
     * This method gets the active user
     */
    public function setUser()
    {
        $user = new User();
        $user->findUserById($_SESSION['userid']);
        $this->user = $user;
    }

    /**
     * Here we set the JsonServer_Request
     */
    public function setRequest()
    {
        $this->request = new JsonServer_Request();
    }

    /**
     * This method approves a user
     * 
     * @param (array) $args
     */
    protected function actionApproveUser($args = null)
    {
        if (null === $this->getRequest()->getParam('userid')) {
            throw new Exception('User ID not set!');
        }

        if ($this->getUser()->isRunner()) {
            $user = new User();
            $user->findUserById($this->getRequest()->getParam('userid'));
            $user->setHas_w9approval(1);

            if ($user->save()) {
                return $this->setOutput(array(
                    'success' => true,
                    'message' => 'The user ' . $user->getNickname() . ' has been approved!'
                ));
            } else {
                return $this->setOutput(array(
                    'success' => false,
                    'message' => 'Something went wrong, try it again later.'
                ));
            }
        } else {
            return $this->setOutput(array(
                'success' => false,
                'message' => 'You are not allowed to do that!'
            ));
        }
    }
    
    /**
     * This method checks the approval status of a user
     */
    protected function actionApprovalStatus()
    {
        if (null === $this->getRequest()->getParam('userid')) {
            throw new Exception('User ID not set!');
        }
        $user = new User();
        $user->findUserById($this->getRequest()->getParam('userid'));
        
        if ($user->isW9Approved()) {
            return $this->setOutput(array(
                'success' => true,
                'approved'=> true,
                'message' => 'The user ' . $user->getNickname() . ' is approved!'
            ));
        } else {
            return $this->setOutput(array(
                'success' => true,
                'approved'=> false,
                'message' => 'The user ' . $user->getNickname() . ' is not approved!'
            ));
        }
    }
    
    /**
     * This method checks the approval status of a user
     */
    protected function actionIsUSCitizen()
    {
        if (null === $this->getRequest()->getParam('userid')) {
            throw new Exception('User ID not set!');
        }
        $user = new User();
        $user->findUserById($this->getRequest()->getParam('userid'));
        
        if ($user->isUsCitizen()) {
            return $this->setOutput(array(
                'success' => true,
                'isuscitizen'=> true,
                'message' => 'The user ' . $user->getNickname() . ' is an US citizen!'
            ));
        } else {
            return $this->setOutput(array(
                'success' => true,
                'isuscitizen'=> false,
                'message' => 'The user ' . $user->getNickname() . ' is not an US citizen!'
            ));
        }
    }
    
    /**
     * This method removes a file from a workitem
     */
    protected function actionFileRemove()
    {
        if(isset($_SESSION['userid']) && $_SESSION['userid'] > 0) {
            
            $fileid = $this->getRequest()->getParam('fileid');
            $file = new File();
            $file->findFileById($fileid);
            
            require_once(APP_PATH . '/workitem.class.php');
            try {
                $workitem = Workitem::getById($file->getWorkitem());
            } catch (Exception $e) {}
            if (
              $_SESSION['is_runner'] || 
              $_SESSION['is_payer'] ||
              $_SESSION['userid'] == $file->getUserid() ||  
              $_SESSION['userid'] == $workitem->getCreatorId() ||
              $_SESSION['userid'] == $workitem->getMechanicId() ||
              $_SESSION['userid'] == $workitem->getRunnerId()
            ) {
                $success = $file->remove();
            } else {
                $success = array(
                    'success' => false,
                    'message' => 'Permission denied!'
                );
            }
        } else {
            $success = array(
                'success' => false,
                'message' => 'Permission denied!'
            );
        }
        return $this->setOutput($success);
    }
    
    /**
     * This method adds a file to a workitem
     */
    protected function actionFileUpload()
    {
        if(!isset($_SESSION['userid']) || !($_SESSION['userid'] > 0)) {
            return $this->setOutput(array(
                'success' => false,
                'message' => 'Not enough rights!'
            ));
        }

        // check if we have a error
        if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            return $this->setOutput(array(
                'success' => false,
                'message' => File::fileUploadErrorMessage($_FILES['file']['error'])
            ));
        }
        
        if ((isset($_FILES['file']['type']) && !empty($_FILES['file']['type']))) {
            $mime = $_FILES['file']['type'];
        } else {
            $mime = 'application/pdf';
        }
        $ext = end(explode(".", $_FILES['file']['name']));
        $fileName = File::uniqueFilename($ext);
        $tempFile = $_FILES['file']['tmp_name'];
        $title = basename($_FILES['file']['name']);
        $path = UPLOAD_PATH . '/' . $fileName;
        
        
        
        if (copy($tempFile, $path)) {
            $url = SERVER_URL . 'uploads/' . $fileName;
            $workitem = $this->getRequest()->getParam('workitem');
            $workitem = (is_numeric($workitem) ? $workitem : null);
            $projectid = $this->getRequest()->getParam('projectid');
            $projectid = (is_numeric($projectid) ? $projectid : null);
            
            $file = new File();
            $file->setMime($mime)
                 ->setUserid($this->getRequest()->getParam('userid'))
                 ->setWorkitem($workitem)
                 ->setProjectId($projectid)
                 ->setTitle($title)
                 ->setUrl($url);
            $success = $file->save();
            
            $icon = File::getIconFromMime($file->getMime());
            if ($icon === false) {
                $filetype = 'image';
                $icon = $file->getUrl();
            }
            
            return $this->setOutput(array(
                'success' => true,
                'fileid'  => $file->getId(),
                'url'       => $file->getUrl(),
                'icon'      => $icon,
                'title'      => $file->getTitle(),
                'description' => '',
                'filetype'=> (isset($filetype) ? $filetype : '')
            ));
        } else {
            return $this->setOutput(array(
                'success' => false,
                'message' => 'An error occured while uploading the  '.$tempFile.','. $path.' please try again!'
            ));
        }
        
    }
    
    protected function actionChangeFileTitle()
    {
        if(isset($_SESSION['userid']) && $_SESSION['userid'] > 0) {
            $fileid = $this->getRequest()->getParam('fileid');
            $title = $this->getRequest()->getParam('value');
            
            $file = new File();
            $file->findFileById($fileid);

            require_once(APP_PATH . '/workitem.class.php');
            $workitem = Workitem::getById($file->getWorkitem());
            if (
              $_SESSION['is_runner'] || 
              $_SESSION['is_payer'] ||
              $_SESSION['userid'] == $file->getUserid() ||  
              $_SESSION['userid'] == $workitem->getCreatorId() ||
              $_SESSION['userid'] == $workitem->getMechanicId() ||
              $_SESSION['userid'] == $workitem->getRunnerId()
            ) {
                $file->setTitle((string)$title);
                $success = $file->save();
            }
        }
        die(isset($title) ? $title : '');
    }
    
    protected function actionChangeFileDescription()
    {
        if(isset($_SESSION['userid']) && $_SESSION['userid'] > 0) {
            $fileid = $this->getRequest()->getParam('fileid');
            $description = $this->getRequest()->getParam('value');
        
            $file = new File();
            $file->findFileById($fileid);
            require_once(APP_PATH . '/workitem.class.php');
            $workitem = Workitem::getById($file->getWorkitem());
            if (
              $_SESSION['is_runner'] || 
              $_SESSION['is_payer'] ||
              $_SESSION['userid'] == $file->getUserid() ||  
              $_SESSION['userid'] == $workitem->getCreatorId() ||
              $_SESSION['userid'] == $workitem->getMechanicId() ||
              $_SESSION['userid'] == $workitem->getRunnerId()
            ) {
                $file->setDescription((string)$description);
                $success = $file->save();
            }
        }
        die(isset($description) ? $description : '');
    }

    protected function actionChangeFileStatus()
    {
        if(isset($_SESSION['userid']) && $_SESSION['userid'] > 0) {
            $fileid = $this->getRequest()->getParam('fileid');
            $status = $this->getRequest()->getParam('status');
            
            $file = new File();
            $file->findFileById($fileid);
            
            require_once(APP_PATH . '/workitem.class.php');
            $workitem = Workitem::getById($file->getWorkitem());
            if (
              $_SESSION['is_runner'] || 
              $_SESSION['is_payer'] ||
              $_SESSION['userid'] == $file->getUserid() ||  
              $_SESSION['userid'] == $workitem->getCreatorId() ||
              $_SESSION['userid'] == $workitem->getMechanicId() ||
              $_SESSION['userid'] == $workitem->getRunnerId()
            ) {
                $file->setStatus((int)$status);
                $success = $file->save();
            }
        } else {
            $success = false;
        }
        return $this->setOutput(array(
            'success' => $success
        ));
    }
    
    protected function actionGetFilesForWorkitem()
    {
        $files = File::fetchAllFilesForWorkitem($this->getRequest()->getParam('workitem'));
        $user = new User();
        $user->findUserById($this->getRequest()->getParam('userid'));
        $data = array(
            'images' => array(),
            'documents' => array()
        );
        foreach ($files as $file) {
            if (!File::isAllowed($file->getStatus(), $user)) {
                continue;
            }
            $icon = File::getIconFromMime($file->getMime());
            if ($icon === false) {
                array_push($data['images'], array(
                    'fileid'=> $file->getId(),
                    'url'    => $file->getUrl(),
                    'icon'    => $file->getUrl(),
                    'title' => $file->getTitle(),
                    'description' => $file->getDescription()
                ));
            } else {
                array_push($data['documents'], array(
                    'fileid'=> $file->getId(),
                    'url'    => $file->getUrl(),
                    'icon'    => $icon,
                    'title' => $file->getTitle(),
                    'description' => $file->getDescription()
                ));
            }
        }
        
        return $this->setOutput(array(
            'success' => true,
            'data' => $data
        ));
    }

    protected function actionGetFilesForProject() {
        $files = File::fetchAllFilesForProject($this->getRequest()->getParam('projectid'));
        $user = new User();
        $user->findUserById($this->getRequest()->getParam('userid'));
        $data = array(
            'images' => array(),
            'documents' => array()
        );
        foreach ($files as $file) {
            if (!File::isAllowed($file->getStatus(), $user)) {
                continue;
            }
            $icon = File::getIconFromMime($file->getMime());
            if ($icon === false) {
                array_push($data['images'], array(
                    'fileid'=> $file->getId(),
                    'url'    => $file->getUrl(),
                    'icon'    => $file->getUrl(),
                    'title' => $file->getTitle(),
                    'description' => $file->getDescription()
                ));
            } else {
                array_push($data['documents'], array(
                    'fileid'=> $file->getId(),
                    'url'    => $file->getUrl(),
                    'icon'    => $icon,
                    'title' => $file->getTitle(),
                    'description' => $file->getDescription()
                ));
            }
        }
        
        return $this->setOutput(array(
            'success' => true,
            'data' => $data
        ));
    }

    /**
     * This method handles the upload of the local tax form
     *
     */
    protected function actionLocalUpload()
    {
        // check if we have a file
        if (empty($_FILES)) {
            return $this->setOutput(array(
                'success' => false,
                'message' => 'No file uploaded!'
            ));
        }
        
        $tempFile = $_FILES['Filedata']['tmp_name'];
        $path = UPLOAD_PATH . '/' . $this->getRequest()->getParam('userid') . '_Local.pdf';
        
        if (move_uploaded_file($tempFile, $path)) {
            $user = new User();
            $user->findUserById($this->getRequest()->getParam('userid'));
            $subject = "Local Tax Form from " . $user->getNickname();
            $body = "<p>Hi there,</p>";
            $body .= "<p>" . $user->getNickname() . " just uploaded his/her Local Tax Form you can download and approve it from this URL:</p>";
            $body .= "<p><a href=\"" . SERVER_URL . "uploads/" . $user->getId() . "_Local.pdf\">Click here</a></p>";
                
            if (!send_email(FINANCE_EMAIL, $subject, $body)) { error_log("JsonServer:LocalUpload: send_email failed"); }
            
            return $this->setOutput(array(
                'success' => true,
                'message' => 'The file ' . basename( $_FILES['Filedata']['name']) . ' has been uploaded.'
            ));
        } else {
            return $this->setOutput(array(
                'success' => false,
                'message' => 'An error occured while uploading the file, please try again!'
            ));
        }
    }
    /**
     * This method handles the upload of the W9 form
     *
     */
    protected function actionW9Upload()
    {
        // check if we have a file
        if (empty($_FILES)) {
            return $this->setOutput(array(
                'success' => false,
                'message' => 'No file uploaded!'
            ));
        }
        
        $tempFile = $_FILES['Filedata']['tmp_name'];
        $path = UPLOAD_PATH . '/' . $this->getRequest()->getParam('userid') . '_W9.pdf';
        
        if (move_uploaded_file($tempFile, $path)) {
            $user = new User();
            $user->findUserById($this->getRequest()->getParam('userid'));
            $subject = "W-9 Form from " . $user->getNickname();
            $body = "<p>Hi there,</p>";
            $body .= "<p>" . $user->getNickname() . " just uploaded his/her W-9 Form you can download and approve it from this URL:</p>";
            $body .= "<p><a href=\"" . SERVER_URL . "uploads/" . $user->getId() . "_W9.pdf\">Click here</a></p>";
            
            if(!send_email(FINANCE_EMAIL, $subject, $body)) { error_log("JsonServer:w9Upload: send_email failed"); }
            
            return $this->setOutput(array(
                'success' => true,
                'message' => 'The file ' . basename( $_FILES['Filedata']['name']) . ' has been uploaded.'
            ));
        } else {
            return $this->setOutput(array(
                'success' => false,
                'message' => 'An error occured while uploading the file, please try again!'
            ));
        }
    }
    
    protected function actionChangeUserStatus()
    {
        $aUser = $this->getUser();
        if ($aUser->isRunner()) {
            $user = new User();
            $user->findUserById($this->getRequest()->getParam('userid'));
            $user->setIs_active($this->getRequest()->getParam('status'));
            $user->save();
            return $this->setOutput(array(
                'success' => true
            ));
        }
        return $this->setOutput(array(
            'success' => false
        ));
        
    }
    
    /**
     * This method checks if the user is allowed to bid in the W9 context
     */
    protected function actionCheckUserForW9()
    {
        $user = new User();
        $user->findUserById($this->getRequest()->getParam('userid'));
        
        // If user is no US citizen we don't need the 10099
        if (!$user->isUsCitizen() || $user->isW9Approved()) {
            return $this->setOutput(array(
                'success' => true,
                'message' => 'The user ' . $user->getNickname() . ' is not a US Citizen or has been approved earlier!'
            ));
        }

        // Now we need to get the fee amount
        $sql     =     'SELECT SUM(`amount`) AS `sum_amount` FROM `' . FEES . '` WHERE ';
        // Get the right userfees
        $sql    .=    '`user_id` = ' . $user->getId() . ' ';
        // Only fees that haven't been withdrawn
        $sql    .=    'AND `withdrawn` = 0 ';
        // Status should be DONE
        $sql    .=    'AND `worklist_id` IN (SELECT `id` FROM `worklist` WHERE `status` = "DONE") ';
        // We only need this year
        $sql    .=    'AND YEAR(`date`) = YEAR(NOW()) ';

        // now we fetch the sum
        $result = mysql_query($sql);
        $fees = mysql_fetch_object($result)->sum_amount;
        if (!is_numeric($fees)) {
            return $this->setOutput(array(
                'success' => true,
                'message' => 'The users ' . $user->getNickname() . ' fees are not numeric, which means he has no paid fees.'
            ));
        } else if (((int)$fees + (int)$this->getRequest()->getParam('amount')) < 600) {
            return $this->setOutput(array(
                'success' => true,
                'message' => 'The users ' . $user->getNickname() . ' amount ($' . ((int)$fees + (int)$this->getRequest()->getParam('amount')) . ') does not exceed $600.'
            ));
        }

        return $this->setOutput(array(
            'success' => false,
            'message' => 'The users ' . $user->getNickname() . ' amount ($' . ((int)$fees + (int)$this->getRequest()->getParam('amount')) . ') does exceed $600.'
        ));
    }

    protected function actionSendTestSMS()
    {
        $phone = $this->getRequest()->getParam('phone');
            try {
            notify_sms_by_id($_SESSION['userid'], 'Test SMS', 'Test from LoveMachine') or error_log("failed to create SMS message");
            } catch (Sms_Backend_Exception $e) {
                return $this->setOutput(array(
                    'success' => false,
                    'message' => 'Failed to send test message !'
                ));        
            }
        return $this->setOutput(array(
            'success' => true,
            'message' => 'Test message sent!'
        ));        
    }
    
    protected function actionChangeRunner()
    {
        $workitem = (int)$this->getRequest()->getParam('workitem');
        $runner = new User();
        if ($this->getUser()->isRunner()) {
            if ($runner->findUserById($this->getRequest()->getParam('runner')) && $runner->isRunner()) {
                require_once(APP_PATH . '/workitem.class.php');
                $workitem = new Workitem($workitem);
                $oldRunner = $workitem->getRunner();
                $workitem->setRunnerId($runner->getId())
                         ->save();
                
                $subject = 'Runner #' . $workitem->getId() . ' has been changed';
                $body = "<p>Hi there,</p>";
                $body .= "<p>I just wanted to let you know that the Job #" . $workitem->getId() . " (" . $workitem->getSummary() . ") has been reassigned to Runner " . $runner->getNickname() . ".</p>";
                $body .= "<p>See you in the Workroom!</p>";
                                
                if ($oldRunner) {
                    if(!send_email($oldRunner->getNickname() . ' <' . $oldRunner->getUsername() . '>', $subject, $body)) { error_log("JsonServer:changeOldRunner failed"); }
                }
                if ($workitem->getRunner()) {
                    if(!send_email($workitem->getRunner()->getNickname() . ' <' . $workitem->getRunner()->getUsername() . '>', $subject, $body)) { error_log("JsonServer:changeGetRunner: send_email failed"); }
                }
                if ($workitem->getCreator()) {
                    if(!send_email($workitem->getCreator()->getNickname() . ' <' . $workitem->getCreator()->getUsername() . '>', $subject, $body)) { error_log("JsonServer:changeCreator: send_email failed"); }
                }
                if ($workitem->getMechanic()) {
                    if(!send_email($workitem->getMechanic()->getNickname() . ' <' . $workitem->getMechanic()->getUsername() . '>', $subject, $body)) { error_log("JsonServer:changeMechanic: send_email failed"); }
                }
                
                sendJournalNotification($this->getUser()->getNickname() . ' updated Job #' . $workitem->getId() . ': ' . $workitem->getSummary() . '. Runner reassigned to ' . $workitem->getRunner()->getNickname());
                
                return $this->setOutput(array(
                    'success' => true,
                    'nickname' => $runner->getNickname()
                ));
            } else {
                return $this->setOutput(array(
                    'success' => false,
                    'message' => 'The user specified as new runner is no runner!'
                ));
            }
        } else {
            return $this->setOutput(array(
                'success' => false,
                'message' => 'You are not allowed to do that!'
            ));
        }
    }

}
