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
      $acc_all = [1];
      
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
	"add"=>$acc_admin,
	"edit"=>$acc_admin,
	"delete"=>[],
	
	"type"=>"standart",

        "filter"=>[
            "created_by_user"=>[
		"label"=>"Кто создал",
                "filterType"=>"like",
            ],
        ],

	"columns"=>[
                   "id"=>["type"=>"integer", "label"=>"id", "read"=>$acc_all, "add"=>[], "edit"=>[] ], 
                   "created_at"=>["type"=>"timestamp", "label"=>"Дата создания", "read"=>$acc_all, "hidden"=>true, "add"=>[], "edit"=>[] ], 
                   "updated_at"=>["type"=>"timestamp", "label"=>"Дата изменения", "read"=>$acc_all, "hidden"=>true, "add"=>[], "edit"=>[] ], 
                   "created_by_user"=>["type"=>"linkTable", "label"=>"Создано пользователем", "table"=>"users", "field"=>"login", "hidden"=>true, "read"=>$acc_all, "add"=>[], "edit"=>[] ], 

                   "login"=>["type"=>"string", "label"=>"Логин", "index"=>"unique", "rules"=>"[v=>v && v.length > 3 || 'Обязательное поле']"], 
                   "password"=>["type"=>"password", "label"=>"Пароль",  ], 
                   "role_id"=>["type"=>"linkTable", "label"=>"Роли", "table"=>"roles", "field"=>"description", "multiple"=>false,  ], 
                   "status"=>["type"=>"select", "label"=>"Статус", "typeSelect"=>"combobox", "items"=>["-1"=>"Заблокирован", "1"=>"Активный", ], "default"=>"1",  ], 
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
