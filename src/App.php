<?php

namespace MapDapRest;

class App
{

    private static $instance = null;
    
    public $DB;
    public $SERVER;
    public $ROOT_PATH;
    public $APP_PATH;
    public $ROOT_URL;
    public $FULL_URL;
    public $db_settings;

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
 
        if (file_exists(__DIR__."/cache/models.json")) {
          $this->models = json_decode(file_get_contents(__DIR__."/cache/models.json"), true);
        } else {
          $this->models = false;
        }

        static::$instance = $this;
    }
   
 
    public static function getInstance() {
        return static::$instance;
    }


    public function initDB($settings) {
        $this->db_settings = $settings;
        $this->DB = new \Illuminate\Database\Capsule\Manager();
        $this->DB->addConnection($settings);
        $this->DB->setEventDispatcher(new \Illuminate\Events\Dispatcher(new \Illuminate\Container\Container));
        $this->DB->setAsGlobal();
        $this->DB->bootEloquent();

        try {
           $this->DB->connection()->getPdo();

           if (!$this->models) {
               $this->models = Migrate::migrate();
           }

        } catch (\Exception $e) {
           echo "Could not connect to the database. Please check your configuration. Error:";
           echo "<pre>";
           echo $e;
           echo "</pre>";
           die();
        }

    }
  

    public function setAuth($auth) {
        $this->auth = $auth;
    }
 

 
 
    public function run($methods=['GET','POST']) {

        $this->request = new Request($this);
        $this->response = new Response($this);

        if (!$this->auth) {
           $this->auth = new Auth();
        }

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
            if ($k==0) $args['module'] = $v;
            if ($k==1) $args['controller'] = $v;
            if ($k==2) $args['action'] = $v;
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
 
           //Пытаемся авторизоваться автоматически
           if (method_exists($this->auth, "autoLogin")) {
                $this->auth->autoLogin( $this->request );
           }

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

           //Контроллер создан. перед вызовом методов проверяем состояние авторизации
           if ((method_exists($this->auth, "isGuest") && $this->auth->isGuest()===false) || $controllerClass->requireAuth===false) {
              //Пользователь авторизован тогда идем дальше
           } else {
              //Если авторизации нет то выдаем ошибку 401
              $this->response->setResponseCode(401);
              $this->response->setError(1, "Пользователь не найден");
              if ($this->request->hasHeader('token')) { 
                $this->response->setError(3, "Токен просрочен либо не действителен");
              }
              $this->response->send();
              return false;
           }


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
