<?php
define('MINIFICATION', '/3rdParty/min');
global $CONFIG;
$CONFIG->base->setType('Minify_JS_and_CSS', 'select', array('No', 'Yes'));
class Head {
    private static $HEAD;
    static $MINIFIABLE = array('css-lib', 'js-lib', 'css-url', 'js-url');

    /**
     * Adds a string to either the head or the bottom of the template, using predefined types of strings
     * to identify how to wrap the incoming string. If the type is not set or not recognized, the string is inserted as-is.
     * @param string $string String to append
     * @param string $string_type Type Type of string that is appended. Possible values: [css-url, css-raw, js-url, js-raw, js-lib, formatted].
     * @param $defer
     * @see OBFilter()
     * @return unknown_type
     */
    function add($string, $string_type='formatted', $defer=-1, $min=true, $IE=false)
    {
        global $CONFIG;
        $min = (bool)(  $min
                        && in_array($string_type, self::$MINIFIABLE)
                        && $CONFIG->base->Minify_JS_and_CSS
                        && stripos($string, 'http:') !== 0
                        && stripos($string, 'https:') !== 0
                    );

        $type = $string_type;
        if(in_array($type, array('js-lib', 'js-url'))) $type = 'js';
        if(in_array($type, array('css-lib', 'css-url'))) $type = 'css';

        if(substr($string_type, 0, 2) == 'js') {
            if($defer == -1) $defer = true;
        } else {
            if($defer == -1) $defer = false;
        }
        $w = (int)(bool)$defer;

        $md5 = md5($string);
        if(!isset(self::$HEAD[$IE][$type][$md5])) {
            self::$HEAD[$IE][$type][$md5] = array('where' => $w, 'string' => $string, 'type' => $string_type, 'min' => $min);
        }
        else {
            self::$HEAD[$IE][$type][$md5]['where'] *= $w;
            self::$HEAD[$IE][$type][$md5]['min'] *= $min;
        }
    }


    function finalize() {
        global $CONFIG;
        $min = @(bool)($CONFIG->base->Minify_JS_and_CSS);

        $r = array('','');
        foreach(self::$HEAD as $IE => $types) {
            $IECond = array('', '');
            if($IE) {
                if($IE === true) $IECond[0] = '<!--[if IE]>';
                else $IECond[0] = '<!--[if IE '.$IE.']>';
                $IECond[1] = '<![endif]-->';
            }

            $where = array();
            foreach($types as $type => $data) {
                $prefer_mini = 1;
                foreach($data as $set) {
                    if(!$set['where']) {
                        $prefer_mini = 0;
                        break;
                    }
                }
                foreach($data as $set) {
                    $where[$set['where']][(int)$set['min']][] = $set;
                }
                if(!$prefer_mini && $min && in_array($set['type'], self::$MINIFIABLE)) {
                    if(isset($where[0][1]) && isset($where[1][1])) {
                        $where[0][1] = array_merge($where[0][1], $where[1][1]);
                        unset($where[1][1]);
                    }
                }
            }

            if(isset($where[0])) {
                print_r($where[0]);
                $a = self::compile($where[0]);
                if($a) $r[0] .= $IECond[0].$a.$IECond[1];
            }
            if(isset($where[1])) {
                $a = self::compile($where[1]);
                if($a) $r[1] .= $IECond[0].$a.$IECond[1];
            }
        }
        return $r;
    }

    function compile($sets) {
        global $SITE;
        $data = array('js' => '', 'css' => '', 'js-min' => '', 'css-min' => '', 'css-raw' => '', 'formatted' => '');
        foreach($sets as $min => $set) {
            foreach($set as $s) {
                list($which, $type) = explode('-', strtolower($s['type']));
                $string = $s['string'];
                switch($type)
                {
                    case 'url':
                        if($min) $data[$which.'-min'] .= ','.$string;
                        elseif($which == 'js') $data['js'] .= '<script type="text/javascript" src="'.$string.'"></script>';
                        else $data['css'] .= '<link rel="stylesheet" type="text/css" href="'.$string.'" />'.$IECond[1]."\r\n";
                        break;
                    case 'lib':
                        $cdir = getcwd();
                        chdir($SITE->base_dir.'/lib/'.$which);
                        $files = glob($string.'.'.$which);
                        if(empty($files)) {
                            chdir($cdir);
                            continue;
                        }
                        sort($files);
                        $file = array_pop($files);
                        chdir($cdir);

                        if($min) $data[$which.'-min'] .= ',lib/'.$which.'/'.$file;
                        elseif($which == 'js') $data['js'] .= '<script type="text/javascript" src="/lib/js/'.$file.'"></script>';
                        else $data['css'] .= '<link rel="stylesheet" type="text/css" href="/lib/css/'.$file.'" />';
                        break;
                    case 'raw':
                        if($which == 'css')	$data['css-raw'] .= "\r\n".$string;
                        else {
                            if(substr($data['js'], -11,1)=='-') {
                                $data['js'] = substr($data['js'], 0,-12);
                            } else $data['js'].= ' <script type="text/javascript">'."<!--\r\n";
                            $data['js'] .= $string."\r\n--></script>";
                        }
                        break;
                    default:
                        $data['formatted'] .= $string;
                } // switch
            }
        }

        $r = '';
        if($data['css-min'])
            $r .= '<link rel="stylesheet" type="text/css" href="'.MINIFICATION.'/?f='.substr($data['css-min'], 1).'" />';

        if($data['js'])
            $r .= $data['js'];

        if($data['js-min'])
            $r .= '<script type="text/javascript" src="'.MINIFICATION.'/?f='.substr($data['js-min'], 1).'"></script>';

        if($data['css'])
            $r .= $data['css'];

        if($data['css-raw'])
            $r .= '<style type="text/css">'."\r\n<!--\r\n".$data['css-raw']."\r\n-->\r\n</style>";

        if($data['formatted'])
            $r .= $data['formatted'];

        return $r;
    }
}
?>
