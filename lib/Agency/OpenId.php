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
require_once ('Zend/OpenId/Consumer.php');
require_once ('Zend/Validate/EmailAddress.php');
/**
 * Modified openid consumer to be spec 2.0 compliant
 *
 * @author Thomas Stachl <thomas@stachl.me>
 * @copyright Copyright (c) 2009 Thomas Stachl
 */
class Agency_OpenId extends Zend_OpenId
{
    /**
     * Normalizes OpenID identifier that can be URL or XRI name.
     * Returns true on success and false of failure.
     *
     * Normalization is performed according to the following rules:
     * 1. If the user's input starts with one of the "xri://", "xri://$ip*",
     *    or "xri://$dns*" prefixes, they MUST be stripped off, so that XRIs
     *    are used in the canonical form, and URI-authority XRIs are further
     *    considered URL identifiers.
     * 2. If the first character of the resulting string is an XRI Global
     *    Context Symbol ("=", "@", "+", "$", "!"), then the input SHOULD be
     *    treated as an XRI.
     * 3. Otherwise, the input SHOULD be treated as an http URL; if it does
     *    not include a "http" or "https" scheme, the Identifier MUST be
     *    prefixed with the string "http://".
     * 4. URL identifiers MUST then be further normalized by both following
     *    redirects when retrieving their content and finally applying the
     *    rules in Section 6 of [RFC3986] to the final destination URL.
     * @param string &$id identifier to be normalized
     * @return bool
     */
    static public function normalize(&$id)
    {
        $validator = new Zend_Validate_EmailAddress();
        if ($validator->isValid($id)) {
            if (false !== strpos($id, 'gmail')) {
                $id = 'https://www.google.com/accounts/o8/id';
                return true;
            }
            $email = explode('@', $id);
            $id = 'https://www.google.com/accounts/o8/site-xrds?ns=2&hd=' . $email[1];
            return true;
        } else {
            return Zend_OpenId::normalize($id);
        }
    }
}