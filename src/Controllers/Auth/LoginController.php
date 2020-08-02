<?php

namespace MapDapRest\Controllers\Auth;

class LoginController extends \MapDapRest\Controller
{

    public $APP;


    public function __construct($app, $request, $response, $args)
    {
        $this->APP = $app;
        if ($request->hasParam("login")) {
           $data = ["login"=>$request->params["login"], "password"=>$request->params["password"]];
           $this->APP->auth->login($data);
        }
    }



    public function indexAction($request, $response, $params) {
 
       return $this->APP->auth->getFields();
    }

}