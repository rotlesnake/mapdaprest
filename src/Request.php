<?php
namespace MapDapRest;


class Request
{

    public $method;
    public $headers;
    public $params;
    public $cookies;
    public $files;


    public function __construct()
    {
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->headers = array_change_key_case( apache_request_headers(), CASE_LOWER );
        $postRaw = file_get_contents('php://input');
        $postJson = json_decode($postRaw, true);
        $postPara = [];
        if (substr($postRaw,0,1)=="{" || substr($postRaw,0,1)=="[") {
        } else{
           parse_str($postRaw, $postPara);
        }
        $this->params = array_merge( $_REQUEST, (array)$postPara, (array)$postJson );
        $this->files = $_FILES;
        $this->cookies = $_COOKIE;
    }


    public function getMethod()
    {
        return $this->method;
    }

    public function isMethod($method)
    {
        return $this->getMethod() === $method;
    }

    
    public function getHeaders()
    {
        return $this->headers;
    }
    
    public function hasHeader($name)
    {
        return isset($this->headers[$name]);
    }

    public function getHeader($name)
    {
        return $this->headers[$name];
    }

    
    public function getCookies()
    {
        return $this->cookies;
    }

    public function hasCookie($name)
    {
        return isset($this->cookies[$name]);
    }

    public function getCookie($name)
    {
        return $this->cookies[$name];
    }


    
    public function isXhr()
    {
        return $this->headers['X-Requested-With'] === 'XMLHttpRequest';
    }

    public function getContentType()
    {
        return $this->headers['Content-Type'];
    }
 

    public function getParams()
    {
        return $this->params;
    }

    public function hasParam($name)
    {
        return isset($this->params[$name]);
    }

    public function getParam($name)
    {
        return $this->params[$name];
    }

    public function setParam($name, $value)
    {
        $this->params[$name] = $value;
    }


    public function getFiles()
    {
        return $this->files;
    }
    public function getFile($name)
    {
        return $this->files[$name];
    }

}
