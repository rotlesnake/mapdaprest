<?php
namespace MapDapRest\App\Table\Controllers;
//namespace App\Table\Controllers;


class AnyController  extends \MapDapRest\Controller
{

    /*  return [ error:0, message:'', rows:[] ] */
    public function anyAction($request, $response, $tablename, $action_or_id, $args)
    {
      $tablename = strtolower($tablename);

      //table/users/1?info=true
      if ($request->method=="GET") {
         $tableHandler = new TableHandler($this->APP);

         $resp = $tableHandler->get($tablename, $action_or_id, $request);

         return $resp;
      }//---GET-----------------------------------
 

      //table/users/get/1   //table/users/add  //table/users/edit/1   //table/users/delete/1
      if ($request->method=="POST") {
         $action = $action_or_id;
         $id = (isset($args[0])? (int)$args[0] : 0);
         $rows = ["error"=>9, "message"=>"action not found"];

         $tableHandler = new TableHandler($this->APP);
         
         if ($action=="add")    $rows = $tableHandler->add($tablename, $request);
         if ($action=="edit")   $rows = $tableHandler->edit($tablename, $id, $request);
         if ($action=="delete") $rows = $tableHandler->delete($tablename, $id);

         if ($action=="get")    {  //table/users/get/1    {fields:['id','name'], filter:[ {field:name, oper:'like', value:'asd'} ], sort:['-name'], itemsPerPage:100, page:1, parent_table:[name:users , id:999] }
            $rows = $tableHandler->get($tablename, $id, $request);
            //if (!isset($args[1])) { $rows = $rows["rows"]; }
         }
         
         return $rows;
      }//---POST-----------------------------------


    }//Action

}
