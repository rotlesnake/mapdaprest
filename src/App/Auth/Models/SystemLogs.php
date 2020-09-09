<?php

namespace MapDapRest\App\Auth\Models;

use \Illuminate\Database\Eloquent\Model as EloquentModel;

class SystemLogs extends EloquentModel
{

    protected $table = 'sys_logs';

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
	"table"=>"sys_logs",
	"category"=>"Система",
	"name"=>"Логи",

	"read"=>$acc_admin,
	"add"=>$acc_admin,
	"edit"=>$acc_admin,
	"delete"=>$acc_admin,
	
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


		"user_id"=>[
 			"type"=>"integer",
 			"label"=>"Пользователь",
 			"unsigned"=>true,
 			"width"=>200,
 			"read"=>[],
 			"add"=>[],
 			"edit"=>[],
		],
		"table_name"=>[
 			"type"=>"string",
 			"label"=>"Таблица",
 			"width"=>200,
 			"read"=>[],
 			"add"=>[],
 			"edit"=>[],
		],
		"row_id"=>[
 			"type"=>"integer",
 			"label"=>"id записи",
 			"unsigned"=>true,
 			"width"=>200,
 			"read"=>[],
 			"add"=>[],
 			"edit"=>[],
		],
		"action"=>[
 			"type"=>"integer",
 			"label"=>"Действие",
 			"width"=>200,
 			"read"=>[],
 			"add"=>[],
 			"edit"=>[],
		],
		"fields"=>[
 			"type"=>"text",
 			"label"=>"SQL запрос",
 			"width"=>200,
 			"read"=>[],
 			"add"=>[],
 			"edit"=>[],
		],
 

	],
	
      ];
    }//modelInfo
    
}//class
