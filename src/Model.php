<?php
namespace MapDapRest;

use \Illuminate\Database\Eloquent\Model as EloquentModel;
use \MapDapRest\App\Auth\Models\SystemLog;


class Model extends EloquentModel
{



    public static function boot()
    {
        parent::boot();
        
        
        static::creating(function($model) {
	  $app = \MapDapRest\App::getInstance();
          if ($app->auth->isGuest()) { throw new \Exception('user not found'); }
	  
          $model->sys_log_created_by = $app->auth->getFields()['id'];
          return $model->beforeAdd($model);
        });
        static::created(function($model) {
	  $app = \MapDapRest\App::getInstance();
          if ($app->auth->isGuest()) { throw new \Exception('user not found'); }

          $log = new SystemLog();
          $log->user_id = $app->auth->getFields()['id'];
          $log->table_name = $model->table;
          $log->row_id = $model->id;
          $log->action = 1;
          $log->fields = $model;
          $log->save();
          $model->afterAdd($model);
        });


        static::updating(function($model) {
          return $model->beforeEdit($model);
        });
        static::updated(function($model) {
	  $app = \MapDapRest\App::getInstance();
          if ($app->auth->isGuest()) { throw new \Exception('user not found'); }

          $log = new SystemLog();
          $log->user_id = $app->auth->getFields()['id'];
          $log->table_name = $model->table;
          $log->row_id = $model->id;
          $log->action = 2;
          $log->fields = $model;
          $log->save();
          $model->afterEdit($model);
        });
        

        static::deleting(function($model) {
          return $model->beforeDelete($model);
        });
        static::deleted(function($model) {
	  $app = \MapDapRest\App::getInstance();
          if ($app->auth->isGuest()) { throw new \Exception('user not found'); }

          $log = new SystemLog();
          $log->user_id = $app->auth->getFields()['id'];
          $log->table_name = $model->table;
          $log->row_id = $model->id;
          $log->action = 3;
          $log->fields = $model;
          $log->save();
          $model->afterDelete($model);
        });
        

       
    }//boot------------------------------------



    public function getFieldLinks($field, $asString=false) {
      $APP = App::getInstance();
      $field_values = $this->{$field};
      if (gettype($field_values)!=="array") {
        $field_values = explode(',', $field_values);
      }

      if ($this->modelInfo()["columns"][$field]["type"]=="linkTable") {
        $link_table = $this->modelInfo()["columns"][$field]["table"];
        $link_field = $this->modelInfo()["columns"][$field]["field"];
        $link_field_max = 250;
        if (isset($this->modelInfo()["columns"][$field]["field_max"])) $link_field_max = $this->modelInfo()["columns"][$field]["field_max"];
 
        $rows = $APP->DB->table($link_table)->whereIn('id', $field_values )->get();
        if (!$asString) return $rows;

        $rez="";
        foreach ($rows as $item) {
          if (strpos($link_field,"<%")===false) {
            $str = $item->{$link_field};
            if (strlen($str) > $link_field_max) $str = mb_substr($str, 0,$link_field_max)."... ";
            $rez .= ",".$item->id.". ".str_replace(","," ", $str);
          } else {
            $rez .= ",".preg_replace_callback('|<%(.*)%>|isU', function($prms) use($item, $link_field_max) { 
                                 if (isset($item->{$prms[1]})) {
                                    $str = $item->{$prms[1]};
                                    if (strlen($str) > $link_field_max) $str = mb_substr($str, 0,$link_field_max)."... ";
                                    return str_replace(","," ", $str); 
                                 } else { return ""; } 
                        }, $link_field);
          }
        }
        return substr($rez,1);
      }


      if ($this->modelInfo()["columns"][$field]["type"]=="select") {
        $selects = $this->modelInfo()["columns"][$field]["items"];
        $rez="";
        $arr=[];
        foreach ($field_values as $item) {
          if (!isset($selects[ $item ])) continue;
          $arr[$item] = $selects[ $item ];
          $rez .= ",".$selects[ $item ];
        }
        if (!$asString) return $arr;

        return substr($rez,1);
      }

      return "";
    }//getFieldLinks


}//Class
