<?php

class Files extends Page {
    public $privilegeGroup = 'Administrationpages';
    private $DBTable = 'menu';
    private $ignore = array('Userimages', 'Userfiles', 'UserDirectory');

    public $editable = array(
        'PermissionEditor' => EDIT_PRIVILEGES,
        'MenuEditor' => EDIT
    );

    /**
     * Sets up the object
     * @param integer $id ID of the object
     * @return void
     */
    function __construct($id=false){
        parent::__construct($id);
        $this->suggestName('Files', 'en');
        $this->suggestName('Filer', 'sv');
        $this->icon = 'small/folder_picture';
        $this->deletable = false;

        $this->alias = 'files';
    }


    /**
     * Permission-test overload to allow display if there are any files or folders that allow so
     * @see solidbase/lib/Base#may()
     */
    function may($beneficiary, $accessLevel) {
        $p = parent::may($beneficiary, $accessLevel);
        if(is_bool($p)) return $p;
        if($accessLevel & READ) {
            if(!isset($this->READ[$beneficiary->ID])) {
                global $DB;
                $privilegeIDS = array_merge((array)$beneficiary->ID, $beneficiary->groupIds);
                $this->READ[$beneficiary->ID] = $DB->exists("SELECT `spine`.`id` as id FROM `spine` RIGHT JOIN `privileges` ON `spine`.`id` = `privileges`.`id` WHERE `spine`.`class` IN ('File','Folder') AND `privileges`.`beneficiary` IN ('".join("','", Database::escape($privilegeIDS, true))."') AND (`privileges`.`privileges` & ".READ.") > 0");
            }
            return ($this->READ[$beneficiary->ID]?true:0);
        }
        return 0;
    }
    private $READ=null;

    /**
     * (non-PHPdoc)
     * @see lib/Page#run()
     */
    function run() {
        global $DB, $USER, $Controller, $Templates, $CONFIG;
        /**
         * User input types
         */
        $_REQUEST->setType('del', 'numeric');
        $_REQUEST->setType('fname', 'string');
        $_REQUEST->setType('action', 'string');
        $_REQUEST->setType('popup', 'string');
        $_REQUEST->setType('filter', 'string');
        $_REQUEST->setType('referrer', 'string');
        $_REQUEST->addType('edit', 'numeric');

        if($_REQUEST['del'] && $v = $Controller->{$_REQUEST['del']}(DELETE)) {
            $pid = @$this->Dir->ID;
            $v->delete();
            Flash::create(__('The file/directory was deleted'));
        }

        $groups = $USER->groupIds;
        array_walk($groups, create_function('$id', 'Files::userDir($id);'));

        $r = '';
        if($Controller->{ADMIN_GROUP}(OVERRIDE)->isMember($USER)) {
            $objs = array($Controller->fileRoot);
        } else {
            $privilegeIDS = array_merge((array)$USER->ID, $USER->groupIds);
            $objs = array_merge($Controller->getClass('Folder', ANYTHING, false, false)
                        , $Controller->get($DB->{'spine,privileges'}->asList(array('spine.class' => 'File'), 'spine.id'), ANYTHING, false, false));
        }
        $Folders = $Files = array();

        foreach($objs as $obj) {
            $p = $obj;
            while($p = $p->Dir) {
                if(!$p->may($USER, READ)) break;
                elseif(isset($objs[$p->ID])) continue 2;
            }
            if(is_a($obj, 'Folder')) {
                if(!in_array($obj->filename, $this->ignore))
                $Folders[$obj->filename] = $obj;
            } elseif(is_a($obj, 'File')) {
                $Files[$obj->filename] = $obj;
            }
        }


        if($_REQUEST['popup']) {
            Head::add("function select(id) {try{window.opener.fileCallback(id,'{$_REQUEST['popup']}');} catch(err) {}window.close();}", 'js-raw');
        }

        ksort($Folders);
        foreach($Folders as $Folder) {
            $r .= $Folder->genHTML();
        }

        if(!empty($Files)) {
            ksort($Files);
            Head::add($CONFIG->UI->jQuery_theme.'/jquery-ui-*', 'css-lib');
            $r .= '<div class="ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all"><span class="fixed-width">';
            $r .= __('Files');
            $r	.=	'</span></div>';
            $r .= '<ul class="filetree">';
            $i=0;
            foreach($Files as $cur) {
                if(!$cur->may($USER, READ)) continue;
                if($_REQUEST['filter']) {
                    switch($_REQUEST['filter']) {
                        case 'images':
                        case 'documents':
                            if(!in_array(strtolower($cur->extension), $CONFIG->extensions->{$_REQUEST['filter']})) continue 2;
                            break;
                        default:
                            if(!stristr($cur->basename, $_REQUEST['filter'])) continue 2;
                    }
                }
                $r .= '<li class="'.($i%2?'odd':'even').' file ext_'.$cur->extension.'"><span class="fixed-width">';
                if($_REQUEST['popup']) $r .= '<a href="javascript: select('.$cur->ID.');">';
                $r .= $cur->basename;
                if($_REQUEST['popup']) $r .= '</a>';
                $r .='</span><div class="tools">'
                    .($cur->mayI(EDIT_PRIVILEGES)?icon('small/key', __('Edit permissions'), url(array('id' => 'PermissionEditor', 'edit' => $cur->ID, 'referrer' => $this->ID), array('popup', 'filter'))):'')
                    .($cur->mayI(EDIT)?icon('small/door_in', __('Move'), url(array('id' => $cur->ID, 'referrer' => $this->ID), array('popup', 'filter'))):'')
                    .($cur->mayI(DELETE)?icon('small/delete', __('Delete'), url(array('del' => $cur->ID), array('id', 'popup', 'filter'))):'')
                    .icon('large/down-16', __('Download'), url(array('id' => $cur->ID, 'action' => 'download'), array('popup', 'filter')))
                .'</div></li>';
                $i++;
            }
            $r .= '</ul>';
        }
        $this->setContent('header', __('Files and directories'));
        $this->setContent('main', $r);
        $t = 'admin';
        if($_REQUEST['popup'])
            $t = 'popup';

        $Templates->$t->render();
    }

    /**
     * Load a user's private directory
     * @param user $user Which user's directory to load
     * @param bool $return_obj Set to true to return object instead of
     * 							object id
     * @return int|object
     */
    static function userDir($user, $return_obj=false) {
        global $Controller;
        if(!is_object($user)) $user = $Controller->{(string)$user}(OVERRIDE);
        if(!$user || !is_a($user, 'Benefittor')) return false;
        if(in_array($user->ID, array(NOBODY, EVERYBODY_GROUP, MEMBER_GROUP))) return false;

        $dir = Folder::rootDir().'/UserDirectory/';
        if(!is_dir($dir)) {
            mkdir($dir, 0700);
            Folder::open($dir);
        }
        $dir .= $user->ID;
        if(!is_dir($dir)) mkdir($dir, 0700);
        $dir = realpath($dir);
        $udir = Folder::open($dir);
        if($return_obj) return $udir;
        return $udir->ID;
    }
}
?>
