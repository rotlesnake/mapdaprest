<?php

namespace MapDapRest\App\Auth\Models;



class Roles extends \MapDapRest\Model
{
    protected $table = 'roles';
    protected $primaryKey = 'id';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';



    public function scopeFilterRead($query)
    {
        return $query;
    }

    public function scopeFilterEdit($query)
    {
        return $query;
    }

    public function scopeFilterDelete($query)
    {
        return $query;
    }


    public static function modelInfo() {
      $acc_admin = [1];
      $acc_all = [1,2,3,4,5,6,7,8];
      
      return [
	"table"=>"roles",
	"primary_key"=>"id",
	"category"=>"Система",
	"name"=>"Роли",

        "sortBy"=>["id"],
        "sortDesc"=>["asc"],
        "itemsPerPage"=>100,
        "itemsPerPageVariants"=>[50,100,200,300,500,1000],

	"read"=>$acc_all,
	"add"=>$acc_admin,
	"edit"=>$acc_admin,
	"delete"=>$acc_admin,
	
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
 			"table"=>"users",
 			"field"=>"<%login%>",
 			"multiple"=>false,
 			"typeSelect"=>"table",
 			"object"=>false,
 			"width"=>200,
 			"hidden"=>true,
 			"read"=>$acc_all,
 			"add"=>[],
 			"edit"=>[],
		],


		"name"=>[
 			"type"=>"string",
 			"label"=>"Наименование",
 			"placeholder"=>"",
 			"width"=>200,
 			"rules"=>"[ v => v.length>2 || 'Обязательное поле' ]",
 			"read"=>$acc_all,
 			"add"=>$acc_admin,
 			"edit"=>$acc_admin,
		],


	],


	"seeds"=> [
	             [
                      'id'    => 1,
                      'created_by_user'    => 1,
                      'name'    => 'Администратор системы',
                     ],
	             [
                      'id'    => 2,
                      'created_by_user'    => 1,
                      'name'    => 'Помошник администратора',
                     ],
	             [
                      'id'    => 3,
                      'created_by_user'    => 1,
                      'name'    => 'Директор',
                     ],
	             [
                      'id'    => 4,
                      'created_by_user'    => 1,
                      'name'    => 'Бухгалтер',
                     ],
	             [
                      'id'    => 5,
                      'created_by_user'    => 1,
                      'name'    => 'Пользователь',
                     ],
                  ]
      ];
    }//modelInfo

}//class
