<?php
namespace MapDapRest\App\System\Controllers;


class LogsController extends \MapDapRest\Controller
{

    public function sysLogAction($request, $response, $args)
    {
        $DB = $this->APP->DB;
        $rows = $DB::table("sys_log")->select($DB::raw(
                                            "sys_log.action, 
                                             sys_log.created_at, 
                                             DATE_FORMAT(sys_log.created_at,'%d.%m.%Y %H:%i:%s') as created_at_text, 
                                             sys_log.user_id, 
                                             users.name as user_id_text, 
                                             sys_log.fields"))->
                    leftJoin("users", "users.id","=","sys_log.user_id")->
                    where("sys_log.table_name",$request->getParam("table"))->
                    where("sys_log.row_id", $request->getParam("row_id"))->
                    orderBy("sys_log.created_at")->
                    get();

        $fields_pre = ["id"=>null];
        foreach($rows as $key=>$row) {
            $row->fields = json_decode($row->fields ?? "");
            $rows[$key]->fields = $row->fields;
            $rows[$key]->fields_pre = $fields_pre;
            $fields_pre = $row->fields;
        }
        return $rows;
    }


}
