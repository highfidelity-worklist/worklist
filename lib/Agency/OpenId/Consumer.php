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
require_once('lib/Agency/OpenId.php');
require_once ('Zend/OpenId/Consumer.php');
/**
 * Modified openid consumer to be spec 2.0 compliant
 *
 * @author Thomas Stachl <thomas@stachl.me>
 * @copyright Copyright (c) 2009 Thomas Stachl
 */
class Agency_OpenId_Consumer extends Zend_OpenId_Consumer
{   
    /**
     * Performs discovery of identity and finds OpenID URL, OpenID server URL
     * and OpenID protocol version. Returns true on succees and false on
     * failure.
     *
     * @param string &$id OpenID identity URL
     * @param string &$server OpenID server URL
     * @param float &$version OpenID protocol version
     * @return bool
     * @todo OpenID 2.0 (7.3) XRI and Yadis discovery
     */
    protected function _discovery(&$id, &$server, &$version)
    {        
        /* TODO: OpenID 2.0 (7.3) XRI and Yadis discovery */
        /* HTML-based discovery */
        
        $response = $this->_httpRequest($id, 
        'GET', array(), $status);
        
        if ($response === false) {
            $response = $this->_httpRequest('https://www.google.com/accounts/o8/user-xrds?uri=' . rawurlencode($id),
            'GET', array(), $status);
        }
        
        if ($status != 200 || ! is_string(
        $response)) {
            return false;
        }
        
        if (preg_match(
        '/<URI>([^<]+)<\/URI>/i', $response, $r)) {
            $version = 2.0;
            $server = $r[1];
        } else {
            return parent::_discovery($id, $server, $version);
        }
        
        $expire = time() + 60 * 60;
        $this->_storage->addDiscoveryInfo($id, 
        $realId, $server, $version, $expire);
        return true;
    }

    /**
     * Performs HTTP request to given $url using given HTTP $method.
     * Send additinal query specified by variable/value array,
     * On success returns HTTP response without headers, false on failure.
     *
     * @param string $url OpenID server url
     * @param string $method HTTP request method 'GET' or 'POST'
     * @param array $params additional qwery parameters to be passed with
     * @param int &$staus HTTP status code
     *  request
     * @return mixed
     */
    protected function _httpRequest($url, $method = 'GET', array $params = array(), &$status = null)
    {
        $client = $this->_httpClient;
        if ($client === null) {
            $client = new Zend_Http_Client(
                    $url,
                    array(
                        'maxredirects' => 4,
                        'timeout'      => 15,
                        'useragent'    => 'Zend_OpenId'
                    )
                );
        } else {
            $client->setUri($url);
        }
        
        $client->resetParameters();
        if ($method == 'POST') {
            $client->setMethod(Zend_Http_Client::POST);
            $client->setParameterPost($params);
        } else {
            $client->setMethod(Zend_Http_Client::GET);
            $client->setParameterGet($params);
        }
        
        try {
            $response = $client->request();
        } catch (Exception $e) {
            $this->_setError('HTTP Request failed: ' . $e->getMessage());
            return false;
        }
        $status = $response->getStatus();
        $body = $response->getBody();
        if ($status == 200 || ($status == 400 && !empty($body))) {
            return $body;
        }else{
            $this->_setError('Bad HTTP response');
            return false;
        }
    }
    
