<?php
/**
 * Copyright 2014 - High Fidelity, Inc.
 */

require_once("config.php");

class Dispatcher {
    static public $url = '';

    protected $dispatcher = null;

    public function run() {
        self::$url = isset($_GET['url']) ? $_GET['url'] : '';
        $this->dispatcher = new Pux\Mux;
        $this->loadRoutes();
        $this->dispatch();
    }

    public function loadRoutes() {
        $this->dispatcher->any('/', array('Job', 'getListview'));
        $this->dispatcher->any('/confirmation', array('Confirmation'));
        $this->dispatcher->get('/feedlist', array('FeedList'));
        $this->dispatcher->get('/feeds', array('Feeds'));
        $this->dispatcher->get('/help', array('Help'));
        $this->dispatcher->any('/payments', array('Payments'));
        $this->dispatcher->get('/privacy', array('Privacy'));
        $this->dispatcher->get('/projects', array('Projects'));
        $this->dispatcher->any('/reports', array('Reports'));
        $this->dispatcher->any('/resend', array('Resend'));
        $this->dispatcher->any('/status', array('Status'));
        $this->dispatcher->any('/settings', array('Settings'));
        $this->dispatcher->get('/team', array('Team'));
        $this->dispatcher->get('/timeline', array('Timeline'));
        $this->dispatcher->get('/welcome', array('Welcome'));
        $this->dispatcher->any('/login', array('Github', 'federated'));
        $this->dispatcher->any('/signup', array('Github', 'federated'));

        $this->dispatcher->any('/jobs(/:args)', array('Job', 'getListView'), array(
            'require' => array(
                'args' => '.*'
            ),
            'default' => array(
                'args' => array()
            )
        ));
        $this->dispatcher->get('/uploads/:filename', array('Upload'), array(
            'require' => array(
                'filename' => '.+'
            )
        ));

        $this->dispatcher->any('/:args', null, array(
            'require' => array(
                'args' => '\d+'
            ),
            'default' => array(
                'controller' => 'job',
                'method' => 'view',
                'args' => array()
            )
        ));
        $this->dispatcher->any('/:args', null, array(
            'require' => array(
                'args' => '\w+'
            ),
            'default' => array(
                'controller' => 'project',
                'method' => 'view',
                'args' => array()
            )
        ));
        $this->dispatcher->any('/:controller(/:method(/:args))', null, array(
            'require' => array(
                'controller' => '\w+',
                'method' => '\w+',
                'args' => '.*'
            ),
            'default' => array(
                'controller' => 'job',
                'method' => 'getListView',
                'args' => array()
            )
        ));

    }

    public function dispatch() {
        try {
            $route = $this->dispatcher->dispatch('/' . self::$url);
            $controller = ucfirst
                (
                    !is_null($route[2]) && array_key_exists(0, $route[2])
                        ? $route[2][0]
                        : (
                            !is_null($route[3]) && array_key_exists('controller', $route[3]['vars'])
                                ? $route[3]['vars']['controller']
                                : (
                                    !is_null($route[3]['default']) && array_key_exists('controller', $route[3]['default'])
                                        ? $route[3]['default']['controller']
                                        : DEFAULT_CONTROLLER_NAME
                                )
                        )
                );
            if (strlen($controller) < 10 || substr($controller, -10) != 'Controller') {
                $controller .= 'Controller';
            }
            $Controller = new $controller();
            $method =
                (
                    !is_null($route[2]) && array_key_exists(1, $route[2])
                        ? $route[2][1]
                        : (
                            !is_null($route[3]) && array_key_exists('method', $route[3]['vars'])
                                ? $route[3]['vars']['method']
                                : (
                                    !is_null($route[3]['default']) && array_key_exists('method', $route[3]['default'])
                                        ? $route[3]['default']['method']
                                        : DEFAULT_CONTROLLER_METHOD
                                )
                        )
                );
            $args =
                (
                    !is_null($route[2]) && array_key_exists(2, $route[2])
                        ? $route[2][1]
                        : (
                            !is_null($route[3]) && array_key_exists('args', $route[3]['vars'])
                                ? preg_split('/\//', $route[3]['vars']['args'])
                                : (
                                    !is_null($route[3]['default']) && array_key_exists('args', $route[3]['default'])
                                        ? $route[3]['default']['args']
                                        : array()
                                )
                        )
                );
            call_user_func_array(array($Controller, $method), $args);
        } catch(Exception $e) {
            // TO-DO:
            // probably we couldn't dispatch the request because of
            // an unreachable controller method so we should show a
            // 404 page or handle the exception in a proper way
            error_log('Dispatcher::dispatch: ' . $e->getMessage());
        }
    }
}
