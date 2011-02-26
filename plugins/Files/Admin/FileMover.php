<?php

class FileMover extends Page {
    private $that = false;
    private $ignore = array('Userimages', 'Userfiles', 'UserDirectory');

    static public $edit_icon = 'small/door_in';
    static public $edit_text = 'Move';

    function canEdit($obj) {
        return is_a($obj, 'File');
    }

/*
                .($cur->mayI(DELETE)?icon('small/delete', __('Delete'), url(array('del' => $cur->ID), array('id', 'popup', 'filter'))):'')
                .($cur->mayI(EDIT)?icon('small/folder_add', __('queue subfolder'), url(array('id' => $cur->ID, 'action' => 'newFolder'), array('popup', 'filter'))):'')
                .icon('large/down-16', __('Download'), url(array('id' => $cur->ID, 'action' => 'download'), array('popup', 'filter')))
*/
    function __construct($obj){
        parent::__construct($obj->ID);
        $this->that = $obj;
    }

    /**
     * (non-PHPdoc)
     * @see lib/Page#run()
     */
    function run() {
        if($this->saveChanges()) redirect(-1);

        $this->setContent('main',
            $this->mover()
        );

        global $Templates;
        $Templates->render();
    }

    function saveChanges() {
        $_REQUEST->setType('to', 'numeric');
        if($_REQUEST['action'] == 'move' && $_REQUEST['to'] && $this->that->mayI(EDIT)) {
            if($this->moveFile($_REQUEST['to'])) {
                Flash::queue(__('The object was successfully moved'), 'confirmation');
                redirect();
            } else Flash::queue(__('There was an error moving the file.'), 'warning');
        }
    }

    function mover() {
        __autoload('Form');
        $this->setContent('header', __('Moving '.strtolower(get_class($this->that))).': '.$this->that->basename);
        $_REQUEST->addType('to', '#^\$$#'); // Placeholder
        return new Formsection(__('Select destination'),
            $this->fullStructure(url(array('to' => '$'), array('id', 'action'))));
    }


    /**
     * View contents of folders to which the user has access
     * @param $url URL to send the rendered links to. "$" in the URL will be replaced with the ID of the link
     * @return HTML
     */
    function fullStructure($url=false){
        global $DB, $USER, $Controller;

        $r = '';
        if($Controller->{ADMIN_GROUP}(OVERRIDE)->isMember($USER)) {
            $objs = array($Controller->fileRoot);
        } else {
            $privilegeIDS = array_merge((array)$USER->ID, $USER->groupIds);
            $objs = $Controller->get($DB->asList("SELECT spine.id FROM spine RIGHT JOIN privileges ON spine.id = privileges.id WHERE spine.class = 'Folder' AND privileges.beneficiary IN ('".join("','", Database::escape($privilegeIDS, true))."') AND privileges.privileges > 0"), ANYTHING, false, false);
        }
        $folders = array();

        foreach($objs as $obj) {
            $p = $obj;
            while($p = $p->Dir) {
                if(!$p->may($USER, READ)) break;
                elseif(isset($objs[$p->ID])) continue 2;
            }
            if(is_a($obj, 'Folder')) {
                if(!in_array($obj->filename, $this->ignore))
                    $folders[$obj->filename] = $obj;
            }
        }

        ksort($folders);
        return listify(array_map(array($this, 'displayLink'), $folders, array_fill(0, count($folders), $url)));
    }

    /**
     * Display file link
     * @param $obj Top folder
     * @param $url URL to send the rendered links to. "$" in the URL will be replaced with the ID of the link
     * @return HTML Rendered list
     */
    function displayLink($obj, $url){
        if(is_array($obj)) return arrap_map(array($this, 'displayLink'), $obj, array_fill(0, count($obj), $url));

        $subfolders = $obj->folders;
        return '<a href="'.str_replace(array('%24', '$'), $obj->ID, $url).'">'.$obj.'</a>'
            .(@empty($subfolders)?''
                :listify(array_map(array($this, 'displayLink'), $subfolders, array_fill(0, count($subfolders), $url))));
    }
}
?>
