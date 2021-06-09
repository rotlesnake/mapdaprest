<?php
namespace MapDapRest;


class PhpParser
{
    public function extractPhpClasses($path)
    {
        $code = file_get_contents($path);
        $tokens = token_get_all($code);
        $namespace = $class = $classLevel = $level = NULL;
        $classes = [];
        $count = count($tokens);

        for($i = 0; $i < $count; $i ++)
        {
            if ($tokens[$i][0]===T_NAMESPACE)
            {
                for ($j=$i+1;$j<$count;++$j)
                {
                    if ($tokens[$j][0]===T_STRING)
                        $namespace.="\\".$tokens[$j][1];
                    elseif ($tokens[$j]==='{' or $tokens[$j]===';')
                        break;
                }
            }
            if ($tokens[$i][0]===T_CLASS)
            {
                for ($j=$i+1;$j<$count;++$j)
                    if ($tokens[$j]==='{')
                    {
                        $classes[]=$namespace."\\".$tokens[$i+2][1];
                    }
            }
        }

        return $classes;
    }

    private function fetch(&$tokens, $take)
    {
        $res = NULL;
        while ($token = current($tokens)) {
            list($token, $s) = is_array($token) ? $token : [$token, $token];
            if (in_array($token, (array) $take, TRUE)) {
                $res .= $s;
            } elseif (!in_array($token, [T_DOC_COMMENT, T_WHITESPACE, T_COMMENT], TRUE)) {
                break;
            }
            next($tokens);
        }
        return $res;
    }



    public function getMethods($class)
    {
        $reflector = new \ReflectionClass($class);
        return $reflector->getMethods();
    }

    public function getComments($class, $method)
    {
        $reflector = new \ReflectionClass($class);
        $comment = $reflector->getMethod($method)->getDocComment();
        return str_replace(["/**", "**/", "*/", "\r\n"],["","","","<br>"], $comment);
    }


}//class