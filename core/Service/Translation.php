<?php namespace Core\Service;

class Translation
{
    protected static $dir = SRC_PATH.'/Locales';
    protected static $cache = [];

    public static function translit($str, $spaceSeparator = '-')
    {
        $translit=array(
            "А"=>"a","Б"=>"b","В"=>"v","Г"=>"g","Д"=>"d","Е"=>"e","Ё"=>"e","Ж"=>"zh","З"=>"z","И"=>"i","Й"=>"y","К"=>"k","Л"=>"l","М"=>"m","Н"=>"n","О"=>"o","П"=>"p","Р"=>"r","С"=>"s","Т"=>"t","У"=>"u","Ф"=>"f","Х"=>"h","Ц"=>"ts","Ч"=>"ch","Ш"=>"sh","Щ"=>"shch","Ъ"=>"","Ы"=>"y","Ь"=>"","Э"=>"e","Ю"=>"yu","Я"=>"ya",
            "а"=>"a","б"=>"b","в"=>"v","г"=>"g","д"=>"d","е"=>"e","ё"=>"e","ж"=>"zh","з"=>"z","и"=>"i","й"=>"y","к"=>"k","л"=>"l","м"=>"m","н"=>"n","о"=>"o","п"=>"p","р"=>"r","с"=>"s","т"=>"t","у"=>"u","ф"=>"f","х"=>"h","ц"=>"ts","ч"=>"ch","ш"=>"sh","щ"=>"shch","ъ"=>"","ы"=>"y","ь"=>"","э"=>"e","ю"=>"yu","я"=>"ya",
            "A"=>"a","B"=>"b","C"=>"c","D"=>"d","E"=>"e","F"=>"f","G"=>"g","H"=>"h","I"=>"i","J"=>"j","K"=>"k","L"=>"l","M"=>"m","N"=>"n","O"=>"o","P"=>"p","Q"=>"q","R"=>"r","S"=>"s","T"=>"t","U"=>"u","V"=>"v","W"=>"w","X"=>"x","Y"=>"y","Z"=>"z"
        );

        $result=strtr($str,$translit);
        $result=preg_replace("/[^a-zA-Z0-9_]/i", $spaceSeparator, $result);
        $result=preg_replace("/\-+/i","-",$result);
        $result=preg_replace("/(^\-)|(\-$)/i","",$result);

        return $result;
    }

    public function setDir($dir)
    {
        static::$dir = $dir;
    }

    public static function of($message, $context = null, $locale = null)
    {
        $locale = $locale ?: Locale::getLocale();


        //var_dump($locale);


        if (key_exists($locale, static::$cache)) {
            checkMessage:
            if (key_exists($message, static::$cache[($context ? $context.':' : '').$locale])) {
                return static::$cache[($context ? $context.':' : '').$locale][$message];
            }
        } else if (file_exists(($localeFile = static::$dir.DIRECTORY_SEPARATOR.($context ? $context.DIRECTORY_SEPARATOR : '').$locale.'.php'))) {
            static::$cache[($context ? $context.':' : '').$locale] = include $localeFile;
         //   var_dump(static::$cache);
            goto checkMessage;
        }

        if ($locale != Locale::getDefaultLocale()) {
            return static::of($message, $context, Locale::getDefaultLocale());
        }

        throw new \Exception('There is no translation for "'.($context ? $context.':' : '').$locale.': '.$message.'"');
    }
}