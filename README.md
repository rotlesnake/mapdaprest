# Install
```
composer install rotlesnake/mapdaprest

```


# Init micro app
### database sqllite
### application folder structure
```
+App
 |----Auth
 |    |------Controllers
 |    |      |--------------LoginController.php
 |    |------Models
 |           |--------------Users.php
 |----ModuleOne
 |    |------Controllers
 |    |      |--------------IndexController.php
 |    |------Models
 |           |--------------Items.php
 |
 |----database.json
 |----database.db
+www
 |--------index.html
+vendor

.htaccess
index.php
```

/.htaccess
```
RewriteEngine On
RedirectMatch 404 /App/

RewriteCond %{REQUEST_URI} /www/
RewriteRule ^(.*)$ $1 [L,QSA]

RewriteCond %{REQUEST_FILENAME} !-l
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [L,QSA]
```

/index.php
```
require("vendor/autoload.php");
$APP = new \MapDapRest\AppMicro(__DIR__);
$APP->run();
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
require("vendor/autoload.php");
define("ROOT_PATH",   str_replace("/", DIRECTORY_SEPARATOR, realpath(__DIR__)."/") );
define("APP_PATH",    str_replace("/", DIRECTORY_SEPARATOR, realpath(__DIR__)."/backend/") );
define("VENDOR_PATH", str_replace("/", DIRECTORY_SEPARATOR, realpath(__DIR__)."/vendor/") );

$ROOT_URL = str_replace("//", "/", dirname($_SERVER["SCRIPT_NAME"])."/");
if (!isset($_SERVER["REQUEST_SCHEME"])) $_SERVER["REQUEST_SCHEME"]="http";

define("ROOT_URL", $ROOT_URL );
define("FULL_URL", $_SERVER["REQUEST_SCHEME"]."://".$_SERVER["SERVER_NAME"].ROOT_URL);


$APP = new MapDapRest\App(ROOT_PATH, ROOT_URL, "App", "App", "www");
$settings = require(ROOT_PATH.'settings.php');
$APP->initDB($settings['database']);
$APP->setAuth( new \App\Auth\Auth() );

ini_set('date.timezone', $settings['timezone']);
date_default_timezone_set($settings['timezone']);

$APP->run(["GET", "POST", "PUT", "DELETE"]);
```
