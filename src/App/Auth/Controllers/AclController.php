<?php
namespace MapDapRest\App\Auth\Controllers;


class AclController extends \MapDapRest\Controller
{

    public function updateAction($request, $response, $params) {
        if ($this->APP->auth->user->role_id != 1) $response->sendError(["message"=>"access denied"], 401);
        if (!$request->hasParam("user_id")) $response->sendError(["message"=>"no params"], 500);
        if (!$request->hasParam("acl")) $response->sendError(["message"=>"no params"], 500);

        $user_id = $request->getParam("user_id");
        $acl = $request->getParam("acl");
        $this->APP->DB::table("user_access")->where("user_id",$user_id)->whereNotIn("app_access_id", $acl)->delete();
        $old_acl = $this->APP->DB::table("user_access")->where("user_id",$user_id)->whereIn("app_access_id", $acl)->select("app_access_id")->get()->toArray();
        foreach($acl as $id) {
            if (!in_array($id, $old_acl)) {
                $this->APP->DB::table("user_access")->insert(["user_id"=>$user_id, "app_access_id"=>$id]);
            }
        }
        $response->sendSuccess();
    }

}