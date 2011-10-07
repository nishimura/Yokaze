<?php
/**
 * Simple Session Management Class File
 *
 * PHP versions 5
 *
 * @author    Satoshi Nishimura <nishim314@gmail.com>
 * @copyright 2005-2011 Satoshi Nishimura
 */

/**
 * Simple session manager class.
 *
 * @author    Satoshi Nishimura <nishim314@gmail.com>
 */
class Yokaze_Session
{
    /** @var bool */
    private $isStarted = false;
    /** @ver int */
    private $lifetime = 0;
    /** @var string */
    private $path = '/';
    /** @ver string */
    private $domain;

    public function setSessionName($name)
    {
        session_name($name);
        return $this;
    }

    public function setSid($sid)
    {
        session_id($sid);
        return $this;
    }

    public function setLifetime($time)
    {
        $this->lifetime = $time;
        return $this;
    }

    public function setPath($path)
    {
        $this->path = $path;
        return $this;
    }

    public function setDomain($domain)
    {
        $this->domain = $domain;
        return $this;
    }

    public function start()
    {
        session_set_cookie_params($this->lifetime, $this->path, $this->domain);
        session_start();
        $this->isStarted = true;
        $this->init();
    }

    public function end(){
        foreach ($_SESSION as $k => $v)
            unset($_SESSION[$k]);
        if (isset($_COOKIE[session_name()])){
            setcookie(session_name(), '', time()-4200);
        }

        session_destroy();
        $this->isStarted = false;
    }

    /**
     * Initialization of session. todo.
     */
    public function init(){
        $this->set('_ip_'  , $_SERVER['REMOTE_ADDR']);
        if (isset($_SERVER['HTTP_USER_AGENT']))
            $this->set('_ua_', $_SERVER['HTTP_USER_AGENT']);
        else
            $this->set('_ua_', 'undefined');
        $this->set('_time_', time());
    }

    /**
     * Validation by ip address
     * @return bool
     * @access public
     */
    public function checkIp(){
        list($first, $second, $third, $forth) = explode('.',$_SERVER['REMOTE_ADDR']);
        if (!$s_ip = $this->get('_ip_')){ return false; }
        
        list($s_first, $s_second, $s_third, $s_forth) = explode('.', $s_ip);

        if ($first != $s_first || $second != $s_second || $third != $s_third){
            return false;
        }

        return true;
    }

    /**
     * Validation by user agent
     *
     * @return bool
     * @access public
     */
    public function checkUa(){
        if (isset($_SERVER['HTTP_USER_AGENT']))
            $ua = $_SERVER['HTTP_USER_AGENT'];
        else
            $ua = 'undefined';

        return ($this->get('_ua_') ==  $ua);
    }

    /**
     * Returns value by name
     * @param string $name
     * @return mixed
     * @access public
     */
    function get($name){
        if (!$this->isStarted)
            $this->start();

        if (isset($_SESSION[$name])){
            return $_SESSION[$name];
        }
    }

    /**
     * Sets value
     * @param string $name
     * @param mixed $value;
     * @access public
     */
    function set($name, $value){
        if (!$this->isStarted)
            $this->start();
        $_SESSION[$name] = $value;
    }

    /**
     * Deletes value by name
     *
     * @param string $name
     * @access public
     */
    function remove($name){
        if (!$this->isStarted)
            $this->start();
        $ret = $this->get($name);
        unset($_SESSION[$name]);
        return $ret;
    }

    /**
     * Alias of session_id
     *
     * @return string
     */
    public function getSid(){
        if (!$this->isStarted)
            $this->start();
        return $this->getSessionId();
    }

    /**
     * Returns session name
     *
     * @return string
     */
    public function getSessionName(){
        if (!$this->isStarted)
            $this->start();
        return session_name();
    }

    /**
     * Changes session id
     * @return bool
     */
    public function changeSessionId(){
        if (!$this->isStarted)
            $this->start();
        return session_regenerate_id(true);
    }
}
