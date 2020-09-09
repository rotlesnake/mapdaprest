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
        $this->headers = apache_request_headers();
        $post = json_decode(file_get_contents('php://input'), true);
        $this->params = array_merge( $_REQUEST, (array)$post );
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


    public function getFiles()
    {
        return $this->files;
    }
    public function getFile($name)
    {
        return $this->files[$name];
    }

}
