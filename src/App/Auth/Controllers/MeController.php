<?php

namespace MapDapRest\App\Auth\Controllers;

class MeController extends \MapDapRest\Controller
{

    public $APP;


    public function __construct($app, $request, $response, $args)
    {
        $this->APP = $app;
    }



    public function indexAction($request, $response, $params) {
 
       return ["status"=>1, "user"=>$this->APP->auth->getFields()];
    }

}
