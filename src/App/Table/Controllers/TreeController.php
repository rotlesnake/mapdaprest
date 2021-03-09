<?php
namespace MapDapRest\App\Table\Controllers;


class TreeController extends \MapDapRest\Controller
{


    public function anyAction($request, $response, $controller, $tablename, $args)
    {
       $json_response = [];
 
        if ($tablename=="") return ["error"=>6, "message"=>"tablename empty"];
        if (!$this->APP->hasModel($tablename)) return ["error"=>6, "message"=>"treetable $tablename not found"];
        
        $modelClass = $this->APP->getModel($tablename);

        //Получить всё дерево
        if ($request->method=="GET") {
           $json_response = $this->getTreeTable($modelClass, 0);
           return $json_response;
        }
        //Перезаписать всё дерево
        if ($request->method=="POST") {
           $this->setTreeTable($modelClass, $request->params);
           $json_response = $this->getTreeTable($modelClass, 0);
           return $json_response;
        }
        //Перезаписать один элемент
        if ($request->method=="PUT") {
           $row = $modelClass::find($request->params['id']);
           $row->fill($request->params);
           $row->save();
           return $row;
        }
        //Удалить один элемент
        if ($request->method=="DELETE") {
           $row = $modelClass::find($args[0]);
           $row->delete();
           return $row;
        }
    }


    public function getTreeTable($model, $parent_id=0) {
        $json_response = [];
        $items = $model::filterRead()->where("parent_id", $parent_id)->orderBy("sort")->get();
        foreach ($items as $item) {
             $item_tree = $item->getConvertedRow();
             $item_tree["server_id"] = (int)$item->id;
             $item_tree["children"] = $this->getTreeTable($model, $item->id);

             array_push($json_response, $item_tree);
        }
        return $json_response;
    }


    public function setTreeTable($model, $items, $parent_id=0, $sort=0) {
        
        foreach ($items as $item) {
             $sort++;
             $id = 0;
             if (isset($item["server_id"])) $id = (int)$item["server_id"];

             $row = $model::findOrNew($id);
             $row->fill($item);
             $row->parent_id = $parent_id;
             $row->sort = $sort;
             $row->save();

             if (isset($item["children"]) && count($item["children"])>0 ) {
                 $sort = $this->setTreeTable($model, $item["children"], $row->id, $sort);
             }
        }
        return $sort;
    }

}
