<?php
namespace MapDapRest\App\Auth\Models;


class UserAccess extends \MapDapRest\Model
{
    protected $table = "user_access";
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
        return $query;
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
      $acc_all = ["user_access/view", 1,2,3,4,5];
      
      return [
	"table"=>"user_access",
	"primary_key"=>"id",
	"category"=>"Система",
	"name"=>"Доступ пользователя к возможностям системы",

        "sortBy"=>["id"],
        "itemsPerPage"=>100,
        "itemsPerPageVariants"=>[50,100,200,300,500,1000],

	"acl"=> [
	    "user_access/view" => "Просмотр",
	    "user_access/add" => "Добавление",
	    "user_access/edit" => "Изменение",
	    "user_access/delete" => "Удаление",
	],

	"read"=>["user_access/view", 1,2,3,4,5],
	"add"=>["user_access/add", 1],
	"edit"=>["user_access/edit", 1],
	"delete"=>["user_access/delete"],

        //"parentTables"=>[["table"=>"user", "field"=>"user_id"]],
        //"childrenTables"=>[["table"=>"user_posts", "field"=>"user_id"]],
	
        "filter"=>[
            "created_at"=>["label"=>"Дата создания", "filterType"=>"like"],
            "created_by_user"=>["label"=>"Кто создал", "filterType"=>"="],
        ],

	"columns"=>[
		"id" => ["type"=>"integer", "label"=>"id", "read"=>$acc_all, "add"=>[], "edit"=>[] ],
		"created_at" => ["type"=>"timestamp", "label"=>"Дата создания", "read"=>$acc_all, "add"=>[], "edit"=>[] ],
		"updated_at" => ["type"=>"timestamp", "label"=>"Дата изменения", "read"=>$acc_all, "add"=>[], "edit"=>[] ],
		"created_by_user" => ["type"=>"linkTable", "label"=>"Создано пользователем", "table"=>"users", "field"=>"login", "read"=>$acc_all, "add"=>[], "edit"=>[] ],

		"user_id" => ["type"=>"linkTable", "label"=>"Пользователь", "table"=>"users", "field"=>"login", "read"=>$acc_all, "add"=>$acc_admin, "edit"=>$acc_admin ],
		"app_access_id" => ["type"=>"linkTable", "label"=>"Разрешение", "table"=>"app_access_list", "field"=>"[name] ([slug])", "read"=>$acc_all, "add"=>$acc_admin, "edit"=>$acc_admin ],
	],

	"seeds"=> [
	],

      ];
    }//modelInfo

}//class
