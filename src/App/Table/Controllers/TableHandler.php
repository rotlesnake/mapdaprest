<?php
namespace MapDapRest\App\Table\Controllers;
//namespace App\Table\Controllers;


class TableHandler
{

    public $APP;



    public function __construct($app)
    {
        $this->APP = $app;
    }



    //******************* GET *******************************************************
    public function get($tablename, $id, $reqFields=[], $args)
    {
        $user = $this->APP->auth->getFields();

        $json_response = [];
        $json_response["info"] = [];
        $json_response["rows"] = [];
        $json_response["pagination"] = [];

        if ($tablename=="") return ["error"=>6, "message"=>"tablename empty"];
        if (!isset($this->APP->models[$tablename])) return ["error"=>6, "message"=>"table $tablename not found"];

        $modelClass = $this->APP->models[$tablename];
        $tableInfo = $modelClass::modelInfo();

        //оставляем только поля разрешенные для чтения  или запрашиваемые клиентом $reqFields
        $allowFields=["id"];
        foreach ($tableInfo["columns"] as $x=>$y) {
           if (count($reqFields)>0 && !in_array($x, $reqFields))  continue;
           if (isset($y["is_virtual"]) && $y["is_virtual"]) continue;
           if ($this->APP->auth->hasRoles($y["read"])) array_push($allowFields, $x);
        }//----------------------------------------------------------------------------------

        //Сортировка по умолчанию из модели если в аргументах нет требований сортировки
        if (isset($tableInfo["sortBy"]) && (!isset($args["sortBy"]) || (isset($args["sortBy"]) && count($args["sortBy"])==0))) {
           $args["sortBy"] = $tableInfo["sortBy"];
           if (isset($tableInfo["sortDesc"])) $args["sortDesc"] = $tableInfo["sortDesc"];
        }//----------------------------------------------------------------------------------

        $MODEL=null;
        //если доступ на чтение отсутствует то выдаем сообщение
        if (!$this->APP->auth->hasRoles($tableInfo["read"])) return ["error"=>4, "message"=>"table $tablename access denied"];

        //Значения по умолянию в описании модели
        if (!isset($tableInfo["itemsPerPage"])) $tableInfo["itemsPerPage"] = 100;
        if (!isset($tableInfo["itemsPerPageVariants"])) $tableInfo["itemsPerPageVariants"] = [50,100,200,300,500,1000];
        //----------------------------------------------------------------------------------
        //Значения по умолчанию в запросе
        if (!isset($args["itemsPerPage"])) $args["itemsPerPage"]=$tableInfo["itemsPerPage"];
        if ((int)$args["itemsPerPage"]<0)  $args["itemsPerPage"]=$tableInfo["itemsPerPage"];
        if (!isset($args["page"])) $args["page"]=1;
        //----------------------------------------------------------------------------------


        //Это дочерняя таблица - тогда фильтруем записи по родителю  -
        //parent_table : [name:users , id:999]
        if (isset($args["parent_table"]) && (int)$args["parent_table"]["id"]>0) {
             $parent_field = "";
             foreach ($tableInfo["parent_tables"] as $x=>$y) {
                 if ($y["table"]==$args["parent_table"]) $parent_field = $y["id"];
             }
             $MODEL = $modelClass::select($allowFields)->filterRead()->where($parent_field, (int)$args["parent_table"]["id"] );
        }//----------------------------------------------------------------------------------


        $isFiltered = false;
        //Запрашивают фильтр записей по полям
        //filter:[ {field:name, oper:'like', value:'asd'} ]
        if (isset($args["filter"]) && count($args["filter"])>0) {

            $MODEL = $modelClass::select($allowFields)->filterRead();

            foreach ($args["filter"] as $x=>$y) { //перебираем поля 
                if (isset($args["filter"][$x]["value"])) {  //поле есть - формируем фильтр
                    $isFiltered = true;
                    $s_filed=$args["filter"][$x]["field"]; $s_oper=$args["filter"][$x]["oper"]; $s_value=$args["filter"][$x]["value"];

                    if ($tableInfo["columns"][$s_filed]["type"]=="date")     { $s_value = \MapDapRest\Utils::convDateToSQL($s_value, false); }
                    if ($tableInfo["columns"][$s_filed]["type"]=="dateTime") { $s_value = \MapDapRest\Utils::convDateToSQL($s_value, true); }
                    if ($s_oper=="like")   { $s_value = "%".$s_value."%"; }
                    if ($s_oper=="begins") { $s_oper="like"; $s_value = $s_value."%"; }
                    
                    if ($s_oper=="in") {
                       $MODEL = $MODEL->whereIn($s_filed, $s_value);
                    } else {
                       $MODEL = $MODEL->where($s_filed, $s_oper, $s_value);
                    }
                }
            }
            if ($isFiltered) {
                $all_records = $MODEL->count();
                $MODEL = $MODEL->offset(($req_params["page"]-1)*$req_params["itemsPerPage"])->limit($req_params["itemsPerPage"]);
                if (isset($args["sortBy"]) && isset($args["sortBy"][0]) && strlen(isset($args["sortBy"][0]))>0 ) $MODEL = $MODEL->orderBy($args["sortBy"][0], $args["sortDesc"][0]);
                if (isset($args["sortBy"]) && isset($args["sortBy"][1]) && strlen(isset($args["sortBy"][1]))>0 ) $MODEL = $MODEL->orderBy($args["sortBy"][1], $args["sortDesc"][1]);
                $rows = $MODEL->get();
            }
        }//----------------------------------------------------------------------------------



        //фильтр по ИД
        if ($id > 0) {
            $isFiltered = true;
            $all_records = 1;
            $rows = $modelClass::select($allowFields)->filterRead()->where("id", $id)->get();
            //Колизия - получили более одной записи, это вина (filterRead()) - выдаем ошибку, это косяк
            if (count($rows)>1) return ["error"=>6, "message"=>"scope filterRead error"];
        }//----------------------------------------------------------------------------------


        
        //Фильтров нет - выдаем все записи 
        if (!$isFiltered) {
            //полный запрос данных
            if ($MODEL==null) { 
                $MODEL = $modelClass::select($allowFields)->filterRead();
            }
            $all_records = $MODEL->count();
            $MODEL = $MODEL->offset(((int)$args["page"]-1)*(int)$args["itemsPerPage"])->limit($args["itemsPerPage"]);
            if (isset($args["sortBy"]) && isset($args["sortBy"][0]) && strlen(isset($args["sortBy"][0]))>0 ) $MODEL = $MODEL->orderBy($args["sortBy"][0], $args["sortDesc"][0]);
            if (isset($args["sortBy"]) && isset($args["sortBy"][1]) && strlen(isset($args["sortBy"][1]))>0 ) $MODEL = $MODEL->orderBy($args["sortBy"][1], $args["sortDesc"][1]);
            $rows = $MODEL->get();
        }//----------------------------------------------------------------------------------


//Отладка для просмотра SQL запроса
//die($MODEL->toSql());


        //Выдаем информацию о таблице
        $json_response['info'] = $tableInfo;

        //Если нет поля ID то добавляем его
        //if (!isset($json_response['info']['columns']["id"])) { array_push($json_response['info']['columns'], ["id"=>HelperFieldTypes::typeInteger("id")->width(50)->roles_read([2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20])->roles_edit([])->get()]  ); }

        //Проходим по колонкам, убираем лишние поля
        foreach ($json_response['info']["columns"] as $x=>$y) {
            if (!in_array($x, $allowFields)) { unset($json_response['info']["columns"][$x]); continue; } //Оставляем только те поля которые запросили и разрешены к просмотру
            if (!$this->APP->auth->hasRoles($y["read"])) { unset($json_response['info']["columns"][$x]); continue; } //Если чтение запрещено то удаляем поле
            if (!$this->APP->auth->hasRoles($y["edit"])) { $json_response['info']["columns"][$x]["protected"]=true; continue; } //Если редактирование запрещено то делаем отметку о защищенном поле
        }

        //Заполняем информацию о странице
        $json_response['pagination'] = [
                                "key"=> "id",
                                "page"=> $args["page"],
                                "totalItems"=> (($all_records<=$args["itemsPerPage"])?-1:$all_records),
                                "itemsPerPage"=> $args["itemsPerPage"],
                                ];



        //Берем строки из таблицы и выдаем клиенту ----------------------------------------------------------------------------------
        $need_footer = false;
        $footer_row = [];
        foreach ($rows as $row) {
            $item = $this->rowConvert($json_response['info'], $row); //Форматируем поля для вывода клиенту
            array_push($json_response['rows'], $item);

            //Если для этого поля требуется агрегатная функция в итогах то вычисляем.
            foreach ($json_response['info']["columns"] as $x=>$y) {
                if (isset($y["footer"])) {
                   if (!isset($footer_row[$x])) $footer_row[$x] = 0; //init
                   if ($y["footer"]=="count") $footer_row[$x] = 1; 
                   if ($y["footer"]=="sum")   $footer_row[$x] += (float)$item[$x]; 
                   $need_footer = true; 
                }
            }
        }//----------------------------------------------------------------------------------------------------------------------------

        //Итоги таблицы
        if ($need_footer) $json_response['footer_row'] = $footer_row;

        //убираем лишние данные
        foreach ($json_response['info']["columns"] as $x=>$y) {
            if (isset($json_response['info']["columns"][$x]["read"])) { unset($json_response['info']["columns"][$x]["read"]); }
            if (isset($json_response['info']["columns"][$x]["add"])) { unset($json_response['info']["columns"][$x]["add"]); }
            if (isset($json_response['info']["columns"][$x]["edit"])) { unset($json_response['info']["columns"][$x]["edit"]); }
        }

        return $json_response;
    }
    //******************* GET *******************************************************


    

