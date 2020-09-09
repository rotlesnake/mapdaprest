<?php
namespace MapDapRest;



class Utils {


    public static function getSlug($str, $asFile=false) {
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
            "'"=> "", "\""=> ""
        );
        if ($asFile) {
          $tr["."]=".";
        }
        return strtolower(strtr($str,$tr));
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
        if (strpos(substr($dt,0,10),'-')===true) { return $dt; }

        $out=substr($dt,6,4)."-".substr($dt,3,2)."-".substr($dt,0,2); 
        if ($withtime) $out.=substr($dt,10,9);
        return $out;
    }

    //Дату в формат даты dd.mm.yyyy
    public static function convDateToDate($dt, $withtime=true) {
        if (strlen($dt)<9) return '';
        if (strpos(substr($dt,0,10),'.')===true) { return $dt; }
        if (substr($dt,0,1)=="-") { $dt=substr($dt,1); }

        $out=substr($dt,8,2).".".substr($dt,5,2).".".substr($dt,0,4); 
        if ($withtime) $out.=substr($dt,10,9);
        return $out;
    }




}//CLASS************************************
