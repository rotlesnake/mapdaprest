<?php

namespace MapDapRest\App\Auth\Controllers;

class LoginController extends \MapDapRest\Controller
{

    public $requireAuth = false;
    public $APP;


    public function __construct($app, $request, $response, $args)
    {
        $this->APP = $app;
        if ($request->hasParam("token")) {
            $data = ["token"=>$request->params["token"]];
            $this->APP->auth->login($data);
            return;
        }        
        if ($request->hasParam("login")) {
            $data = ["login"=>$request->params["login"], "password"=>$request->params["password"]];
            $this->APP->auth->login($data);
            return;
        }
    }



    public function indexAction($request, $response, $params) {
       if ($this->APP->auth->isGuest()) {
           $response->setResponseCode(401);
           $response->setError(1, "Пользователь не найден");
           if ($request->hasHeader('token')) { 
               $response->setError(3, "Токен просрочен либо не действителен");
           }
           return [];
       }

       $user = $this->APP->auth->getFields();
       \MapDapRest\App\Auth\Events\Emits::userLogin($this->APP->auth);
       return ["status"=>1, "token"=>$user["token"], "user"=>$user];
    }

}
