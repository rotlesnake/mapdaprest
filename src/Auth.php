<?php
namespace MapDapRest;

use App\Auth\Models\Roles;

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
               $this->login(["token"=>str_replace("Bearer ","", $token)]);
           }
           if ($this->isGuest() && $request->hasHeader('authorization')) {
               $token = $request->getHeader('authorization');
               $this->login(["token"=>str_replace("Bearer ","", $token)]);
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
            $hours_token = 6;
            $hours_refresh_token = 96;
            $this->user = null;

            if (isset($credentials['login'])) {
                $ModelUsers = $this->ModelUsers;
                $tmpuser = $ModelUsers::where('login', $credentials['login'])->where('status', 1)->first();
                if ($tmpuser && password_verify($credentials['password'], $tmpuser->password)) {
                    if ($tmpuser && strlen($tmpuser->token)>10 && strtotime("now") < strtotime($tmpuser->token_expire) ) {
                        $this->user = $tmpuser;
                        setcookie("token", $this->user->token, time()+($hours_token*60*60), $APP->ROOT_URL, $_SERVER["SERVER_NAME"]);
                        return true;
                    }
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
                if ($tmpuser && strlen($tmpuser->token)>10 && strtotime("now") < strtotime($tmpuser->token_expire) ) { 
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
            if (!$this->user) return;
            $this->user->token = "";
            $this->user->token_expire = date("Y-m-d H:i:s");
            $this->user->save();
            $this->user = null;
            setcookie( "token", "", time()-3600, '/', '');
        }

        //***************************************************************************************************************************
        //***************************************************************************************************************************
        public function register($login, $password, $status, $role_id, $fields=[]) {
            $APP = App::getInstance();
            if ($APP->auth->isGuest()) { 
               $this->setUser(1);
            }

            $ModelUsers = $this->ModelUsers;
            $tmpuser = $ModelUsers::where('login', $login)->first();
            if ($tmpuser) return false;

            $user = new $ModelUsers();
            $user = $user->fillRow("add", $fields);
            $user->login = $login;
            $user->password = password_hash($password, PASSWORD_DEFAULT);
            $user->role_id = $role_id;
            $user->status = $status;
            if (!$user->save()) return false;
            $user->fillRow("add", $fields);

            if ($this->user->id == 1) $this->user = null;

            return $user;
        }



        //изменить пароль
        public function changePassword($newpassword) {
            $this->user->password = password_hash($newpassword, PASSWORD_DEFAULT);
            $this->user->save();
        }




        //***************************************************************************************************************************
        //***************************************************************************************************************************
        //Получить список ролей [id, name]
        public function getRoles() {
            return Roles::whereIn("id", explode(',', $this->user->role_id));
        }
        //Получить список ролей [name]
        public function getRoleNames() {
            $roles = $this->getRoles();
            if (!$roles) return [];
            $arr = [];
            foreach ($roles as $row) {
                array_push($arr, $row->name);
            }
            return $arr;
        }

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
            $fields = $this->user->getConvertedRow();
            $fields["token"] = $this->user->token;
            $fields["token_expire"] = $this->user->token_expire;
            $fields["refresh_token"] = $this->user->refresh_token;
            $fields["refresh_token_expire"] = $this->user->refresh_token_expire;
            return $fields;
        }

        public function hasRoles($checkList=[]) {
            if (gettype($checkList)!="array") $checkList = explode(",", $checkList);
            if (count($checkList)==0) return false;

            $roleIds = array_map('intval', explode(',', $this->user->role_id));
            $roleNames = $this->getRoleNames();
            foreach ($checkList as $v) {
                if ((int)$v>0 && in_array((int)$v, $roleIds)) return true;
                if (gettype($v)=="string" && strlen($v)>1 && in_array($v, $roleNames)) return true;
            }
            return false;
        }

        public function __get($property)
        {
            if ($this->user->{$property}) {
                return $this->user->{$property};
            }
        }


}//class
