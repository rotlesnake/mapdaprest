<?php
namespace MapDapRest;

class AppMicro
{

    private static $instance = null;
    
    public $DB;
    public $SERVER;
    public $ROOT_PATH;
    public $APP_PATH;
    public $ROOT_URL;
    public $FULL_URL;
    public $db_settings=null;

    public $site_folder;
    public $app_folder;
    public $app_class;
    public $auth = null;
    
    public $request;
    public $response;
    public $models = [];
    
    
    public function __construct($ROOT_PATH, $ROOT_URL="/", $app_folder="App", $app_class="App", $site_folder=null)
    {
        $this->SERVER = $_SERVER["REQUEST_SCHEME"]."://".$_SERVER["SERVER_NAME"];
        $this->ROOT_PATH = str_replace("/", DIRECTORY_SEPARATOR, realpath($ROOT_PATH)."/");
        $this->APP_PATH = $ROOT_PATH.$app_folder."/";
        $this->ROOT_URL = $ROOT_URL;
        $this->FULL_URL = $this->SERVER.$ROOT_URL;
        $this->app_folder = $app_folder;
        $this->app_class = $app_class;
        $this->site_folder = $site_folder;
        $this->models = Utils::loadModels();

        static::$instance = $this;

        $this->initDB();
        $this->auth = new Auth();
        $this->auth->setUser(1);
    }
   
 
    public static function getInstance() {
        return static::$instance;
    }


    public function initDB() {
        $dbjson = $this->ROOT_PATH."App/database.json";
        $dbhash = $this->ROOT_PATH."App/database.hash";
        $dbfile = $this->ROOT_PATH."App/database.db";

        if (!file_exists($dbfile)) file_put_contents($dbfile, '');

        $this->DB = new \Illuminate\Database\Capsule\Manager();
        $this->DB->addConnection(['driver'=>'sqlite', 'database'=>$dbfile, 'prefix'=>'']);
        $this->DB->setEventDispatcher(new \Illuminate\Events\Dispatcher(new \Illuminate\Container\Container));
        $this->DB->setAsGlobal();
        $this->DB->bootEloquent();

        
        try {
           $this->DB->connection()->getPdo();

           if (file_exists($dbjson)) {
              $hash1 = "";
              $hash2 = md5_file($dbjson);
              if (file_exists($dbhash)) {$hash1 = file_get_contents($dbhash);}

              if (!$this->models || $hash1!=$hash2) {
                  $this->generateModels();
                  $this->models = Migrate::migrate();
                  $this->models = Utils::loadModels();
              }
           }
        } catch (\Exception $e) {
           echo "Could not connect to the database. Please check your configuration. Error:";
           echo "<pre>";
           echo $e;
           echo "</pre>";
           die();
        }

    }