    //******************* CONVERT FOR OUT*******************************************************
    public function rowConvert($tableInfo, $row){
            $item = [];
            $item["id"] = $row->id;

            //Каждую строку разбираем на поля, проверяем уровни доступа, заполняем и отдаем
            foreach ($tableInfo["columns"] as $x=>$y) {
              if (!$this->APP->auth->hasRoles($y["read"])) continue; //Чтение поля запрещено
              $item[$x] = $row->{$x};

              if ($y["type"]=="linkTable" || $y["type"]=="select") { 
                 $item[$x."_text"] = $row->getFieldLinks($x, true); 
                 if (isset($y["object"]) && $y["object"]) $item[$x."_rows"] = $row->getFieldLinks($x, false); 
              } 
              if ($y["type"]=="integer")  { $item[$x] = (int)$row->{$x}; }
              if ($y["type"]=="float")    { $item[$x] = (float)$row->{$x}; }
              if ($y["type"]=="double")   { $item[$x] = (double)$row->{$x}; }
              if ($y["type"]=="checkBox") { $item[$x."_text"] = ((int)$row->{$x}==1?"Да":"Нет"); } 
              if ($y["type"]=="images")   { $item[$x] = $this->getUploadedFiles(json_decode($item[$x]), "image", $tableInfo["table"], $row->id, $x); }
              if ($y["type"]=="files" )   { $item[$x] = $this->getUploadedFiles(json_decode($item[$x]), "file", $tableInfo["table"], $row->id, $x); }
              if ($y["type"]=="password")   $item[$x] = "";
              if ($y["type"]=="date")       $item[$x] = \MapDapRest\Utils::convDateToDate($item[$x], false);
              if ($y["type"]=="dateTime")   $item[$x] = \MapDapRest\Utils::convDateToDate($item[$x], true);
              if ($y["type"]=="timestamp")  $item[$x] = \MapDapRest\Utils::convDateToDate($item[$x], true);
            }
        return $item;
    }
    //******************* CONVERT FOR OUT *******************************************************


    
    //******************* FILL ROW *******************************************************
    public function fillRowParams($row, $action, $tableInfo, $params)
    {
        foreach ($tableInfo["columns"] as $x=>$y) {
          if (isset($y["is_virtual"]) && $y["is_virtual"]) continue;      //Поле виртуальное
          if (!isset($params[$x]))  continue;                             //Поле отсутствует
          if (!$this->APP->auth->hasRoles($y[$action])) continue;         //Нет прав не заполняем поле
          if ($y["type"]=="password" && strlen($params[$x])<4) continue;  //Пароль пустой не заполняем

          //Если картики или фалы то подготавливаем массив в специальном формате
          if ($y["type"]=="images" || $y["type"]=="files") {
              $files = $this->prepareFileUploads($params[$x], $tableInfo["table"], $row->id, $x, $y);
              if ($files) { $row->{$x} = $files; }
          } else {
              $row->{$x} = $params[$x];
              if (is_array($params[$x])) {  $row->{$x} = \MapDapRest\Utils::arrayToString($params[$x]);  } //массив преобразуем в строку [12,32,34] -> 12,32,34
          }

          
          if ($y["type"]=="password") { $row->{$x} = password_hash($params[$x], PASSWORD_DEFAULT); } //пароль хешируем
          if (!empty($y["default"]) && $action=="add" && strlen($params[$x])==0) { $row->{$x} = $y["default"]; } //при добавлении поля если оно пустое то заполняем его значение по умолчанию
          //Меняем даты в формат SQL
          if ($y["type"]=="date")       { $row->{$x} = \MapDapRest\Utils::convDateToSQL($row->{$x}, false); }
          if ($y["type"]=="dateTime")   { $row->{$x} = \MapDapRest\Utils::convDateToSQL($row->{$x}, true);  }
          if ($y["type"]=="timestamp")  { $row->{$x} = \MapDapRest\Utils::convDateToSQL($row->{$x}, true);  }
        }
        return $row;
    }
    //******************* FILL ROW *******************************************************



