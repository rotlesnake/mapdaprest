<?php

namespace MapDapRest\Controllers\Uploads;


class ImageController  extends \MapDapRest\Controller
{

    public $APP;


    public function __construct($app, $request, $response, $args)
    {
        $this->APP = $app;
        //$this->APP->auth->setUser(1);
    }


    public function anyAction($request, $response, $table, $filename)
    {
      if (ob_get_level()) { ob_end_clean(); }
      $file = ROOT_PATH."uploads/".$table."/".$filename[0];
      if (!file_exists($file)) { return ""; }

      header('Content-Type: '.mime_content_type($file));
      header('Content-Length: ' . filesize($file));
      
      readfile($file);
      exit;
    }

}
