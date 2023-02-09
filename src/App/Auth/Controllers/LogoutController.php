<?php
namespace MapDapRest\App\Auth\Controllers;

class LogoutController extends \MapDapRest\Controller
{

    public function indexAction($request, $response, $params) {
       \MapDapRest\App\Auth\Events\Emits::userLogout($this->APP->auth);
       return $this->APP->auth->logout();
    }

}