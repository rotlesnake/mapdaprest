<?php
namespace MapDapRest;

use App\Auth\Models\Roles;

class Auth
{

	public $ModelUsers = "\\MapDapRest\\App\\Auth\\Models\\Users";
	public $user = null;
	public $user_token = null;
	public $user_acl = null;
	
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
            $ModelUserToken = $APP->getModel("user_tokens");
            $ModelUsers = $this->ModelUsers;
            $this->user = null;
            $this->user_token = null;
            $this->user_acl = null;

            //Удаляем просроченные токены
            $APP->DB::table("user_tokens")->where("expire", "<=", date("Y-m-d"))->delete();

            if (isset($credentials['login']) && isset($credentials['password'])) {
                $tmpuser = $ModelUsers::where('login', $credentials['login'])->where('status', 1)->first();
                if ($tmpuser && password_verify($credentials['password'], $tmpuser->password)) {
                    $this->appendToken($tmpuser, isset($tmpuser->token_hours) ? $tmpuser->token_hours : 3);
                    return true;
                }
            }
    
            if (isset($credentials['token'])) {
                $tmptoken = $ModelUserToken::where('token', $credentials['token'])->first();
                if ($tmptoken && strlen($tmptoken->token)>10 && strtotime("now") < strtotime($tmptoken->expire) ) { 
                    $tmpuser = $ModelUsers::find($tmptoken->user_id);
                    if ($tmpuser && $tmpuser->status == 1) {
                        $this->user_token = $tmptoken;
                        $this->user = $tmpuser;
                        $tmptoken->touch();
                        return true;
                    }
                }
            }

            return false;
        }


        public function appendToken($tmpuser, $hours_token=3, $hours_refresh_token=96) {
            $APP = App::getInstance();
            $this->user = $tmpuser;

            $ModelUserToken = $APP->getModel("user_tokens");
            $tmptoken = new $ModelUserToken();
            $tmptoken->user_id = $tmpuser->id;
            $tmptoken->token = sha1($tmpuser->login . $tmpuser->password . time());
            $tmptoken->expire = date("Y-m-d H:i:s", strtotime("now +".$hours_token." hours"));
            $tmptoken->browser_ip    = \MapDapRest\Utils::getRemoteIP();
            $tmptoken->browser_agent = isset($APP->request) ? $APP->request->getHeader("user-agent") : "";
            $tmptoken->save();

            $this->user_token = $tmptoken;

            setcookie("token", $this->user_token->token, strtotime($this->user_token->expire), $APP->ROOT_URL, $_SERVER["SERVER_NAME"]);
            return true;
        }

        public function setUser($id) {
            $this->user = null;
            $this->user_token = null;
            $this->user_acl = null;
		
            $ModelUsers = $this->ModelUsers;
            $tmpuser = $ModelUsers::where('id', $id)->where('status', 1)->first();
            if ($tmpuser) { 
                $this->user = $tmpuser;
                return true;
            }
            return false;
        }

        //Забываем данные авторизации, становимся гостем
        public function logout() {
            $this->user = null;
            $this->user_acl = null;
            if (!$this->user_token) $this->user_token->delete();
            $this->user_token = null;

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
        //Получить user acl
        public function getAcl() {
            if (!$this->user_acl) $this->user_acl = \MapDapRest\Utils::getUserAcl($this->user->id);
            return $this->user_acl;
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
            $fields["acl"] = $this->getAcl();
            $fields["token"] = isset($this->user_token) ? $this->user_token->token : "";
            $fields["token_expire"] = isset($this->user_token) ? $this->user_token->expire : "";
            return $fields;
        }

        public function hasAcl($checkList=[]) {
            if (gettype($checkList)!="array") $checkList = explode(",", $checkList);
            if (count($checkList)==0) return false;

            $roleIds = array_map('intval', explode(',', $this->user->role_id));
            $acl = $this->getAcl();
            foreach ($checkList as $v) {
                if ((int)$v>0 && in_array((int)$v, $roleIds)) return true;
                if (gettype($v)=="string" && strlen($v)>1 && in_array($v, $acl)) return true;
            }
            return false;
        }
        public function hasRoles($checkList=[]) {
            if (gettype($checkList)!="array") $checkList = explode(",", $checkList);
            if (count($checkList)==0) return false;

            $roleIds = array_map('intval', explode(',', $this->user->role_id));
            $acl = $this->getAcl();
            foreach ($checkList as $v) {
                if ((int)$v>0 && in_array((int)$v, $roleIds)) return true;
                if (gettype($v)=="string" && strlen($v)>1 && in_array($v, $acl)) return true;
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
