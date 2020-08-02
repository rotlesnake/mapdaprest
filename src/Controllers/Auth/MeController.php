<?php

namespace MapDapRest\Controllers\Auth;

class MeController extends \MapDapRest\Controller
{

    public $APP;


    public function __construct($app, $request, $response, $args)
    {
        $this->APP = $app;
    }



    public function indexAction($request, $response, $params) {
 
       return $this->APP->auth->getFields();
    }

}