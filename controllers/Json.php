<?php

/**
 * Base class for controllers migrated from the old JsonServer (such as FileController)
 */
class JsonController extends Controller {
    protected $output;
    protected $user;
    protected $request;

    public function __destruct()
    {
        if (!$this->internal) {
            echo $this->getOutput();
        }
    }

    /**
     * Get the output
     *
     * @return (string) $this->output
     */
    protected function getOutput()
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
    protected function setOutput(array $output)
    {
        $this->output = json_encode($output);
    }

    /**
     * @return the $action
     */
    protected function getAction()
    {
        return $this->getRequest()->getActionName();
    }

    /**
     * @return the $user
     */
    protected function getUser()
    {
        if (null === $this->user) {
            $this->setUser();
        }
        return $this->user;
    }

    /**
     * @return the $request
     */
    protected function getRequest()
    {
        if (null === $this->request) {
            $this->setRequest();
        }
        return $this->request;
    }

    /**
     * This method gets the active user
     */
    protected function setUser()
    {
        $user = new User();
        $user->findUserById($_SESSION['userid']);
        $this->user = $user;
    }

    /**
     * Here we set the JsonServer_Request
     */
    protected function setRequest()
    {
        $this->request = new JsonServer_Request();
    }
}