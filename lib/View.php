<?php
/**
 * This class should do the messaging part between logic and views.
 * Common used values as header/footer location or page title should
 * use a public static variable, and view-specific values should be
 * stored through read/write methods
 *
 *
 */
require_once('MVCObject.php');
require_once('Layout.php');

use \Mustache\Mustache;

class View extends MVCObject {
    public $name = '';
    public $layout = null;

    public $jumbotron = '';

    public $config = array(
        'ajaxRefresh' => AJAX_REFRESH
    );

    public $stylesheets = array();
    public $scripts = array();

    protected $globalsLoaded = false;

    /**
     * Constructor method
     */
    public function __construct() {
        parent::__construct();
        $this->name = strtolower(preg_replace('/View$/', '', get_class($this)));
        $this->loadGlobals();
    }

    /**
     * Load default views global values/properties
     */
    public function loadGlobals($force = false) {
        if ($this->globalsLoaded && !$force) {
            return;
        }

        $this->redir_url = Dispatcher::$url;
        $this->globalsLoaded = true;
    }


    /**
     * Outputs stylesheets references to the view/layout
     */
    public function styleTags() {
        $layout = $this->layout;
        if (!is_null($layout)) {
            $stylesheets = array_merge($layout->stylesheets, $this->stylesheets);
        }
        $ret = '';
        foreach ($stylesheets as $path) {
            $ret .= sprintf('<link rel="stylesheet" href="%s">', $path);
        }
        return $ret;
    }

    /**
     * Outputs scripts references to the view/layout
     */
    public function scriptTags() {
        $layout = $this->layout;
        if (!is_null($layout)) {
            $scripts = array_merge($layout->scripts, $this->scripts);
        }
        $ret = '';
        foreach ($scripts as $path) {
            $ret .= sprintf('<script type="text/javascript" src="%s"></script>', $path);
        }
        return $ret;
    }

    /**
     * View rendering, called once Controller has done its work
     */
    public function render() {
        $this->loadGlobals();

        $layout = $this->layout;
        if (is_string($layout) && class_exists($layout . 'Layout')) {
            $layoutClass = $layout . 'Layout';
            $layout = $this->layout = new $layoutClass();
        } elseif (is_null($layout) && class_exists('NewWorklistLayout')) {
            /* In case no layout were specified, NewWorklist will be used. 19-MAY-2014 <kordero> */
            $layout = $this->layout = new NewWorklistLayout();
        }
        $base = VIEWS_DIR . DIRECTORY_SEPARATOR . 'mustache';
        $partials = VIEWS_DIR . DIRECTORY_SEPARATOR . 'mustache' . DIRECTORY_SEPARATOR . 'partials';
        $mustache = new Mustache_Engine(array(
            'loader' => new Mustache_Loader_FilesystemLoader($base),
            'partials_loader' => new Mustache_Loader_FilesystemLoader($partials)
        ));
        $template = $mustache->loadTemplate($this->name);
        $content = $this->content = $template->render($this);

        /**
         * Layout could still not be present here because whether it's
         * missing or an empty/false value were specified, so in that
         * case, rendered content will not be wraped into any other
         * markup or layout behavior. 19-MAY-2014 <kordero>
         */
        return is_null($layout) ? $content : $layout->render($this);
    }

    /**
     * When a mustache tries to reach a property that is not stored in
     * the View class, the following __get and __isset methods will help
     * catching that property read request in order look at Layout
     * for its value (__get) or existence (__isset)
     */
    public function __get($name) {
        if (property_exists($this, $name)) {
            return $this->$name;
        } elseif (property_exists($this->layout, $name)) {
            return $this->layout->$name;
        }
    }
    public function __isset($name) {
        return property_exists($this, $name) || property_exists($this->layout, $name);
    }

}