<?php

require_once('Dispatcher.php');

class Core {
    public static function bootstrap($dispatch = true) {
        spl_autoload_register(array('Core', 'autoload'));
        if ($dispatch) {
            register_shutdown_function(array('Core', 'shutdown'));
        }
    }

    public static function autoload($class) {
        if ($class != 'View' && substr($class, -4) == 'View') {
            $fileName = substr($class, 0, -4);
            $file = VIEWS_DIR . DIRECTORY_SEPARATOR . $fileName . '.php';
        } elseif ($class != 'Layout' && substr($class, -6) == 'Layout') {
            $fileName = substr($class, 0, -6);
            $file = VIEWS_DIR . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . $fileName . '.php';
        } else if ($class != 'Controller' && substr($class, -10) == 'Controller') {
            $fileName = substr($class, 0, -10);
            $file = CONTROLLERS_DIR . DIRECTORY_SEPARATOR . $fileName . '.php';
        } else if ($class != 'Model' && substr($class, -5) == 'Model') {
            $fileName = substr($class, 0, -5);
            $file = MODELS_DIR . DIRECTORY_SEPARATOR . $fileName . '.php';
        } else {
            $file = realpath(CLASSES_PATH) . DIRECTORY_SEPARATOR . "$class.php";
        }
        if (file_exists($file)) {
            require_once($file);
        }
    }

    public static function shutdown() {
        Dispatcher::dispatch();
    }
}
