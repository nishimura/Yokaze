<?php

class Yokaze_Request
{
    private $https;
    private $host;
    public function __construct($vars = array())
    {
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
            $this->https = true;
        else
            $this->https = false;

        if (isset($_SERVER['HTTP_HOST']))
            $this->host = $_SERVER['HTTP_HOST'];
        else
            $this->host = gethostname();

        foreach ($vars as $k => $v)
            $this->$k = $v;
    }

    public function get($name)
    {
        if (isset($_GET[$name]))
            return $_GET[$name];
        elseif (isset($_POST[$name]))
            return $_POST[$name];
    }
    public function getMethod()
    {
        return $_SERVER['REQUEST_METHOD'];
    }
    public function isPost()
    {
        return strtolower($this->getMethod()) === 'post';
    }
    public function initGet()
    {
        if (!isset($_GET) || !is_array($_GET))
            return $this;
        return $this->initReq($_GET);
    }
    public function initPost()
    {
        if (!isset($_POST) || !is_array($_POST))
            return $this;
        return $this->initReq($_POST);
    }
    private function initReq($req)
    {
        foreach ($req as $k => $v)
            $this->$k = $v;
        return $this;
    }
    public function initDto($dto)
    {
        $tmp = clone $dto;
        foreach ($dto as $k => $v){
            if (isset($this->$k))
                $dto->$k = $this->$k;
            else
                $this->$k = $dto->$k;
        }
    }

    public function redirect($fileName)
    {
        if ($this->https)
            $location = 'https://';
        else
            $location = 'http://';
        $location .= $this->host;
        $location .= preg_replace('|/[^/]+\.php$|', "/$fileName", $_SERVER['SCRIPT_NAME']);
        header("Location: $location");
    }
}
