<?php
error_log("c:Login 1");
require_once ("config.php");
error_log("c:Login 2");
require_once ("class.session_handler.php");
error_log("c:Login 3");
require_once ("class/CURLHandler.php");
error_log("c:Login 4");
require_once ("class/Response.class.php");
error_log("c:Login 5");
require_once ("class/Database.class.php");
error_log("c:Login 6");
require_once("send_email.php");
error_log("c:Login 7");

class Login {
    /**
     * @var array
     */
    private $params;
    /** 
     * @var Response
     */
    protected $response;
    /**
     * @var Database
     */
    protected $database;
    
    public function __construct(){
error_log("c:Login 9");
        $this->params = array("app" => SERVICE_NAME, "key" => API_KEY);
    }
    public function setDatabase($db){
error_log("c:Login 10");
        $this->database = $db;
        return $this;
    }
    public function getDatabase(){
error_log("c:Login 11");
        if(! isset($this->database)){
            $this->setDatabase(new Database());
        }
        return $this->database;
    }
    public function setResponse($response){
error_log("c:Login 12");
        $this->response = $response;
        return $this;
    }
    public function getResponse(){
error_log("c:Login 13");
        if(! isset($this->response)){
            $this->setResponse(new Response());
        }
        return $this->response;
    }
    public function saveToken($token){
error_log("c:Login 14");
        $this->getDatabase()->insert(TOKENS, array('token' => $token, 'completed' => 0), array('%s', '%d'));
    }
    public function updateToken($token){
error_log("c:Login 15");
        $this->getDatabase()->update(TOKENS, array('completed' => 1), array('token' => $token), array('%d'), array('%s'));
    }
    public function checkToken($token){
error_log("c:Login 16");
        $res = $this->getDatabase()->query("SELECT completed FROM ".TOKENS." WHERE token = '" . sprintf('%s', $token) . "'");
        $ret = mysql_fetch_object($res);
        
        $found = mysql_num_rows($res);
        if($found > 0 && $ret->completed == 0){
            return true;
        }else{
            return false;
        }
    }
    public function signup(){
        if(! isset($_REQUEST["username"])){
            $this->getResponse()->getError()->setError("Username field is missing.");
        }else if(! isset($_REQUEST["password"])){
            $this->getResponse()->getError()->setError("Password field is missing.");
        }else if(! isset($_REQUEST["confirm_string"])){
            $this->getResponse()->getError()->setError("Confirm string is missing.");
        }else{
            $token = uniqid();
            $this->saveToken($token);
            $this->params["username"] = $_REQUEST["username"];
            $this->params["password"] = $_REQUEST["password"];
            if(isset($_REQUEST["nickname"])){
                $this->params["nickname"] = $_REQUEST["nickname"];
            }
            $this->params["token"] = $token;
            $this->params["confirm_string"] = $_REQUEST["confirm_string"];
            
            ob_start();
            // send the request
            CURLHandler::Post(LOGIN_APP_URL . 'create', $this->params, false, true);
            $result = ob_get_contents();
            ob_end_clean();
            $result = json_decode($result);
            
            if(!empty($result->error) && $result->error == 1){
                $this->getResponse()->getError()->setError($result->message);
            }else{
                if(isset($result->token) && $this->checkToken($result->token) && $token == $result->token){
                    $this->updateToken($result->token);
                    $this->getResponse()->addParams($result);
                }else{
                    $this->getResponse()->getError()->setError("Invalid Token aka Malicious attempt.");
                }
            }
        }
    }
    public function loginrequest(){
error_log("c:Login 17");
        if(! isset($_REQUEST["username"])){
error_log("c:Login 18");
            $this->getResponse()->getError()->setError("Username field is missing.");
        }else if(! isset($_REQUEST["password"])){
error_log("c:Login 19");
            $this->getResponse()->getError()->setError("Password field is missing.");
        }else{
error_log("c:Login 20");
            $token = uniqid();
            $this->saveToken($token);
            $this->params["username"] = $_REQUEST["username"];
            $this->params["password"] = $_REQUEST["password"];
            $this->params["token"] = $token;
error_log("c:Login 21");
            ob_start();
            // send the request
error_log("c:Login 22".LOGIN_APP_URL);
            CURLHandler::Post(LOGIN_APP_URL . 'login', $this->params, false, true);
error_log("c:Login 23");
            $result = ob_get_contents();
            ob_end_clean();
error_log("c:Login 24");
            $result = json_decode($result);
            if($result->error == 1){
error_log("c:Login 25");
                $this->getResponse()->getError()->setError($result->message);
            }else{
error_log("c:Login 26");
                if($this->checkToken($result->token) && $token == $result->token){
                    $this->updateToken($result->token);
                    $this->getResponse()->addParams($result);
                }else{
                    $this->getResponse()->getError()->setError("Invalid Token aka Malicious attempt.");
                }
            }
        }
    }

    public function getUserData(){
error_log("c:Login 27");
        if(!isset($_REQUEST["user_id"])){
            $this->getResponse()->getError()->setError("No user id set.");
        } else if(!isset($_SESSION["userid"])) {
            $this->getResponse()->getError()->setError("You are not logged in.");
        } else {
            $user_id = (int)$_REQUEST["user_id"];
            $admin_id = (int)$_SESSION["userid"];
            $token = uniqid();
            $this->saveToken($token);
            $this->params["user_id"] = $user_id;
            $this->params["admin_id"] = $admin_id;
            $this->params["token"] = $token;
            ob_start();
            // send the request
            CURLHandler::Post(LOGIN_APP_URL . 'getuserdata', $this->params, false, true);
            $result = ob_get_contents();
            ob_end_clean();
            $result = json_decode($result);
            if($result->error == 1){
                $this->getResponse()->getError()->setError($result->message);
            }else{
                if($this->checkToken($result->token) && $token == $result->token){
                    $this->updateToken($result->token);
                    $this->getResponse()->addParams($result);
                }else{
                    $this->getResponse()->getError()->setError("Invalid Token aka Malicious attempt.");
                }
            }
        }
    }

