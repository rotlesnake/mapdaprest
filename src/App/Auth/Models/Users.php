<?php

namespace MapDapRest\App\Auth\Models;



class Users extends \MapDapRest\Model
{

    protected $table = 'users';



    public static function modelInfo() {
      $acc_admin = [1];
      $acc_all = [1,2,3,4,5,6,7,8];
      
      return [
	"table"=>"users",
	"category"=>"Система",
	"name"=>"Пользователи",

        "sortBy"=>["id"],
        "sortDesc"=>["asc"],
        "itemsPerPage"=>100,
        "itemsPerPageVariants"=>[50,100,200,300,500,1000],

	"read"=>[],
	"add"=>[],
	"edit"=>[],
	"delete"=>[],
	
	"type"=>"standart",
        "style"=>["outlined"=>true, "filled"=>false, "color"=>"#909090", "counter"=>true, "dark"=>false, "dense"=>false, "hide-details"=>false, "persistent-hint"=>false, "rounded"=>false, "shaped"=>false, "clearable"=>false],

        //"parentTables"=>[["table"=>"users", "id"=>"user_id"]],
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
 			"visible"=>true,
 			"read"=>[],
 			"add"=>[],
 			"edit"=>[],
		],
		"created_at"=>[
 			"type"=>"dateTime",
 			"label"=>"Дата создания",
 			"width"=>200,
 			"read"=>[],
 			"add"=>[],
 			"edit"=>[],
		],
		"updated_at"=>[
 			"type"=>"dateTime",
 			"label"=>"Дата изменения",
 			"width"=>200,
 			"hidden"=>true,
 			"read"=>[],
 			"add"=>[],
 			"edit"=>[],
		],
		"created_by_user"=>[
 			"type"=>"linkTable",
 			"label"=>"Создано пользователем",
 			"table"=>"users",
 			"field"=>"<%login%>",
 			"multiple"=>false,
 			"typeSelect"=>"table",
 			"object"=>false,
 			"width"=>200,
 			"hidden"=>true,
 			"read"=>[],
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
 			"style"=>["prepend-icon"=>"person", "append-icon"=>"person", "type"=>"text", "outlined"=>true, "filled"=>false, "color"=>"#909090", "counter"=>true, "dark"=>false, "dense"=>false, "hide-details"=>false, "persistent-hint"=>false, "rounded"=>false, "shaped"=>false, "clearable"=>false ],
 			"read"=>[],
 			"add"=>[],
 			"edit"=>[],
		],
		"password"=>[
 			"type"=>"password",
 			"label"=>"Пароль",
 			"width"=>200,
 			"rules"=>"[ v => v.length==0 || v.length>7 || 'Минимальная длинна 8 символов' ]",
 			"defaut"=>"12345678",
 			"read"=>[],
 			"add"=>[],
 			"edit"=>[],
		],
		"roles"=>[
 			"type"=>"linkTable",
 			"label"=>"Роли",
 			"table"=>"roles",
 			"field"=>"<%name%>",
 			"multiple"=>true,
 			"typeSelect"=>"table",
 			"object"=>false,
 			"width"=>200,
 			"read"=>[],
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
 			"read"=>[],
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
                      'roles'    => '1',
                      'status'   => '1'
                     ],
	             [
                      'id'    => 2,
                      'created_by_user'    => 1,
                      'login'    => 'admin',
                      'password' => password_hash('admin', PASSWORD_DEFAULT),
                      'roles'    => '1',
                      'status'   => '1'
                     ],
                  ]
      ];
    }//modelInfo

}//class
