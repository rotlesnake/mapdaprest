<?php
namespace MapDapRest\App\Table\Controllers;


class TreeController extends \MapDapRest\Controller
{
    public $lastError = [];
    public $modelClass;
    public $tableInfo;
    public $treeFilter = [];
    public $sortField = "sort";

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

    public function anyAction($request, $response, $controller, $tablename, $args)
    {
 
        if (!$this->loadModelInfo($tablename, "read")) return $this->lastError;
        $modelClass = $this->modelClass;
        $tableInfo = $this->tableInfo;
        $json_response = [];
        $json_response = ["error"=>0, "info"=>[], "rows"=>[]];

        if ($request->hasParam("sort")) $this->sortField = $request->getParam("sort");

        //Получить всё дерево
        if ($request->method=="GET") {
           $json_response["info"] = $tableInfo;
           $json_response["rows"] = $this->getTreeTable($modelClass, 0);
           return $json_response;
        }

        //Добавление / Изменение элемента
        if ($request->method=="POST") {
           $tableHandler = new TableHandler($this->APP);
           $action = trim($args[0]);
           $id = (isset($args[1])? (int)$args[1] : 0);
           if ($request->hasParam("filter")) $this->treeFilter = $request->getParam("filter");
           if (!$request->hasParam("parent_id")) $request->params["parent_id"] = 0;

           $rows = [];
           if ($action=="get")             { $rows["info"] = $tableInfo; $rows["rows"] = $this->getTreeTable($modelClass, 0); }
           if ($action=="add")             $rows = $tableHandler->add($tablename, $request);
           if ($action=="edit" && $id>0)   $rows = $tableHandler->edit($tablename, $id, $request);
           if ($action=="delete")          $rows = $tableHandler->delete($tablename, $id);

           //$this->setTreeTable($modelClass, $request->params);
           //$json_response = $this->getTreeTable($modelClass, 0);
           return $rows;
        }
    }


    public function getTreeTable($model, $parent_id=0) {
        $json_response = [];

        $modelRequest = $model::filterRead()->where("parent_id", $parent_id)->orderBy($this->sortField);
        //фильтр записей по полям
        $filter = [];
        if (count($this->treeFilter)>0) {
            foreach ($this->treeFilter as $x=>$y) { //перебираем поля 
                if (isset($this->treeFilter[$x]["value"])) {  //поле есть - формируем фильтр
                    $s_field=$this->treeFilter[$x]["field"]; $s_oper=$this->treeFilter[$x]["oper"]; $s_value=$this->treeFilter[$x]["value"];

                    if ($this->tableInfo["columns"][$s_field]["type"]=="date")     { $s_value = \MapDapRest\Utils::convDateToSQL($s_value, false); }
                    if ($this->tableInfo["columns"][$s_field]["type"]=="dateTime") { $s_value = \MapDapRest\Utils::convDateToSQL($s_value, true); }
                    if ($s_oper=="like")   { $s_value = "%".$s_value."%"; }
                    if ($s_oper=="begins") { $s_oper="like"; $s_value = $s_value."%"; }
                    
                    if ($s_oper=="in") {
                       if (gettype($s_value)=="string" || gettype($s_value)=="integer") { $s_value=explode(",", $s_value); }
                       $modelRequest = $modelRequest->whereIn($s_field, $s_value);
                    } else {
                       if (gettype($s_value)=="array") { $s_value = \MapDapRest\Utils::arrayToString($s_value); }
                          $modelRequest = $modelRequest->where($s_field, $s_oper, $s_value); 
                    }
                }
            }
        } else {
            if ($parent_id == 0) $modelRequest->orWhereNull('parent_id');
        }//----------------------------------------------------------------------------------

        $items = $modelRequest->get();
        foreach ($items as $item) {
             $item_tree = $item->getConvertedRow();
             $item_tree["parent_id"] = (int)$item_tree["parent_id"];
             $item_tree["sort"] = (int)$item_tree["sort"];
             $item_tree["children"] = $this->getTreeTable($model, $item->id);
             if (count($item_tree["children"]) == 0) unset($item_tree["children"]);

             array_push($json_response, $item_tree);
        }
        return $json_response;
    }



}
