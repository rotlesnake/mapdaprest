<?php
namespace MapDapRest\App\Auth\Events;


class Emits
{

    public static function userLogin($user) 
    {
        $APP = \MapDapRest\App::getInstance();
        $APP->emit("userLogin", $user);
    }

    public static function userLogout($user) 
    {
        $APP = \MapDapRest\App::getInstance();
        $APP->emit("userLogout", $user);
    }


    public static function userRegistered($user) 
    {
        $APP = \MapDapRest\App::getInstance();
        $APP->emit("userRegistered", $user);
    }


}