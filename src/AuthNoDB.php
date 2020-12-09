<?php

namespace MapDapRest;


class EmptyClass {
  public $login = 'Админ настройки системы';
}


class AuthNoDB
{

	public $user = null;
	public $login    = "admin";
	public $password = "12345";
	public $token = "-";

	
	public function __construct(){
            $this->token = sha1($this->login.$this->password);
	}
	

        //Проверяем текущий пользователь гость
        public function isGuest() {
          if (!isset($this->user)) return true;
          return false;
        }
        
        //Пытаемся авторизоваться по данным из кукисов и хедеров
        public function autoLogin($request) {
           if ($this->isGuest() && $request->hasHeader('Authorization')) {
               $token = $request->getHeader('Authorization');
               $this->login(["token"=>$token]);
           }
           if ($this->isGuest() && $request->hasHeader('token')) {
               $token = $request->getHeader('token');
               $this->login(["token"=>$token]);
           }
           if ($this->isGuest() && $request->hasCookie('token')) {
               $token = $request->getCookie('token');
               $this->login(["token"=>$token]);
           }
        }

        //Авторизуемся в системе входные параметры [login, password, refresh_token, token]
        public function login($credentials) {
            $APP = App::getInstance();
            if (isset($credentials['login']) && $credentials['login']==$this->login && $credentials['password']==$this->password) {
                $hours_token = 4;
                $this->user = new EmptyClass();
                $this->user->token = $this->token;
                setcookie("token", $this->user->token, time()+($hours_token*60*60), $APP->ROOT_URL, $_SERVER["SERVER_NAME"]);
                return true;
            }
            if (isset($credentials['token']) && $credentials['token']==$this->token) {
                $this->user = new EmptyClass();
                $this->user->token = $this->token;
                return true;
            }
            return false;
        }


        public function logout() {
            $this->user = null;
            setcookie( "token", "", time()-3600, '/', '');
        }

        public function getFields() {
            return (array)$this->user;
        }


}//class
