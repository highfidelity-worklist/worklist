<?php
/**
 * This class should do the messaging part between logic and views.
 * Common used values as header/footer location or page title should 
 * use a public static variable, and view-specific values should be 
 * stored through read/write methods
 * 
 *  
 */
use \Mustache\Mustache;

class View extends AppObject {
    public $layout = 'common';
    public $header = 'header';
    public $footer = 'footer';
    public $navbar = 'navbar';
    public $title = 'Worklist';
    public $app = array(
        'version' => -1,
        'url' => WORKLIST_URL,
        'metadata' => array(
            'title' => 'Worklist - A community of independent software developers',
            'image' => 'images/worklist_logo_large.png',
            'description' => 'Worklist is a software development forum to rapidly prototype and build software and websites using a global network of developers, designers and testers.'
        ),
        'self_url' => '',
        'feeds_url' => 'feedlist'
    );

    public $currentUser = array(
        'id' => 0,
        'username' => '',
        'nickname' => 'Guest',
        'is_runner' => false,
        'runner_id' => 0,
        'is_admin' => false,
        'is_payer' => false
    );

    public $config = array(
        'ajaxRefresh' => AJAX_REFRESH
    );
    
    public static $default_stylesheets = array(
        'css/bootstrap/css/bootstrap.min.css',
        'css/jquery/jquery.combobox.css',
        'css/jquery/jquery-ui.css',
        'css/font-awesome/css/font-awesome.min.css',
        'css/tooltip.css',
        'css/common.css',
        'css/menu.css'
    );
    public static $default_scripts = array(
        'js/jquery/jquery-1.7.1.min.js',
        'js/jquery/jquery.class.js',
        'js/jquery/jquery-ui-1.8.12.min.js',
        'js/jquery/jquery.watermark.min.js',
        'js/jquery/jquery.livevalidation.js',
        'js/jquery/jquery.scrollTo-min.js',
        'js/jquery/jquery.combobox.js',
        'js/jquery/jquery.autogrow.js',
        'js/jquery/jquery.tooltip.min.js',
        'js/bootstrap/bootstrap.min.js',
        'js/common.js',
        'js/utils.js',
        'js/userstats.js',
        'js/worklist.js',
        'js/budget.js'
    );
    
    public $stylesheets = array();
    public $scripts = array();
    
    /**
     * Constructor method
     */
    public function __construct() {
        $user_id = getSessionUserId();
        $user = new User();
        if ($user_id) {
            initUserById($user_id);
            $user->findUserById($user_id);
        }

        $this->name = strtolower(preg_replace('/View$/', '', get_class($this)));
        $this->app['version'] = Utils::getVersion();
        $this->app['self_url'] = $_SERVER['PHP_SELF'];
        $this->currentUser['id'] = $user_id;
        $this->currentUser['username'] = $user_id ? $user->getUsername() : '';
        $this->currentUser['nickname'] = $user_id ? $user->getNickname() : '';
        $this->currentUser['is_runner'] = !empty($_SESSION['is_runner']) ? 1 : 0;
        $this->currentUser['is_payer'] = !empty($_SESSION['is_payer']) ? 1 : 0;
        $this->currentUser['is_admin'] = !empty($_SESSION['is_admin']) ? 1 : 0;
    }
    
    /**
     * Add a css stylesheet to be used in the view
     */
    public function addStyle($path) {
        if (array_key_exists($path, self::$stylesheets)) {
            return false;
        }
        $this->stylesheets[] = $path;
        return true;
    }

    /**
     * Removes an existing stylesheet from the view
     */
    public function removeStyle($path) {
        if (!array_key_exists($path, $this->stylesheets)) {
            return false;
        }
        unset($this->stylesheets[$path]);
        return true;
    }

    /**
     * Add a script to be ran in the view side
     */
    private function addScript($path) {
        if (array_key_exists($path, $this->scripts)) {
            return false;
        }
        $this->scripts[] = $path;
        return true;
    }

    /**
     * Removes an existing script from the view
     */
    private function removeScript($path) {
        if (!array_key_exists($path, $this->scripts)) {
            return false;
        }
        unset($this->scripts[$path]);
        return true;
    }

    /**
     * Outputs scripts references to the view
     */
    public function getScriptMetaTags() {
        $scripts = array_merge(self::$default_scripts, $this->scripts);
        $ret = '';
        foreach ($scripts as $path) {
            $ret .= sprintf('<script type="text/javascript" src="%s"></script>', $path);
        }
        return $ret;
    }
    
    /**
     * Outputs stylesheets references to the view
     */
    public function getStyleMetaTags() {
        $stylesheets = array_merge(self::$default_stylesheets, $this->stylesheets);
        $ret = '';
        foreach ($stylesheets as $path) {
            $ret .= sprintf('<link rel="stylesheet" href="%s">', $path);
        }
        return $ret;
    }
    
    /**
     * Includes the content/php logic of the header file
     */
    public function renderHeader() {
        $base = VIEWS_DIR . DIRECTORY_SEPARATOR . 'mustache' . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . $this->layout;
        $mustache = new Mustache_Engine(array(
            'loader' => new Mustache_Loader_FilesystemLoader($base)
        ));
        $template = $mustache->loadTemplate($this->header);
        return $template->render($this);
    }
    
    /**
     * Includes the content/php logic of the navbar file
     */
    public function renderNavBar() {
        $base = VIEWS_DIR . DIRECTORY_SEPARATOR . 'mustache' . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . $this->layout;
        $mustache = new Mustache_Engine(array(
            'loader' => new Mustache_Loader_FilesystemLoader($base)
        ));
        $template = $mustache->loadTemplate($this->navbar);
        return $template->render($this);
    }
    
    /**
     * Includes the content/php logic of the footer file
     */
    public function renderFooter() {
        $base = VIEWS_DIR . DIRECTORY_SEPARATOR . 'mustache' . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . $this->layout;
        $mustache = new Mustache_Engine(array(
            'loader' => new Mustache_Loader_FilesystemLoader($base)
        ));
        $template = $mustache->loadTemplate($this->footer);
        return $template->render($this);
    }
        
    public function render() {
        $base = VIEWS_DIR . DIRECTORY_SEPARATOR . 'mustache';
        $template = strtolower(preg_replace('/View$/', '', get_class($this)));
        $mustache = new Mustache_Engine(array(
            'loader' => new Mustache_Loader_FilesystemLoader($base)
        ));
        $template = $mustache->loadTemplate($template);
        $ret = $this->renderHeader() . $template->render($this) . $this->renderFooter();
        return $ret;
    }
}
