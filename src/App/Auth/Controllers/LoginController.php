<?php
namespace MapDapRest\App\Auth\Controllers;

class LoginController extends \MapDapRest\Controller
{

    public $requireAuth = false;
    public $APP;


    public function __construct($app, $request, $response, $args)
    {
        $this->APP = $app;
        if ($request->hasParam("login") && $request->hasParam("password")) {
            $data = ["login"=>$request->getParam("login"), "password"=>$request->getParam("password")];
            return $this->APP->auth->login($data);
        }
        if ($request->hasParam("token")) {
            $data = ["token"=>$request->getParam("token")];
            return $this->APP->auth->login($data);
        }
    }



    public function indexAction($request, $response, $params) {
       if ($this->APP->auth->isGuest()) {
           $response->setResponseCode(401);
           $message = "Ошибка в логине или пароле";
           if ($request->hasParam("token")) $message = "Ошибка в токене";
           if ($request->hasParam("password")) $message = "Ошибка в логине или пароле";
           return ["error"=>1, "message"=>$message];
           die();
       }

       $user = $this->APP->auth->getFields();
       \MapDapRest\App\Auth\Events\Emits::userLogin($this->APP->auth);
       return ["status"=>1, "token"=>$user["token"], "user"=>$user];
    }

}
