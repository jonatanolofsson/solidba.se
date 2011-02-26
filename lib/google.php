<?php
class google {

    /**
     * Translate a text to another language using Google's translation site
     * @param string|array $text The text to translate
     * @param string|array $from The language(s) to translate from
     * @param string|array $to The language(s) to translate to
     * @return string|array
     */
    static function translate($text, $from='en', $to= 'sv') {
        $languagepairs = array();
        if(!($text && $from && $to)) return false;
        if(!(self::validLang($from) && self::validLang($to))) return false;
        if(is_array($text) && is_array($to) && count($text) != count($to)) return false;
        if(is_array($from)) {
            if(is_array($to)) {
                if(count($from) != count($to)) return false;
                foreach($from as $i => $f) {
                    $languagepairs[] = $f.'%7C'.$to[$i];
                }
            } elseif(is_array($text)) {
                foreach($from as $f) {
                    $languagepairs[] = $f.'%7C'.$to;
                }
            } else return false;
            $langpair = join('&langpair=', $languagepairs);
        }
        else{
            $langpair = $from.'%7C'.join('&langpair='.$from.'%7C', (array)$to);
        }
        $translate = join('&q=', array_map('urlencode', (array)$text));
        $url = "http://ajax.googleapis.com/ajax/services/language/translate"
        .'?v=1.0&langpair='.$langpair.'&q='.$translate;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        if(isset($_SERVER['HTTP_REFERER']))
            curl_setopt($ch, CURLOPT_REFERER, $_SERVER['HTTP_REFERER']);
        $response = json_decode($r = curl_exec($ch), true);
        if(curl_errno($ch)) return "";
        curl_close($ch);
        if(!is_array($to) && !is_array($text)) return $response['responseData']['translatedText'];
        $translation = array();
        foreach($response['responseData'] as $i => $tr) {
            if(is_array($tr)) $translation[] = $tr['responseData']['translatedText'];
            else $translation[] = $tr;
        }
        return $translation;
    }

    /**
     * Add a JavaScript using google.load()
     * @param string $what What should be loaded
     * @param string $version Which version
     * @param string $extra Anything extra
     * @return void
     */
    static function load($what, $version, $extra = false) {
        Head::add('http://www.google.com/jsapi', 'js-url', false, false);
        JS::raw("google.load('".$what."', '".$version."'".($extra?", {".$extra."}":"").");", false, false);
    }

    static $languageMap = array(	"sq" => 'Albanian',
                                "ar" => 'Arabic',
                                "bg" => 'Bulgarian',
                                "ca" => 'Catalan',
                                "zh-CN" => 'Chinese (Simplified)',
                                "zh-TW" => 'Chinese (Traditional)',
                                "hr" => 'Croatian',
                                "cs" => 'Czech',
                                "da" => 'Danish',
                                "nl" => 'Dutch',
                                "en" => 'English',
                                "et" => 'Estonian',
                                "tl" => 'Filipino',
                                "fi" => 'Finnish',
                                "fr" => 'French',
                                "gl" => 'Galician',
                                "de" => 'German',
                                "el" => 'Greek',
                                "iw" => 'Hebrew',
                                "hi" => 'Hindi',
                                "hu" => 'Hungarian',
                                "id" => 'Indonesian',
                                "it" => 'Italian',
                                "ja" => 'Japanese',
                                "ko" => 'Korean',
                                "lv" => 'Latvian',
                                "lt" => 'Lithuanian',
                                "mt" => 'Maltese',
                                "no" => 'Norwegian',
                                "pl" => 'Polish',
                                "pt" => 'Portuguese',
                                "ro" => 'Romanian',
                                "ru" => 'Russian',
                                "sr" => 'Serbian',
                                "sk" => 'Slovak',
                                "sl" => 'Slovenian',
                                "es" => 'Spanish',
                                "sv" => 'Swedish',
                                "th" => 'Thai',
                                "tr" => 'Turkish',
                                "uk" => 'Ukrainian',
                                "vi"=>'Vietnamese');

    function languages($languageCodes) {
        if(!is_array($languageCodes)) return @self::$languageMap[$languageCodes];
        $res = array();
        foreach($languageCodes as $l) $res[$l] = @self::$languageMap[$l];
        return $res;
    }

    function validLang($languageCodes) {
        $languageCodes = (array)$languageCodes;
        foreach($languageCodes as $l){
            if(!isset(self::$languageMap[$l])) return false;
        }
        return true;
    }
}
?>
