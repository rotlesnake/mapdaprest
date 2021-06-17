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
          if ($app->auth) {
             if ($app->auth->isGuest()) { throw new \Exception('user not found'); }
             $model->created_by_user = $app->auth->getFields()['id'];
          }

          return $model->beforeAdd($model);
        });
        static::created(function($model) {
	  $app = \MapDapRest\App::getInstance();
          if ($app->auth) {
             if ($app->auth->isGuest()) { throw new \Exception('user not found'); }

             $log = new SystemLog();
             $log->user_id = $app->auth->getFields()['id'];
             $log->created_by_user = $log->user_id;
             $log->table_name = $model->table;
             $log->row_id = $model->id;
             $log->action = 1;
             $log->fields = $model;
             $log->save();
          }

          $model->afterAdd($model);
        });


        static::updating(function($model) {
          return $model->beforeEdit($model);
        });
        static::updated(function($model) {
	  $app = \MapDapRest\App::getInstance();
          if ($app->auth) {
             if ($app->auth->isGuest()) { throw new \Exception('user not found'); }

             $log = new SystemLog();
             $log->user_id = $app->auth->getFields()['id'];
             $log->created_by_user = $log->user_id;
             $log->table_name = $model->table;
             $log->row_id = $model->id;
             $log->action = 2;
             $log->fields = $model;
             $log->save();
          }

          $model->afterEdit($model);
        });
        

        static::deleting(function($model) {
          return $model->beforeDelete($model);
        });
        static::deleted(function($model) {
	  $app = \MapDapRest\App::getInstance();
          if ($app->auth) {
             if ($app->auth->isGuest()) { throw new \Exception('user not found'); }

             $log = new SystemLog();
             $log->user_id = $app->auth->getFields()['id'];
             $log->created_by_user = $log->user_id;
             $log->table_name = $model->table;
             $log->row_id = $model->id;
             $log->action = 3;
             $log->fields = $model;
             $log->save();
          }

          $model->afterDelete($model);
        });
        

       
    }//boot------------------------------------



    public function getFieldLinks($field) {
      $response_array = ["rows"=>[], "values"=>[], "text"=>""];
      $APP = App::getInstance();
      $field_values = $this->{$field};
      if (gettype($field_values)!=="array") {
        $field_values = array_map('intval', explode(',', $field_values));
      }

      if ($this->modelInfo()["columns"][$field]["type"]=="linkTable") {
          $link_table = $this->modelInfo()["columns"][$field]["table"];
          $link_field = $this->modelInfo()["columns"][$field]["field"];
          $link_field_max = 250;
          if (isset($this->modelInfo()["columns"][$field]["field_maxlen"])) $link_field_max = $this->modelInfo()["columns"][$field]["field_maxlen"];
 
          $rows = $APP->DB->table($link_table)->whereIn('id', $field_values )->get();
          $response_array["rows"] = $rows;

          foreach ($rows as $item) {
              if (strpos($link_field,"<%")===false) {
                  $str = $item->{$link_field};
                  if (strlen($str) > $link_field_max) $str = mb_substr($str, 0,$link_field_max)."... ";
                  array_push($response_array["values"], ["value"=>(int)$item->id, "text"=>$str]);
              } else {
                  $str = preg_replace_callback('|<%(.*)%>|isU', function($prms) use($item, $link_field_max) {
                                 if (isset($item->{$prms[1]})) {
                                    $str = $item->{$prms[1]};
                                    if (strlen($str) > $link_field_max) $str = mb_substr($str, 0,$link_field_max)."... ";
                                    return $str; 
                                 } else { return ""; }
                        }, $link_field);
                  array_push($response_array["values"], ["value"=>(int)$item->id, "text"=>$str]);
              }
          }//foreach

          $response_text="";
          foreach ($response_array["values"] as $item) {
              $response_text .= "|".$item["text"];
          }
          $response_array["text"] = substr($response_text,1);

          return $response_array;
      }//linkTable


      if ($this->modelInfo()["columns"][$field]["type"]=="select") {
          $selects = $this->modelInfo()["columns"][$field]["items"];

          foreach ($field_values as $key=>$val) {
              if (!isset($selects[ $val ])) continue;
              array_push($response_array["values"], ["value"=>(int)$val, "text"=>$selects[ $val ]]);
          }

          $response_text="";
          foreach ($response_array["values"] as $item) {
              $response_text .= "|".$item["text"];
          }
          $response_array["text"] = substr($response_text,1);

          return $response_array;
      }

      return $response_array;
    }//getFieldLinks




    

    //******************* CONVERT FOR OUT*******************************************************
    public function getConvertedRow($fastMode=false){
        $APP = App::getInstance();
        $tablename = $this->modelInfo()["table"];
        $item = [];
        $item["id"] = $this->id;
 
        //Каждую строку разбираем на поля, проверяем уровни доступа, заполняем и отдаем
        foreach ($this->modelInfo()["columns"] as $x=>$y) {
            if (!$APP->auth->hasRoles($y["read"])) continue; //Чтение поля запрещено
            $item[$x] = $this->{$x};

            if ($y["type"]=="linkTable") {
                if (isset($y["multiple"]) && $y["multiple"]) { $item[$x] = array_map('intval', explode(',', $item[$x])); } else { $item[$x] = (int)$this->{$x}; }
                if (!$fastMode) {
                    $FieldLinks = $this->getFieldLinks($x);
                    $item[$x."_text"] = $FieldLinks["text"];
                    if (isset($y["multiple"]) && $y["multiple"]) $item[$x."_values"] = $FieldLinks["values"];
                    if (isset($y["object"]) && $y["object"]) $item[$x."_rows"] = $FieldLinks["rows"];
                }
            } 
            if ($y["type"]=="select") {
                if (isset($y["multiple"]) && $y["multiple"]) { $item[$x] = array_map('intval', explode(',', $item[$x])); } else { $item[$x] = (int)$this->{$x}; }
                if (!$fastMode) {
                    $FieldLinks = $this->getFieldLinks($x);
                    $item[$x."_text"] = $FieldLinks["text"];
                    if (isset($y["multiple"]) && $y["multiple"]) $item[$x."_values"] = $FieldLinks["values"];
                }
            }
            if ($y["type"]=="integer")  { $item[$x] = (int)$this->{$x}; }
            if ($y["type"]=="float")    { $item[$x] = (float)$this->{$x}; }
            if ($y["type"]=="double")   { $item[$x] = (double)$this->{$x}; }
            if ($y["type"]=="checkBox") { $item[$x] = (int)$this->{$x}; $item[$x."_text"] = ($item[$x]==1?"Да":"Нет"); }
            if ($y["type"]=="images")   { $item[$x] = $this->getUploadedFiles(json_decode($item[$x],true), $APP, "image", $tablename, $this->id, $x); }
            if ($y["type"]=="files" )   { $item[$x] = $this->getUploadedFiles(json_decode($item[$x],true), $APP, "file", $tablename, $this->id, $x); }
            if ($y["type"]=="password")   $item[$x] = "";
            if ($y["type"]=="date")      { $item[$x."_text"] = \MapDapRest\Utils::convDateToDate($item[$x], false); }
            if ($y["type"]=="dateTime")  { $item[$x."_text"] = \MapDapRest\Utils::convDateToDate($item[$x], true);  }
            if ($y["type"]=="timestamp") { $item[$x."_text"] = \MapDapRest\Utils::convDateToDate($item[$x], true);  }
        }
        return $item;
    }
    //******************* CONVERT FOR OUT *******************************************************


    //******************* GET FILES *******************************************************
    public function getUploadedFiles($files_array, $APP, $type, $table_name="", $row_id=0, $field_name=""){
       $files = []; 
       if (!is_array($files_array)) return $files;
       if (count($files_array)==0)  return $files;

       foreach ($files_array as $y) {
         if ($y["type"]==1) {
            $fname = $y["name"];
            $fpath = $APP->FULL_URL."uploads/$type/$table_name/".$row_id."_".$field_name."_".$fname;
            array_push($files, ["type"=>1, "name"=>$fname, "caption"=>$y["caption"], "src"=>$fpath]);
         } else {
            $y["name"] = urldecode($y["src"]);
            $y["name"] = substr($y["name"], strrpos($y["name"], "/")+1 );
            array_push($files, $y);
         }
       }
       return $files; //[type:1, name:'filename', caption:'description', src:'http:// or base64']
    }
    //******************* GET FILES *******************************************************


    
    //******************* FILL ROW *******************************************************
    public function fillRow($action, $params, &$fill_count=null)
    {
        $APP = App::getInstance();
        $tablename = $this->modelInfo()["table"];

        $i=0;
        foreach ($this->modelInfo()["columns"] as $x=>$y) {
          if (isset($y["is_virtual"]) && $y["is_virtual"]) continue;      //Поле виртуальное
          if (!isset($params[$x]))  continue;                             //Поле отсутствует
          if (!$APP->auth->hasRoles($y[$action])) continue;               //Нет прав не заполняем поле
          if ($y["type"]=="password" && strlen($params[$x])<4) continue;  //Пароль пустой не заполняем
 
          //Если картики или фалы то подготавливаем массив в специальном формате
          if ($y["type"]=="images" || $y["type"]=="files") {
              $files = $this->prepareFileUploads($params[$x], $tablename, $this->id, $x, $y);
              if ($files) { $this->{$x} = $files; }
          } else {
              $this->{$x} = $params[$x];
              if (is_array($params[$x])) {  $this->{$x} = \MapDapRest\Utils::arrayToString($params[$x]);  } //массив преобразуем в строку [12,32,34] -> 12,32,34
          }

          
          if ($y["type"]=="password") { $this->{$x} = password_hash($params[$x], PASSWORD_DEFAULT); } //пароль хешируем
          if (!empty($y["default"]) && $action=="add" && strlen($params[$x])==0) { $this->{$x} = $y["default"]; } //при добавлении поля если оно пустое то заполняем его значение по умолчанию
          //Меняем даты в формат SQL
          if ($y["type"]=="date")       { $this->{$x} = \MapDapRest\Utils::convDateToSQL($this->{$x}, false); }
          if ($y["type"]=="dateTime")   { $this->{$x} = \MapDapRest\Utils::convDateToSQL($this->{$x}, true);  }
          if ($y["type"]=="timestamp")  { $this->{$x} = \MapDapRest\Utils::convDateToSQL($this->{$x}, true);  }

          $i++;
        }
        if ($fill_count!==null) $fill_count = $i;
        return $this;
    }
    //******************* FILL ROW *******************************************************



    
    //******************* SET FILES *******************************************************
    public function prepareFileUploads($files_array, $table_name="", $row_id=0, $field_name="", $field_params=[]){
        if (gettype($files_array)=="string") $files_array = json_decode($files_array,true);
        if (!is_array($files_array)) return false;

        $APP = App::getInstance();
        $files=[];
        for($i=0; $i<count($files_array); $i++) {
            if (!isset($files_array[$i]["type"])) continue;
            if (!isset($files_array[$i]["src"]))  continue;

            if ($files_array[$i]["type"]==1) {
                //is file
                $fileInfo = [];
                $fname = \MapDapRest\Utils::getSlug($files_array[$i]["name"], true);
                $fsrc = $files_array[$i]["src"];
                if (strlen($fname)<2) continue;
                $base64_pos = strpos($fsrc, 'base64,');
                if (substr($fsrc,0,5)!="data:" || $base64_pos===false) continue;
                $fsrc = substr($fsrc, $base64_pos+7 );

                if ($row_id>0) {
                   $folder_path = $APP->ROOT_PATH."uploads/".$table_name;
                   if ( !is_dir($folder_path) ) { mkdir($folder_path, 0777); }
                   file_put_contents($folder_path."/".$row_id."_".$field_name."_".$fname, base64_decode($fsrc) );

                   if ($field_params['type']=='images' && isset($field_params['resize'])) {  //resize and crop image
                      $file_name = $folder_path."/".$row_id."_".$field_name."_".$fname;
                      $image = initWideImage($file_name);
                      if ((int)$field_params['resize'][0]==0) $field_params['resize'][0]=null;
                      if ((int)$field_params['resize'][1]==0) $field_params['resize'][1]=null;
                      $image = $image->resize($field_params['resize'][0], $field_params['resize'][1], $field_params['resize'][2], 'down');
                      if (isset($field_params['crop']) && (int)$field_params['crop'][0]>0 && (int)$field_params['crop'][1]>0) {
                        $image = $image->crop($field_params['crop'][2], $field_params['crop'][3], $field_params['crop'][0], $field_params['crop'][1]);
                      }
                      $image->saveToFile($file_name);
                   }
                }
                $fileInfo["type"] = 1;
                $fileInfo["name"] = $fname;
                $fileInfo["caption"] = (isset($files_array[$i]["caption"]) ? $files_array[$i]["caption"] : '');
                $fileInfo["src"] = $APP->ROOT_URL."uploads/".$table_name."/".$row_id."_".$field_name."_".$fname;
                array_push($files, $fileInfo );
            } else {
                //is url
                $fileInfo = [];
                $fileInfo["type"] = 2;
                $fileInfo["name"] = "";
                $fileInfo["caption"] = (isset($files_array[$i]["caption"]) ? $files_array[$i]["caption"] : '');
                $fileInfo["src"] = $files_array[$i]["src"];
                array_push($files, $fileInfo );
            }
        }//for
        if (count($files)>0) return json_encode($files); //[type:1, name:'filename', caption:'description', src:'http:// or base64']

        return false;
    }
    //******************* SET FILES *******************************************************



}//Class
