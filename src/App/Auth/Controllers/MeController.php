<?php
namespace MapDapRest\App\Auth\Controllers;

class MeController extends \MapDapRest\Controller
{

    public function indexAction($request, $response, $params) {
       return $this->APP->auth->getFields();
    }

}
