<?php
class Short {
    /**
     * Short function to create a simple 'back'-button
     * @param array $saveVars Which GET-vars to keep in link
     * @return string
     */
    function back($saveVars = null) {
        return '<a href="'.url(($saveVars?null:@$_SERVER['HTTP_REFERER']), ($saveVars?$saveVars:array('id', 'edit'))).'">'.icon('small/arrow_left').__('Back').'</a>';
    }

    /**
     * Go back n steps in history
     * @param $n How many steps to go back
     * @param $displayTitle Display the title of the target page if true. If false, show 'Back'
     * @return string
     */
    function backn($n=1, $displayTitle=false) {
        if(isset($_SESSION['TRACE'][$n]))
            return '<a href="'.url(array_merge(@$_SESSION['TRACE'][$n]['_GET'], array('history' => 'back')), null, false).'">'.icon('small/arrow_left').($displayTitle?$Controller->get(@$_SESSION['TRACE'][$n]['id']):__('Back')).'</a>';
        return '';
    }


    /**
     * Display a combined form for date and time
     * @param $label Label of the form
     * @param $name name of the form fields
     * @param $datetime Value of the fields, in unix time
     * @param $postOverride If the POST variable should override the value in the form
     * @return string
     */
    function datetime($label, $name, $datetime=false, $postOverride=true) {
        if($postOverride && $r = Short::parseDateAndTime($name, $datetime)) $datetime = $r;

        return new Li(
            new Datepicker($label, $name.'[date]', $datetime),
            new Timepickr(false, $name.'[time]', $datetime)
        );
    }

    /**
     * Parses a request variable from Short::datetime to a timestamp
     * @param string $name name of the reuqest variable. Type is set internally
     * @return int Timestap parsed from the input
     */
    function parseDateAndTime($name, $fallback = true) {
        if(is_array($name)) $parse = $name;
        else {
            $_REQUEST->setType($name, 'string', true);
            if(!isset($_REQUEST[$name])) {
                if($fallback === true) return time();
                else return $fallback;
            } else {
                $parse = $_REQUEST[$name];
            }
        }
        if(empty($parse['date']) && empty($parse['time'])) {
            if($fallback === true) return time();
            else return $fallback;
        }
        return strtotime(@$parse['date'].' '.@$parse['time']);
    }

    function registerFilter($callback) {
        global $CONFIG;
        $m=$CONFIG->content->filters;
        if(!is_array($m)) $m = array();
        if(!isset($m[__CLASS__])) {
            $m[__CLASS__] = $callback;
            $CONFIG->content->filters = $m;
        }
    }

    function selectTemplate($selected = false, $text = 'Template', $name = 'template') {
        global $Templates;

        return new Select(__($text), $name,
                array_merge(array(__('System') => array(     'inherit' => __('Inherit'),
                                    'default' => __('Default'),
                                'admin' => __('Admin default')
                        )),
            $Templates->listAll()),
            $selected);
    }

    function datespan($from, $to) {
        if(date('Y-m-d', $from) == date('Y-m-d', $to) || date('Y-m-d', $from) == date('Y-m-d', $to-3600*2)) {
            return date('Y-m-d, H:i', $from).' - '.date('H:i', $to);
        }
        elseif(date('Y', $from) == date('Y', $to)) {
            return date('j/n H:i', $from).' - '.date('j/n H:i, Y');
        }
        else {
            return date('Y-m-d H:i', $from).' - '.date('Y-m-d H:i');
        }
    }

    function toolList($objects) {
        return listify(array_map(array('self', '__toolListHelper'), $objects));
    }
    function __toolListHelper($obj) {
        return $obj.Box::tools($obj);
    }
}
?>
