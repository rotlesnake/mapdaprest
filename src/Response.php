<?php
namespace MapDapRest;


class Response
{

    public $code;
    public $headers;
    public $body = [];

    public $error_message = [
                      "0"=>"success",
                      "1"=>"user not authorized",
                      "2"=>"error in login or password",
                      "3"=>"token expired",
                      "4"=>"access denied",
                      "5"=>"data divergence",
                    ];
    public $error = null;


    public function __construct()
    {
        $this->code = 200;
        $this->headers = ["Content-Type"=>"application/json"];
        $this->body = [];
    }


    public function setResponseCode($code)
    {
        $this->code = $code;
    }
    
    public function setContentType($type)
    {
        if ($type=="json") $type="application/json";
        if ($type=="pdf") $type="application/pdf";
        if ($type=="html") $type="text/html";
        if ($type=="xml") $type="application/xml";
        if ($type=="file") $type="application/octet-stream";
        
        $this->headers['Content-Type'] = $type;
    }
 
    public function setBody($body)
    {
        $this->body = $body;
    }

    public function setHeader($key, $value)
    {
        $this->headers[$key] = $value;
    }

    public function setError($code, $message="")
    {
        if (strlen($message)==0) $message = $this->error_message[$code];
        $this->error = ["error"=>$code, "message"=>$message];
    }
    
 

    public function send()
    {
       http_response_code($this->code);
       foreach ($this->headers as $k=>$v) {
          header("$k: $v");
       }
       
       if (gettype($this->body)=="array") {
          if ($this->error) $this->body = array_merge( $this->error , $this->body );
          $this->body = json_encode($this->body);
       }
       if (gettype($this->body)=="object") {
          $this->body = Utils::objectToArray($this->body);
          if ($this->error) $this->body = array_merge( $this->error , $this->body );
          $this->body = json_encode($this->body);
       }

       echo $this->body;
       die();
    }


    public function redirect($url){
        if (substr($url,0,1)=="/") $url = substr($url,1);

        $APP = App::getInstance();
        header("Location: ".$APP->ROOT_URL.$url);
        die();
    }

}
