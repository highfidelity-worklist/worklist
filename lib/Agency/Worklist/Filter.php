<?php
//  Copyright (c) 2010, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com

require_once ('functions.php');
require_once ('classes/User.class.php');
require_once ('workitem.class.php');

class Agency_Worklist_Filter
{

    protected $user = 0;
    protected $status = 'BIDDING';
    protected $query = '';
    protected $sort = 'priority';
    protected $dir = 'ASC';
    protected $page = 1;

    /**
     * @return the $user
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return the $status
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return the $query
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @return the $sort
     */
    public function getSort()
    {
        return $this->sort;
    }

    /**
     * @return the $dir
     */
    public function getDir()
    {
        return $this->dir;
    }
    
    /**
     * @return the $page
     */
    public function getPage()
    {
        return $this->page;	
    }

    /**
     * @param $user the $user to set
     */
    public function setUser($user)
    {
        $this->user = (int) $user;
        return $this;
    }

    /**
     * @param $status the $status to set
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @param $query the $query to set
     */
    public function setQuery($query)
    {
        $this->query = $query;
        return $this;
    }

    /**
     * @param $query the $query to set
     */
    public function setPage($page)
    {
        $this->page = (int)$page;
        return $this;
    }

    /**
     * @param $sort the $sort to set
     */
    public function setSort($sort)
    {
        switch (strtoupper($sort)) {
            case 'WHO':
                $sort = 'creator_nickname';
                break;
            case 'SUMMARY':
                $sort = 'summary';
                break;
            case 'WHEN':
                $sort = 'delta';
                break;
            case 'STATUS':
                $sort = 'status';
                break;
            case 'COMMENTS':
                $sort = 'comments';
                break;
            case 'PRIORITY':
            default:
                $sort = 'priority';
                break;
        }
        $this->sort = $sort;
        return $this;
    }

    /**
     * @param $dir the $dir to set
     */
    public function setDir($dir)
    {
        if ($dir == 'desc') {
            $this->dir = strtoupper(
            $dir);
        } else {
            $this->dir = 'ASC';
        }
        return $this;
    }

    public function getUserSelectbox()
    {
        $users = User::getUserlist(
        getSessionUserId());
        $box = '<select name="user">';
        $box .= '<option value="0"' . (($this->getUser() ==
         0) ? ' selected="selected"' : '') . '>All Users</option>';
        foreach ($users as $user) {
            $box .= '<option value="' .
             $user->getId() . '"' .
             (($this->getUser() ==
             $user->getId()) ? ' selected="selected"' : '') .
             '>' . $user->getNickname() .
             '</option>';
        }
        $box .= '</select>';
        return $box;
    }

    public function getStatusSelectbox()
    {
        $status_array = array_merge(
        array('ALL'
        ), WorkItem::getStates());
        $box = '<select name="status">';
        foreach ($status_array as $status) {
            $selected = '';
            if ($this->getStatus() ==
             $status) {
                $selected = ' selected="selected"';
            }
            $box .= '<option value="' .
             $status . '"' . $selected .
             '>' . $status . '</option>';
        }
        $box .= '</select>';
        return $box;
    }

    public function __construct(array $options = array())
    {
        if (!empty($options) && ($options['reload'] ==
         'false')) {
            $this->setOptions(
            $options);
        } elseif (getSessionUserId() > 0) {
            $this->initByDatabase();
        } else {
            $this->initByCookie();
        }
    }

    private function setOptions(array $options)
    {
        $cleanOptions = array();
        $methods = get_class_methods($this);
        foreach ($options as $key => $value) {
            $method = 'set' . ucfirst(
            $key);
            if (in_array($method, 
            $methods)) {
                $this->$method(
                $value);
                $cleanOptions[$key] = $value;
            }
        }
        $this->save(serialize($cleanOptions));
        return $this;
    }

    private function saveToDatabase($serializedOptions)
    {
        $user = new User();
        $user->findUserById(getSessionUserId());
        $user->setFilter($serializedOptions);
        $user->save();
    }

    private function saveToCookie($serializedOptions)
    {
        $setcookie = setcookie('FilterCookie', 
        $serializedOptions, time() + 3600, '/', 
        SERVER_NAME, false, false);
        if ($setcookie === false) {
            throw new Exception(
            'Cookie could not be set!');
        }
    }

    private function save($serializedOptions)
    {
        if (getSessionUserId() > 0) {
            $this->saveToDatabase(
            $serializedOptions);
        } else {
            $this->saveToCookie(
            $serializedOptions);
        }
    }

    private function initByDatabase()
    {
        $user = new User();
        $user->findUserById(getSessionUserId());
        if ($user->getFilter()) {
            $this->setOptions(
            unserialize(
            $user->getFilter()));
        }
    }

    private function initByCookie()
    {
        if (isset($_COOKIE['FilterCookie'])) {
            $this->setOptions(
            unserialize(
            $_COOKIE['FilterCookie']));
        }
    }
}