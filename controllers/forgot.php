<?php
/**
 * vim:ts=4:et
 * 
 * Copyright (c) 2013, CoffeeandPower Inc.
 * All Rights Reserved. 
 *
 * http://www.worklist.net
 */

class ForgotController extends Controller {
    public function run () {
        // @TODO: We extra the request but it seems we then don't use it?
        extract($_REQUEST);

        $msg = '';
        if(!empty($_POST['username'])) { 
            
            $token = md5(uniqid());
            $user = new User();
            if ($user->findUserByUsername($_POST['username'])) {
                $user->setForgot_hash($token);
                $user->save();
                $resetUrl = SECURE_SERVER_URL . 'resetpass?un=' . base64_encode($_POST['username']) . '&amp;token=' . $token;
                $resetUrl = '<a href="' . $resetUrl . '" title="Password Recovery">' . $resetUrl . '</a>';
                sendTemplateEmail($_POST['username'], 'recovery', array('url' => $resetUrl));
                $msg = '<p class="LV_valid">Login information will be sent if the email address ' . $_POST['username'] . ' is registered.</p>';
            } else {
                $msg = '<p class="LV_invalid">Sorry, unable to send password reset information. Try again or contact an administrator.</p>';
            }
        }
        $this->write('msg', $msg);    
        parent::run();
    }
}