<?php
namespace MapDapRest\App\Api\Controllers;
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
        $response = [];

        if ($tablename=="") return ["error"=>6, "message"=>"tablename empty"];
        if (!isset($this->APP->models[$tablename])) return ["error"=>6, "message"=>"table $tablename not found"];

        $modelClass = $this->APP->models[$tablename];
        $tableInfo = $modelClass::modelInfo();


 
        //оставляем только поля разрешенные для чтения  или запрашиваемые клиентом fields[] ------------------------------------------
        $fields = [];
        if ($request->hasParam("fields")) $fields = $request->getParam("fields");
        
        $allowFields=["id"];
        foreach ($tableInfo["columns"] as $x=>$y) {
           if (count($fields)>0 && !in_array($x, $fields)) continue;
           if (isset($y["is_virtual"])) continue;

           if ($this->APP->auth->hasRoles($y["read"])) array_push($allowFields, $x);
        }//---------------------------------------------------------------------------------------------------------------------------
        
 


        //если доступ на чтение отсутствует то выдаем сообщение
        if (!$this->APP->auth->hasRoles($tableInfo["read"])) return ["error"=>4, "message"=>"table $tablename access denied"];


        $MODEL = $modelClass::select($allowFields)->filterRead();
        

        //FILTER
        $filter = [];
        if ($request->hasParam("filter")) $filter = $request->getParam("filter");
        foreach ($filter as $fld=>$val) { //перебираем поля 
            $cnd = "="; 
            if (isset($request->params["filter_oper"][$x])) $cnd = $request->params["filter_oper"][$x];
            
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

        //FIND
        if ($id > 0) {
            $MODEL = $MODEL->where("id", $id);
            $rows = $MODEL->first();
        } else {
            $rows = $MODEL->get();
        }

        

//Отладка для просмотра SQL запроса
//die($MODEL->toSql());
/*
        //Берем строки из таблицы и выдаем клиенту ----------------------------------------------------------------------------------
        foreach ($rows as $row) {
            $item = $this->rowConvert($response['info'], $row); //Форматируем поля для вывода клиенту
            array_push($response, $item);
        }//----------------------------------------------------------------------------------------------------------------------------
*/
  
        return $rows;
    }
    //******************* GET *******************************************************


    


    



}
