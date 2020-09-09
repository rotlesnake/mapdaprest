<?php

namespace MapDapRest\App\Uploads\Controllers;


class FileController extends \MapDapRest\Controller
{

    public $APP;


    public function __construct($app, $request, $response, $args)
    {
        $this->APP = $app;
    }


    public function anyAction($request, $response, $type, $table, $filename)
    {
      if (ob_get_level()) {ob_end_clean();}
      $file = ROOT_PATH."uploads/".$table."/".$filename[0];
      if (!file_exists($file)) { return ""; }

      header('Content-Description: File Transfer');
      header('Content-Type: application/octet-stream');
      header('Content-Disposition: attachment; filename=' . basename($file));
      header('Content-Transfer-Encoding: binary');
      header('Expires: 0');
      header('Cache-Control: must-revalidate');
      header('Pragma: public');
      header('Content-Length: ' . filesize($file));

      readfile($file);
      exit;
    }


}
