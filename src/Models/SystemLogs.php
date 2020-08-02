<?php

namespace MapDapRest\Models;

use \Illuminate\Database\Eloquent\Model as EloquentModel;

class SystemLogs extends EloquentModel
{

    protected $table = 'sys_logs';



    public static function modelInfo() {

      return [
	"table"=>"sys_logs",
	"category"=>"Система",
	"name"=>"Логи",

	"read"=>[],
	"add"=>[],
	"edit"=>[],
	"delete"=>[],
	
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
