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
     */
    public function getOutput()
    {
        if ($this->output === null) {
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

    protected function actionChangeRunner()
    {
        $workitem = (int)$this->getRequest()->getParam('workitem');
        $runner_id = (int) $this->getRequest()->getParam('runner');
        $runner = new User();
        
        if ($this->getUser()->isRunner()) {
            $workitem = new WorkItem($workitem);
            if ($runner->findUserById($runner_id) && $runner->isRunner()
                && Project::isAllowedRunnerForProject($runner_id, $workitem->getProjectId()) ) { 
                $oldRunner = $workitem->getRunner();
                $workitem->setRunnerId($runner->getId())
                         ->save();
                $project = new Project();
                $project->loadById($workitem->getProjectId());
                $project_name = $project->getName();
                $from_address = '<noreply-' . $project_name . '@worklist.net>';
                $headers = array('From' => '"' . $project_name . '-designer changed" ' . $from_address);

                // This should eventually be moved to Notification->workitemNotify - dans
                $subject = '#' . $workitem->getId() . ' '. $workitem->getSummary();
                $body = "<p>Hi there,</p>";
                $body .= "<p>I just wanted to let you know that the Job #" . $workitem->getId() . " (" . $workitem->getSummary() . ") has been reassigned to Designer " . $runner->getNickname() . ".</p>";
                $body .= "<p>See you in the Worklist!</p>";

                if ($oldRunner) {
                    if(!send_email($oldRunner->getNickname() . ' <' . $oldRunner->getUsername() . '>', $subject, $body, null, $headers)) { error_log("JsonServer:changeOldRunner failed"); }
                }
                if ($workitem->getRunner()) {
                    if(!send_email($workitem->getRunner()->getNickname() . ' <' . $workitem->getRunner()->getUsername() . '>', $subject, $body, null, $headers)) { error_log("JsonServer:changeGetRunner: send_email failed"); }
                }
                if ($workitem->getCreator()) {
                    if(!send_email($workitem->getCreator()->getNickname() . ' <' . $workitem->getCreator()->getUsername() . '>', $subject, $body, null, $headers)) { error_log("JsonServer:changeCreator: send_email failed"); }
                }
                if ($workitem->getMechanic()) {
                    if(!send_email($workitem->getMechanic()->getNickname() . ' <' . $workitem->getMechanic()->getUsername() . '>', $subject, $body, null, $headers)) { error_log("JsonServer:changeMechanic: send_email failed"); }
                }

                sendJournalNotification('\#' . $workitem->getId() . ' updated by @' . $this->getUser()->getNickname() .' Designer reassigned to @' . $workitem->getRunner()->getNickname());

                return $this->setOutput(array(
                    'success' => true,
                    'nickname' => $runner->getNickname()
                ));
            } else {
                return $this->setOutput(array(
                    'success' => false,
                    'message' => 'The user specified is not allowed designer for this project!'
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