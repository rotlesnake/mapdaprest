<?php
namespace MapDapRest\App\DbQuery\Controllers;


class TableHandler
{

    public $APP;
    public $lastError = [];
    public $modelClass;
    public $tableInfo;


    public function __construct($app)
    {
        $this->APP = $app;
    }


    public function loadModelInfo($tablename, $access) {
        if ($tablename=="") {
           $this->lastError = ["error"=>6, "message"=>"table name is empty"];
           return false;
        }
        if (!isset($this->APP->models[$tablename])) {
           $this->lastError = ["error"=>6, "message"=>"table ($tablename) not found"];
           return false;
        }

        $modelClass = $this->APP->models[$tablename];
        $this->tableInfo = $modelClass::getStaticModelInfo($this->APP->auth);
        if (!$this->APP->auth->hasRoles($this->tableInfo[$access])) {
           $this->lastError = ["error"=>4, "message"=>"access to table ($tablename) denied"];
           return false;
        }

        $this->modelClass = $modelClass;
        unset($this->tableInfo["seeds"]);
        return true;
    }


    //******************* GET *******************************************************
    public function get($tablename, $id, $request)
    {
        if (!$this->loadModelInfo($tablename, "read")) return $this->lastError;

        $modelClass = $this->modelClass;
        $tableInfo = $this->tableInfo;

        if (trim($id)=="info" || trim($id)=="modelInfo") {
           return $tableInfo;
        }

        //оставляем только поля разрешенные для чтения  или запрашиваемые клиентом fields[] ------------------------------------------
        $fields = [];
        if ($request->hasParam("fields")) $fields = $request->getParam("fields");
        if (isset($fields[$tablename])) $fields = explode(",", $fields[$tablename] );
        if (gettype($fields)=="string") $fields = explode(",", $fields );
        $allowFields=["id"];
        foreach ($tableInfo["columns"] as $x=>$y) {
           if (count($fields)>0 && !in_array($x, $fields)) continue;
           if (isset($y["is_virtual"])) continue;
           if ($this->APP->auth->hasRoles($y["read"])) array_push($allowFields, $x);
        }//---------------------------------------------------------------------------------------------------------------------------
        

        $MODEL = $modelClass::select($allowFields)->filterRead();
        

        //Запрашивают фильтр записей по полям
        //filter_type:"or", filter:[ {field:'name', oper:'like', value:'asd'} ]
        $filter = [];
        $filter_type = "and";
        if ($request->hasParam("filter_type")) $filter_type = $request->getParam("filter_type");
        if ($request->hasParam("filter")) $filter = $request->getParam("filter");
        if (count($filter)>0) {
            if (gettype($filter[0])=="string" && $filter[0][0]=="{") {
                foreach ($filter as $x=>$y) $filter[$x] = json_decode($filter[$x], true);
            }
            foreach ($filter as $x=>$y) { //перебираем поля 
                if (isset($filter[$x]["value"])) {  //поле есть - формируем фильтр
                    $s_field=$filter[$x]["field"]; $s_oper=$filter[$x]["oper"]; $s_value=$filter[$x]["value"];

                    if ($tableInfo["columns"][$s_field]["type"]=="date")     { $s_value = \MapDapRest\Utils::convDateToSQL($s_value, false); }
                    if ($tableInfo["columns"][$s_field]["type"]=="dateTime") { $s_value = \MapDapRest\Utils::convDateToSQL($s_value, true); }
                    if ($s_oper=="like")   { $s_value = "%".$s_value."%"; }
                    if ($s_oper=="begins") { $s_oper="like"; $s_value = $s_value."%"; }
                    
                    if ($s_oper=="in" || $s_oper=="not_in") {
                       if (gettype($s_value)=="string" || gettype($s_value)=="integer") { $s_value=explode(",", $s_value); }
                       foreach($s_value as $key=>$val) { if (!$val) unset($s_value[$key]); }
                       if (count($s_value) == 0) continue;
                       if (isset($tableInfo["columns"][$s_field]["multiple"]) && $tableInfo["columns"][$s_field]["multiple"]===true) {
                           $findinset = "";
                           foreach($s_value as $value){
                               if ($s_oper=="in") {
                                   $findinset .= "or FIND_IN_SET(?, ".$s_field.") > 0 ";
                               } else {
                                   $findinset .= "or FIND_IN_SET(?, ".$s_field.") = 0 ";
                               }
                           }
                           $findinset = "(".substr($findinset, 3).")";
                           $MODEL = $MODEL->whereRaw($findinset, $s_value);
                       } else {
                           if ($s_oper=="in") {
                               $MODEL = $MODEL->whereIn($s_field, $s_value);
                           } else {
                               $MODEL = $MODEL->whereNotIn($s_field, $s_value);
                           }
                       }
                    } else {
                       if (gettype($s_value)=="array") { $s_value = \MapDapRest\Utils::arrayToString($s_value); if (strlen($s_value)==0) continue; }

                       if (isset($tableInfo["columns"][$s_field]["multiple"]) && $tableInfo["columns"][$s_field]["multiple"]===true) {
                           $MODEL = $MODEL->findInSet($s_field, $s_value); 
                       } else {
                           if (strtoupper($filter_type) == "OR") { 
                              $MODEL = $MODEL->orWhere($s_field, $s_oper, $s_value);  
                           } else { 
                              $MODEL = $MODEL->where($s_field, $s_oper, $s_value); 
                           }
                       }
                    }
                }
            }

        }//filter----------------------------------------------------------------------------------


        //Это дочерняя таблица - тогда фильтруем записи по родителю  -
        //parent : [table:'users', field:'user_id', value:999]
        if ($request->hasParam("parent")) {
             foreach ($request->getParam("parent") as $x) {
               foreach ($tableInfo["parentTables"] as $y) {
                 if ($y["table"]==$x["table"]) {
                    if (is_array($x["value"])) {  
                       $x["value"] = \MapDapRest\Utils::arrayToString($x["value"]);  
                    }
                    $MODEL = $MODEL->where($y["field"], (int)$x["value"] );
                 }
               }
             }
        }//----------------------------------------------------------------------------------


        //Сортировка по умолчанию из модели если в аргументах нет требований сортировки sort[] || order[] ---------------------------------------
        $sort = [];
        if ($request->hasParam("sort")) $sort = $request->getParam("sort");
        if ($request->hasParam("sortBy")) $sort = $request->getParam("sortBy");
        if (gettype($sort)=="string") { $sort = explode(",", $sort); }
        if (count($sort)==0 && isset($tableInfo["sortBy"])) { $sort = $tableInfo["sortBy"]; }
        if (count($sort)==0 && isset($tableInfo["orderBy"])) { $sort = $tableInfo["orderBy"]; }
        foreach ($sort as $ndx=>$fld) { //перебираем поля 
            if (gettype($fld)=="array") {
                $ord = $fld["dir"];
                $fld = $fld["field"];
            } else {
                $ord = "asc";
                if ($request->hasParam("sortDesc") && isset($request->params["sortDesc"][$ndx]) && $request->params["sortDesc"][$ndx]) $ord = "desc";
                if (substr($fld,0,1) == "-") {
                   $fld = substr($fld,1);
                   $ord = "desc";
                }
            }
            if (substr($fld,-5)=="_text") $fld=substr($fld,0,-5);
            $sort[$ndx] = ($ord=="desc" ? "-".$fld : $fld);
            $MODEL = $MODEL->orderBy($fld, $ord);
        }//sort-----------------------------------------------------------------------------------------------------------------------


 
        //Значения по умолянию в описании модели
        if (!isset($tableInfo["itemsPerPage"])) $tableInfo["itemsPerPage"] = 100;
        if (!isset($tableInfo["itemsPerPageVariants"])) $tableInfo["itemsPerPageVariants"] = [50,100,200,300,500,1000];
        //LIMIT
        $limit = $tableInfo["itemsPerPage"];
        if ($request->hasParam("limit") && (int)$request->getParam("limit")>0) $limit = $request->getParam("limit");

        //PAGE
        $page = 1;
        if ($request->hasParam("page")) $page = $request->getParam("page");
        $rows_count = $MODEL->count();
        $MODEL = $MODEL->offset( ($page-1)*$limit )->limit($limit);

        $is_single = false;

        //FIND
        if (strlen($id) > 0 && $id != "get") {
            $rows = $MODEL->find($id);
            if (!$rows) $rows = [];
            $is_single = true;
        } else {
            if ($request->hasParam("first")) {
               $rows = $MODEL->first();
               $is_single = true;
            } else {
               $rows = $MODEL->get();
            }
        }


        //Проходим по колонкам, убираем лишние поля
        foreach ($tableInfo["columns"] as $x=>$y) {
            if (!in_array($x, $allowFields)) { unset($tableInfo["columns"][$x]); continue; } //Оставляем только те поля которые запросили и разрешены к просмотру
            if (!$this->APP->auth->hasRoles($y["read"])) { unset($tableInfo["columns"][$x]); continue; } //Если чтение запрещено то удаляем поле
            if (!$this->APP->auth->hasRoles($y["edit"])) { $tableInfo["columns"][$x]["protected"]=true; continue; } //Если редактирование запрещено то делаем отметку о защищенном поле
        }

        $isFast = $request->hasParam("fast");
 
        $items = [];
        if ($is_single) {
           $items = $rows->getConvertedRow($isFast);
        } else {
          //Берем строки из таблицы и выдаем клиенту ----------------------------------------------------------------------------------
          foreach ($rows as $row) {
              $item = $row->getConvertedRow($isFast); //Форматируем поля для вывода клиенту
              array_push($items, $item);
          }//----------------------------------------------------------------------------------------------------------------------------
        }


        return $items;
    }
    //******************* GET *******************************************************



