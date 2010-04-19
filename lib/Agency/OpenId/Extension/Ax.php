<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_OpenId
 * @subpackage Zend_OpenId_Consumer
 * @copyright  Copyright (c) 2005-2009 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

require_once('Zend/OpenId/Extension.php');

/**
 * Attributes Exchange Extension. 
 *
 * @author Thomas Stachl <thomas@stachl.me>
 * @copyright Copyright (c) 2009 Thomas Stachl
 */
class Agency_OpenId_Extension_Ax extends Zend_OpenId_Extension
{
    /**
     * AX 1.0 namespace. All OpenID AX 1.0 messages MUST contain variable
     * openid.ns.ax with its value.
     */
    const NAMESPACE_1_0 = 'http://openid.net/srv/ax/1.0';
    const MODE = 'fetch_request';

    private $_props;
    private $_policy_url;
    private $_version;

    /**
     * Creates AX extension object
     *
     * @param array $props associative array of AX variables
     * @param string $policy_url AX policy URL
     * @param float $version AX version
     * @return array
     */
    public function __construct(array $props=null, $policy_url=null, $version=1.0)
    {
        $this->_props = $props;
        $this->_policy_url = $policy_url;
        $this->_version = $version;
    }

    /**
     * Returns associative array of AX variables
     *
     * @return array
     */
    public function getProperties() {
        if (is_array($this->_props)) {
            return $this->_props;
        } else {
            return array();
        }
    }

    /**
     * Returns AX protocol version
     *
     * @return float
     */
    public function getVersion() {
        return $this->_version;
    }

    /**
     * Returns array of allowed SREG variable names.
     *
     * @return array
     */
    public static function getAxProperties()
    {
        return array(
            "firstname"=>'http://axschema.org/namePerson/first',
            "email" => 'http://axschema.org/contact/email',
            "lastname" => 'http://axschema.org/namePerson/last',
            "dob" => 'http://axschema.org/birthDate',
            "gender" => 'http://axschema.org/person/gender',
            "postcode" => 'http://axschema.org/contact/postalCode/home',
            "country"=>'http://axschema.org/contact/country/home',
            "language"=>'http://axschema.org/pref/language',
            "timezone" => 'http://axschema.org/pref/timezone'
        );
    }

    /**
     * Adds additional AX data to OpenId 'checkid_immediate' or
     * 'checkid_setup' request.
     *
     * @param array &$params request's var/val pairs
     * @return bool
     */
    public function prepareRequest(&$params)
    {
        $tproperties = self::getAxProperties();
        
        if (is_array($this->_props) && count($this->_props) > 0) {
            foreach ($this->_props as $prop => $req) {
                if ($req) {
                    
                    if (isset($required)) {
                        $required .= ','.$prop;
                    } else {
                        $required = $prop;
                    }
                } else {
                    if (isset($optional)) {
                        $optional .= ','.$prop;
                    } else {
                        $optional = $prop;
                    }
                }
                if (array_key_exists($prop,$tproperties))
                $params['openid.ax.type.'.$prop] = $tproperties[$prop];
            }
         
            $params['openid.ns.ax'] = Agency_OpenId_Extension_Ax::NAMESPACE_1_0;
            $params['openid.ax.mode'] = Agency_OpenId_Extension_Ax::MODE;

            if (!empty($required)) {
                $params['openid.ax.required'] = $required;
            }
            if (!empty($optional)) {
                $params['openid.ax.if_available'] = $optional;
            }

        }
        return true;
    }

    /**
     * Parses OpenId 'checkid_immediate' or 'checkid_setup' request,
     * extracts AX variables and sets ovject properties to corresponding
     * values.
     *
     * @param array $params request's var/val pairs
     * @return bool
     */
    public function parseRequest($params)
    {
        $this->_version= 1.0;

        $props = array();
        if (!empty($params['openid_ax_optional'])) {
            foreach (explode(',', $params['openid_ax_optional']) as $prop) {
                $prop = trim($prop);
                $props[$prop] = false;
            }
        }
        if (!empty($params['openid_ax_required'])) {
            foreach (explode(',', $params['openid_ax_required']) as $prop) {
                $prop = trim($prop);
                $props[$prop] = true;
            }
        }
        $props2 = array();
        foreach (array_keys(self::getAxProperties()) as $prop) {
            if (isset($props[$prop])) {
                $props2[$prop] = $props[$prop];
            }
        }

        $this->_props = (count($props2) > 0) ? $props2 : null;
        return true;
    }

    /**
     * Adds additional SREG data to OpenId 'id_res' response.
     *
     * @param array &$params response's var/val pairs
     * @return bool
     */
    public function prepareResponse(&$params)
    {
        if (is_array($this->_props) && count($this->_props) > 0) {

           $params['openid.ns.ax'] = Agency_OpenId_Extension_Ax::NAMESPACE_1_0;
            
            foreach (self::getAxProperties() as $prop=>$value) {
                if (!empty($this->_props[$prop])) {
                    $params['openid.ax.type.' . $prop] = $this->_props[$prop];
                }
            }
        }
        return true;
    }

    /**
     * Parses OpenId 'id_res' response and sets object's properties according
     * to 'openid.sreg.*' variables in response
     *
     * @param array $params response's var/val pairs
     * @return bool
     */
    public function parseResponse($params)
    {
        $this->_version= 1.0;

        $props = array();
        foreach (self::getAxProperties() as $prop=>$type) {
            if (!empty($params['openid_ext1_type_' . $prop])) {
                $props[$prop] = $params['openid_ext1_value_' . $prop];
            }
        }

        if (isset($this->_props) && is_array($this->_props)) {
            foreach (self::getAxProperties() as $prop=>$type) {
                if (isset($this->_props[$prop]) &&
                    $this->_props[$prop] &&
                    !isset($props[$prop])) {
                    return false;
                }
            }
        }
        $this->_props = (count($props) > 0) ? $props : null;
        return true;
    }

    /**
     * Addes SREG properties that are allowed to be send to consumer to
     * the given $data argument.
     *
     * @param array &$data data to be stored in tusted servers database
     * @return bool
     */
    public function getTrustData(&$data)
    {
        $data[get_class()] = $this->getProperties();
        return true;
    }

    /**
     * Check if given $data contains necessury SREG properties to sutisfy
     * OpenId request. On success sets SREG response properties from given
     * $data and returns true, on failure returns false.
     *
     * @param array $data data from tusted servers database
     * @return bool
     */
    public function checkTrustData($data)
    {
        if (is_array($this->_props) && count($this->_props) > 0) {
            $props = array();
            $name = get_class();
            if (isset($data[$name])) {
                $props = $data[$name];
            } else {
                $props = array();
            }
            $props2 = array();
            foreach ($this->_props as $prop => $req) {
                if (empty($props[$prop])) {
                    if ($req) {
                        return false;
                    }
                } else {
                    $props2[$prop] = $props[$prop];
                }
            }
            $this->_props = (count($props2) > 0) ? $props2 : null;
        }
        return true;
    }
}
