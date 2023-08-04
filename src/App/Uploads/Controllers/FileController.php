<?php

namespace MapDapRest\App\Uploads\Controllers;


class FileController extends \MapDapRest\Controller
{

    public $requireAuth = false;
    public $APP;


    public function __construct($app, $request, $response, $args)
    {
        $this->APP = $app;
    }


    public function anyAction($request, $response, $type, $table, $filename)
    {
      if (ob_get_level()) {ob_end_clean();}
      $orig_filename = substr($filename[0], strpos($filename[0],"_file_")+6 );
      $file = ROOT_PATH."uploads/".$table."/".$filename[0];
      if (!file_exists($file)) { return ""; }

      header('Content-Description: File Transfer');
      header('Content-Type: application/octet-stream');
      header('Content-Disposition: attachment; filename=' . $orig_filename);
      header('Content-Transfer-Encoding: binary');
      header('Expires: 0');
      header('Cache-Control: must-revalidate');
      header('Pragma: public');
      header('Content-Length: ' . filesize($file));

      readfile($file);
      exit;
    }


}
