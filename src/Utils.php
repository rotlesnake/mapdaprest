<?php
namespace MapDapRest;


class Utils {


    public static function getSlug($str, $asFile=false, $withCase=false) {
        $tr = array(
            "А"=>"a","Б"=>"b","В"=>"v","Г"=>"g",
            "Д"=>"d","Е"=>"e","Ж"=>"j","З"=>"z","И"=>"i",
            "Й"=>"y","К"=>"k","Л"=>"l","М"=>"m","Н"=>"n",
            "О"=>"o","П"=>"p","Р"=>"r","С"=>"s","Т"=>"t",
            "У"=>"u","Ф"=>"f","Х"=>"h","Ц"=>"ts","Ч"=>"ch",
            "Ш"=>"sh","Щ"=>"sch","Ъ"=>"","Ы"=>"yi","Ь"=>"",
            "Э"=>"e","Ю"=>"yu","Я"=>"ya","а"=>"a","б"=>"b",
            "в"=>"v","г"=>"g","д"=>"d","е"=>"e","ж"=>"j",
            "з"=>"z","и"=>"i","й"=>"y","к"=>"k","л"=>"l",
            "м"=>"m","н"=>"n","о"=>"o","п"=>"p","р"=>"r",
            "с"=>"s","т"=>"t","у"=>"u","ф"=>"f","х"=>"h",
            "ц"=>"ts","ч"=>"ch","ш"=>"sh","щ"=>"sch","ъ"=>"y",
            "ы"=>"yi","ь"=>"","э"=>"e","ю"=>"yu","я"=>"ya", 
            " "=>"_", "."=>"_", ","=>"_", "/"=>"_", "\\"=>"_", 
            "'"=>"", "\""=>"", ":"=>"_", ";"=>"_"
        );
        if ($asFile) {
          $tr["."]=".";
        }
        if ($withCase) {
          return strtr($str,$tr);
        } else {
          return strtolower(strtr($str,$tr));
        }
    }
   
    public static function strPadRight($str,$len,$ch) { $str = substr($str,0,$len); return str_pad($str, $len, $ch, STR_PAD_RIGHT); } 
    public static function strPadLeft($str,$len,$ch) { $str = substr($str,0,$len); return str_pad($str, $len, $ch, STR_PAD_LEFT); } 
    public static function strPadBoth($str,$len,$ch) { $str = substr($str,0,$len); return str_pad($str, $len, $ch, STR_PAD_BOTH); } 
    public static function getStrAfter($after, $string){ if (!is_bool(strpos($string, $after))) return substr($string, strpos($string,$after)+strlen($after)); } 
    public static function getStrBefore($before, $string){ return substr($string, 0, strpos($string, $before)); } 
    public static function numberFormat($number, $delim=""){ return number_format((float)$number, 2, ".", $delim); } 
   