    public function setUserData(){
error_log("c:Login 28");
        if(!isset($_REQUEST["user_data"])){
            $this->getResponse()->getError()->setError("No user data set.");
        } else if(!isset($_SESSION["userid"])) {
            $this->getResponse()->getError()->setError("You are not logged in.");
        } else {
            $user_data = $_REQUEST["user_data"];
            $admin_id = (int)$_SESSION["userid"];
            $token = uniqid();
            $this->saveToken($token);
            foreach($user_data as $key=>$value){
                $this->params["user_data"][$key] = $value;
            }
            $this->params["admin_id"] = $admin_id;
            $this->params["token"] = $token;
            ob_start();
            // send the request
            CURLHandler::Post(LOGIN_APP_URL . 'setuserdata', $this->params, false, true);
            $result = ob_get_contents();
            ob_end_clean();
            $result = json_decode($result);
            if($result->error == 1){
                $this->getResponse()->getError()->setError($result->message);
            }else{
                if($this->checkToken($result->token) && $token == $result->token){
                    $this->updateToken($result->token);
                    $this->getResponse()->addParams($result);
                }else{
                    $this->getResponse()->getError()->setError("Invalid Token aka Malicious attempt.");
                }
            }
        }
    }
    public function update(){
error_log("c:Login 29");
        if(!isset($_REQUEST["user_data"])){
            $this->getResponse()->getError()->setError("No user data set.");
        } else if(!isset($_REQUEST["sid"])){
            $this->getResponse()->getError()->setError("Session id is not set");
        } else {
            session::initById($_REQUEST["sid"]);
            if(!isset($_SESSION["userid"])) {
                $this->getResponse()->getError()->setError("You are not logged in.");
            }else {
                $user_data = $_REQUEST["user_data"];
                $token = uniqid();
                $this->saveToken($token);
                foreach($user_data as $key=>$value){
                    $this->params["user_data"][$key] = $value;
                }
                $this->params["user_data"]["userid"] = $_SESSION["userid"]; 
                $this->params["token"] = $token;
                ob_start();
                // send the request
                CURLHandler::Post(LOGIN_APP_URL . 'update', $this->params, false, true);
                $result = ob_get_contents();
                ob_end_clean();
                $result = json_decode($result);
                if($result->error == 1){
                    $this->getResponse()->getError()->setError($result->message);
                }else{
                    if($this->checkToken($result->token) && $token == $result->token){
                        $this->updateToken($result->token);
                        $this->getResponse()->addParams($result);
                    }else{
                        $this->getResponse()->getError()->setError("Invalid Token aka Malicious attempt.");
                    }
                }
            }
        }
    }
    
    public function resetUserPassword(){
        if(!isset($_REQUEST["user_id"])){
            $this->getResponse()->getError()->setError("No user id set.");
        } else if(!isset($_SESSION["userid"])) {
            $this->getResponse()->getError()->setError("You are not logged in.");
        } else {
            $user_id = (int)$_REQUEST["user_id"];
            $admin_id = (int)$_SESSION["userid"];
            $token = uniqid(); 
            $this->saveToken($token);
            $this->params["user_id"] = $user_id;
            $this->params["admin_id"] = $admin_id;
            $this->params["token"] = $token;
            ob_start();
            // send the request
            CURLHandler::Post(LOGIN_APP_URL . 'adminresettoken', $this->params, false, true);
            $result = ob_get_contents();
            ob_end_clean();
            $result = json_decode($result);
            if($result->error == 1){
                $this->getResponse()->getError()->setError($result->message);
            }else{
                if($this->checkToken($result->token) && $token == $result->token){
                    $this->updateToken($result->token);
                    $resetUrl = SECURE_SERVER_URL . 'resetpass.php?un=' . base64_encode($result->username) . '&amp;token=' . $result->confirm_string;
                    $resetUrl = '<a href="' . $resetUrl . '" title="Password Recovery">' . $resetUrl . '</a>';
                    sendTemplateEmail($result->username, 'recovery', array('url' => $resetUrl));
                    $this->getResponse()->addParams($result);
                }else{
                    $this->getResponse()->getError()->setError("Invalid Token aka Malicious attempt.");
                }
            }
        }
    }
    
    public function notify($user_id, $session_id){
error_log("c:Login 30");
        $token = uniqid();
error_log("c:Login 31");
        $this->saveToken($token);
error_log("c:Login 32");
        $this->params["userid"] = $user_id;
        $this->params["sessionid"] = $session_id;
        $this->params["token"] = $token;
error_log("c:Login 33".LOGIN_APP_URL);
        ob_start();
        // send the request
        CURLHandler::Post(LOGIN_APP_URL . 'notify', $this->params, false, true);
error_log("c:Login 34");
        $result = ob_get_contents();
        ob_end_clean();
error_log("c:Login 35");

        $result = json_decode($result);
error_log("c:Login 36");
        if($result->error == 1){
            $this->getResponse()->getError()->setError($result->message);
        }else{
            if($this->checkToken($result->token) && $token == $result->token){
                $this->updateToken($result->token);
                $this->getResponse()->addParams($result);
            }else{
                $this->getResponse()->getError()->setError("Invalid Token aka Malicious attempt.");
            }
        }
    }
}
