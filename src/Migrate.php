<?php
namespace MapDapRest;


class Migrate {


    //Сгенерировать список всех моделей в папках
    public static function receiveAllModels($extDir, $all_models=[], $onlyModules = [], $onlyTables = []) {
        $php_parser = new PhpParser();

        if ($dh = opendir($extDir)) {
            while (($file = readdir($dh)) !== false) {
               if ($file != "." && $file != ".." && is_dir($extDir."/".$file) && is_dir($extDir."/".$file."/Models")) {
                   if (count($onlyModules)>0 && !in_array($file, $onlyModules)) { continue; }
                   $files = glob($extDir.$file."/Models/*.php");
                   foreach ($files as $model) {
                       $classes = $php_parser->extractPhpClasses($model);
                       $class = $classes[0];
                       if (!method_exists($class, "modelInfo")) {continue;}
                       $info = $class::modelInfo();
                       if (count($onlyTables)>0 && !in_array($info["table"], $onlyTables)) { echo $info["table"]."\r\n"; continue; }
                       $all_models[$info["table"]] = $class;
                   }
               }
            }
        closedir($dh);
        }


        return $all_models;
    }


    public static function hasIndex($tableName, $indexName, $indexType) {
        $APP = App::getInstance();
        if (is_array($indexName)) $indexName = implode("_", $indexName);
        $indexName = $tableName."_".$indexName."_".$indexType;
        $indexesFound = $APP->DB->schema()->getIndexes($tableName);
        foreach($indexesFound as $ndx) {
            if ($ndx["name"]==$indexName) return true;
        }
        return false;
    }


    public static function migrate($onlyModules = [], $onlyTables = []) {
        $rez="";
        if (!is_array($onlyModules)) $onlyModules=[];
        if (!is_array($onlyTables)) $onlyTables=[];

        $APP = App::getInstance();
        $all_models = [];

        $extDir = __DIR__."/App/";
        $all_models = static::receiveAllModels($extDir, $all_models, $onlyModules, $onlyTables);
        $rez .= static::doMigrate($all_models);

        $extDir = $APP->APP_PATH;
        self::importAcl($extDir, $onlyModules, $onlyTables);

        $all_models = static::receiveAllModels($extDir, $all_models, $onlyModules, $onlyTables);

/*
echo "<pre>"; print_r($onlyModules); 
echo "<pre>"; print_r($onlyTables); 
echo "<pre>"; print_r($all_models); 
die();
*/
        $rez .= static::doMigrate($all_models);

        if (!file_exists(__DIR__."/cache")) { mkdir(__DIR__."/cache", 0777); };
        $all_models = [];
        $extDir = __DIR__."/App/";
        $all_models = static::receiveAllModels($extDir, $all_models);
        $extDir = $APP->APP_PATH;
        $all_models = static::receiveAllModels($extDir, $all_models);
        file_put_contents(Utils::getFilenameModels(), json_encode($all_models));

        return $rez;
    }

    public static function importAcl($extDir, $onlyModules = [], $onlyTables = []) {
        $APP = App::getInstance();
        if (!$APP->hasModel("user_access")) return;
        //$APP->DB::statement("truncate table supp_app_access_list");

        if ($dh = opendir($extDir)) {
            while (($file = readdir($dh)) !== false) {
               if ($file == "." || $file == ".." || !is_dir($extDir."/".$file)) continue;

               if (count($onlyModules)>0 && !in_array($file, $onlyModules)) continue;

               $module_id = self::addAcl(["module/App/$file" => "Модуль $file"]);
               if (file_exists($extDir."/".$file."/Settings.php")) {
                   $class = "\\App\\$file\\Settings";
                   if (property_exists($class,"acl")) self::addAcl($class::$acl, $module_id);
               }
               if (is_dir($extDir."/".$file."/Models")) {
                   $files = glob($extDir.$file."/Models/*.php");
                   foreach ($files as $model) {
                       $modelName = basename($model, ".php");
                       $class = "\\App\\$file\\Models\\".basename($model, ".php");
                       if (!method_exists($class, "modelInfo")) {continue;}
                       $info = $class::modelInfo();
                       if (count($onlyTables)>0 && !in_array($info["table"], $onlyTables)) continue;
                       if (isset($info["acl"])) {
                           $table_id = self::addAcl(["model/App/$file/Models/$modelName" => "Модель $file/$modelName"], $module_id);
                           self::addAcl($info["acl"], $table_id);
                       }
                   }
               }
            }
            closedir($dh);
        }
    }
    public static function addAcl($list, $parent_id=0) {
        $APP = App::getInstance();
        $id = 0;
        foreach($list as $key=>$name) {
            $old = $APP->DB::table("app_access_list")->where("slug",$key)->first();
            if ($old) {
                if ($name != $old->name) $APP->DB::table("app_access_list")->where("slug",$key)->update(["name"=>$name]);
                $id = $old->id;
            } else {
                $id = $APP->DB::table("app_access_list")->insertGetId(["parent_id"=>$parent_id, "slug"=>$key, "name"=>$name]);
            }
        }
        return $id;
    }
    
