<?php namespace Core\Service;

class Registry
{
    protected static $data = [];
    protected static $cache = [];

    public static function set($key, $value)
    {
        if (self::hasCache($key) !== false) {
            return (self::$cache[$key] = $value);
        }

        $path = explode('.', $key);
        $last = array_pop($path);
        $target = &self::$data;

        if (count($path)) {
            foreach ($path as $p) {
                $target = &$target[$p];
            }

            self::setCache($key, $target[$last]);
        }

        return ($target[$last] = $value);
    }

    public static function setReference($key, &$value)
    {
        $path = explode('.', $key);
        $last = array_pop($path);
        $target = &self::$data;

        if (count($path)) {
            foreach ($path as $p) {
                $target = &$target[$p];
            }
            self::setCache($key, $target[$last]);
        }

        $target[$last] = &$value;

        return $target[$last];
    }

    public static function get($key, $default = null)
    {
        if (self::getCache($key, $target) === false) {
            $path = explode('.', $key);
            $target = &self::$data;

            if (($countPath = count($path))) {
                foreach ($path as $p) {
                    if (!is_array($target) || !array_key_exists($p, $target)) return $default;
                    $target = &$target[$p];
                }
            }

            if ($countPath > 1) self::setCache($key, $target);
        }

        return $target;
    }

    public static function delete($key)
    {
        $path = explode('.', $key);
        $last = array_pop($path);
        $target = &self::$data;

        if (count($path)) {
            foreach ($path as $p) {
                if (!is_array($target) || !array_key_exists($p, $target)) return false;
                $target = &$target[$p];
            }
        }

        unset($target[$last]);
        self::deleteCache($key);

        return true;
    }

    public static function pull($key, $default = null)
    {
        $get = self::get($key, $default);
        self::delete($key);

        return $get;
    }

    protected static function setCache($key, &$link)
    {
        self::$cache[$key] = &$link;
    }

    protected static function hasCache($key)
    {
        return array_key_exists($key, self::$cache);
    }

    protected static function getCache($key, &$target)
    {
        if (self::hasCache($key)) {
            $target = self::$cache[$key];

            return true;
        }

        return false;
    }

    protected static function deleteCache($key)
    {
        foreach (self::$cache as $cacheKey => $cacheLink) {
            if (mb_strpos($cacheKey, $key) === 0) {
                unset(self::$cache[$cacheKey]);
            }
        }
    }

    public static function debug()
    {
        dump(self::$data);
        dump(self::$cache);
    }
}