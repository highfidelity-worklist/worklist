<?php

require_once("config.php");

class Dispatcher {
    public function run() {
        $path = '/' . $_GET['url'];
        $dispatcher = new Pux\Mux;

        $dispatcher->get('/addjob', array('AddJob'));
        $dispatcher->get('/help', array('Help'));
        $dispatcher->get('/job/:id', array('Job'));
        $dispatcher->get('/jobs', array('Jobs'));
        $dispatcher->get('/login', array('Login'));
        $dispatcher->post('/login', array('Login'));
        $dispatcher->get('/logout', array('Logout'));
        $dispatcher->get('/privacy', array('Privacy'));
        $dispatcher->get('/projects', array('Projects'));
        $dispatcher->get('/status', array('Status'));
        $dispatcher->get('/team', array('Team'));
        $dispatcher->get('/welcome', array('Welcome'));
        #$dispatcher->get('/', array(''));
        $dispatcher->get('/:project', array('Project'));

        try {
            $route = $dispatcher->dispatch($path);
            $controller = isset($route[2][0]) ? $route[2][0] : DEFAULT_CONTROLLER_NAME;

            if (strlen($controller) < 10 || substr($controller, -10) != 'Controller') {
                $controller .= 'Controller';
            }

            $method = isset($route[2][1]) ? $route[2][1] : DEFAULT_CONTROLLER_METHOD;

            $variables = isset($route[3]['variables']) ? $route[3]['variables'] : array();
            $values = isset($route[3]['vars']) ? $route[3]['vars'] : array();
            $params = array();
            foreach($variables as $variable) {
                if (isset($values[$variable])) {
                    $params[$variable] = $values[$variable];
                }
            }

            $Controller = new $controller();
            call_user_func_array(array($Controller, $method), $params);
        } catch(Exception $e) {
            
        }
    }
}
