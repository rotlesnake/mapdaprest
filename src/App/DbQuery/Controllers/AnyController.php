<?php
namespace MapDapRest\App\DbQuery\Controllers;


class AnyController  extends \MapDapRest\Controller
{


    public function anyAction($request, $response, $tablename, $id, $args)
    {
      $tablename = strtolower($tablename);
      $tableHandler = new TableHandler($this->APP);
      $rows = [];

      //GET - is SELECT
      if ($request->method=="GET") {
         //?filter[status]=1,2,3
         //?filter[name]=archived
         //?fields[users]=title,text
         //?fields=title,text
         //?sort=-created_at,name
         //?page=1&limit=20
         $rows = $tableHandler->get($tablename, $id, $request);
      }//---GET-----------------------------------
 

      //POST - is INSERT
      if ($request->method=="POST") {
         $rows = $tableHandler->add($tablename, $request);
      }//---POST-----------------------------------

      
      //PUT - is UPDATE
      if ($request->method=="PUT") {
         $rows = $tableHandler->edit($tablename, $id, $request);
      }//---PUT-----------------------------------


      //DELETE - is DELETE
      if ($request->method=="DELETE") {
         $rows = $tableHandler->delete($tablename, $id);
      }//---DELETE-----------------------------------

      return $rows;
    }//Action

}
