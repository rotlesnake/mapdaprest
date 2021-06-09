<?php

namespace MapDapRest\App\Auth\Controllers;

class LogoutController extends \MapDapRest\Controller
{

    public $APP;


    public function __construct($app, $request, $response, $args)
    {
        $this->APP = $app;
    }


    public function indexAction($request, $response, $params) {
       \MapDapRest\App\Auth\Events\Emits::userLogout($this->APP->auth);
 
       return $this->APP->auth->logout();
    }

}