<?php

class Yokaze_Request
{
    private $__https__;
    private $__host__;
    public function __construct($vars = array())
    {
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
            $this->__https__ = true;
        else
            $this->__https__ = false;

        if (isset($_SERVER['HTTP_HOST']))
            $this->__host__ = $_SERVER['HTTP_HOST'];
        else
            $this->__host__ = gethostname();

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
    public function initGet($obj = null)
    {
        if ($obj === null)
            $obj = $this;
        if (!isset($_GET) || !is_array($_GET))
            return $this;
        return $this->initReq($_GET, $obj);
    }
    public function initPost($obj = null)
    {
        if ($obj === null)
            $obj = $this;
        if (!isset($_POST) || !is_array($_POST))
            return $this;
        return $this->initReq($_POST, $obj);
    }
    private function initReq($req, $obj)
    {
        foreach ($req as $k => $v)
            $obj->$k = $v;
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
        if ($this->__https__)
            $location = 'https://';
        else
            $location = 'http://';
        $location .= $this->__host__;
        $location .= preg_replace('|/[^/]+\.php$|', "/$fileName", $_SERVER['SCRIPT_NAME']);
        header("Location: $location");
        exit;
    }
}
