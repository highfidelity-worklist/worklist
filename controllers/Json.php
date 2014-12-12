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
     * This method gets the active user
     */
    protected function setUser()
    {
        $user = new User();
        $user->findUserById($_SESSION['userid']);
        $this->user = $user;
    }
}