    //********************* ADD **************************************************************************************************
    public function add($tablename, $request) {
        if (!$this->loadModelInfo($tablename, "add")) return $this->lastError;

        $modelClass = $this->modelClass;
        $tableInfo = $this->tableInfo;
       
        //Создаем запись
        $row = new $modelClass();
        try { 
           $row->created_by_user = $this->APP->auth->user->id;
        } catch(Exception $e) {
        }
   
        //Заполняем поля данными
        $fill_count=0;
        $row = $row->fillRow("add", $request->params, $fill_count);  //Заполняем строку данными из формы
        if ($fill_count==0) return [];

        //Это дочерняя таблица - тогда устанавливаем поля родителя
        if (isset($tableInfo["parentTables"])) {
             foreach ($tableInfo["parentTables"] as $x=>$y) {
                 if ($request->hasParam($y["field"])) {
                    if (is_array($request->getParam($y["field"]))) {  
                       $row->{$y["field"]} = \MapDapRest\Utils::arrayToString($request->getParam($y["field"]));  
                    } else {
                       $row->{$y["field"]} = (int)$request->getParam($y["field"]);
                    }
                 }
             }
        }//----------------------------------------------------------------------------------
        
        //Событие
        if (method_exists($modelClass, "beforePost")) {  if ($modelClass::beforePost("add", $row, $request->params)===false) { return ["error"=>4, "message"=>"break by beforePost"]; };  }
        
        $result = $row->save(); //Сохраняем запись
        if (!$result) { return ["error"=>4, "message"=>"save error"];  }  //Если ошибка сохранения то сообщаем и выходим
        
        //Событие
        if (method_exists($modelClass, "afterPost")) {  $modelClass::afterPost("add", $row, $request->params);  }

        //Повторное заполнение необходимо для сохранения файла
        $row = $row->fillRow("add", $request->params);  //Заполняем строку данными из формы
        
        $id = $row->id;
        $row = $modelClass::find($id); //Считываем данные из базы и отдаем клиенту
        
        $item = $row->getConvertedRow();
        return $item;
    }
    //*****************************************************************************************************************************
 


