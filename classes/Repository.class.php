<?php
require_once('config.php');
require_once('class/CURLHandler.php');
/*
 * Copyright (c) 2010, LoveMachine Inc.
 * All Rights Reserved.
 * http://www.lovemachineinc.com
 *
 * Repository class
 */
class Repository {
    private static function _webSvnUrl(){
        // 1. Read the config file.
        $config = Zend_Registry::get('config');
        $result = ( $config instanceof Zend_Config ) ? $config->websvn->baseUrl : null;
        if ( empty( $result ) ){
            throw new Exception( "Cannot find a WebSVN base URL under key 'websvn.baseUrl' in configuration." );
        }
        return $result;
    }

    private static function _repoLinkFragment(){
        // 1. Read the config file.
        $config = Zend_Registry::get('config');
        $result = ( $config instanceof Zend_Config ) ? $config->websvn->repLinkFragment : null;
        if ( empty( $result ) ){
            throw new Exception( "Cannot find a WebSVN repo link pattern under key 'websvn.repLinkFragment' in configuration." );
        }
        return $result;
    }

    public static function allAvailableRepositories(){
        // 1. Read the config file. Find a link to WebSVN page
        $web_svn_url = self::_webSvnUrl();
        // 2. Retrieve the content of WebSVN front page
        ob_start();
        CURLHandler::Get($web_svn_url);
        $html = ob_get_contents();
        ob_end_clean();
        // 3. form the regex pattern. We are searching the html for lines like this:
        // '<a href="listing.php?repname=admin&amp;">admin</a>'. Resulting matches are dumped into 'project' array.
        $repoLinkFragment = preg_quote( self::_repoLinkFragment() );
        $regexp = "<a\s[^>]*href=[\"']".$repoLinkFragment."\\b[^>]*>(?P<project>.*)<\/a>";
        return ( preg_match_all( "/$regexp/siU", $html, $matches )) ? $matches['project'] : array();
    }

    public static function getRepoUrl( $repo ){
        if ( empty( $repo ) ){
            throw new Exception( "Repository name is required." );
        }
        return self::_webSvnUrl().'/'.self::_repoLinkFragment().$repo;
    }
}