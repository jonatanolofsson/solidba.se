<?php

/**
 * @author Jonatan Olofsson [joolo]
 * @version 1.1
 * @license http://creativecommons.org/licenses/by-nc/3.0/ Creative Commons Attribution-Noncommercial 3.0 Unported License
 * @package Content
 */
/**
 * Provides the default solidba.se interface for administrating news
 * @package Content
 */
class NewsAdmin extends Page {
    public $privilegeGroup = 'Administrationpages';
    static $VERSION = 1;
    static public function installable() {return __CLASS__;}
    //static public function uninstallable() {return __CLASS__;}
    static public function upgradable() {return __CLASS__;}

    function upgrade() {}

    function install() {
        global $Controller, $CONFIG;
        $CONFIG->News->setDescription('Preamble_size', 'Size of preamble');
        $Controller->newObj('NewsAdmin')->move('last', 'adminMenu');
        return self::$VERSION;
    }

    /**
     * Setup the page
     * @param integer $id The object's ID
     * @return void
     */
    function __construct($id=false){
        parent::__construct($id);
        $this->alias = 'newsAdmin';
        $this->suggestName('News');

        $this->icon = 'small/newspaper';
        $this->deletable = false;
    }

    /**
     * In this function, most actions of the module are carried out and the page generation is started, distibuted and rendered.
     * @return void
     * @see solidbase/lib/Page#run()
     */
    function run(){
        global $Templates, $USER, $CONFIG, $Controller, $DB;
        if(!$this->may($USER, READ|EDIT)) { errorPage('401'); return false; }


        /**
         * User input types
         */
        $_REQUEST->setType('order', 'numeric', true);
        $_REQUEST->setType('expand', 'bool');
        $_REQUEST->setType('del', 'numeric');

        if($_REQUEST['del']) {
            if($Controller->{$_REQUEST['del']} && $Controller->{$_REQUEST['del']}->delete()) {
                Flash::create(__('Newsitem removed'), 'confirmation');
            }
        }

        /**
         * Here, the page request and permissions decide what should be shown to the user
         */
        $this->setContent('header', __('News'));
        $this->setContent('main',$this->mainView());

        $Templates->admin->render();
    }

    /**
     * @return string
     */
    private function mainView() {
        global $USER, $CONFIG, $DB, $Controller;

        $newsList = array();

        $r = $DB->query("SELECT DISTINCT sp.id FROM updates AS t1
            LEFT JOIN spine sp ON sp.id = t1.id
            LEFT JOIN updates t2 ON t1.id = t2.id
            AND t1.edited < t2.edited
            WHERE t2.edited IS NULL
            AND sp.class = 'NewsItem'
            ORDER BY t1.edited DESC"
            .(!$_REQUEST['expand']?" LIMIT 5":""));

        while(false !== ($newsItem = $DB->fetchAssoc($r))) {
            if($newsItem = $Controller->{$newsItem['id']}(EDIT))
                $newsList[] = '<span class="fixed-width">'.$newsItem->Name.'</span>'
                .'<div class="tools">'
                    .icon('small/eye', __('View'), url(array('id' => $newsItem->ID)))
                    .(!$newsItem->isActive()?icon('small/flag_red', __('Not active')):'')
                .'</div>';
        }
        __autoload('Form');
        return new Formsection(
            new Sortable(false,'order',$newsList),
            ($_REQUEST['expand']
                ? '<a href="'.url(array('expand' => false), true).'">'.__('Display less').'</a>'
                : '<a href="'.url(array('expand' => true), true).'">'.__('Display all').'</a>'
            )
        );
    }
}

?>
