<?php

class ResetPassController extends Controller {
    public function run() {
        $msg = '';

        if (! empty($_POST['submit'])) {
            if (! empty($_POST['password'])) {
                $user = new User();
                if ($user->findUserByUsername($_POST['username'])) {
                    if ($user->getForgot_hash() == $_REQUEST['token']) {

                        $password = '{crypt}' . Utils::encryptPassword($_POST['password']);
                        $user->setPassword($password)
                             ->setForgot_hash(md5(uniqid()))
                             ->save();

                        sendTemplateEmail($_POST['username'], 'changed_pass', array(
                            'app_name' => APP_NAME
                        ));

                        $this->view = null;
                        Utils::redirect('./login');
                    }

                } else {
                    $msg = 'The link to reset your password has expired or is invalid. <a href="./forgot">Please try again.</a>';
                }

            } else {
                $msg = "Please enter a password!";
            }
        }

        if (empty($_REQUEST['token'])) {
            // no required information specified, redirect user
            $this->view = null;
            Utils::redirect('./');
        }

        $this->write('msg', $msg);
        $this->write('un', isset($_REQUEST['un']) ? base64_decode($_REQUEST['un']) : "");
        $this->write('token', $_REQUEST['token']);
        parent::run();
    }
}
