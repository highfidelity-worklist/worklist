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
	const CONFIG_DEFAULT_OFILTER = 'defaultOfilter';
	const CONFIG_DEFAULT_DFILTER = 'defaultDfilter';
    const CONFIG_COOKIE_NAME     = 'cookieName';
    const CONFIG_COOKIE_EXPIRY   = 'cookieExpiry';
    const CONFIG_COOKIE_PATH     = 'cookiePath';

    private $defaultSfilter = 'BIDDING';
    private $defaultUfilter = 'ALL';
	private $defaultOfilter = 'priority';
	private $defaultDfilter = 'UP';

    protected $cookieName   = 'worklist_filter';
    /**
     * Cookie expiry. Default: at end of session
     * @var int
     */
    protected $cookieExpiry = 0;
    protected $cookiePath   = null;
    protected $sfilter;
    protected $ufilter;
	protected $ofilter;
	protected $dfilter;
	
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

                case self::CONFIG_DEFAULT_OFILTER:
                    $this->defaultOfilter = $value;
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
		$this->setOfilter($this->defaultOfilter);
		$this->setDfilter($this->defaultDfilter);
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
		if (isset($_COOKIE[$this->cookieName]['ofilter'])) {
            $this->setOfilter($_COOKIE[$this->cookieName]['ofilter']);
        }
		if (isset($_COOKIE[$this->cookieName]['dfilter'])) {
            $this->setDfilter($_COOKIE[$this->cookieName]['dfilter']);
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
		if (!isset($_COOKIE[$this->cookieName]['ofilter']) || $_COOKIE[$this->cookieName]['ofilter'] != $this->ofilter) {
			setcookie($this->cookieName . '[ofilter]', $this->ofilter, $this->cookieExpiry, $this->cookiePath);
        }
		if (!isset($_COOKIE[$this->cookieName]['dfilter']) || $_COOKIE[$this->cookieName]['dfilter'] != $this->dfilter) {
			setcookie($this->cookieName . '[dfilter]', $this->dfilter, $this->cookieExpiry, $this->cookiePath);
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
	
    /**
     * Set ofilter
     *
     * @param string $ofilter
     * @return Worklist_Filter
     */
    public function setOfilter($ofilter)
    {
		$this->ofilter = $ofilter;
        return $this;
    }
	
	/**
	 * @return string
	 */
	public function getOfilter() {
		return $this->ofilter;
	}
	
    /**
     * @return string
     */
    public function getOfilterConverted()
    {
		switch (strtoupper($this->ofilter)) {
			case 'WHO':
				$order = 'creator_nickname';
			break;
			
			case 'SUMMARY':
				$order = 'summary';
			break;
			
			case 'WHEN':
				$order = 'delta';
			break;
			
			case 'STATUS':
				$order = 'status';
			break;
			
			case 'FEES/BIDS':
				$order = "IF(STRCMP(status,'BIDDING'),total_fees,IF(bid_amount,bid_amount,0))";
			break;
			
			default:
				$order = 'priority';
			break;
		}
        return $order;
    }
	
	/**
     * Set dfilter
     *
     * @param string $dfilter
     * @return Worklist_Filter
     */
    public function setDfilter($dfilter)
    {
        $this->dfilter = $dfilter;
        return $this;
    }
	
	/**
	 * @return string
	 */
	public function getDfilter() {
		return $this->dfilter;
	}
    /**
     * @return string
     */
    public function getDfilterConverted()
    {
		switch(strtoupper($this->dfilter)) {
			case 'UP':
				$r = 'ASC';
			break;
			
			case 'DN':
				$r = 'DESC';
			break;
			/*
				These two are just here for expansion purposes only :)
			*/
			case 'ASC':
				$r = 'ASC';
			break;
			
			case 'DESC':
				$r = 'DESC';
			break;
			// end of expansion cases
			default:
				$r = 'ASC';
			break;
		}
        return $r;
    }

}