<?php
namespace MapDapRest\App\Auth\Models;


class UserTokens extends \MapDapRest\Model
{
    public $ignoreSysLog = true;
    protected $table = "user_tokens";
    protected $primaryKey = 'id';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
 
    //Добавление записи----------------------
    //Перед добавлением
    public static function beforeAdd($model) {
    }
    
    //После добавления
    public static function afterAdd($model) {
    }
    //--------------------------------

    //Изменение записи----------------------
    //Перед изменением
    public static function beforeEdit($model) {
    }

    //После изменения
    public static function afterEdit($model) {
    }
    //--------------------------------

    //Удаление записи----------------------
    //Перед удалением
    public static function beforeDelete($model) {
    }

    //После удаления
    public static function afterDelete($model) {
    }
    //--------------------------------


    //Перехват post данных
    //Перед сохранением
    public static function beforePost($action, $model, $post) {
    }

    //После сохранения
    public static function afterPost($action, $model, $post) {
    }
    //--------------------------------



    //************************************************************************************************
    //Области видимости записей
    //фильтр на чтение
    public function scopeFilterRead($query)
    {
	$APP = \MapDapRest\App::getInstance();
        if (!$APP->auth->user) { throw new Exception('user not found'); }
        if ($APP->auth->user->hasRoles([1])) return $query;

        return $query->where('user_id', '=', $APP->auth->user->id);
    }

    //фильтр на изменение
    public function scopeFilterEdit($query)
    {
        return $query;
    }

    //фильтр на удаление
    public function scopeFilterDelete($query)
    {
        return $query;
    }
    //************************************************************************************************


    //************************************************************************************************
    //Пользовательская логика модели******************************************************************
    //************************************************************************************************

    //************************************************************************************************


    public static function modelInfo() {
      $acc_admin = [1];
      $acc_all = [1];
      
      return [
	"table"=>"user_tokens",
	"primary_key"=>"id",
	"category"=>"Система",
	"name"=>"Токены доступа",

        "sortBy"=>["id"],
        "itemsPerPage"=>100,
        "itemsPerPageVariants"=>[50,100,200,300,500,1000],

	"read"=>$acc_all,
	"add"=>$acc_admin,
	"edit"=>$acc_admin,
	"delete"=>$acc_admin,

        "parentTables"=>[["table"=>"user", "field"=>"user_id"]],
	
        "filter"=>[
            "created_at"=>["label"=>"Дата создания", "filterType"=>"like"],
            "created_by_user"=>["label"=>"Кто создал", "filterType"=>"="],
        ],

	"columns"=>[
		"id" => ["type"=>"integer", "label"=>"id", "read"=>$acc_all, "add"=>[], "edit"=>[] ],
		"created_at" => ["type"=>"timestamp", "label"=>"Дата создания", "read"=>$acc_all, "add"=>[], "edit"=>[] ],
		"updated_at" => ["type"=>"timestamp", "label"=>"Дата изменения", "read"=>$acc_all, "add"=>[], "edit"=>[] ],
		"created_by_user" => ["type"=>"linkTable", "label"=>"Создано пользователем", "table"=>"users", "field"=>"login", "read"=>$acc_all, "add"=>[], "edit"=>[] ],

		"user_id" => ["type"=>"linkTable", "label"=>"Пользователь", "table"=>"users", "field"=>"login" ],
		"token" => ["type"=>"string", "label"=>"Токен",  ],
		"expire" => ["type"=>"dateTime", "label"=>"Срок действия",  ],

		"browser_agent" => ["type"=>"text", "label"=>"Браузер",  ],
		"browser_ip" => ["type"=>"string", "label"=>"IP адрес",  ],
		"comment" => ["type"=>"text", "label"=>"Комментарий",  ],
	],

	"seeds"=> [
	],

      ];
    }//modelInfo

}//class
