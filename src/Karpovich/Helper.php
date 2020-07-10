<?php


namespace Karpovich;

use SimpleXMLElement;

class Helper
{
    /**
     * Возвращает атрибут объекта SimpleXMLElement, приведенный к строке
     * @param SimpleXMLElement $xmlObject
     * @param string $attribute
     * @return null|string
     */
    public static function xmlAttributeToString(SimpleXMLElement $xmlObject, string $attribute): ?string
    {
        if (isset($xmlObject[$attribute])) {
            return (string)$xmlObject[$attribute];
        }
        return null;
    }

    /**
     * Сканировать директорию без учета . и ..
     * @param string $dir
     * @param int $sort
     * @return array|bool|false
     */
    public static function scanDir(string $dir, int $sort = 0)
    {
        $list = scandir($dir, $sort);

        // если директории не существует
        if (!$list) {
            return false;
        }

        // удаляем . и ..
        if ($sort == 0) {
            unset($list[0], $list[1]);
        } else {
            unset($list[count($list) - 1], $list[count($list) - 1]);
        }
        return $list;
    }

    /**
     * unparsing url, parsed with parse_url();
     * @param $parsed_url
     * @return string
     */
    public static function unparseUrl($parsed_url)
    {
        $scheme = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
        $host = isset($parsed_url['host']) ? $parsed_url['host'] : '';
        $port = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
        $user = isset($parsed_url['user']) ? $parsed_url['user'] : '';
        $pass = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : '';
        $pass = ($user || $pass) ? "$pass@" : '';
        $path = isset($parsed_url['path']) ? $parsed_url['path'] : '';
        $query = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
        $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';
        return "$scheme$user$pass$host$port$path$query$fragment";
    }

    public static function formatInt(string $param)
    {
        return (int)preg_replace('/[^0-9,]/', '', $param);
    }
}
