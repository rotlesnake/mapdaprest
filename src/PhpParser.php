<?php
namespace MapDapRest;


class PhpParser
{
    private $classes = array();
    private $extends = array();
    private $implements = array();

    const STATE_CLASS_HEAD = 100001;
    const STATE_FUNCTION_HEAD = 100002;

    public function extractPhpClasses($path)
    {
        $ver = explode(".",PHP_VERSION);
        if ((int)$ver[0] == 7) {
            return $this->extractPhpClasses7($path);
        } else {
            return $this->extractPhpClasses8($path);
        }
    }

    public function extractPhpClasses7($path)
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

    public function extractPhpClasses8($path)
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
                for ($j=$i+1; $j<$count; $j++)
                {
                    if ($tokens[$j][0]==314) {
                        $namespace = "\\".$tokens[$j][1];
                        break;
                    }
                }
            }
            if ($tokens[$i][0]===T_CLASS)
            {
                for ($j=$i+1; $j<$count; $j++)
                    if ($tokens[$j][0]==311) {
                        $classes[] = $namespace."\\".$tokens[$i+2][1];
                        break;
                    }
            }
        }

        return $classes;
    }


    public function parsePhpFile($file)
    {
        $file = realpath($file);
        $tokens = token_get_all(file_get_contents($file));
        $classes = array();

        $si = NULL;
        $depth = 0;
        $mod = array();
        $doc = NULL;
        $state = NULL;
        $line = NULL;

        foreach ($tokens as $idx => &$token) {
          if (is_array($token)) {
            switch ($token[0]) {
              case T_DOC_COMMENT:
                $doc = $token[1];
                break;
              case T_PUBLIC:
              case T_PRIVATE:
              case T_STATIC:
              case T_ABSTRACT:
              case T_PROTECTED:
                $mod[] = $token[1];
                break;
              case T_CLASS:
              case T_FUNCTION:
                $state = $token[0];
                $line = $token[2];
                break;
              case T_EXTENDS:
              case T_IMPLEMENTS:
                switch ($state) {
                  case self::STATE_CLASS_HEAD:
                  case T_EXTENDS:
                    $state = $token[0];
                    break;
                }
                break;
              case T_STRING:
                switch ($state) {
                  case T_CLASS:
                    $state = self::STATE_CLASS_HEAD;
                    $si = $token[1];
                    $classes[] = array('name' => $token[1], 'modifiers' => $mod, 'line' => $line, 'doc' => $doc);
                    break;
                  case T_FUNCTION:
                    $state = self::STATE_FUNCTION_HEAD;
                    $clsc = count($classes);
                    if ($depth>0 && $clsc) {
                      $classes[$clsc-1]['functions'][$token[1]] = array('modifiers' => $mod, 'line' => $line, 'doc' => $doc);
                    }
                    break;
                  case T_IMPLEMENTS:
                  case T_EXTENDS:
                    $clsc = count($classes);
                    $classes[$clsc-1][$state==T_IMPLEMENTS ? 'implements' : 'extends'][] = $token[1];
                    break;
                }
                break;
            }
          }
          else {
            switch ($token) {
              case '{':
                $depth++;
                break;
              case '}':
                $depth--;
                break;
            }

            switch ($token) {
              case '{':
              case '}':
              case ';':
                $state = 0;
                $doc = NULL;
                $mod = array();
                break;
            }
          }
        }

        foreach ($classes as $class) {
          $class['file'] = $file;
          $this->classes[$class['name']] = $class;

          if (!empty($class['implements'])) {
            foreach ($class['implements'] as $name) {
              $this->implements[$name][] = $class['name'];
            }
          }

          if (!empty($class['extends'])) {
            foreach ($class['extends'] as $name) {
              $this->extends[$name][] = $class['name'];
            }
          }
        }

        return ["classes"=>$this->classes];
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