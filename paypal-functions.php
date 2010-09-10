<?php
//  vim:ts=4:et

//  Copyright (c) 2010, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com


/*******************************************************
    Page: paypal-functions.php
    Features:  Paypal Functions.  
        PPHttpPost: NVP post function for masspay.
    Author: Jason (jkofoed@gmail.com)
    Date: 2010-04-01 [Happy April Fool's!]

********************************************************/
    
    function PPHttpPost($methodName_, $nvpStr_, $credentials) {
        $environment = 'live'; // 'sandbox' or 'beta-sandbox' or 'live'
        $pp_user = $credentials['pp_api_username'];
        $pp_pass = $credentials['pp_api_password'];
        $pp_signature = $credentials['pp_api_signature'];

        $API_Endpoint = "https://api-3t.paypal.com/nvp";
        if("sandbox" === $environment || "beta-sandbox" === $environment) {
            $API_Endpoint = "https://api-3t.$environment.paypal.com/nvp";
        }
        $version = urlencode('51.0');

        // Set the curl parameters.
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $API_Endpoint);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);

        // Turn off the server and peer verification (TrustManager Concept).
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);

        // Set the API operation, version, and API signature in the request.
        $nvpreq = 'METHOD='.$methodName_.'&VERSION='.$version.'&PWD='.$pp_pass.'&USER='.$pp_user.'&SIGNATURE='.$pp_signature.''.$nvpStr_;

        // Set the request as a POST FIELD for curl.
        curl_setopt($ch, CURLOPT_POSTFIELDS, $nvpreq);

        // Get response from the server.
        $httpResponse = curl_exec($ch);

        if(!$httpResponse) {
            exit("$methodName_ failed: ".curl_error($ch).'('.curl_errno($ch).')');
        }

        // Extract the response details.
        $httpResponseAr = explode("&", $httpResponse);
        $httpParsedResponseAr = array();
        foreach ($httpResponseAr as $i => $value) {
            $tmpAr = explode("=", $value);
            if(sizeof($tmpAr) > 1) {
                $httpParsedResponseAr[$tmpAr[0]] = $tmpAr[1];
            }
        }
        $httpParsedResponseAr["nvpEndpoint"] = $API_Endpoint;
        $httpParsedResponseAr["nvpString"] = $nvpreq;
        if((0 == sizeof($httpParsedResponseAr)) || !array_key_exists('ACK', $httpParsedResponseAr)) {
            exit("Invalid HTTP Response for POST request($nvpreq) to $API_Endpoint.");
        }

        return $httpParsedResponseAr;
    }

?>
