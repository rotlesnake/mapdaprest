# Install
```
composer install rotlesnake/mapdaprest

```



# 
# 
# Init rest full app
application folder structure
```
+App
 |----Auth
 |    |------Controllers
 |    |      |--------------LoginController.php
 |    |------Models
 |           |--------------Users.php
 |----ModuleOne
      |------Controllers
      |      |--------------IndexController.php
      |------Models
             |--------------Items.php
+uploads
 |--------users
          |--------photo.jpg
+www
 |--------index.html

+vendor

.htaccess
index.php
```

/.htaccess
```
Options All -Indexes
<IfModule mod_headers.c>
    Header add Access-Control-Allow-Origin "*"
    Header add Access-Control-Allow-Headers "authorization, token, origin, content-type"
    Header add Access-Control-Allow-Methods "GET, POST, PUT, DELETE, PATCH, OPTIONS"
</IfModule>

RewriteCond %{REQUEST_URI} /www/
RewriteRule ^(.*)$ $1 [L,QSA]

RedirectMatch 404 /App/
RedirectMatch 404 /uploads/
RedirectMatch 404 /vendor/

RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-l
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [L,QSA]
```

/index.php
```
define("ROOT_PATH",   str_replace("/", DIRECTORY_SEPARATOR, realpath(__DIR__)."/") );
define("APP_PATH",    str_replace("/", DIRECTORY_SEPARATOR, realpath(__DIR__)."/backend/") );
define("VENDOR_PATH", str_replace("/", DIRECTORY_SEPARATOR, realpath(__DIR__)."/vendor/") );
require(VENDOR_PATH."autoload.php");

$ROOT_URL = str_replace("//", "/", dirname($_SERVER["SCRIPT_NAME"])."/");
if (!isset($_SERVER["REQUEST_SCHEME"])) $_SERVER["REQUEST_SCHEME"]="http";

define("ROOT_URL", $ROOT_URL );
define("FULL_URL", $_SERVER["REQUEST_SCHEME"]."://".$_SERVER["SERVER_NAME"].ROOT_URL);


$APP = new MapDapRest\App(ROOT_PATH, ROOT_URL, "App", "App", "www");
$settings = [
        'debug'         => true,
        'timezone'      => 'Etc/GMT-3',

        'database' => [
            'driver'    => 'sqlite',
            'database'  => ROOT_PATH."App/database.db",
            'prefix'    => '',
        ],
//      --- or ---
        'database' => [
            'driver'    => 'mysql',
            'host'      => 'localhost',
            'port'      => '3306',
            'database'  => 'learn',
            'username'  => 'root',
            'password'  => '',
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => 'prj_',
            'engine'    => 'InnoDB', //'InnoDB' 'MyISAM'
        ],

];
$APP->initDB($settings['database']);
$APP->setAuth( new \App\Auth\Auth() );

ini_set('date.timezone', $settings['timezone']);
date_default_timezone_set($settings['timezone']);

$APP->run(["GET", "POST", "PUT", "DELETE"]);
```
