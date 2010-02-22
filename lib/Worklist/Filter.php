<?php
/**
 * Worklist filter
 *
 * @package worklist
 * @subpackage filter
 * @version $Id$
 */
class Worklist_Filter
{
    const CONFIG_DEFAULT_SFILTER = 'defaultSfilter';
    const CONFIG_DEFAULT_UFILTER = 'defaultUfilter';
    const CONFIG_COOKIE_NAME     = 'cookieName';
    const CONFIG_COOKIE_EXPIRY   = 'cookieExpiry';
    const CONFIG_COOKIE_PATH     = 'cookiePath';

    private $defaultSfilter = 'BIDDING';
    private $defaultUfilter = 'ALL';

    protected $cookieName   = 'worklist_filter';
    /**
     * Cookie expiry. Default: at end of session
     * @var int
     */
    protected $cookieExpiry = 0;
    protected $cookiePath   = null;
    protected $sfilter;
    protected $ufilter;

    public function __construct(Array $config = array())
    {
        foreach ($config as $key => $value) {
            switch ($key) {
                case self::CONFIG_DEFAULT_SFILTER:
                    $this->defaultSfilter = $value;
                    break;

                case self::CONFIG_DEFAULT_UFILTER:
                    $this->defaultUfilter = $value;
                    break;

                case self::CONFIG_COOKIE_NAME:
                    $this->setCookieName($value);
                    break;

                case self::CONFIG_COOKIE_EXPIRY:
                    $this->cookieExpiry = time() + (int)$value;
                    break;

                case self::CONFIG_COOKIE_PATH:
                    $this->cookiePath = $value;
                    break;
            }
        }
        $this->setDefaultFilters();
        $this->loadFilters();
    }

    /**
     * @return void
     */
    protected function setDefaultFilters()
    {
        $this->setSfilter($this->defaultSfilter);
        $this->setUfilter($this->defaultUfilter);
    }

    /**
     * @return boolean
     */
    protected function loadFilters()
    {
        if (!isset($_COOKIE[$this->cookieName])) {
            return false;
        }
        if (isset($_COOKIE[$this->cookieName]['sfilter'])) {
            $this->setSfilter($_COOKIE[$this->cookieName]['sfilter']);
        }
        if (isset($_COOKIE[$this->cookieName]['ufilter'])) {
            $this->setUfilter($_COOKIE[$this->cookieName]['ufilter']);
        }
        return true;
    }

    /**
     * @return boolean
     */
    public function saveFilters()
    {
        if (headers_sent()) {
            return false;
        }
        if (!isset($_COOKIE[$this->cookieName]['sfilter']) || $_COOKIE[$this->cookieName]['sfilter'] != $this->sfilter) {
            setcookie($this->cookieName . '[sfilter]', $this->sfilter, $this->cookieExpiry, $this->cookiePath);
        }
        if (!isset($_COOKIE[$this->cookieName]['ufilter']) || $_COOKIE[$this->cookieName]['ufilter'] != $this->ufilter) {
            setcookie($this->cookieName . '[ufilter]', $this->ufilter, $this->cookieExpiry, $this->cookiePath);
        }
        return true;
    }

    /**
     * Set cookie name
     * @param string $cookieName
     * @return Worklist_Filter
     */
    public function setCookieName($cookieName)
    {
        $this->cookieName = $cookieName;
        return $this;
    }

    /**
     * Set sfilter
     *
     * @param string $sfilter
     * @return Worklist_Filter
     */
    public function setSfilter($sfilter)
    {
        $this->sfilter = $sfilter;
        return $this;
    }

    /**
     * @return string
     */
    public function getSfilter()
    {
        return $this->sfilter;
    }

    /**
     * Set ufilter
     *
     * @param string $ufilter
     * @return Worklist_Filter
     */
    public function setUfilter($ufilter)
    {
        $this->ufilter = $ufilter;
        return $this;
    }

    /**
     * @return string
     */
    public function getUfilter()
    {
        return $this->ufilter;
    }
}