    public static function random_str($length, $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ')
    {
        $pieces = [];
        $max = mb_strlen($keyspace, '8bit') - 1;
        for ($i = 0; $i < $length; ++$i) {
            $pieces []= $keyspace[random_int(0, $max)];
        }
        return implode('', $pieces);
    }


    //массив преобразуем в объект
    public static function arrayToObject($arr) {
        return json_decode( json_encode($arr) );
    }
    //объект преобразуем в массив
    public static function objectToArray($obj) {
        return json_decode( json_encode($obj), true );
    }


    //массив преобразуем в строку
    public static function arrayToString($arr) {
        if (!is_array($arr)) return $arr;
        $list=trim( implode(",", $arr) );
        if (substr($list,0,1)==',') $list = substr($list,1);
        if (substr($list,-1,1)==',') $list = substr($list,0,-1);
        $list = str_replace(",,",",",$list);
        return $list;
    }

    //Дату в формат SQL yyyy-mm-dd
    public static function convDateToSQL($dt, $withtime=true) {
        if (strlen($dt)<9) return '';
        if (strpos(substr($dt,0,10),'-')!==false) { return $dt; }

        $out=substr($dt,6,4)."-".substr($dt,3,2)."-".substr($dt,0,2); 
        if ($withtime) $out.=substr($dt,10,9);
        return $out;
    }

    //Дату в формат даты dd.mm.yyyy
    public static function convDateToDate($dt, $withtime=true) {
        if (strlen($dt)<9) return '';
        if (strpos(substr($dt,0,10),'.')!==false) { return $dt; }
        if (substr($dt,0,1)=="-") { $dt=substr($dt,1); }

        $out=substr($dt,8,2).".".substr($dt,5,2).".".substr($dt,0,4); 
        if ($withtime) $out.=substr($dt,10,9);
        return $out;
    }

    public static function convUrlToModel($url) {
        $url = self::getSlug($url, false, true); 
        $urls = explode("-", $url); 
        $out = ""; 
        foreach ($urls as $x=>$y) {
           $out .= ucfirst($y);
        }
        return $out;
    }
    public static function convUrlToMethod($url) {
        $url = self::convUrlToModel($url); 
        return lcfirst($url);
    }
    public static function convUrlToTable($url) {
        $url = self::convUrlToMethod($url); 
        $url = preg_replace_callback('|([A-Z]+)|', function($word) { return "_".strtolower($word[0]); }, $url);
        return $url;
    }
    public static function convNameToUrl($name) {
        $name = lcfirst($name);
        $name = preg_replace_callback('|([A-Z]+)|', function($word) { return "-".strtolower($word[0]); }, $name);
        return $name;
    }


    public static function getFilenameModels() {
        $APP = App::getInstance();
        $root_path = ($APP ? $APP->ROOT_PATH : ROOT_PATH);
        $root_path = str_replace(["/","\\",":"],["_","_","_"], $root_path);
        $root_path = substr($root_path,1);
        $root_path = substr($root_path,0,-1);
        $filename = __DIR__."/cache/".$root_path.".json";
        return $filename;
    }
    public static function loadModels() {
        $filename = Utils::getFilenameModels();
        if (!file_exists($filename)) return false;
        return json_decode(file_get_contents($filename), true);
    }

    //Получить список всех ролей
    public static function getAllRoles($ids=true) {
        if (Utils::loadModels()===false) return [];

	$APP = \MapDapRest\App::getInstance();
	$roles = \App\Auth\Models\Roles::get();

        $arr = [];
        foreach ($roles as $row) {
            array_push($arr, $row->id);
        }

	if (!$ids) return $roles;
        return $arr;
    }


    //Получить список всех типов полей
    public static function getAllColumnTypes() {
        $arr = [];

        $arr[] = ["value"=>"string", "text"=>"Строка до 255 символов"];
        $arr[] = ["value"=>"password", "text"=>"Пароль зашифрованный"];
        $arr[] = ["value"=>"text", "text"=>"Текст"];
        $arr[] = ["value"=>"html", "text"=>"Html"];

        $arr[] = ["value"=>"integer", "text"=>"Целое число"];
        $arr[] = ["value"=>"bigInteger", "text"=>"Целое число большое"];
        $arr[] = ["value"=>"float", "text"=>"Сумма"];
        $arr[] = ["value"=>"double", "text"=>"Число с плавающей точкой"];

        $arr[] = ["value"=>"color", "text"=>"Выбор цвета"];

        $arr[] = ["value"=>"checkBox", "text"=>"Да/Нет"];
        $arr[] = ["value"=>"select", "text"=>"Выбор из списка"];
        $arr[] = ["value"=>"linkTable", "text"=>"Ссыка на таблицу"];

        $arr[] = ["value"=>"json", "text"=>"JSON данные"];
        $arr[] = ["value"=>"images", "text"=>"Картинки"];
        $arr[] = ["value"=>"files", "text"=>"Файлы"];

        $arr[] = ["value"=>"date", "text"=>"Дата"];
        $arr[] = ["value"=>"time", "text"=>"Время"];
        $arr[] = ["value"=>"dateTime", "text"=>"Дата и время"];
        $arr[] = ["value"=>"dateTimeTz", "text"=>"ДатаВремя с временной зоной"];
        $arr[] = ["value"=>"timestamp", "text"=>"Штамп времени"];

        return $arr;
    }

}//CLASS************************************
