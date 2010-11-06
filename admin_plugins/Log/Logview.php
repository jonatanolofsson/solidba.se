<?php
class Logview extends page {
    public $privilegeGroup = 'Administrationpages';
    static public function installable() {return __CLASS__;}
    static public function uninstallable() {return __CLASS__;}

    function install() {
        global $Controller;
        $o = $Controller->newObj('Logview', false, true)->move('last', 'adminMenu');
    }
    function uninstall() {

    }

    /**
     * Setup the page
     * @param integer $id The object's ID
     * @return void
     */
    function __construct($id=false){
        parent::__construct($id);
        $this->suggestName('Logview');

        $this->icon = 'small/book_error';
        $this->deletable = false;
    }

    function run() {
        global $DB, $Templates, $Controller, $CONFIG;
        $_REQUEST->setType('lfrom', '#\d\d-\d\d-\d\d#');
        $_REQUEST->setType('lto', '#\d\d-\d\d-\d\d#');
        $_REQUEST->setType('lrh', 'string');
        $_REQUEST->setType('luser', 'string');
        $_REQUEST->setType('lsource', 'string');
        $_REQUEST->setType('llevel', 'numeric');

        $ENTRIES = array();

        $from = false;
        $to = false;

        if($_REQUEST['lfrom']) $from = strtotime($_REQUEST['lfrom']);
        if(!$from) $from = mktime(0,0,0,date('m')-1,date('d'),date('Y'));
        if($_REQUEST['lto']) $to = strtotime($_REQUEST['lto'])+86400;
        if(!$to) $to = time();

        $ENTRIES = $DB->log->asArray(array('time>=' => date('Y-m-d H:i:s', $from), 'time<=' => date('Y-m-d H:i:s', $to)));

        if(file_exists(PRIV_PATH.'/log')) {
            $logfile = file(PRIV_PATH.'/log');
            foreach($logfile as $row => $entryRow) {
                if(empty($entryRow) || $entryRow == "\n") continue;
                $entry = array();
                list($entry['time'], $entry['remote_addr'], $entry['user'], $entry['source'], $entry['level'], $entry['message']) = explode("\t", $entryRow);
                $t = strtotime($entry['time']);
                if($t >= $from && $t <= $to) {
                    $ENTRIES[] = $entry;
                }
            }
        }

        usort($ENTRIES, create_function('$a,$b', 'return -strcmp($a["time"], $b["time"]);'));
        $perpage = 250;
        $total = count($ENTRIES);
        $pager = Pagination::getRange($perpage, $total);
        $ENTRIES = array_slice($ENTRIES, $pager['range']['start'], $perpage);

        $TEXT = '';
        $i=0;
        foreach($ENTRIES as $entry) {
            if($a = $Controller->{$entry['user']}('User'))
                $entry['user'] = $a->link();

            if($entry['level'] < $_REQUEST['llevel']
                || ($_REQUEST['luser'] && stristr($entry['user'],$_REQUEST['luser']) === false)
                || ($_REQUEST['lrh'] && stristr($entry['remote_addr'],$_REQUEST['lrh']) === false)
                || ($_REQUEST['lsource'] && stristr($entry['source'],$_REQUEST['lsource']) === false)) continue;

            $entry['message'] = preg_replace_callback ('/id=([0-9]+)/', create_function('$matches', 'global $Controller; if ($obj = $Controller->retrieve($matches[1], ANYTHING, false, false)) return $obj->link(); return $matches[0];'), $entry['message']);
            $entry['source'] = preg_replace_callback ('/([0-9]+)/', create_function('$matches', 'global $Controller; if ($obj = $Controller->retrieve($matches[1], ANYTHING, false, false)) return $obj->link(); return $matches[0];'), $entry['source']);
            $TEXT .= '<tr class="'.(++$i%2?'even':'odd').'"><td>'.join("</td><td>", $entry).'</td></tr>';
        }

        $this->setContent('header', __('View log'));
        JS::loadjQuery();
        Head::add('$(function(){$(".Datepicker").Datepicker({ dateFormat: "yy-mm-dd" });});', 'js-raw');

        Head::add($CONFIG->UI->jQuery_theme.'/jquery-ui-*', 'css-lib');
        $this->setContent('main', '<form action="'.url(null, 'id').'" method="post"><input type="submit" value="'.__('Filter').'" /><table class="log">'
                                        .'<tr><th>'.__('Time')."</th><th>".__('Remote address')."</th><th>".__('User')."</th><th>".__('Source')."</th><th>".__('Level')."</th><th>".__('Message').'</th></tr>'
                                        .'<tr><td><input name="lfrom" class="small Datepicker" value="'.$_REQUEST['lfrom'].'" />-<input name="lto" class="small Datepicker" value="'.$_REQUEST['lto'].'" /></td><td><input name="lrh" class="small" value="'.$_REQUEST['lrh'].'" /></td><td><input name="luser" class="small" value="'.$_REQUEST['luser'].'" /></td><td><input name="lsource" class="small" value="'.$_REQUEST['lsource'].'" /></td><td><input name="llevel" class="small" value="'.$_REQUEST['llevel'].'" /></td><td></td></tr>'
                                        .$TEXT.'</table></form>'
                                        .($total > $perpage ? $pager['links'] : ''));

        $Templates->render();
    }
}
?>