    /**
     * Performs check of OpenID identity.
     *
     * This is the first step of OpenID authentication process.
     * On success the function does not return (it does HTTP redirection to
     * server and exits). On failure it returns false.
     *
     * @param bool $immediate enables or disables interaction with user
     * @param string $id OpenID identity
     * @param string $returnTo HTTP URL to redirect response from server to
     * @param string $root HTTP URL to identify consumer on server
     * @param mixed $extensions extension object or array of extensions objects
     * @param Zend_Controller_Response_Abstract $response an optional response
     * object to perform HTTP or HTML form redirection
     * @return bool
     */
    protected function _checkId ($immediate, $id, 
    $returnTo = null, $root = null, $extensions = null, 
    Zend_Controller_Response_Abstract $response = null)
    {
        $this->_setError('');
        
        if (!Agency_OpenId::normalize($id)) {
            $this->_setError(
            "Normalisation failed");
            return false;
        }
        
        $claimedId = $id;
        if (! $this->_discovery($id, $server, 
        $version)) {
        	// retry with normal google account
        	$claimedId = $id = 'https://www.google.com/accounts/o8/id';
        	if (! $this->_discovery($id, $server,
        	$version)) {
		        $this->_setError(
		        "Discovery failed: " .
		         $this->getError());
		        return false;
		    }
        }
        
        if (! $this->_associate($server, 
        $version)) {
            $this->_setError(
            "Association failed: " .
             $this->getError());
            return false;
        }
        if (! $this->_getAssociation($server, 
        $handle, $macFunc, $secret, $expires)) {
            /* Use dumb mode */
            unset($handle);
            unset($macFunc);
            unset($secret);
            unset($expires);
        }
        $params = array();
        if ($version >= 2.0) {
            $params['openid.ns'] = Zend_OpenId::NS_2_0;
        }
        $params['openid.mode'] = $immediate ? 'checkid_immediate' : 'checkid_setup';
        $params['openid.identity'] = $id;
        $params['openid.claimed_id'] = $claimedId;
        if ($version <= 2.0) {
            if ($this->_session !==
             null) {
                $this->_session->identity = $id;
                $this->_session->claimed_id = $claimedId;
                if (false !== strpos($server, 'google')) {
                    $this->_session->identity = 'http://specs.openid.net/auth/2.0/identifier_select';
                    $this->_session->claimed_id = 'http://specs.openid.net/auth/2.0/identifier_select';
                }
            } else 
                if (defined(
                'SID')) {
                    $_SESSION["zend_openid"] = array(
                        "identity" => $id, 
                        "claimed_id" => $claimedId
                    );
                    if (false !== strpos($server, 'google')) {
                        $_SESSION['zend_openid']['identity'] = 'http://specs.openid.net/auth/2.0/identifier_select';
                        $_SESSION['zend_openid']['claimed_id'] = 'http://specs.openid.net/auth/2.0/identifier_select';
                    }
                } else {
                    require_once "Zend/Session/Namespace.php";
                    $this->_session = new Zend_Session_Namespace(
                    "zend_openid");
                    $this->_session->identity = $id;
                    $this->_session->claimed_id = $claimedId;
                    if (false !== strpos($server, 'google')) {
                        $params['openid.identity'] = 'http://specs.openid.net/auth/2.0/identifier_select';
                        $params['openid.claimed_id'] = 'http://specs.openid.net/auth/2.0/identifier_select';
                    }
                }
        }
        if (isset($handle)) {
            $params['openid.assoc_handle'] = $handle;
        }
        $params['openid.return_to'] = Zend_OpenId::absoluteUrl(
        $returnTo);
        if (empty($root)) {
            $root = Zend_OpenId::selfUrl();
            if ($root[strlen(
            $root) - 1] != '/') {
                $root = dirname(
                $root);
            }
        }
        if ($version >= 2.0) {
            $params['openid.realm'] = $root;
        } else {
            $params['openid.trust_root'] = $root;
        }
        if (! Zend_OpenId_Extension::forAll(
        $extensions, 'prepareRequest', $params)) {
            $this->_setError(
            "Extension::prepareRequest failure");
            return false;
        }
        if (false !== strpos($server, 'google')) {
            $params['openid.identity'] = 'http://specs.openid.net/auth/2.0/identifier_select';
            $params['openid.claimed_id'] = 'http://specs.openid.net/auth/2.0/identifier_select';
        }
        Zend_OpenId::redirect($server, $params, 
        $response);
        return true;
    }
}
