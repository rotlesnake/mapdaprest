<?php

namespace App\Table\Controllers;


class TreeHandler
{


    public function anyAction($request, $response, $tablename, $args)
    {
       $json_response = [];
 
        if ($tablename=="") return ["error"=>6, "message"=>"tablename empty"];
        if (!$this->APP->hasModel($tablename)) return ["error"=>6, "message"=>"table $tablename not found"];
        
        $modelClass = $this->APP->getModel($tablename);


        if ($request->method=="GET") {
           $json_response = $this->getTreeTable($modelClass, 0);
           return $json_response;
        }

        if ($request->method=="POST") {
           $this->setTreeTable($modelClass, $args);
           $json_response = $this->getTreeTable($model, 0);
           return $json_response;
        }
    }


    public function getTreeTable($model, $parent_id=0) {
        $json_response = [];
        $items = $model::where("parent_id", $parent_id)->orderBy("sort")->get();
        foreach ($items as $item) {
             $item_tree = [];
             $item_tree["server_id"] = $item->id;
             $item_tree["id"] = $item->id;
             $item_tree["pid"] = (int)$item->parent_id;
             $item_tree["open"] = true;
             $item_tree["name"] = $item->name;
             $item_tree["childrens"] = $this->getTreeTable($model, $item->id);

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
             $row->parent_id = $parent_id;
             $row->name = $item["name"];
             if ($row->id == 0) {
               //default values
             }
             $row->sort = $sort;
             $row->save();

             if (isset($item["childrens"]) && count($item["childrens"])>0 ) {
                 $sort = $this->setTreeTable($model, $item["childrens"], $row->id, $sort);
             }
        }
        return $sort;
    }

}
