<?php
namespace MapDapRest\App\Table\Controllers;



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
        $this->tableInfo = $modelClass::modelInfo();
        if (!$this->APP->auth->hasRoles($this->tableInfo[$access])) {
           $this->lastError = ["error"=>4, "message"=>"access to table ($tablename) denied"];
           return false;
        }

        $this->modelClass = $modelClass;
        unset($this->tableInfo["seeds"]);
        //Оставляем разрешенные поля
        foreach ($this->tableInfo["columns"] as $x=>$y) {
            if (!$this->APP->auth->hasRoles($y["read"])) { unset($this->tableInfo["columns"][$x]); continue; }
            if (!$this->APP->auth->hasRoles($y["edit"])) { $this->tableInfo["columns"][$x]["protected"]=true; }
            $this->tableInfo["columns"][$x]["name"]=$x;
        }

        return true;
    }



    //******************* GET *******************************************************
    public function get($tablename, $id, $request)
    {
        if (!$this->loadModelInfo($tablename, "read")) return $this->lastError;

        $modelClass = $this->modelClass;
        $tableInfo = $this->tableInfo;

        if (trim($id)=="modelInfo()") {
           $json_response = ["error"=>0, "info"=>$tableInfo, "rows"=>[], "pagination"=>[]];
           return $json_response;
        }

        $json_response = ["error"=>0, "info"=>[], "rows"=>[], "pagination"=>[]];

        //оставляем только поля разрешенные для чтения  или запрашиваемые клиентом $reqFields
        $fields = [];
        if ($request->hasParam("fields")) $fields = $request->getParam("fields");
        $allowFields=["id"];
        foreach ($tableInfo["columns"] as $x=>$y) {
           if (count($fields)>0 && !in_array($x, $fields))  continue;
           if (isset($y["is_virtual"]) && $y["is_virtual"]) continue;
           if ($this->APP->auth->hasRoles($y["read"])) array_push($allowFields, $x);
        }//----------------------------------------------------------------------------------


        $MODEL = $modelClass::select($allowFields)->filterRead();


        //Запрашивают фильтр записей по полям
        //filter:[ {field:name, oper:'like', value:'asd'} ]
        $filter = [];
        if ($request->hasParam("filter")) $filter = $request->getParam("filter");
        if (count($filter)>0) {

            foreach ($filter as $x=>$y) { //перебираем поля 
                if (isset($filter[$x]["value"])) {  //поле есть - формируем фильтр
                    $s_field=$filter[$x]["field"]; $s_oper=$filter[$x]["oper"]; $s_value=$filter[$x]["value"];

                    if ($tableInfo["columns"][$s_field]["type"]=="date")     { $s_value = \MapDapRest\Utils::convDateToSQL($s_value, false); }
                    if ($tableInfo["columns"][$s_field]["type"]=="dateTime") { $s_value = \MapDapRest\Utils::convDateToSQL($s_value, true); }
                    if ($s_oper=="like")   { $s_value = "%".$s_value."%"; }
                    if ($s_oper=="begins") { $s_oper="like"; $s_value = $s_value."%"; }
                    
                    if ($s_oper=="in") {
                       if (gettype($s_value)=="string" || gettype($s_value)=="integer") { $s_value=explode(",", $s_value); }
                       $MODEL = $MODEL->whereIn($s_field, $s_value);
                    } else {
                       if (gettype($s_value)=="array") { $s_value = \MapDapRest\Utils::arrayToString($s_value); }
                       $MODEL = $MODEL->where($s_field, $s_oper, $s_value);
                    }
                }
            }
        }//----------------------------------------------------------------------------------

        //Это дочерняя таблица - тогда фильтруем записи по родителю  -
        //parent : [table:'users', field:'user_id', value:999]
        if ($request->hasParam("parent")) {
             foreach ($request->getParam("parent") as $x) {
               foreach ($tableInfo["parentTables"] as $y) {
                 if ($y["table"]==$x["table"]) {
                     $MODEL = $MODEL->where($y["field"], (int)$x["value"] );
                 }
               }
             }
        }//----------------------------------------------------------------------------------



        //Сортировка по умолчанию из модели если в аргументах нет требований сортировки sort[] || order[] ---------------------------------------
        $sort = [];
        if ($request->hasParam("sort")) $sort = $request->getParam("sort");
        if ($request->hasParam("order")) $sort = $request->getParam("order");
        if (gettype($sort)=="string") { $sort = explode(",", $sort); }
        if (count($sort)==0 && isset($tableInfo["sortBy"])) { $sort = $tableInfo["sortBy"]; }
        if (count($sort)==0 && isset($tableInfo["orderBy"])) { $sort = $tableInfo["orderBy"]; }
        foreach ($sort as $fld) { //перебираем поля 
            $ord = "asc";
            if (substr($fld,0,1) == "-") {
               $fld = substr($fld,1);
               $ord = "desc";
            }
            $MODEL = $MODEL->orderBy($fld, $ord);
        }


        //Значения по умолянию в описании модели
        if (!isset($tableInfo["itemsPerPage"])) $tableInfo["itemsPerPage"] = 100;
        if (!isset($tableInfo["itemsPerPageVariants"])) $tableInfo["itemsPerPageVariants"] = [50,100,200,300,500,1000];

        //LIMIT
        $limit = $tableInfo["itemsPerPage"];
        if ($request->hasParam("limit")) $limit = $request->getParam("limit");

        //PAGE
        $page = 1;
        if ($request->hasParam("page")) $page = $request->getParam("page");

        $MODEL = $MODEL->offset( ($page-1)*$limit )->limit($limit);



        $rows = [];
        //GET
        if ((int)$id > 0) {
            $rows = $MODEL->where("id", $id)->get();
            if (count($rows)>1) return ["error"=>6, "message"=>"scope filterRead error"];
        } else {
            $rows = $MODEL->get();
        }


//Отладка для просмотра SQL запроса
//die($MODEL->toSql());


        //Выдаем информацию о таблице
        $json_response['info'] = $tableInfo;

        //Проходим по колонкам, убираем лишние поля
        foreach ($tableInfo["columns"] as $x=>$y) {
            if (!in_array($x, $allowFields)) { unset($json_response['info']["columns"][$x]); continue; } //Оставляем только те поля которые запросили и разрешены к просмотру
            if (!$this->APP->auth->hasRoles($y["read"])) { unset($json_response['info']["columns"][$x]); continue; } //Если чтение запрещено то удаляем поле
            if (!$this->APP->auth->hasRoles($y["edit"])) { $json_response['info']["columns"][$x]["protected"]=true; continue; } //Если редактирование запрещено то делаем отметку о защищенном поле
        }

        //Заполняем информацию о странице
        $json_response['pagination'] = [
                                "key"=> "id",
                                "page"=> $page,
                                "totalItems"=> ((count($rows) <= $limit)? -1 : count($rows)),
                                "itemsPerPage"=> $limit,
                                ];



        //Берем строки из таблицы и выдаем клиенту ----------------------------------------------------------------------------------
        $need_footer = false;
        $footer_row = [];
        $isFast = $request->hasParam("fast");
        foreach ($rows as $row) {
            //$item = $this->rowConvert($json_response['info'], $row, $isFast ); //Форматируем поля для вывода клиенту
            $item = $row->getConvertedRow( $isFast ); //Форматируем поля для вывода клиенту

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

        if ($request->hasParam("mini")) {
           unset($json_response['info']);
           unset($json_response['pagination']);
        }

        return $json_response;
    }
    //******************* GET *******************************************************


    



    




    

    //********************* ADD **************************************************************************************************
    public function add($tablename, $request) {
        if (!$this->loadModelInfo($tablename, "add")) return $this->lastError;

        $modelClass = $this->modelClass;
        $tableInfo = $this->tableInfo;
        $json_response = ["error"=>0];
 
        //Создаем запись
        $row = new $modelClass();
        try { 
           $row->created_by_user = $this->APP->auth->user->id; 
        } catch(Exception $e) {
        }

        $fill_count=0;
        $row = $row->fillRow("add", $request->params, $fill_count);  //Заполняем строку данными из формы
        if ($fill_count==0) return ["error"=>7, "message"=>"fields not filled"];

        //Это дочерняя таблица - тогда устанавливаем поля родителя
        if (isset($tableInfo["parentTables"])) {
             foreach ($tableInfo["parentTables"] as $x=>$y) {
                 if ($request->hasParam($y["field"])) {
                     $row->{$y["field"]} = (int)$request->getParam($y["field"]);
                 }
             }
        }//----------------------------------------------------------------------------------

 
        //Событие
        if (method_exists($modelClass, "beforePost")) {  if ($modelClass::beforePost("add", $row, $request->params)===false) { return ["error"=>4, "message"=>"break by beforePost"]; };  }

        try {
            $result = $row->save(); //Сохраняем запись
            if (!$result) { return ["error"=>4, "message"=>"save error"];  }  //Если ошибка сохранения то сообщаем и выходим
        } catch(Exception $e) {
            return ["error"=>4, "message"=>$e->getMessage()];
        }
        
        //Событие
        if (method_exists($modelClass, "afterPost")) {  $modelClass::afterPost("add", $row, $request->params);  }


        //Повторное заполнение необходимо для сохранения файла
        $row = $row->fillRow("add", $request->params);  //Заполняем строку данными из формы

        $id = $row->id;
        $row = $modelClass::filterRead()->where("id",$id)->first(); //Считываем данные из базы и отдаем клиенту
        
        $json_response["rows"] = [ $row->getConvertedRow() ];
        
        return $json_response;

    }
    //*****************************************************************************************************************************
 


    //********************* EDIT **************************************************************************************************
    public function edit($tablename, $id, $request) {
        if (!$this->loadModelInfo($tablename, "edit")) return $this->lastError;

        $modelClass = $this->modelClass;
        $tableInfo = $this->tableInfo;
        $json_response = ["error"=>0];
       
        //Читаем запись
        $row = $modelClass::filterRead()->filterEdit()->where("id", $id)->first();
        if (!$row) { return ["error"=>4, "message"=>"id $id not found"]; } //если не нашли строку то выходим
        if ($row->id != $id) { return ["error"=>4, "message"=>"id $id not found"]; } //если не нашли строку то выходим
        
        $row = $row->fillRow("edit", $request->params);  //Заполняем строку данными из формы
        
        //Событие
        if (method_exists($modelClass, "beforePost")) {  if ($modelClass::beforePost("edit", $row, $request->params)===false) { return ["error"=>4, "message"=>"break by beforePost"]; };  }

        try {
            $result = $row->save(); //Сохраняем запись
            if (!$result) { return ["error"=>4, "message"=>"save error"];  }  //Если ошибка сохранения то сообщаем и выходим
        } catch(Exception $e) {
            return ["error"=>4, "message"=>$e->getMessage()];
        }
        
        //Событие
        if (method_exists($modelClass, "afterPost")) {  $modelClass::afterPost("edit", $row, $request->params);  }
        
        $id = $row->id;
        $row = $modelClass::filterRead()->where("id",$id)->first(); //Считываем данные из базы и отдаем клиенту
        
        $json_response["rows"] = [ $row->getConvertedRow() ];

        return $json_response;
    }
    //*****************************************************************************************************************************
 

    
    
    //********************* DELETE **************************************************************************************************
    public function delete($tablename, $id) {
        if (!$this->loadModelInfo($tablename, "delete")) return $this->lastError;

        $modelClass = $this->modelClass;
        $tableInfo = $this->tableInfo;
        $json_response = ["error"=>0];
       
        //Читаем запись
        $row = $modelClass::filterRead()->filterEdit()->filterDelete()->where("id",$id)->first();
        if (!$row) { return ["error"=>4, "message"=>"id $id not found"]; } //если не нашли строку то выходим
        if ($row->id != $id) { return ["error"=>4, "message"=>"id $id not found"]; } //если не нашли строку то выходим

        //Событие
        if (method_exists($modelClass, "beforePost")) {  if ($modelClass::beforePost("delete", $row, [])===false) { return ["error"=>4, "message"=>"break by beforePost"]; };  }

        $row->delete();
        
        //Событие
        if (method_exists($modelClass, "afterPost")) {  $modelClass::afterPost("delete", $row, []);  }

        return $json_response;
    }
    //*****************************************************************************************************************************





    



}
