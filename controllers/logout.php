<?php

class LogoutController extends Controller {
	public function run() {
		$this->view = null;

		unset($_SESSION['username']);
		unset($_SESSION['userid']);
		unset($_SESSION['confirm_string']);
		unset($_SESSION['nickname']);
		unset($_SESSION['running']);
		if (isset($_COOKIE[session_name()])) {
		    setcookie(session_name(), '', time() - 42000, '/');
		}

		session_destroy();

		if (array_key_exists('HTTP_REFERER', $_SERVER)) {
		    $url = $_SERVER['HTTP_REFERER'];
		} else {
		    $url = './login';
		}
		Utils::redirect($url);		
	}
}


