<?php
namespace MapDapRest\App\Table\Controllers;
//namespace App\Table\Controllers;


class AnyController  extends \MapDapRest\Controller
{

    /*  return [ error:0, message:'', rows:[] ] */
    public function anyAction($request, $response, $tablename, $action_or_id, $args)
    {
      $tablename = strtolower($tablename);
      $tableHandler = new TableHandler($this->APP);

      //table/users/1?info=true
      if ($request->method=="GET") {
         $rows = $tableHandler->get($tablename, $action_or_id, $request);
      }//---GET-----------------------------------

      //table/users/get/1   //table/users/add  //table/users/edit/1   //table/users/delete/1
      if ($request->method=="POST") {
         $action = $action_or_id;
         if (strlen($action) == 0) $action = "add";
         $id = (isset($args[0])? $args[0] : "");
         $rows = ["error"=>9, "message"=>"action not found"];

         if ($action=="add")    $rows = $tableHandler->add($tablename, $request);
         if ($action=="edit")   $rows = $tableHandler->edit($tablename, $id, $request);
         if ($action=="delete") $rows = $tableHandler->delete($tablename, $id);

         if ($action=="get")    {  //table/users/get/1    {fields:['id','name'], filter:[ {field:name, oper:'like', value:'asd'} ], sort:['-name'], itemsPerPage:100, page:1, parent_table:[name:users , id:999] }
            $rows = $tableHandler->get($tablename, $id, $request);
         }
      }//---POST-----------------------------------

      if ($request->method=="PUT") {
          $rows = $tableHandler->edit($tablename, $action_or_id, $request);
      }

      if ($request->method=="DELETE") {
          $rows = $tableHandler->delete($tablename, $action_or_id);
      }

      return $rows;
    }//Action

}