    //********************* EDIT **************************************************************************************************
    public function edit($tablename, $id, $request) {
        if (!$this->loadModelInfo($tablename, "edit")) return $this->lastError;

        $modelClass = $this->modelClass;
        $tableInfo = $this->tableInfo;
       
        //Читаем запись
        $row = $modelClass::filterRead()->filterEdit()->where("id", $id)->first();
        if (!$row) { return []; } //если не нашли строку то выходим
        if ($row->id != $id) { return []; } //если не нашли строку то выходим
        
        $row = $row->fillRow("edit", $request->params);  //Заполняем строку данными из формы
        
        //Это дочерняя таблица - тогда устанавливаем поля родителя
        if (isset($tableInfo["parentTables"])) {
             foreach ($tableInfo["parentTables"] as $x=>$y) {
                 if ($request->hasParam($y["field"])) {
                    if (is_array($request->getParam($y["field"]))) {  
                       $row->{$y["field"]} = \MapDapRest\Utils::arrayToString($request->getParam($y["field"]));  
                    } else {
                       $row->{$y["field"]} = (int)$request->getParam($y["field"]);
                    }
                 }
             }
        }//----------------------------------------------------------------------------------

        //Событие
        if (method_exists($modelClass, "beforePost")) {  if ($modelClass::beforePost("edit", $row, $request->params)===false) { return ["error"=>4, "message"=>"break by beforePost"]; };  }

        $result = $row->save(); //Сохраняем запись
        if (!$result) { return ["error"=>4, "message"=>"save error"]; }  //Если ошибка сохранения то сообщаем и выходим
        
        //Событие
        if (method_exists($modelClass, "afterPost")) {  $modelClass::afterPost("edit", $row, $request->params);  }
        
        $id = $row->id;
        $row = $modelClass::find($id); //Считываем данные из базы и отдаем клиенту
        
        $item = $row->getConvertedRow();

        return $item;
    }
    //*****************************************************************************************************************************
 

    
    
    //********************* DELETE **************************************************************************************************
    public function delete($tablename, $id) {
        if (!$this->loadModelInfo($tablename, "delete")) return $this->lastError;

        $modelClass = $this->modelClass;
        $tableInfo = $this->tableInfo;
       
        //Читаем запись
        $row = $modelClass::filterRead()->filterEdit()->filterDelete()->where("id",$id)->first();
        if (!$row) { return []; } //если не нашли строку то выходим
        if ($row->id != $id) { return []; } //если не нашли строку то выходим
        
        //Событие
        if (method_exists($modelClass, "beforePost")) {  if ($modelClass::beforePost("delete", $row, [])===false) { return ["error"=>4, "message"=>"break by beforePost"]; };  }

        $row->delete();
        
        //Событие
        if (method_exists($modelClass, "afterPost")) {  $modelClass::afterPost("delete", $row, []);  }

        return $row;
    }
    //*****************************************************************************************************************************





    


}//class
