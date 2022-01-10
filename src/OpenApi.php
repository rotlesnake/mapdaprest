<?php
namespace MapDapRest;


class OpenApi {

    
    public static function generate() {
        $APP = App::getInstance();
        $AppDir = $APP->APP_PATH;

        error_reporting(0);
        $openapi = \OpenApi\Generator::scan([__DIR__."/App/", $AppDir]);
        $oajson = json_decode($openapi->toJson(), true);
        error_reporting(E_ALL);
        $oajson["openapi"] = "3.0.3";
        if (!isset($oajson["info"])) {
            $oajson["info"] = ["title"=>"empty app", "description"=>"empty app", "version"=>"1.0.0"];
        }
        if (!isset($oajson["servers"])) {
            $oajson["servers"][] = ["url"=>$APP->ROOT_URL, "description"=>"app"];
        }
        if (!isset($oajson["components"]["securitySchemes"])) {
            if (!isset($oajson["components"])) $oajson["components"] = [];
            $oajson["components"]["securitySchemes"] = ["bearerAuth"=>["type"=>"http", "scheme"=>"bearer"], "tokenAuth"=>["type"=>"apiKey", "name"=>"token", "in"=>"header"] ];
        }
        //paths
        if (!isset($oajson["paths"]["/auth/login"])) {
            if (!isset($oajson["paths"])) $oajson["paths"] = [];
            $oajson["paths"]["/auth/login"]["post"] = ["tags"=>["Auth"], "summary"=>"Вход в систему", "description"=>"Авторизация пользователя в системе", 
                                                       "parameters"=>[ ["in"=>"query", "name"=>"login", "required"=>true, "description"=>"Логин пользователя"], ["in"=>"query", "name"=>"password", "required"=>true, "description"=>"Пароль пользователя"] ],
                                                       "responses"=>[ "200"=>["description"=>"Успешная авторизация"], "401"=>["description"=>"Ошибка авторизации"] ],
                                                      ];
            $oajson["paths"]["/auth/logout"]["get"] = ["tags"=>["Auth"], "summary"=>"Выход из системы", "security"=>[["bearerAuth"=>[]], ["tokenAuth"=>[]]], "responses"=>[ "200"=>["description"=>"Успешный ответ"],]  ];
            $oajson["paths"]["/auth/me"]["get"] = ["tags"=>["Auth"], "summary"=>"Текущий пользователь", "description"=>"Получить данные текущего пользователя", "security"=>[["bearerAuth"=>[]], ["tokenAuth"=>[]]], "responses"=>[ "200"=>["description"=>"Успешный ответ"],]];
        }

        //components/schemas
        if (!isset($oajson["components"]["schemas"])) {
            if (!isset($oajson["components"])) $oajson["components"] = [];
            $oajson["components"]["schemas"] = [];
        }
        //components/parameters
        $oajson["components"]["schemas"]["tableField"] = ["title"=>"Описание поля", "type"=>"object", "properties"=>[
                                                                                                                      "type"=>["type"=>"string", "description"=>"Тип поля"],
                                                                                                                      "label"=>["type"=>"string","description"=>"Название поля"],
                                                                                                                      "read"=>["type"=>"array", "items"=>["type"=>"integer"] ],
                                                                                                                      "edit"=>["type"=>"array", "items"=>["type"=>"integer"] ],
                                                                                                                   ] ];
        $oajson["components"]["schemas"]["filterRows"] = ["title"=>"Фильтр записей в таблице", "type"=>"object", "properties"=>[
                                                                                                                                "field"=>["type"=>"string", "description"=>"Поле для фильтрации"],
                                                                                                                                "oper"=>["type"=>"string","description"=>"Операция сравнения  =, >, <, in, like"],
                                                                                                                                "value"=>["type"=>"string","description"=>"Значение поля"],
                                                                                                                               ] ];
        $oajson["components"]["schemas"]["tableInfo"] = ["title"=>"Подробная информация о таблице", "type"=>"object", "properties"=>[
                                                                                                                                "table"=>["type"=>"string", "description"=>"Имя таблицы"],
                                                                                                                                "name"=>["type"=>"string", "description"=>"Описание таблицы"],
                                                                                                                                "read"=>["type"=>"array", "items"=>["type"=>"integer"] ],
                                                                                                                                "edit"=>["type"=>"array", "items"=>["type"=>"integer"] ],
                                                                                                                                "delete"=>["type"=>"array", "items"=>["type"=>"integer"] ],
                                                                                                                                "columns"=>["type"=>"object", "properties"=>["fieldname"=>["\$ref"=>"#/components/schemas/tableField"]] ],
                                                                                                                               ] ];
        $oajson["components"]["parameters"] = [];
        $oajson["components"]["parameters"]["tableId"] = ["in"=>"path", "name"=>"id", "description"=>"id записи", "required"=>true, "schema"=>["type"=>"integer", "default"=>"1"] ];
        $oajson["components"]["parameters"]["tablePage"] = ["in"=>"query", "name"=>"page", "description"=>"Номер страницы", "required"=>false, "schema"=>["type"=>"integer", "default"=>"1"] ];
        $oajson["components"]["parameters"]["tableLimit"] = ["in"=>"query", "name"=>"limit", "description"=>"Количество записей на странице", "required"=>false, "schema"=>["type"=>"integer","default"=>"100"]];
        $oajson["components"]["parameters"]["tableSort"] = ["in"=>"query", "name"=>"sort", "description"=>"Сортировка по полю (name, -name)", "required"=>false, "schema"=>["type"=>"string","default"=>"id"]];
        $oajson["components"]["parameters"]["tableFieldsGet"] = ["in"=>"query", "name"=>"fields[]", "description"=>"Список полей (иначе выдаст все поля)", "required"=>false, "schema"=>["type"=>"array", "default"=>[], "items"=>["type"=>"string","default"=>"name"] ]];
        $oajson["components"]["parameters"]["tableFilterGet"] = ["in"=>"query", "name"=>"filter[]", "description"=>"Фильтрация записей", "required"=>false, "schema"=>["type"=>"array", "default"=>[], "items"=>["\$ref"=>"#/components/schemas/filterRows"]] ];
        $oajson["components"]["parameters"]["tableFieldsPost"] = ["in"=>"query", "name"=>"fields", "description"=>"Список полей (иначе выдаст все поля)", "required"=>false, "schema"=>["type"=>"array", "default"=>[], "items"=>["type"=>"string","default"=>"name"] ]];
        $oajson["components"]["parameters"]["tableFilterPost"] = ["in"=>"query", "name"=>"filter", "description"=>"Фильтрация записей", "required"=>false, "schema"=>["type"=>"array", "default"=>[], "items"=>["\$ref"=>"#/components/schemas/filterRows"]] ];

        $models = static::receiveAllModels($AppDir);
        foreach ($models as $tableName=>$class) {
            $tableInfo = $class::modelInfo();
            $oajson["components"]["schemas"][$tableName] = ["title"=>$tableName." (".$tableInfo["name"].")", "type"=>"object", "properties"=>[] ];
            foreach ($tableInfo["columns"] as $x=>$y) {
                $type = "string";
                if (in_array($y["type"], ["integer", "checkBox", "select", "linkTable", "select"])) $type = "integer";
                if (in_array($y["type"], ["float", "decimal", "double"])) $type = "decimal";
                if ($y["type"]=="linkTable" && isset($y["multiple"]) && $y["multiple"]) $type = "integer";
                if ($y["type"]=="select" && isset($y["multiple"]) && $y["multiple"]) $type = "integer";
                $format = $y["type"];

                $oajson["components"]["schemas"][$tableName]["properties"][$x] = ["type"=>$type, "format"=>$format, "description"=>$y["label"] ];
                if (count($y["edit"])==0) $oajson["components"]["schemas"][$tableName]["properties"][$x]["readOnly"] = true;
                if ($y["type"]=="select") $oajson["components"]["schemas"][$tableName]["properties"][$x]["items"] = $y["items"];
            }

            $oajson["paths"]["/db-query/".$tableName."/info"]["get"] = ["tags"=>["Table/".$tableName.""], "summary"=>"Информация о таблице", "description"=>"Получить подробную информацию о таблице", "security"=>[["bearerAuth"=>[]], ["tokenAuth"=>[]]], 
                                                       "responses"=>[ "200"=>["description"=>"Успешный ответ", "content"=>["application/json"=>["schema"=>["\$ref"=>"#/components/schemas/tableInfo"]]] ], 
                                                                      "401"=>["description"=>"Ошибка авторизации"] ],
                                                      ];
            $oajson["paths"]["/db-query/".$tableName.""]["get"] = ["tags"=>["Table/".$tableName.""], "summary"=>"Получить список записей", "description"=>"Получить список всех записей в таблице, с пагинацией", "security"=>[["bearerAuth"=>[]], ["tokenAuth"=>[]]], 
                                                       "parameters"=>[ ["\$ref"=>"#/components/parameters/tablePage"],["\$ref"=>"#/components/parameters/tableLimit"],["\$ref"=>"#/components/parameters/tableSort"],["\$ref"=>"#/components/parameters/tableFieldsGet"],["\$ref"=>"#/components/parameters/tableFilterGet"] ],
                                                       "responses"=>[ "200"=>["description"=>"Успешный ответ", "content"=>["application/json"=>["schema"=>["type"=>"array", "items"=>["\$ref"=>"#/components/schemas/".$tableName]]]] ], 
                                                                      "401"=>["description"=>"Ошибка авторизации"] ],
                                                      ];
            $oajson["paths"]["/db-query/".$tableName."/{id}"]["get"] = ["tags"=>["Table/".$tableName.""], "summary"=>"Получить запись по id", "description"=>"Получить одну запись по id", "security"=>[["bearerAuth"=>[]], ["tokenAuth"=>[]]], 
                                                       "parameters"=>[ ["\$ref"=>"#/components/parameters/tableId"],["\$ref"=>"#/components/parameters/tableFieldsGet"] ],
                                                       "responses"=>[ "200"=>["description"=>"Успешный ответ", "content"=>["application/json"=>["schema"=>["\$ref"=>"#/components/schemas/".$tableName]]] ], 
                                                                      "401"=>["description"=>"Ошибка авторизации"] ],
                                                      ];
            $oajson["paths"]["/db-query/".$tableName.""]["post"] = ["tags"=>["Table/".$tableName.""], "summary"=>"Добавить новую запись", "description"=>"Добавить в таблицу новую запись", "security"=>[["bearerAuth"=>[]], ["tokenAuth"=>[]]], 
                                                       "requestBody"=>["required"=>true, "content"=>["application/json"=>["schema"=>["\$ref"=>"#/components/schemas/".$tableName]]] ],
                                                       "responses"=>[ "200"=>["description"=>"Данные новой записи", "content"=>["application/json"=>["schema"=>["\$ref"=>"#/components/schemas/".$tableName]]] ], 
                                                                      "401"=>["description"=>"Ошибка авторизации"] ],
                                                      ];
            $oajson["paths"]["/db-query/".$tableName."/{id}"]["put"] = ["tags"=>["Table/".$tableName.""], "summary"=>"Изменить запись по id", "description"=>"Изменить запись в таблице по id", "security"=>[["bearerAuth"=>[]], ["tokenAuth"=>[]]], 
                                                       "parameters"=>[ ["\$ref"=>"#/components/parameters/tableId"] ],
                                                       "requestBody"=>["required"=>true, "content"=>["application/json"=>["schema"=>["\$ref"=>"#/components/schemas/".$tableName]]] ],
                                                       "responses"=>[ "200"=>["description"=>"Данные измененной записи", "content"=>["application/json"=>["schema"=>["\$ref"=>"#/components/schemas/".$tableName]]] ], 
                                                                      "401"=>["description"=>"Ошибка авторизации"] ],
                                                      ];
            $oajson["paths"]["/db-query/".$tableName."/{id}"]["delete"] = ["tags"=>["Table/".$tableName.""], "summary"=>"Удалить запись по id", "description"=>"Удалить запись в таблице по id", "security"=>[["bearerAuth"=>[]], ["tokenAuth"=>[]]], 
                                                       "parameters"=>[ ["\$ref"=>"#/components/parameters/tableId"] ],
                                                       "responses"=>[ "200"=>["description"=>"Данные удаленной записи", "content"=>["application/json"=>["schema"=>["\$ref"=>"#/components/schemas/".$tableName]]] ], 
                                                                      "401"=>["description"=>"Ошибка авторизации"] ],
                                                      ];


        }//foreach models
        $oajson["components"]["schemas"]["tableColumns"] = $oajson["components"]["schemas"]["roles"];
        $oajson["components"]["schemas"]["tableColumns"]["title"] = "Описание полей таблицы";

        return $oajson;
    }//generate()


    //Сгенерировать список всех моделей в папках
    public static function receiveAllModels($extDir, $all_models=[]) {
        $php_parser = new PhpParser();
        if ($dh = opendir($extDir)) {
            while (($file = readdir($dh)) !== false) {
               if ($file != "." && $file != ".." && is_dir($extDir."/".$file) && is_dir($extDir."/".$file."/Models")) {
                   
                   $files = glob($extDir.$file."/Models/*.php");
                   foreach ($files as $model) {
                       $classes = $php_parser->extractPhpClasses($model);
                       $class = $classes[0];
                       if (!method_exists($class, "modelInfo")) {continue;}
                       $info = $class::modelInfo();
    
                       $all_models[$info["table"]] = $class;
                   }
               }
            }
            closedir($dh);
        }
        return $all_models;
    }


    
}//CLASS************************************
