<?php
namespace MapDapRest;


class Response
{

    public $timeInit;
    public $timeStart;
    public $timeFinish;
    public $timeDuration;

    public $code;
    public $headers;
    public $body = null;

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
        $this->body = null;
        $this->timeStart = round(microtime(true) * 1000);
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

       if ($this->body==null) {
           if ($this->error) json_encode($this->error);
           die(); 
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


    public function sendSuccess($body=[], $response_code=200)
    {
        $this->sendResult($body, $response_code, 0);
    }

    public function sendError($body=[], $response_code=500)
    {
        $this->sendResult($body, $response_code, 1);
    }

    public function sendResult($body=[], $response_code=200, $error_code=0)
    {
        http_response_code($response_code);
        foreach ($this->headers as $k=>$v) {
            header("$k: $v");
        }
       
        $this->timeFinish = round(microtime(true) * 1000);
        $this->timeDuration = round($this->timeFinish - $this->timeStart, 3);
        $timeDurationFull  = round($this->timeFinish - $this->timeInit, 3);

        $response = [];
        $response["ok"] = $error_code == 0;
        $response["error"] = $error_code;
        $response["result"] = $body;
        $response["time"] = ["start"=>$this->timeStart, "finish"=>$this->timeFinish, "duration"=>$this->timeDuration, "duration_full"=>$timeDurationFull];

        echo json_encode($response);
        die();
    }


}
