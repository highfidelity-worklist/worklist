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
        $this->dispatcher->get('/status', array('Status', 'getView'));
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
            $vars = array_key_exists(3 , $route) && array_key_exists('vars', $route[3])
                ? $route[3]['vars']
                : array();
            $default = array_key_exists(3 , $route) && array_key_exists('default', $route[3])
                ? $route[3]['default']
                : array();
            $controller = ucfirst
                (
                    !is_null($route[2]) && array_key_exists(0, $route[2])
                        ? $route[2][0]
                        : (
                            array_key_exists('controller', $vars)
                                ? $vars['controller']
                                : (
                                    array_key_exists('controller', $default)
                                        ? $default['controller']
                                        : DEFAULT_CONTROLLER_NAME
                                )
                        )
                );
            if (strlen($controller) < 10 || substr($controller, -10) != 'Controller') {
                $controller .= 'Controller';
            }
            $Controller = new $controller();
            $method = (!is_null($route[2]) && array_key_exists(1, $route[2])
                ? $route[2][1]
                : (array_key_exists('method', $vars)
                    ? $vars['method']
                    : (array_key_exists('method', $default)
                            ? $default['method']
                            : DEFAULT_CONTROLLER_METHOD
                    )
                )
            );
            $args = (!is_null($route[2]) && array_key_exists(2, $route[2])
                ? $route[2][1]
                : (
                    !is_null($route[3]) && array_key_exists('args', $vars)
                        ? preg_split('/\//', $vars['args'])
                        : (
                            !is_null($default) && array_key_exists('args', $default)
                                ? $default['args']
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