    //Выполнить миграцию
    public static function doMigrate($models) {
        $php_parser = new PhpParser();
        $APP = App::getInstance();
        $isSqlite = $APP->DB->connection()->getDriverName() == "sqlite";
        $rez="";
        
        foreach ($models as $tableName=>$class) {
            try {
            $tableInfo = $class::modelInfo();
            $table_created = false;
           
            $rez .= "Миграция  <b>".$class."</b> <br>\r\n";
            //если нет таблицы то создаем
            if (!$APP->DB->schema()->hasTable($tableInfo["table"])) {
               $table_created = true;
               $rez .= " - Создаем таблицу (<b>".$tableInfo["table"]."</b>) <br>\r\n";
               $APP->DB->schema()->create($tableInfo["table"], function($table) use($APP, $tableInfo){
                 $table->increments('id');
                 $table->integer('created_by_user')->unsigned()->nullable()->default(0);
                 $table->timestamps();
                 if (isset($tableInfo["columns"]["deleted_at"])) $table->softDeletes();
                 
                 if ($APP->db_settings && isset($APP->db_settings['engine'])) $table->engine = $APP->db_settings['engine'];
               });
            }//---hasTable---

            //Создаем новые поля
            $APP->DB->schema()->table($tableInfo["table"], function($table) use($APP, $tableInfo, &$rez) {

               foreach ($tableInfo["columns"] as $x=>$y) {
                 //колонка системная тогда ничего не делаем
                 if (in_array($x, ["id","created_at","updated_at","deleted_at","created_by_user"])) { continue; }
                 //колонка уже есть тогда проверяем тип, если тип не поменялся тогда ничего не делаем
                 $columnExists = false;
                 if ($APP->DB->schema()->hasColumn($tableInfo["table"],$x)) { 
                     $columnExists = true;
                     $fldType = $APP->DB::getSchemaBuilder()->getColumnType($tableInfo["table"], $x);
                     $fldType = strtolower($fldType);
                     if ($fldType == strtolower($y["type"])) continue; 
                     if (!isset($y["multiple"]))  { $y["multiple"] = false; }
                     if ($y["type"]=="select" && !$y["multiple"] && $fldType=="integer") continue; 
                     if ($y["type"]=="select" && $y["multiple"] && $fldType=="text") continue; 
                     if ($y["type"]=="selectText" && $fldType=="text") continue; 
                     if ($y["type"]=="linkTable" && !$y["multiple"] && $fldType=="integer") continue; 
                     if ($y["type"]=="linkTable" && $y["multiple"] && $fldType=="text") continue; 
                     if (in_array($y["type"], ["text", "images", "files", "html"]) && $fldType=="text") continue; 
                     if (in_array($y["type"], ["string", "password", "masked", "color"]) && $fldType=="string") continue; 
                     if (in_array($y["type"], ["integer", "checkBox", "checkBoxText"]) && $fldType=="integer") continue; 
                     if ($y["type"]=="float" && $fldType=="decimal") continue; 
                     if ($y["type"]=="double" && $fldType=="float") continue; 
                     if (in_array($y["type"], ["json"])) continue; 
                 }
                 if (isset($y["virtual"]) && $y["virtual"]) { continue; }

                 $rez .= " - Добавляем поле (".$x.") <br>\r\n";

                 if (in_array($y["type"], ["string", "password", "masked", "color"]))  { $fld = $table->string($x)->nullable(); }
                 if (in_array($y["type"], ["integer", "checkBox", "checkBoxText"])) { $fld = $table->integer($x)->nullable(); }
                 if (in_array($y["type"], ["bigInteger"])) { $fld = $table->bigInteger($x)->nullable(); }
                 if (in_array($y["type"], ["select"])) {
                      if (!isset($y["multiple"]))  { $y["multiple"] = false; }
                      if ($y["multiple"]) {
                         $fld = $table->longText($x)->nullable();
                      } else {
                         $fld = $table->integer($x)->nullable();
                      }
                 }
                 if (in_array($y["type"], ["linkTable"])) { 
                      if (!isset($y["multiple"]))  { $y["multiple"] = false; }
                      if ($y["multiple"]) {
                         $fld = $table->text($x)->nullable();
                      } else {
                         $fld = $table->integer($x)->unsigned()->nullable();
                         if (!self::hasIndex($tableInfo["table"], $x, "index")) $fld->index();
                      }
                 }
                 if (in_array($y["type"], ["float"]))   { $fld = $table->decimal($x, 15,2)->nullable(); }
                 if (in_array($y["type"], ["double"]))  { $fld = $table->double($x)->nullable(); }
                 if (in_array($y["type"], ["text", "images", "files", "html", "selectText"]))  { $fld = $table->longText($x)->nullable(); }
                 if ($y["type"]=="json")  { if ($APP->jsonAsText) { $fld = $table->longText($x)->nullable(); } else { $fld = $table->json($x)->nullable(); } }
                 if (in_array($y["type"], ["date"]))      { $fld = $table->date($x)->nullable(); }
                 if (in_array($y["type"], ["time"]))      { $fld = $table->time($x,0)->nullable(); }
                 if (in_array($y["type"], ["dateTime"]))  { $fld = $table->dateTime($x,0)->nullable(); }
                 if (in_array($y["type"], ["dateTimeTz"])) { $fld = $table->dateTimeTz($x,0)->nullable(); }
                 if (in_array($y["type"], ["timestamp"]))  { $fld = $table->timestamp($x,0)->nullable(); }

                 if (isset($y["default"])) $fld->default($y["default"]);
                 if (isset($y["unsigned"])) $fld->unsigned();
                 if (isset($y["index"])) { 
                   if ($y["index"]=="index" && !self::hasIndex($tableInfo["table"], $x, "index")) { $fld->index(); }
                   if ($y["index"]=="unique" && !self::hasIndex($tableInfo["table"], $x, "unique")) { $fld->unique(); }
                 }

                 //Создаем constrains для linkTable но Если ссылка на таблицу но таблицы нет то откладываем это действие на потом
                 //if ($y["type"]=="linkTable" && !$APP->DB->schema()->hasTable($y["table"])) { $rez .= " - Поле не создано требуется повторная миграция (<font color=red>".$x."</font>) <br>\r\n";  continue; }

                 if ($columnExists) { $fld->change(); $rez .= " - Модифицируем поле (".$x.") <br>\r\n"; }
               }//foreach
            });

            $rez .= " ---------------------------- <br>\r\n";
            //if ($table_created && isset($tableInfo["seeds"])) { 
            if ($class::count() == 0 && isset($tableInfo["seeds"]) && count($tableInfo["seeds"]) > 0) {
               $class::insert( $tableInfo["seeds"] ); 
               $rez .= "Таблица (<b>".$tableInfo["table"]."</b>) пустая, засееваем её семенами...<br>\r\n"; 
            }
            $rez .= "<hr>\r\n\r\n";

 
            } catch (Exception $e) { $rez .= "Ошибка миграции -> ".$class."<br>\r\n";  $rez .= "<font color=red>".$e->getMessage()."</font><hr>\r\n\r\n"; }
 
        }//foreach models

        //Создаем foreign ключи
        foreach ($models as $tableName=>$class) {
            $tableInfo = $class::modelInfo();
            $APP->DB->schema()->table($tableInfo["table"], function($table) use($APP, $tableInfo, &$rez) {
                foreach ($tableInfo["columns"] as $x=>$y) {
                    try {
                        if (in_array($y["type"], ["linkTable"])) {
                             if (!isset($y["multiple"]))  { $y["multiple"] = false; }
                             if ($y["multiple"]) {
                             } else {
                                //$table->foreign($x)->references('id')->on($y["table"]);
                             }
                        }//linkTable
                    } catch (Exception $e) { echo "Ошибка миграции -> ".$class."<br>\r\n";  echo "<font color=red>".$e->getMessage()."</font><hr>\r\n\r\n"; }
                }//foreach
            });
        }//foreach models

        return $rez;
    }//migrate()


    
}//CLASS************************************
