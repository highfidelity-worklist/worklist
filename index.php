<?php
/**
 * Copyright 2014 - High Fidelity, Inc.
 */

require_once("config.php");

class Dispatcher {
    static public $url = '';
    static public $dispatcher = null;

    static public function loadRoutes() {
        // root url path routes to Home controller, that will take
        // the user to /jobs or /welcome if not authenticated
        self::$dispatcher->any('/', array('Home'));

        // static routes
        self::$dispatcher->any('/confirmation', array('Confirmation'));
        self::$dispatcher->get('/feedlist', array('FeedList'));
        self::$dispatcher->get('/feeds', array('Feeds'));
        self::$dispatcher->get('/help', array('Help'));
        self::$dispatcher->any('/payments', array('Payments'));
        self::$dispatcher->get('/privacy', array('Privacy'));
        self::$dispatcher->any('/reports', array('Reports'));
        self::$dispatcher->any('/resend', array('Resend'));
        self::$dispatcher->get('/status', array('Status', 'getView'));
        self::$dispatcher->any('/settings', array('Settings'));
        self::$dispatcher->get('/team', array('Team'));
        self::$dispatcher->get('/timeline', array('Timeline'));
        self::$dispatcher->get('/welcome', array('Welcome'));
        self::$dispatcher->any('/login', array('Github', 'federated'));
        self::$dispatcher->any('/signup', array('Github', 'federated'));

        // the /jobs url is actually an alias of /job/listView
        self::$dispatcher->any('/jobs(/:args)', array('Job', 'listView'), array(
            'require' => array('args' => '.*'),
            'default' => array('args' => array())
        ));

        // as well as /projects is an alias of /project/listView
        self::$dispatcher->get('/projects', array('Project', 'listView'));

        // uploads is another special case
        self::$dispatcher->get('/uploads/:filename', array('Upload'), array(
            'require' => array('filename' => '.+')
        ));

        // enable /job_number requests, alias of /job/view/job_number
        self::$dispatcher->any('/:args', null, array(
            'require' => array('args' => '\d+'),
            'default' => array(
                'controller' => 'job',
                'method' => 'view',
                'args' => array()
            )
        ));

        // enable /project_name requests, alias of /project/view/project_name
        self::$dispatcher->any('/:args', null, array(
            'require' => array(
                'args' => '\w+'
            ),
            'default' => array(
                'controller' => 'project',
                'method' => 'view',
                'args' => array()
            )
        ));

        // generic route
        self::$dispatcher->any('/:controller(/:method(/:args))', null, array(
            'require' => array(
                'controller' => '\w+',
                'method' => '\w+',
                'args' => '.*'
            ),
            'default' => array(
                'controller' => 'job',
                'method' => 'listView',
                'args' => array()
            )
        ));
    }

    static public function dispatch() {
        try {
            self::$url = isset($_GET['url']) ? $_GET['url'] : '';
            self::$dispatcher = new Pux\Mux;
            self::loadRoutes();
            $route = self::$dispatcher->dispatch('/' . self::$url);
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