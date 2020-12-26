<?php
namespace MapDapRest;

use \Illuminate\Database\Eloquent\Model as EloquentModel;


class Modelite extends EloquentModel
{



    public static function boot()
    {
        parent::boot();
        
        
        static::creating(function($model) {
          return $model->beforeAdd($model);
        });
        static::created(function($model) {
          $model->afterAdd($model);
        });


        static::updating(function($model) {
          return $model->beforeEdit($model);
        });
        static::updated(function($model) {
          $model->afterEdit($model);
        });
        

        static::deleting(function($model) {
          return $model->beforeDelete($model);
        });
        static::deleted(function($model) {
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

        $response=[];
        foreach ($rows as $item) {
            if (strpos($link_field,"<%")===false) {
                $str = $item->{$link_field};
                if (strlen($str) > $link_field_max) $str = mb_substr($str, 0,$link_field_max)."... ";
                array_push($response, ["value"=>$item->id, "text"=>$str]);
            } else {
                $str = preg_replace_callback('|<%(.*)%>|isU', function($prms) use($item, $link_field_max) {
                                 if (isset($item->{$prms[1]})) {
                                    $str = $item->{$prms[1]};
                                    if (strlen($str) > $link_field_max) $str = mb_substr($str, 0,$link_field_max)."... ";
                                    return $str; 
                                 } else { return ""; }
                        }, $link_field);
                array_push($response, ["value"=>$item->id, "text"=>$str]);
            }
        }//foreach
        return $response;
      }//linkTable


      if ($this->modelInfo()["columns"][$field]["type"]=="select") {
          $selects = $this->modelInfo()["columns"][$field]["items"];
          $response=[];
          foreach ($field_values as $key=>$val) {
              if (!isset($selects[ $key ])) continue;
              array_push($response, ["value"=>$key, "text"=>$val]);
          }
          if (!$asString) return $arr;

          return $response;
      }

      return [];
    }//getFieldLinks


}//Class
