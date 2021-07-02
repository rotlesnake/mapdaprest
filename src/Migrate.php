<?php
namespace MapDapRest;


class Migrate {


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



    public static function migrate() {
        $rez="";

        $APP = App::getInstance();
        $all_models = [];

        $extDir = __DIR__."/App/";
        $all_models = static::receiveAllModels($extDir, $all_models);
        $rez .= static::doMigrate($all_models);


        $extDir = $APP->APP_PATH;
        $all_models = static::receiveAllModels($extDir, $all_models);
        $rez .= static::doMigrate($all_models);


        if (!file_exists(__DIR__."/cache")) { mkdir(__DIR__."/cache", 0777); };
        file_put_contents(Utils::getFilenameModels(), json_encode($all_models));

        return $rez;
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
                 
                 if ($APP->db_settings && isset($APP->db_settings['engine'])) $table->engine = $APP->db_settings['engine'];
               });
            }//---hasTable---

            //Создаем новые поля
            $APP->DB->schema()->table($tableInfo["table"], function($table) use($APP, $tableInfo, &$rez) {

               foreach ($tableInfo["columns"] as $x=>$y) {
                 //колонка уже есть тогда ничего не делаем
                 if (in_array($x, ["id","created_at","updated_at","created_by_user"])) { continue; }
                 if ($APP->DB->schema()->hasColumn($tableInfo["table"],$x)) { continue; }
                 if (isset($y["virtual"]) && $y["virtual"]) { continue; }
                 //Если ссылка на таблицу но таблицы нет то откладываем это действие на потом
                 if ($y["type"]=="linkTable" && !$APP->DB->schema()->hasTable($y["table"])) { $rez .= " - Поле не создано требуется повторная миграция (<font color=red>".$x."</font>) <br>\r\n";  continue; }

                 $rez .= " - Добавляем поле (".$x.") <br>\r\n";

                 if (in_array($y["type"], ["string", "password", "masked", "color"]))  { $fld = $table->string($x)->nullable(); }
                 if (in_array($y["type"], ["integer", "checkBox"])) { $fld = $table->integer($x)->nullable(); }
                 if (in_array($y["type"], ["bigInteger"])) { $fld = $table->bigInteger($x)->nullable(); }
                 if (in_array($y["type"], ["select"])) {
                      if (!isset($y["multiple"]))  { $y["multiple"] = false; }
                      if ($y["multiple"]) {
                         $fld = $table->text($x)->nullable();
                      } else {
                         $fld = $table->integer($x)->nullable();
                      }
                 }
                 if (in_array($y["type"], ["linkTable"])) { 
                      if (!isset($y["multiple"]))  { $y["multiple"] = false; }
                      if ($y["multiple"]) {
                         $fld = $table->text($x)->nullable();
                      } else {
                         $fld = $table->integer($x)->unsigned()->index()->nullable();
                      }
                 }
                 if (in_array($y["type"], ["float"]))   { $fld = $table->decimal($x, 15,2)->nullable(); }
                 if (in_array($y["type"], ["double"]))  { $fld = $table->double($x)->nullable(); }
                 if (in_array($y["type"], ["text", "images", "files", "html"]))  { $fld = $table->longText($x)->nullable(); }
                 if (in_array($y["type"], ["date"]))      { $fld = $table->date($x)->nullable(); }
                 if (in_array($y["type"], ["time"]))      { $fld = $table->time($x,0)->nullable(); }
                 if (in_array($y["type"], ["dateTime"]))  { $fld = $table->dateTime($x,0)->nullable(); }
                 if (in_array($y["type"], ["dateTimeTz"])) { $fld = $table->dateTimeTz($x,0)->nullable(); }
                 if (in_array($y["type"], ["timestamp"]))  { $fld = $table->timestamp($x,0); }

                 if (isset($y["default"])) $fld->default($y["default"]);
                 if (isset($y["unsigned"])) $fld->unsigned();
                 if (isset($y["index"])) { 
                   if ($y["index"]=="index") { $fld->index(); }
                   if ($y["index"]=="unique") { $fld->unique(); }
                 }

                 if ($APP->DB->schema()->hasColumn($tableInfo["table"],$x)) { $fld->change(); }
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
