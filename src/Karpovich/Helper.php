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
}
