<?php
//  Copyright (c) 2010, LoveMachine Inc.
//  All Rights Reserved. 
//  http://www.lovemachineinc.com

/** 
 * ShortUrl
 *
 * @package ShortUrl
 */
class ShortUrl {
    protected $apiKey = '';
    protected $longUrl = '';
    protected $shortUrl = '';
    
    /**
     * Constructor
     * 
     * @param String $url The url to be shortened 
     */
    public function __construct($url) 
    {
        if (defined('GOOGLE_SHORTENER_API_KEY')) {
            $this->apiKey = GOOGLE_SHORTENER_API_KEY;
        }
        $this->longUrl = $url;
    }
    
    /**
     * Fetch the short url from google shortener
     */
    public function getShortUrl()
    {
        if (parse_url($this->longUrl) !== false) {
            if (strlen($this->shortUrl) > 0) {
                return $this->shortUrl;
            }
            
            $url = 'https://www.googleapis.com/urlshortener/v1/url';
            if (!empty($this->apiKey)) {
                $url .= '?key=' . trim($this->apiKey);
            }
            
            $curlres = curl_init();
            curl_setopt_array($curlres, array(
                CURLOPT_URL => $url,
                CURLOPT_SSLVERSION => 3, 
                CURLOPT_SSL_VERIFYPEER => FALSE, 
                CURLOPT_SSL_VERIFYHOST => 2, 
                CURLOPT_CONNECTTIMEOUT => 5, 
                CURLOPT_RETURNTRANSFER => 1, 
                CURLOPT_FOLLOWLOCATION => 1, 
                CURLOPT_HEADER => 0,
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json'
                ),
                CURLOPT_POST => 1, 
                CURLOPT_POSTFIELDS => json_encode(array(
                    'longUrl' => $this->longUrl
                ))
            ));
            
            $result = curl_exec($curlres);
            curl_close($curlres);
            if ($result === false) {
                return false;
            }
            $result = json_decode($result);
            return $this->shortUrl = $result->id;
        } else {
            return null;
        }
    }    
}
