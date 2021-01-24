<?php
namespace MapDapRest\App\DBQuery\Controllers;
//namespace App\Table\Controllers;


class TableHandler
{

    public $APP;



    public function __construct($app)
    {
        $this->APP = $app;
    }



    //******************* GET *******************************************************
    public function get($tablename, $id, $request)
    {
        $user = $this->APP->auth->getFields();

        if ($tablename=="") return ["error"=>6, "message"=>"tablename empty"];
        if (!isset($this->APP->models[$tablename])) return ["error"=>6, "message"=>"table $tablename not found"];

        $modelClass = $this->APP->models[$tablename];
        $tableInfo = $modelClass::modelInfo();
        unset($tableInfo["seeds"]);

        if (trim($id)=="modelInfo()") {
           return $tableInfo;
        }

        //если доступ на чтение отсутствует то выдаем сообщение
        if (!$this->APP->auth->hasRoles($tableInfo["read"])) return ["error"=>4, "message"=>"table $tablename access denied"];

       
        //оставляем только поля разрешенные для чтения  или запрашиваемые клиентом fields[] ------------------------------------------
        $fields = [];
        if ($request->hasParam("fields")) $fields = explode(",", $request->getParam("fields")[$tablename] );
        $allowFields=["id"];
        foreach ($tableInfo["columns"] as $x=>$y) {
           if (count($fields)>0 && !in_array($x, $fields)) continue;
           if (isset($y["is_virtual"])) continue;

           if ($this->APP->auth->hasRoles($y["read"])) array_push($allowFields, $x);
        }//---------------------------------------------------------------------------------------------------------------------------
        

        $MODEL = $modelClass::select($allowFields)->filterRead();
        

        //FILTER
        $filter = [];
        if ($request->hasParam("filter")) $filter = $request->getParam("filter");
        foreach ($filter as $fld=>$val) { //перебираем поля 
            $cnd = "="; 
            if (isset($request->params["filter_oper"][$fld])) $cnd = $request->params["filter_oper"][$fld];
            if (strpos($val,",") !== false) { $cnd="in"; $val=explode(",", $val); }
            
            if ($tableInfo["columns"][$fld]["type"]=="date")     { $val = \MapDapRest\Utils::convDateToSQL($val, false); }
            if ($tableInfo["columns"][$fld]["type"]=="dateTime") { $val = \MapDapRest\Utils::convDateToSQL($val, true);  }

            if ($cnd=="like")   { $val = "%".$val."%"; }
            if ($cnd=="begins") { $cnd="like"; $val = $val."%"; }

            if ($cnd=="in") {
                $MODEL = $MODEL->whereIn($fld, $val);
            } else {
                $MODEL = $MODEL->where($fld, $cnd, $val);
            }
        }
        //filter----------------------------------------------------------------------------------



        //Сортировка по умолчанию из модели если в аргументах нет требований сортировки sort[] ---------------------------------------
        $sort = [];
        if ($request->hasParam("sort")) $sort = explode(",", $request->getParam("sort"));
        if (count($sort)==0 && isset($tableInfo["sortBy"])) { $sort = $tableInfo["sortBy"]; }
        foreach ($sort as $fld) { //перебираем поля 
            $ord = "asc";
            if (substr($fld,0,1) == "-") {
               $fld = substr($fld,1);
               $ord = "desc";
            }
            $MODEL = $MODEL->orderBy($fld, $ord);
        }


 
        //LIMIT
        $limit = 100;
        if (isset($tableInfo["itemsPerPage"])) $limit = $tableInfo["itemsPerPage"];
        if ($request->hasParam("limit")) $limit = $request->getParam("limit");

        //PAGE
        $page = 1;
        if ($request->hasParam("page")) $page = $request->getParam("page");
        
        $MODEL = $MODEL->offset( ($page-1)*$limit )->limit($limit);

        $is_single = false;

        //FIND
        if ((int)$id > 0) {
            $MODEL = $MODEL->where("id", $id);
            $rows = $MODEL->first();
            $is_single = true;
        } else {
            if ($request->hasParam("first")) {
               $rows = $MODEL->first();
               $is_single = true;
            } else {
               $rows = $MODEL->get();
            }
        }


        //Отладка для просмотра SQL запроса
        //die($MODEL->toSql());



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
        $user = $this->APP->auth->getFields();
 
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
   
        //Заполняем поля данными
        $fill_count=0;
        $row = $row->fillRow("add", $request->params, $fill_count);  //Заполняем строку данными из формы
        if ($fill_count==0) return [];
        
        //Событие
        if (method_exists($modelClass, "beforePost")) {  if ($modelClass::beforePost("add", $row, $request->params)===false) { return ["error"=>4, "message"=>"break by beforePost"]; };  }
        
        $result = $row->save(); //Сохраняем запись
        if (!$result) { return ["error"=>4, "message"=>"save error"];  }  //Если ошибка сохранения то сообщаем и выходим
        
        //Событие
        if (method_exists($modelClass, "afterPost")) {  $modelClass::afterPost("add", $row, $request->params);  }

        //Повторное заполнение необходимо для сохранения файла
        $row = $row->fillRow("add", $request->params);  //Заполняем строку данными из формы

        
        $id = $row->id;
        $row = $modelClass::filterRead()->where("id",$id)->first(); //Считываем данные из базы и отдаем клиенту
        
        $item = $row->getConvertedRow();

        return $item;
    }
    //*****************************************************************************************************************************
 


    //********************* EDIT **************************************************************************************************
    public function edit($tablename, $id, $request) {
        $user = $this->APP->auth->getFields();
 
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
        
        $row = $row->fillRow("edit", $request->params);  //Заполняем строку данными из формы
        
        //Событие
        if (method_exists($modelClass, "beforePost")) {  if ($modelClass::beforePost("edit", $row, $request->params)===false) { return ["error"=>4, "message"=>"break by beforePost"]; };  }

        $result = $row->save(); //Сохраняем запись
        if (!$result) { return ["error"=>4, "message"=>"save error"]; }  //Если ошибка сохранения то сообщаем и выходим
        
        //Событие
        if (method_exists($modelClass, "afterPost")) {  $modelClass::afterPost("edit", $row, $request->params);  }
        
        
        $id = $row->id;
        $row = $modelClass::filterRead()->where("id",$id)->first(); //Считываем данные из базы и отдаем клиенту
        
        $item = $row->getConvertedRow();

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





    


}//class