    public function generateModels() {
        $dbjson = $this->ROOT_PATH."App/database.json";
        $dbhash = $this->ROOT_PATH."App/database.hash";
        file_put_contents($dbhash, md5_file($dbjson));

        if (!file_exists($this->APP_PATH."Site")) {
            mkdir($this->APP_PATH."Site", 0777);
            mkdir($this->APP_PATH."Site/Controllers", 0777);
            $template = file_get_contents(__DIR__."/stub/controller.stub");
            $template = str_replace("<%MODULE%>", "Site", $template);
            file_put_contents($this->APP_PATH."Site/Controllers/IndexController.php", $template);
        }

        $models = json_decode( file_get_contents($dbjson), true);
        foreach ($models as $momo=>$fields) {
            $module = ucfirst(explode("/", $momo)[0]);
            $model = ucfirst(explode("/", $momo)[1]);
            if (!file_exists($this->APP_PATH.$module)) {
               mkdir($this->APP_PATH.$module, 0777);
               mkdir($this->APP_PATH.$module."/Models", 0777);
               mkdir($this->APP_PATH.$module."/Controllers", 0777);
               $template = file_get_contents(__DIR__."/stub/controller.stub");
               $template = str_replace("<%MODULE%>", $module, $template);
               file_put_contents($this->APP_PATH.$module."/Controllers/IndexController.php", $template);
            }

            $template = file_get_contents(__DIR__."/stub/controller.stub");
            $template = str_replace("<%MODULE%>", $module, $template);
            file_put_contents($this->APP_PATH.$module."/Controllers/IndexController.php", $template);

            $template = file_get_contents(__DIR__."/stub/model.stub");
            $str="";
            $str .= "\"id\" => [\"type\"=>\"integer\", \"label\"=>\"id\", \"read\"=>\$acc_all, \"add\"=>\$acc_all, \"edit\"=>\$acc_all ],\r\n";
            $str .= "\"created_at\" => [\"type\"=>\"timestamp\", \"label\"=>\"Дата создания\", \"read\"=>\$acc_all, \"add\"=>\$acc_all, \"edit\"=>\$acc_all ],\r\n";
            $str .= "\"updated_at\" => [\"type\"=>\"timestamp\", \"label\"=>\"Дата изменения\", \"read\"=>\$acc_all, \"add\"=>\$acc_all, \"edit\"=>\$acc_all ],\r\n";
            $str .= "\"created_by_user\" => [\"type\"=>\"linkTable\", \"label\"=>\"Создано пользователем\", \"table\"=>\"users\", \"field\"=>\"login\", \"read\"=>\$acc_all, \"add\"=>\$acc_all, \"edit\"=>\$acc_all ],\r\n\r\n";

            foreach ($fields as $fn=>$opts) {
              $str .= "\"".$fn."\" => [";
              foreach ($opts as $k=>$v) {
                 $str .= "\"".$k."\"=>\"".$v."\", ";
              }
              $str .= " \"read\"=>\$acc_all, \"add\"=>\$acc_all, \"edit\"=>\$acc_all ";
              $str .= "], \r\n";
            }//fields

            $template = str_replace("<%MODULE%>", $module, $template);
            $template = str_replace("<%MODEL%>", $model, $template);
            $template = str_replace("<%TABLE%>", strtolower($model), $template);
            $template = str_replace("<%FIELDS%>", $str, $template);
            file_put_contents($this->APP_PATH.$module."/Models/".$model.".php", $template);
        }//models
    }


  

 
    public function run($methods=["GET", "POST", "PUT", "DELETE"]) {

        $this->request = new Request($this);
        $this->response = new Response($this);

        $httpMethod = $_SERVER['REQUEST_METHOD'];
        if (!in_array($httpMethod, $methods)) {
            echo "REQUEST METHOD NOT ALLOWED";
            return false;
        }

        $uri = $_SERVER['REQUEST_URI'];
        if (false !== $pos = strpos($uri, '?')) { $uri = substr($uri, 0, $pos); }
        $uri = rawurldecode($uri);
        $uri = substr($uri, strlen($this->ROOT_URL));
        $uri = trim($uri, '/');

        $args = [];
        $routes = explode("/",$uri);

        foreach ($routes as $k=>$v) {
            if (strlen($v)==0) continue;
            if ($k==0) $args['module'] = Utils::convUrlToModel($v);
            if ($k==1) $args['controller'] = Utils::convUrlToModel($v);
            if ($k==2) $args['action'] = Utils::convUrlToMethod($v);
            if ($k>=3) $args['params'][] = $v;
        }

        //Если путь пустой то перенаправляем в папку сайта или в модуль Site\IndexController\indexAction
        if (count($args)==0) { 
            if ($this->site_folder) { 
               $this->response->redirect($this->site_folder); 
               return true;  
            } else {
               $args['module'] = "Site";
            }
        }
   
           $module     = ucfirst($args['module']);
           $controller = (isset($args['controller']) ? ucfirst($args['controller'])."Controller" : "IndexController");
           $action     = (isset($args['action'])     ? $args['action']."Action" : "indexAction");
           $params     = (isset($args['params'])     ? $args['params'] : []);

           if (!isset($args['controller'])) { $args['controller']=""; }
           if (!isset($args['action'])) { $args['action']=""; }
 
           //Ищем контроллер в папке приложения
           $className = "\\".$this->app_class."\\".$module."\\Controllers\\".$controller;
           if (!class_exists($className)) { 
              //Если не нашли то ищем AnyController в папке приложения
              $anyClassName = "\\".$this->app_class."\\".$module."\\Controllers\\AnyController";
              if (class_exists($anyClassName)) { $className = $anyClassName; }
           }

           //Контроллера нет, тогда ищем в \MapDapRest\App\...
           if (!class_exists($className)) { 
              $localClassName = "\\MapDapRest\\App\\".$module."\\Controllers\\".$controller;
              if (class_exists($localClassName)) { 
                 $className = $localClassName; 
              } else {
                 //Если не нашли то ищем AnyController
                 $anyClassName = "\\MapDapRest\\App\\".$module."\\Controllers\\AnyController";
                 if (class_exists($anyClassName)) { $className = $anyClassName; }
              }
           }
          
           //Контроллера нигде нет выдаем ошибку 404
           if (!class_exists($className)) { 
              $this->response->setResponseCode(404);
              $this->response->setError(5, $className);
              $this->response->send();
              return false;
           }
 
           //Создаем класс найденного контроллера, даем возможность контроллеру решить что делать дальше с этими данными и этим пользователем
           $controllerClass = new $className($this, $this->request, $this->response, $params);

           //Проверяем наличие метода в этом контроллере
           $body = [];
           if (method_exists($controllerClass, $action)) {
             //Вызываем метод контроллера и возвращаем результат
             $body = $controllerClass->$action($this->request, $this->response, $params);
           } else {
             //Метода нет тогда вызываем метод anyAction()
             $body = $controllerClass->anyAction($this->request, $this->response, $args['controller'], $args['action'], $params);
           }
 
           //Ответ контроллева обрабатываем и отдаем клиенту
           if ($body) {
              $this->response->setBody($body);
           }
           $this->response->send();

           return true;
    }//run




    public function __get($property)
    {
        if ($this->{$property}) {
            return $this->{$property};
        }
    }


    public function hasModel($tablename)
    {
        return isset($this->models[$tablename]);
    }
    public function getModel($tablename)
    {
        return $this->models[$tablename];
    }

}
