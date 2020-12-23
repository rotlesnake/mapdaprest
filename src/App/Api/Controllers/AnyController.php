<?php
namespace MapDapRest\App\Api\Controllers;
//namespace App\Table\Controllers;


class AnyController  extends \MapDapRest\Controller
{


    public function anyAction($request, $response, $tablename, $id, $args)
    {
 
      //GET - is SELECT
      if ($request->method=="GET") {
         //?filter[status]=1,2,3
         //?filter[name]=archived
         //?fields[users]=title,text
         //?sort=-created_at,name
         //?page=1&limit=20

         $tableHandler = new TableHandler($this->APP);
         $fields = [];
         if ($request->hasParam("fields")) $fields = $request->getParam("fields");
         $filter = [];
         if ($request->hasParam("filter")) $filter = $request->getParam("filter");
         
         $result = $tableHandler->get($tablename, (int)$id, $request);

         return $result;
      }//---GET-----------------------------------
 

      //POST - is INSERT
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

      
      //PUT - is UPDATE
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


      //DELETE - is DELETE
      if ($request->method=="DELETE") {
         $id = $args[0];
         $rows = [];

         $tableHandler = new TableHandler($this->APP);
        
         $rows = $tableHandler->delete($tablename, $id);
         
         return $rows;
      }//---DELETE-----------------------------------

    }//Action

}
