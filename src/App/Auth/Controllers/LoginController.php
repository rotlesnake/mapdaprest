<?php
namespace MapDapRest\App\Auth\Controllers;

class LoginController extends \MapDapRest\Controller
{

    public $requireAuth = false;
    public $APP;


    public function __construct($app, $request, $response, $args)
    {
        $this->APP = $app;
        if ($request->hasParam("login")) {
            $data = ["login"=>$request->getParam("login"), "password"=>$request->getParam("password")];
            $this->APP->auth->login($data);
            if (!$this->APP->auth->isGuest()) return;
        }
        if ($request->hasParam("token")) {
            $data = ["token"=>$request->getParam("token")];
            $this->APP->auth->login($data);
            if (!$this->APP->auth->isGuest()) return;
        }        
        return $response->sendError(["message"=>"Ошибка в логине или пароле"], 500);
    }



    public function indexAction($request, $response, $params) {
       if ($this->APP->auth->isGuest()) {
           if ($request->hasHeader('token')) { 
               $response->sendError(["message"=>"Токен просрочен либо не действителен"], 401);
           }
           $response->sendError(["message"=>"Пользователь не найден"], 401);
       }

       $user = $this->APP->auth->getFields();
       \MapDapRest\App\Auth\Events\Emits::userLogin($this->APP->auth);
       $response->sendSuccess(["token"=>$user["token"], "user"=>$user]);
    }

}
