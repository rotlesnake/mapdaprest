<?php
namespace MapDapRest\App\DBQuery\Controllers;
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
         
         $result = $tableHandler->get($tablename, (int)$id, $request);

         return $result;
      }//---GET-----------------------------------
 

      //POST - is INSERT
      if ($request->method=="POST") {
         $tableHandler = new TableHandler($this->APP);
         
         $rows = $tableHandler->add($tablename, $request);
         
         return $rows;
      }//---POST-----------------------------------

      
      //PUT - is UPDATE
      if ($request->method=="PUT") {
         $tableHandler = new TableHandler($this->APP);
         
         $rows = $tableHandler->edit($tablename, (int)$id, $request);

         return $rows;
      }//---PUT-----------------------------------


      //DELETE - is DELETE
      if ($request->method=="DELETE") {
         $tableHandler = new TableHandler($this->APP);
        
         $rows = $tableHandler->delete($tablename, (int)$id);
         
         return $rows;
      }//---DELETE-----------------------------------

    }//Action

}
