<?php
namespace MapDapRest;

use \Illuminate\Database\Eloquent\Model as EloquentModel;
use \MapDapRest\App\Auth\Models\SystemLog;


class Model extends EloquentModel
{

    public $modelInfo = null;

    public static function boot()
    {
        parent::boot();
        
        
        static::creating(function($model) {
          $app = \MapDapRest\App::getInstance();
          if ($app->auth) {
             if ($app->auth->isGuest()) { throw new \Exception('user not found'); }
             $model->created_by_user = $app->auth->getFields()['id'];
          }
          try {
              return $model->beforeAdd($model);
          } catch(Exception $e) {
              throw new Exception($e->getMessage());
          }
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




    public function scopeFindInSet($query, $field, $values)
    {
        return $query->whereRaw("FIND_IN_SET(?, ".$field.") > 0", [$values]);
    }



    public function getModelInfo($auth=null) {
        $modelInfo = $this->modelInfo();
        foreach ($modelInfo["columns"] as $x=>$y) {
            //если у поля нет разрешений то добавляем их
            if (!isset($y["read"])) { $modelInfo["columns"][$x]["read"] = $modelInfo["read"];  $y["read"] = $modelInfo["read"]; }
            if (!isset($y["add"]))  { $modelInfo["columns"][$x]["add"]  = $modelInfo["add"];   $y["add"] = $modelInfo["add"]; }
            if (!isset($y["edit"])) { $modelInfo["columns"][$x]["edit"] = $modelInfo["edit"];  $y["edit"] = $modelInfo["edit"]; }
            //Оставляем разрешенные поля
            if ($auth && !$auth->hasRoles($y["read"])) { unset($modelInfo["columns"][$x]); continue; }
            if ($auth && !$auth->hasRoles($y["edit"])) { $modelInfo["columns"][$x]["protected"]=true; }
            $modelInfo["columns"][$x]["name"]=$x; //Добавляем имя поля
        }
        if (isset($modelInfo["extendTable"]) && isset($modelInfo["extendTable"]["properties"])) {
            $APP = App::getInstance();
            $modelInfo["columns_extended"] = [];
            $properties = $APP->DB::table($modelInfo["extendTable"]["properties"])->orderBy("sort")->get();
            foreach ($properties as $prop) {
                $modelInfo["columns_extended"][] = $prop;
            }
        }
        return $modelInfo;
    }
    public static function getStaticModelInfo($auth=null) {
        $modelInfo = self::modelInfo();
        foreach ($modelInfo["columns"] as $x=>$y) {
            //если у поля нет разрешений то добавляем их
            if (!isset($y["read"])) { $modelInfo["columns"][$x]["read"] = $modelInfo["read"];  $y["read"] = $modelInfo["read"]; }
            if (!isset($y["add"]))  { $modelInfo["columns"][$x]["add"]  = $modelInfo["add"];   $y["add"] = $modelInfo["add"]; }
            if (!isset($y["edit"])) { $modelInfo["columns"][$x]["edit"] = $modelInfo["edit"];  $y["edit"] = $modelInfo["edit"]; }
            //Оставляем разрешенные поля
            if ($auth && !$auth->hasRoles($y["read"])) { unset($modelInfo["columns"][$x]); continue; }
            if ($auth && !$auth->hasRoles($y["edit"])) { $modelInfo["columns"][$x]["protected"]=true; }
            $modelInfo["columns"][$x]["name"]=$x; //Добавляем имя поля
        }
        if (isset($modelInfo["extendTable"]) && isset($modelInfo["extendTable"]["properties"])) {
            $APP = App::getInstance();
            $modelInfo["columns_extended"] = [];
            $properties = $APP->DB::table($modelInfo["extendTable"]["properties"])->orderBy("sort")->get();
            foreach ($properties as $prop) {
                $modelInfo["columns_extended"][] = $prop;
            }
        }
        return $modelInfo;
    }



    public function getFieldLinks($field, $full_links=false) {
        if (!$this->modelInfo) $this->modelInfo = $this->getModelInfo();
        $response_array = ["rows"=>[], "values"=>[], "text"=>""];
        $APP = App::getInstance();
        $field_values = $this->{$field};
        $field_type = $this->modelInfo["columns"][$field]["type"];

        if ($field_type=="linkTable") {
            if (gettype($field_values)!=="array") $field_values = array_map('intval', explode(',', $field_values));
            $link_table = $this->modelInfo["columns"][$field]["table"];
            $link_field = $this->modelInfo["columns"][$field]["field"];
            $link_field_max = 250;
            if (isset($this->modelInfo["columns"][$field]["field_maxlen"])) $link_field_max = $this->modelInfo["columns"][$field]["field_maxlen"];

            $cnt = 0;
            if (!isset($APP->cachedLinks[$link_table])) { $cnt = $APP->getModel($link_table)::count(); }
            if ($cnt > 1500) {
                $APP->cachedLinks[$link_table] = [];
                $rows = $APP->getModel($link_table)::whereIn('id', $field_values )->get();
                foreach ($rows as $row) {
                    $APP->cachedLinks[$link_table][$row->id] = $row;
                }
            }
            if (!isset($APP->cachedLinks[$link_table])) {
                $APP->cachedLinks[$link_table] = [];
                $rows = $APP->getModel($link_table)::get();
                foreach ($rows as $row) {
                    $APP->cachedLinks[$link_table][$row->id] = $row;
                }
            }

            $rows = [];
            foreach ($field_values as $val) {
                if (isset($APP->cachedLinks[$link_table][$val])) $rows[] = $APP->cachedLinks[$link_table][$val];
            }
 
            $response_array["rows"] = [];
            foreach ($rows as $item) {
                if (!$item) continue;
                if (strpos($link_field,"[")===false) {
                    $str = $item->{$link_field};
                    if (strlen($str) > $link_field_max) $str = mb_substr($str, 0,$link_field_max)."... ";
                    array_push($response_array["values"], ["value"=>(int)$item->id, "text"=>$str]);
  
                    if ($full_links) {
                        $crow = $item->getConvertedRow();
                        array_push($response_array["rows"], $crow);
                    } else {
                        array_push($response_array["rows"], $item->toArray());
                    }
                } else {
                    $crow = $item->getConvertedRow();
                    $str = preg_replace_callback('|\[(.*)\]|isU', function($prms) use($crow, $link_field_max) {
                                 if (isset($crow[$prms[1]])) {
                                    $str = $crow[$prms[1]];
                                    if (strlen($str) > $link_field_max) $str = mb_substr($str, 0,$link_field_max)."... ";
                                    return $str; 
                                 } else { return ""; }
                          }, $link_field);
                    array_push($response_array["values"], ["value"=>(int)$item->id, "text"=>$str]);
                    array_push($response_array["rows"], $crow);
                }
            }//foreach

            $response_text="";
            foreach ($response_array["values"] as $item) {
                $response_text .= "|".$item["text"];
            }
            $response_array["text"] = substr($response_text,1);
  
            return $response_array;
        }//linkTable


        if ($field_type=="select" || $field_type=="selectText") {
            if ($field_type=="select" && gettype($field_values)!=="array") $field_values = array_map('intval', explode(',', $field_values));
            if ($field_type=="selectText") $field_values = explode(',', $field_values);
            $selects = $this->modelInfo["columns"][$field]["items"];

            foreach ($field_values as $key=>$val) {
                if (!isset($selects[ $val ])) continue;
                if ($field_type=="select") array_push($response_array["values"], ["value"=>(int)$val, "text"=>$selects[ $val ]]);
                if ($field_type=="selectText") array_push($response_array["values"], ["value"=>$val, "text"=>$selects[ $val ]]);
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
    public function getConvertedRow($fastMode=false, $full_links=false){
        $APP = App::getInstance();
        if (!$this->modelInfo) $this->modelInfo = $this->getModelInfo();

        $tablename = $this->modelInfo["table"];
        $item = [];
        $item["id"] = $this->id;
 
        //Каждую строку разбираем на поля, проверяем уровни доступа, заполняем и отдаем
        foreach ($this->modelInfo["columns"] as $x=>$y) {
            if (!$APP->auth->hasRoles($y["read"])) continue; //Чтение поля запрещено
            if (!isset($this->{$x})) continue; //Поле отсутствует

            $item[$x] = $this->{$x};

            if ($y["type"]=="linkTable") {
                if (isset($y["multiple"]) && $y["multiple"]) { $item[$x] = array_map('intval', explode(',', $item[$x])); } else { $item[$x] = (int)$this->{$x}; }
                if (!$fastMode) {
                    $FieldLinks = $this->getFieldLinks($x, $full_links);
                    $item[$x."_text"] = $FieldLinks["text"];
                    if (isset($y["multiple"]) && $y["multiple"]) $item[$x."_values"] = $FieldLinks["values"];
                    if ($full_links || isset($y["object"]) && $y["object"]) $item[$x."_rows"] = $FieldLinks["rows"];
                }
            } 
            if ($y["type"]=="select" || $y["type"]=="selectText") {
                if ($y["type"]=="select") {
                    if (isset($y["multiple"]) && $y["multiple"]) { $item[$x] = array_map('intval', explode(',', $item[$x])); } else { $item[$x] = (int)$this->{$x}; }
                }
                if ($y["type"]=="selectText") {
                    if (isset($y["multiple"]) && $y["multiple"]) { $item[$x] = explode(',', $item[$x]); } else { $item[$x] = $this->{$x}; }
                }
                if (!$fastMode) {
                    $FieldLinks = $this->getFieldLinks($x, $full_links);
                    $item[$x."_text"] = $FieldLinks["text"];
                    if ($full_links || isset($y["multiple"]) && $y["multiple"]) $item[$x."_values"] = $FieldLinks["values"];
                }
            }
            if ($y["type"]=="integer")   { $item[$x] = (int)$this->{$x}; }
            if ($y["type"]=="float")     { $item[$x] = (float)$this->{$x}; }
            if ($y["type"]=="double")    { $item[$x] = (double)$this->{$x}; }
            if ($y["type"]=="checkBox")  { $item[$x] = (int)$this->{$x}; $item[$x."_text"] = ($item[$x]==1?"Да":"Нет"); }
            if ($y["type"]=="checkBoxText")  { $item[$x] = (int)$this->{$x}; $item[$x."_text"] = ($item[$x]==1?$y["label"]:""); }
            if ($y["type"]=="images")    { $item[$x] = $this->getUploadedFiles(json_decode($item[$x],true), $APP, "image", $tablename, $this->id, $x); }
            if ($y["type"]=="files" )    { $item[$x] = $this->getUploadedFiles(json_decode($item[$x],true), $APP, "file",  $tablename, $this->id, $x); }
            if ($y["type"]=="json" && gettype($item[$x])=="string") { $item[$x] = json_decode($this->{$x}, true); }
            if ($y["type"]=="password")  { $item[$x] = ""; }
            if ($y["type"]=="date")      { $item[$x."_text"] = \MapDapRest\Utils::convDateToDate($item[$x], false); }
            if ($y["type"]=="dateTime")  { $item[$x."_text"] = \MapDapRest\Utils::convDateToDate($item[$x], true);  }
            if ($y["type"]=="timestamp") { $item[$x."_text"] = \MapDapRest\Utils::convDateToDate($item[$x], true);  }
        }

        if (isset($this->modelInfo["columns_extended"])) {
            $item["extended_values"] = [];
            $values = $APP->DB::table($this->modelInfo["extendTable"]["values"])->where("object_id",$item["id"])->get();
            foreach ($values as $val) {
                $item["extended_values"][$val->name] = $val->value;
            }
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
         if (isset($y["type"]) && $y["type"]==1) {
            $fname = $y["name"];
            $fext = substr($fname, -4,4);
            $fpath = $APP->FULL_URL."uploads/$type/$table_name/".$row_id."_".$field_name."_".$fname;
            $caption = isset($y["caption"]) ? $y["caption"] : "";
            if (strlen($fname)>0) array_push($files, ["type"=>1, "name"=>$fname, "caption"=>$caption, "src"=>$fpath, "icon"=>\MapDapRest\Utils::extToIcon($fext)]);
         } else {
            $y["name"] = urldecode($y["src"]);
            if (strrpos($y["name"], "/") !== false) $y["name"] = substr($y["name"], strrpos($y["name"], "/")+1 );
            $fext = substr($y["name"], -4,4);
            $y["icon"] = \MapDapRest\Utils::extToIcon($fext);
            if (strlen($y["src"])>0) array_push($files, $y);
         }
       }
       return $files; //[type:1, name:'filename', caption:'description', src:'http:// or base64']
    }
    //******************* GET FILES *******************************************************


    
    //******************* FILL ROW *******************************************************
    public function fillRow($action, $params, &$fill_count=null)
    {
        $APP = App::getInstance();
        if (!$this->modelInfo) $this->modelInfo = $this->getModelInfo();
        $tablename = $this->modelInfo["table"];

        $i=0;
        foreach ($this->modelInfo["columns"] as $x=>$y) {
          if (isset($y["is_virtual"]) && $y["is_virtual"]) continue;      //Поле виртуальное
          if (!isset($params[$x]))  continue;                             //Поле отсутствует
          if (!$APP->auth->hasRoles($y[$action])) continue;               //Нет прав не заполняем поле
          if ($y["type"]=="password" && strlen($params[$x])<4) continue;  //Пароль пустой не заполняем
 
          //Если картики или фалы то подготавливаем массив в специальном формате
          if ($y["type"]=="images" || $y["type"]=="files") {
              $files = $this->prepareFileUploads($params[$x], $tablename, $this->id, $x, $y, $this->{$x});
              if ($files) { $this->{$x} = $files; }
          } else {
              $this->{$x} = $params[$x];
              if (is_array($params[$x]) && !in_array($y["type"],["json"])) {  $this->{$x} = \MapDapRest\Utils::arrayToString($params[$x]);  } //массив преобразуем в строку [12,32,34] -> 12,32,34
          }

          if ($y["type"]=="json")       { $this->{$x} = \MapDapRest\Utils::objectToString($params[$x]); }
          if ($y["type"]=="time")       { $this->{$x} = strlen($params[$x])==0 ? null : $params[$x]; }
          if ($y["type"]=="password")   { $this->{$x} = password_hash($params[$x], PASSWORD_DEFAULT); } //пароль хешируем
          if (!empty($y["default"]) && $action=="add" && strlen((string)$params[$x])==0) { $this->{$x} = $y["default"]; } //при добавлении поля если оно пустое то заполняем его значение по умолчанию
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
    public function prepareFileUploads($files_array, $table_name="", $row_id=0, $field_name="", $field_params=[], $oldValue){
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
                //if (strlen($fname)<2) continue;
                $base64_pos = strpos($fsrc, 'base64,');
                if (substr($fsrc,0,5)=="data:" || $base64_pos!==false) {
                    $fsrc = substr($fsrc, $base64_pos+7 );
                } else {
                    $fileInfo["type"] = 1;
                    $fileInfo["name"] = $fname;
                    $fileInfo["caption"] = (isset($files_array[$i]["caption"]) ? $files_array[$i]["caption"] : '');
                    $fileInfo["src"] = $fsrc;
                    array_push($files, $fileInfo );
                    continue;
                }

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
                $fileInfo["caption"] = (isset($files_array[$i]["caption"]) ? $files_array[$i]["caption"] : "");
                $fileInfo["src"] = $files_array[$i]["src"];
                array_push($files, $fileInfo );
            }
        }//for
        if (count($files)>0) return json_encode($files); //[type:1, name:'filename', caption:'description', src:'http:// or base64']

        return false;
    }
    //******************* SET FILES *******************************************************



}//Class
