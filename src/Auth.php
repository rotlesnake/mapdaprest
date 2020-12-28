<?php

namespace MapDapRest;



class Auth
{

	public $ModelUsers = "\\MapDapRest\\App\\Auth\\Models\\Users";
	public $user = null;
	
	public function __construct(){

	}
	

        //Проверяем текущий пользователь гость
        public function isGuest() {
          if (!isset($this->user)) return true;
          if (!isset($this->user->id)) return true;
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
           //Если гость и есть логин пароль в basic auth то авторизуемся логином и паролем
           if ($this->isGuest() && isset($_SERVER["PHP_AUTH_USER"]) && isset($_SERVER["PHP_AUTH_PW"])) {
               $this->login(["login"=>$_SERVER["PHP_AUTH_USER"], "password"=>$_SERVER["PHP_AUTH_PW"]]);
           }
        }

        //Авторизуемся в системе входные параметры [login, password, refresh_token, token]
        public function login($credentials) {
            $APP = App::getInstance();
            $hours_token = 4;
            $hours_refresh_token = 25;

            if (isset($credentials['login'])) {
                $ModelUsers = $this->ModelUsers;
                $tmpuser = $ModelUsers::where('login', $credentials['login'])->where('status', 1)->first();
                if ($tmpuser && password_verify($credentials['password'], $tmpuser->password)) {
                    $this->user = $tmpuser;
                    $this->user->token_expire = date("Y-m-d H:i:s", strtotime("+".$hours_token." hours"));
                    $this->user->token = sha1($credentials['login'].$this->user->password.$this->user->token_expire);
                    $this->user->refresh_token_expire = date("Y-m-d H:i:s", strtotime("+".$hours_refresh_token." hours"));
                    $this->user->refresh_token = sha1($credentials['login'].$this->user->password.$this->user->refresh_token_expire);
                    $this->user->save();
                    setcookie("token", $this->user->token, time()+($hours_token*60*60), $APP->ROOT_URL, $_SERVER["SERVER_NAME"]);
                    return true;
                }
            }

            if (isset($credentials['refresh_token'])) {
                $ModelUsers = $this->ModelUsers;
                $tmpuser = $ModelUsers::where('refresh_token', $credentials['refresh_token'])->where('status', 1)->first();
                if ($tmpuser && strtotime("now") < strtotime($tmpuser->refresh_token_expire) ) { 
                    $this->user = $tmpuser;
                    $this->user->token_expire = date("Y-m-d H:i:s", strtotime("+".$hours_token." hours"));
                    $this->user->token = sha1($credentials['login'].$this->user->password.$this->user->token_expire);
                    $this->user->refresh_token_expire = date("Y-m-d H:i:s", strtotime("+".$hours_refresh_token." hours"));
                    $this->user->refresh_token = sha1($credentials['login'].$this->user->password.$this->user->refresh_token_expire);
                    $this->user->save();

                    setcookie("token", $this->user->token, time()+($hours_token*60*60), $APP->ROOT_URL, $_SERVER["SERVER_NAME"]);
                    return true;
                }
            }
    
            if (isset($credentials['token'])) {
                $ModelUsers = $this->ModelUsers;
                $tmpuser = $ModelUsers::where('token', $credentials['token'])->where('status', 1)->first();
                if ($tmpuser && strtotime("now") < strtotime($tmpuser->token_expire) ) { 
                    $this->user = $tmpuser;
                    return true;
                }
            }

            return false;
        }


        public function setUser($id) {
            $ModelUsers = $this->ModelUsers;
            $tmpuser = $ModelUsers::where('id', $id)->where('status', 1)->first();
            if ($tmpuser) { 
                $this->user = $tmpuser;
                return true;
            }
        }

        //Забываем данные авторизации, становимся гостем
        public function logout() {
            $this->user->token = "";
            $this->user->token_expire = date("Y-m-d H:i:s");
            $this->user->save();
            $this->user = null;
            setcookie( "token", "", time()-3600, '/', '');
        }

        //изменить пароль
        public function changePassword($newpassword) {
            $this->user->password = password_hash($newpassword, PASSWORD_DEFAULT);
            $this->user->save();
        }




        //***************************************************************************************************************************
        //***************************************************************************************************************************
        //Поля таблицы пользователя
        public function getFields($keys=[], $exclude=[]) {
            $fields = $this->getAllFields();
            unset($fields["password"]);
            unset($fields["created_by_user"]);
            unset($fields["created_at"]);
            unset($fields["updated_at"]);
  
            if (count($keys)>0) {
               $fields = array_filter($fields, function($k) use($keys) { return in_array($k,$keys); }, ARRAY_FILTER_USE_KEY);
            }
            if (count($exclude)>0) {
               foreach ($exclude as $k=>$v) {
                  unset($fields[$v]);
               }
            }
            
            return $fields;
        }


        //Поля таблицы пользователя
        public function getAllFields() {
          $fields = $this->user->attributesToArray();
          return $fields;
        }

        public function hasRoles($checkList=[]) {
            return $this->user->hasRoles($checkList);
        }

        public function __get($property)
        {
            if ($this->user->{$property}) {
                return $this->user->{$property};
            }
        }


}//class
