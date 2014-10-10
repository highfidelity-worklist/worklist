<?php
/**
 * Dispatcher class
 *
 * Takes care of dispatching the flow of the request/server thread to its
 * corresponding controller object and method. Also handles arguments sending
 * too in case they are expected/present.
 *
 * This is just a wrapper class that handles a proper running and parsing of
 * the real dispatcher (Pux), for more information see https://github.com/c9s/Pux
 *
 * Copyright 2014 - High Fidelity, Inc.
 */

require_once("config.php");

class Dispatcher {
    static public $url = '';
    static public $dispatcher = null;

    /**
     * Loads routes used by the app to be used by the dispatcher
     */
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

    /**
     * Call route loads, process requested url and dispatchs to controllers
     */
    static public function dispatch() {
        try {
            self::$url = isset($_GET['url']) ? $_GET['url'] : '';
            self::$dispatcher = new Pux\Mux;
            self::loadRoutes();

            // real dispatcher (Pux) call
            $route = self::$dispatcher->dispatch('/' . self::$url);

            // shorthand pointers to processed route variables
            $vars = array_key_exists(3 , $route) && array_key_exists('vars', $route[3])
                ? $route[3]['vars']
                : array();
            $default = array_key_exists(3 , $route) && array_key_exists('default', $route[3])
                ? $route[3]['default']
                : array();

            // parse controller name from the processed route
            $controller = ucfirst(!is_null($route[2]) && array_key_exists(0, $route[2])
                // search for controller name at static params
                ? $route[2][0]
                : (array_key_exists('controller', $vars)
                    // if not present, look at values returned by route variables
                    ? $vars['controller']
                    : (array_key_exists('controller', $default)
                        // on failure, take the default route variable if set
                        ? $default['controller']
                        // non of the attempts worked (worst case)
                        // let's use a hardcoded one
                        : DEFAULT_CONTROLLER_NAME
                    )
                )
            );

            // all controller must have the Controller suffix
            if (strlen($controller) < 10 || substr($controller, -10) != 'Controller') {
                $controller .= 'Controller';
            }

            // let's instantiate the controller so if it not exists, should fire
            // an exception and the rest of the code in this method won't run
            $Controller = new $controller();

            // parse method name from the processed route
            $method = (!is_null($route[2]) && array_key_exists(1, $route[2])
                // search for method name at static params
                ? $route[2][1]
                : (array_key_exists('method', $vars)
                    // if not present, look at values returned by route variables
                    ? $vars['method']
                    : (array_key_exists('method', $default)
                        // on failure, take the default route variable if set
                        ? $default['method']
                        // non of the attempts worked (worst case)
                        // let's use a hardcoded one
                        : DEFAULT_CONTROLLER_METHOD
                    )
                )
            );

            // parse arguments to be sent to the controller method from the processed route
            $args = (!is_null($route[2]) && array_key_exists(2, $route[2])
                // search for static arguments
                ? $route[2][3]
                : (!is_null($route[3]) && array_key_exists('args', $vars)
                    // if not present, look at values returned by route variables
                    ? preg_split('/\//', $vars['args'])
                    : (!is_null($default) && array_key_exists('args', $default)
                        // on failure, take the default route variable if set
                        ? $default['args']
                        // non of the attempts worked (worst case)
                        // let's not send arguments at all
                        : array()
                    )
                )
            );

            // there we go
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