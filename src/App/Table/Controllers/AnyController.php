<?php
namespace MapDapRest\App\Table\Controllers;
//namespace App\Table\Controllers;


class AnyController  extends \MapDapRest\Controller
{


    public function anyAction($request, $response, $tablename, $action_or_id, $args)
    {
 
      //
      if ($request->method=="GET") {  //table/users/all/info  {fields:[], filter:[ {field:name, oper:'like', value:'asd'} ], sortBy:'', sortDesc:'', itemsPerPage:100, page:1, parent_table:[name:users , id:999] }
         $id = (int)$action_or_id;

         $tableHandler = new TableHandler($this->APP);
         $reqFields = [];
         if (isset($request->params["fields"])) $reqFields = $request->params["fields"];

         $rows = $tableHandler->get($tablename, $id, $reqFields, $request->params);
         if (count($args)>0) {
            return $rows;
         }

         return isset($rows["rows"]) ? $rows["rows"] : $rows;
      }//---GET-----------------------------------
 

      //table/users/edit/1
      if ($request->method=="POST") {
         $action = $action_or_id;
         $id = $args[0];
         $rows = [];

         $tableHandler = new TableHandler($this->APP);
         
         if ($action=="add")    $rows = $tableHandler->add($tablename, $request->params);
         if ($action=="edit")   $rows = $tableHandler->edit($tablename, $id, $request->params);
         if ($action=="delete") $rows = $tableHandler->delete($tablename, $id);

         if ($action=="get")    {  //table/users/get/all/info  {fields:[], filter:[ {field:name, oper:'like', value:'asd'} ], sortBy:'', sortDesc:'', itemsPerPage:100, page:1, parent_table:[name:users , id:999] }
            $reqFields = [];
            if (isset($request->params["fields"])) $reqFields = $request->params["fields"];
            $rows = $tableHandler->get($tablename, $id, $reqFields, $request->params);
            if (!isset($args[1])) { $rows = $rows["rows"]; }
         }
         
         return $rows;
      }//---POST-----------------------------------


      //
      if ($request->method=="PUT") {
         $id = $action_or_id;
         $rows = [];

         $tableHandler = new TableHandler($this->APP);
         
         if ($id==0) {
            $rows = $tableHandler->add($tablename, $request->params);
         } else {
            $rows = $tableHandler->edit($tablename, $id, $request->params);
         }

         return $rows;
      }//---PUT-----------------------------------


      //
      if ($request->method=="DELETE") {
         $id = $args[0];
         $rows = [];

         $tableHandler = new TableHandler($this->APP);
        
         $rows = $tableHandler->delete($tablename, $id);
         
         return $rows;
      }//---DELETE-----------------------------------

    }//Action

}
