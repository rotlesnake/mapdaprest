<?php

namespace MapDapRest;


class Controller
{
        
    public $APP;


    public function __construct($app, $request, $response, $args)
    {
        $this->APP = $app;
    }


}
