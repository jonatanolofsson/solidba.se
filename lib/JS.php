<?php
class JS {
    static $currentWindowHandle = 'popup';

    //FIXME: Finish
    function popup($URL, $window_name=false, $settings = false) {
        if($settings) {
            $settings_ = $settings;
            $settings = '';
            foreach($settings_ as $key => $opt) $settings .= $key.'='.$opt.',';
        }
        if(!$window_name) $window_name = self::$currentWindowHandle;
        else self::$currentWindowHandle = $window_name;
        $id = idfy($URL);
        JS::loadjQuery();
        Head::add('$(function(){$("'.$id.'").click(function(){});});', 'js-raw');
        return '<a href="'.url($URL).'" id="'.$id.'"';
    }

    function loadjQuery($UI=true, $defer=true) {
/*
        google::load('jquery', '1');
*/
        Head::add('jquery/jquery-1*', 'js-lib', false, false);
        if($UI) {
            global $CONFIG;
            Head::add($CONFIG->UI->jQuery_theme.'/jquery-ui-*', 'css-lib');
            Head::add('jquery/jquery-ui-1*', 'js-lib', false, false);
/*
            google::load('jqueryui', '1');
*/
        }
    }

    function raw($what, $defer = -1){
        Head::add($what, 'js-raw', $defer);
    }

    function lib($what, $IE=false, $defer=true) {
        if(!is_array($what)) $what = array($what);
        global $SITE;
        foreach($what as $inc) {
            $cdir = getcwd();
            chdir($SITE->base_dir.'/lib/js');
            $files = glob($inc.'.js');
            if(empty($files)) {
                chdir($cdir);
                return;
            }
            sort($files);
            $file = array_pop($files);

            if(!($dep = file($file))) {
                chdir($cdir);
                return;
            }
            chdir($cdir);

            $dep = $dep[0];
            if(preg_match('#^//Deps:#', $dep))
            {
                $deps = explode(',', substr($dep, 7));
                foreach($deps as $d)
                {
                    $d = trim($d);
                    if($d == 'jquery') self::loadjQuery(false, $defer);
                    elseif($d == 'jquery-ui') self::loadjQuery(true, $defer);
                    else JS::lib($d, $IE, $defer);
                }
            }
            Head::add($inc, 'js-lib', $defer, true, $IE);
        }
    }
}
