# Install
```
composer install rotlesnake/mapdaprest

```


# Init minimal app
/.htaccess
```
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-l
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [L,QSA]
```
/index.php
```
require("vendor/autoload.php");
$APP = new \MapDapRest\App(__DIR__);
$APP->initDB([
            'driver'    => 'mysql',
            'host'      => 'localhost',
            'port'      => '3306',
            'database'  => 'mydatabase',
            'username'  => 'root',
            'password'  => '',
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => 'prj_',
            'engine'    => 'InnoDB'
        ]);
$APP->run();
```


# 
# Init full app
```
index.php

```
