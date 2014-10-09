<?php

class HomeController extends Controller {
	public function run() {
        $this->view = null;
        if (isset($_SESSION['userid']) && $_SESSION['userid']) {
            $controller = 'Job';
            $method = 'getListView';
        } else {
            $controller = 'Welcome';
            $method = DEFAULT_CONTROLLER_METHOD;
        }
        $controller .= 'Controller';
        $Controller = new $controller(false);
        $Controller->$method();
	}
}