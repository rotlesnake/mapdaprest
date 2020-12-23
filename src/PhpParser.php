<?php

namespace MapDapRest;


class PhpParser
{
    public function extractPhpClasses($path)
    {
        $code = file_get_contents($path);
        $tokens = @token_get_all($code);
        $namespace = $class = $classLevel = $level = NULL;
        $classes = [];
        foreach ($tokens as $key=>$token) {
            switch (is_array($token) ? $token[0] : $token) {
                case T_NAMESPACE:
                    $namespace = ltrim($this->fetch($tokens, [T_STRING, T_NS_SEPARATOR]) . '\\', '\\');
                    break;
                case T_CLASS:
                case T_INTERFACE:
                    if ($name = $this->fetch($tokens, T_STRING)) {
                        $classes[] = $namespace . $name;
                    }
                    break;
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



    public function getMethods($class, $method)
    {
        $reflector = new \ReflectionClass("\\".$class);
        return $reflector->getMethods();
    }
    public function getComments($class, $method)
    {
        $reflector = new \ReflectionClass("\\".$class);
        $comment = $reflector->getMethod($method)->getDocComment();
        return str_replace(["/**", "**/", "*/", "\r\n"],["","","","<br>"], $comment);
    }


}//class