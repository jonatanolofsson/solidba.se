<?php

class dntHash {
    static $store;

    /**
     * Store matching DNT elements and replace the match with it's md5 string
     * @param string $match Pregexp match
     * @return string md5 hash
     */
    function hash($match) {
        $match = $match[0];
        $hash = md5($match);
        self::$store[$hash] = $match;
        return $hash;
    }
    /**
     * Store matching DNT elements and replace the match with it's md5 string
     * @param string $match Pregexp match
     * @return string md5 hash
     */
    function mailhash($match) {
        $match = $match[0];
        if(strpos($match, '@') === false) return $match;
        $hash = md5($match);
        self::$store[$hash] = $match;
        return $hash;
    }
    
    /**
     * Replaces the md5 hashes from dntHash with the original content
     * @param $text Text to search and replace in
     * @return string
     */
    function deHash($text) {
        if(!self::$store) return $text;
        $return = str_replace(array_keys(self::$store), self::$store, $text);
        self::$store = array();
        return $return;
    }
}
?>