<?php

namespace MapDapRest\App\Auth\Models;



class Users extends \MapDapRest\Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';



    public function scopeFilterRead($query)
    {
	$APP = \MapDapRest\App::getInstance();
        if (!$APP->auth->user) { throw new Exception('user not found'); }
 
        if ($APP->auth->user->hasRoles([1])) return $query;

        return $query->where('created_by_user', '=', $APP->auth->user->id);
    }

    public function scopeFilterEdit($query)
    {
        return $query;
    }

    public function scopeFilterDelete($query)
    {
        return $query;
    }


    //************************************************************************************************
        //Привязка один к одному
        public function role()
        {
            return $this->hasOne('App\Auth\Models\Roles', 'id', 'role_id');
        }


        public function getRolesAttribute() {
           return array_map('intval', explode(',', $this->role_id));
        }

        //проверить на содержание одной из ролей
        public function hasRoles($checkList=[]) {
          if (gettype($checkList)!="array") $checkList = explode(",", $checkList);
          if (count($checkList)==0) return false;

          $rolesList = $this->roles;
          foreach ($checkList as $v) {
             if (in_array($v, $rolesList)) return true;
          }
          return false;
        }
    //************************************************************************************************


    public static function modelInfo() {
      $acc_admin = [1];
      $acc_all = [1,2,3,4,5,6,7,8];
      
      return [
	"table"=>"users",
	"primary_key"=>"id",
	"category"=>"Система",
	"name"=>"Пользователи",

        "sortBy"=>["id"],
        "sortDesc"=>["asc"],
        "itemsPerPage"=>100,
        "itemsPerPageVariants"=>[50,100,200,300,500,1000],

	"read"=>$acc_all,
	"add"=>[],
	"edit"=>[],
	"delete"=>[],
	
	"type"=>"standart",

        //"parentTables"=>[["table"=>"user", "id"=>"user_id"]],
        //"childrenTables"=>[["table"=>"user_posts", "id"=>"user_id"]],

        "filter"=>[
            "created_by_user"=>[
		"label"=>"Кто создал",
                "filterType"=>"like",
            ],
        ],

	"columns"=>[
		"id"=>[
 			"type"=>"integer",
 			"label"=>"id",
 			"width"=>200,
 			"read"=>$acc_all,
 			"add"=>[],
 			"edit"=>[],
		],
		"created_at"=>[
 			"type"=>"timestamp",
 			"label"=>"Дата создания",
 			"width"=>200,
 			"read"=>$acc_all,
 			"add"=>[],
 			"edit"=>[],
		],
		"updated_at"=>[
 			"type"=>"timestamp",
 			"label"=>"Дата изменения",
 			"width"=>200,
 			"hidden"=>true,
 			"read"=>$acc_all,
 			"add"=>[],
 			"edit"=>[],
		],
		"created_by_user"=>[
 			"type"=>"linkTable",
 			"label"=>"Создано пользователем",
 			"table"=>"user",
 			"field"=>"<%login%>",
 			"multiple"=>false,
 			"typeSelect"=>"table",
 			"object"=>false,
 			"width"=>200,
 			"read"=>$acc_all,
 			"add"=>[],
 			"edit"=>[],
		],


		"login"=>[
 			"type"=>"string",
 			"label"=>"Логин",
 			"placeholder"=>"Фамилия Имя - пользователя",
 			"hint"=>"Уникальное поле",
                        "index"=>"unique",
 			"width"=>200,
 			"rules"=>"[ v => v.length>2 || 'Обязательное поле' ]",
 			"read"=>$acc_all,
 			"add"=>[],
 			"edit"=>[],
		],
		"password"=>[
 			"type"=>"password",
 			"label"=>"Пароль",
 			"width"=>200,
 			"rules"=>"[ v => v.length==0 || v.length>7 || 'Минимальная длинна 8 символов' ]",
 			"defaut"=>"12345678",
 			"read"=>$acc_all,
 			"add"=>[],
 			"edit"=>[],
		],
		"role_id"=>[
 			"type"=>"linkTable",
 			"label"=>"Роли",
 			"table"=>"roles",
 			"field"=>"<%name%>",
 			"multiple"=>false,
 			"typeSelect"=>"table",
 			"object"=>false,
 			"width"=>200,
 			"read"=>$acc_all,
 			"add"=>[],
 			"edit"=>[],
		],
		"status"=>[
 			"type"=>"select",
 			"label"=>"Статус",
 			"typeSelect"=>"combobox",
 			"items"=>["1"=>"Активный", "0"=>"Заблокирован"],
 			"defaut"=>"1",
 			"width"=>200,
 			"read"=>$acc_all,
 			"add"=>[],
 			"edit"=>[],
		],
		"token"=>[
 			"type"=>"string",
 			"label"=>"Токен",
                        "index"=>"index",
 			"width"=>200,
 			"read"=>[],
 			"add"=>[],
 			"edit"=>[],
		],
		"token_expire"=>[
 			"type"=>"dateTime",
 			"label"=>"Срок токена",
 			"width"=>200,
 			"read"=>[],
 			"add"=>[],
 			"edit"=>[],
		],
		"refresh_token"=>[
 			"type"=>"string",
 			"label"=>"Токен",
                        "index"=>"index",
 			"width"=>200,
 			"read"=>[],
 			"add"=>[],
 			"edit"=>[],
		],
		"refresh_token_expire"=>[
 			"type"=>"dateTime",
 			"label"=>"Срок токена",
 			"width"=>200,
 			"read"=>[],
 			"add"=>[],
 			"edit"=>[],
		],


	],


	"seeds"=> [
	             [
                      'id'    => 1,
                      'created_by_user'    => 1,
                      'login'    => 'system',
                      'password' => password_hash( \MapDapRest\Utils::random_str(10), PASSWORD_DEFAULT),
                      'role_id'  => '1',
                      'status'   => '1'
                     ],
	             [
                      'id'    => 2,
                      'created_by_user'    => 1,
                      'login'    => 'admin',
                      'password' => password_hash('admin', PASSWORD_DEFAULT),
                      'role_id'  => '1',
                      'status'   => '1'
                     ],
                  ]
      ];
    }//modelInfo

}//class
