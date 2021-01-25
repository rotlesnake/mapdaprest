<?php

namespace MapDapRest\App\Uploads\Controllers;


class ImageController  extends \MapDapRest\Controller
{

    public $requireAuth = false;
    public $APP;


    public function __construct($app, $request, $response, $args)
    {
        $this->APP = $app;
    }


    public function anyAction($request, $response, $type, $table, $filename)
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