    //******************* GET FILES *******************************************************
    public function getUploadedFiles($files_array, $type, $table_name="", $row_id=0, $field_name=""){
       $files = [];
       if (!is_array($files_array)) return $files;
       if (count($files_array)==0)  return $files;

       foreach ($files_array as $y) {
         $fname = $y;
         $fpath = $this->APP->FULL_URL."uploads/$type/$table_name/".$row_id."_".$field_name."_".$y;
         array_push($files, ["name"=>$fname, "url"=>$fpath]);
       }
       return $files;
    }
    //******************* GET FILES *******************************************************


    
    public function prepareFileUploads($files_array, $table_name="", $row_id=0, $field_name="", $field_params=[]){
              if (!is_array($files_array)) return false;

              $files=[];
              for($i=0; $i<count($files_array); $i++) {
                if (!isset($files_array[$i]["name"])) continue;
                if (!isset($files_array[$i]["src"]))  continue;

                $fname = \MapDapRest\Utils::getSlug($files_array[$i]["name"], true);
                $fsrc = $files_array[$i]["src"];
                if (strlen($fname)<2) continue;
                if (strlen($fsrc)<8)  continue;
                $fsrc = substr($fsrc, strpos($fsrc, 'base64,')+7 );
                array_push($files, $fname );

                if ($row_id>0) {
                   $folder_path = $this->APP->ROOT_PATH."uploads/".$table_name;
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
              }//for
              if (count($files)>0) return json_encode($files);

        return false;
    }




    

    //********************* ADD **************************************************************************************************
    public function add($tablename, $args) {
        $user = $this->APP->auth->getFields();
        $json_response = [];
 
        if ($tablename=="") return ["error"=>6, "message"=>"tablename empty"];
        if (!isset($this->APP->models[$tablename])) return ["error"=>6, "message"=>"table $tablename not found"];

        $modelClass = $this->APP->models[$tablename];
        $tableInfo = $modelClass::modelInfo();

        //если доступ на добавление отсутствует то выдаем сообщение
        if (!$this->APP->auth->hasRoles($tableInfo["add"])) return ["error"=>4, "message"=>"table $tablename access denied"];
       
        //Создаем запись
        $row = new $modelClass();
        try { 
           $row->created_by_user = $user["id"]; 
        } catch(Exception $e) {
        }

        $row = $this->fillRowParams($row, "add", $tableInfo, $args);  //Заполняем строку данными из формы

        //Это дочерняя таблица - тогда устанавливаем родителя
        //&parentTables=["users"=>12, "posts"=>33]
        if (isset($tableInfo["parentTables"]) && count($tableInfo["parentTables"])>0 && isset($args["parentTables"])) {
             foreach ($tableInfo["parentTables"] as $x=>$y) {
                $row->{$y["id"]} = (int)$args["parentTables"][$y["table"]];
             }
        }
 
        //Событие
        if (method_exists($modelClass, "beforePost")) {  if ($modelClass::beforePost("add", $row, $args)===false) { return ["error"=>4, "message"=>"break by beforePost"]; };  }

        $result = $row->save(); //Сохраняем запись
        if (!$result) { return ["error"=>4, "message"=>"save error"];  }  //Если ошибка сохранения то сообщаем и выходим
        
        //Событие
        if (method_exists($modelClass, "afterPost")) {  $modelClass::afterPost("add", $row, $args);  }


        $row = $this->fillRowParams($row, "add", $tableInfo, $args);  //Заполняем строку данными из формы

        $id = $row->id;
        $row = $modelClass::filterRead()->filterEdit()->where("id",$id)->first(); //Считываем данные из базы и отдаем клиенту
        
        $item = $this->rowConvert($tableInfo, $row);

        return $item;

    }
    //*****************************************************************************************************************************
 


    //********************* EDIT **************************************************************************************************
    public function edit($tablename, $id, $args) {
        $user = $this->APP->auth->getFields();
        $json_response = [];
 
        if ($tablename=="") return ["error"=>6, "message"=>"tablename empty"];
        if (!isset($this->APP->models[$tablename])) return ["error"=>6, "message"=>"table $tablename not found"];

        $modelClass = $this->APP->models[$tablename];
        $tableInfo = $modelClass::modelInfo();

        //если доступ на изменение отсутствует то выдаем сообщение
        if (!$this->APP->auth->hasRoles($tableInfo["edit"])) return ["error"=>4, "message"=>"table $tablename access denied"];
       
        //Читаем запись
        $row = $modelClass::filterRead()->filterEdit()->where("id", $id)->first();
        if (!$row) { return ["error"=>4, "message"=>"id $id not found"]; } //если не нашли строку то выходим
        if ($row->id != $id) { return ["error"=>4, "message"=>"id $id not found"]; } //если не нашли строку то выходим
        
        $row = $this->fillRowParams($row, "edit", $tableInfo, $args);  //Заполняем строку данными из формы
        
        //Событие
        if (method_exists($modelClass, "beforePost")) {  if ($modelClass::beforePost("edit", $row, $args)===false) { return ["error"=>4, "message"=>"break by beforePost"]; };  }

        $result = $row->save(); //Сохраняем запись
        if (!$result) { return ["error"=>4, "message"=>"save error"]; }  //Если ошибка сохранения то сообщаем и выходим
        
        //Событие
        if (method_exists($modelClass, "afterPost")) {  $modelClass::afterPost("edit", $row, $args);  }

        
        $id = $row->id;
        $row = $modelClass::filterRead()->filterEdit()->where("id",$id)->first(); //Считываем данные из базы и отдаем клиенту
        
        $item = $this->rowConvert($tableInfo, $row);

        return $item;

    }
    //*****************************************************************************************************************************
 

    
    
    //********************* DELETE **************************************************************************************************
    public function delete($tablename, $id) {
        $user = $this->APP->auth->getFields();
        $json_response = [];
 
        if ($tablename=="") return ["error"=>6, "message"=>"tablename empty"];
        if (!isset($this->APP->models[$tablename])) return ["error"=>6, "message"=>"table $tablename not found"];

        $modelClass = $this->APP->models[$tablename];
        $tableInfo = $modelClass::modelInfo();

        //если доступ на добавление отсутствует то выдаем сообщение
        if (!$this->APP->auth->hasRoles($tableInfo["delete"])) return ["error"=>4, "message"=>"table $tablename access denied"];
       
        //Читаем запись
        $row = $modelClass::filterRead()->filterEdit()->filterDelete()->where("id",$id)->first();
        if (!$row) { return ["error"=>4, "message"=>"id $id not found"]; } //если не нашли строку то выходим
        if ($row->id != $id) { return ["error"=>4, "message"=>"id $id not found"]; } //если не нашли строку то выходим

        
        //Событие
        if (method_exists($modelClass, "beforePost")) {  if ($modelClass::beforePost("delete", $row, [])===false) { return ["error"=>4, "message"=>"break by beforePost"]; };  }

        $row->delete();
        
        //Событие
        if (method_exists($modelClass, "afterPost")) {  $modelClass::afterPost("delete", $row, []);  }

        return $row;
    }
    //*****************************************************************************************************************************





    



}
