<?php

/**
 * @author Jonatan Olofsson [joolo]
 * @version 1.0
 * @license http://creativecommons.org/licenses/by-nc/3.0/ Creative Commons Attribution-Noncommercial 3.0 Unported License
 * @package Privileges
 */

/**
 * User input types
 */
$_REQUEST->setType('uinfo', 'string', true);

/**
 * Handle the user information fields
 *
 * @package Privileges
 * @todo Field sorting and editing
 */
class UInfoFields extends Page{
    static private $DBTable = 'userinfo';
    public $privilegeGroup = 'Administrationpages';
    protected $Fields = array();
    static public function installable() {return __CLASS__;}
    static public function uninstallable() {return __CLASS__;}

    /**
     * Sets up the object
     * @param integer $id The ID of the object
     * @return void
     */
    function __construct($id=false){
        global $CONFIG;

        parent::__construct($id);
        $this->suggestName('User information-fields');
        $this->alias = 'UInfoFields';

        $this->icon = 'small/user_comment';
        $this->deletable = false;

        $CONFIG->userinfo->setType('Fields', 'not_editable');
        $uinfoFields = @$CONFIG->userinfo->Fields;
        $this->Fields = @array_keys($uinfoFields);
    }

    /**
     * Creates the database table on installation
     * @return bool
     */
    function install() {
        global $DB, $USER;
        $DB->query("CREATE TABLE IF NOT EXISTS `".self::$DBTable."` (
  `id` int(11) NOT NULL,
  `prop` varchar(255) NOT NULL,
  `val` text NOT NULL,
  KEY `id` (`id`,`prop`),
  FULLTEXT KEY `val` (`val`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;");
    }

    /**
     * Drops the database table on uninstall
     * @return bool
     */
    function uninstall() {
        global $DB, $USER, $Controller;
        if(!$USER->may(INSTALL)) return false;
        $Controller->UInfoFields->delete();
        $DB->dropTable(self::$DBTable);
    }

    /**
     * Render the page
     * @return void
     */
    function run(){
        global $Templates, $USER, $CONFIG, $Controller, $DB;
        if(!$this->may($USER, ANYTHING)) errorPage('401');

        /**
         * User input types
         */
        $_REQUEST->setType('editField', 'string');
        $_REQUEST->setType('editFieldSubm', 'string');
        $_REQUEST->setType('newFieldSubm', 'any');
        $_REQUEST->setType('fieldlabel', 'string');
        $_REQUEST->setType('fieldtype', '#^(string|image|file)$#');
        $_REQUEST->setType('fieldvalidation', 'string');
        $_REQUEST->setType('deleteField', 'string');
        $_REQUEST->setType('moveField', '#^(up|down)$#');
        $_REQUEST->setType('field', 'string');
        $_REQUEST->setType('oldname', 'string');

        /**
         * Create a new field or edit an existing one
         */
        if($this->may($USER, EDIT) && ($_REQUEST['newFieldSubm'] || $_REQUEST['editFieldSubm'])) {
            if(!is_array($a = $CONFIG->userinfo->Fields)) {
                $a = array();
            }
            $busy=false;
            $newname = idfy($_REQUEST['fieldlabel']);
            if(!empty($newname)) {
                if($_REQUEST['editFieldSubm'] || !in_array($_REQUEST['newname'], (array)$this->Fields)) {
                    $a[$newname] = array(   'label' => $_REQUEST['fieldlabel'],
                                    'type' => $_REQUEST['fieldtype'],
                                    'validation' => $_REQUEST['fieldvalidation'],
                                    'description' => $_REQUEST['fielddesc']);

                    if($_REQUEST['editFieldSubm'] && $_REQUEST['oldname'] != $newname) {
                        $this->Fields = arrayRemove($this->Fields, $_REQUEST['oldname']);
                        unset($a[$_REQUEST['oldname']]);
                        $DB->userinfo->update(array('prop' => $newname), array('prop' => $_REQUEST['oldname']), false, false);
                        $this->Fields[] = $newname;
                    }

                    if($_REQUEST['newFieldSubm']) {
                        $this->Fields[] = $newname;
                        Flash::create(__('Field created'), 'confirmation');
                    } else {
                        Flash::create(__('Field updated'), 'confirmation');
                    }

                    $CONFIG->userinfo->Fields = $a;

                }
                else Flash::create(__('Name is already taken. Please try again'));
            } else Flash::create('Fieldname is not valid. Please try again', 'warning');
        }

        /**
         * Move a field up or down
         */
        elseif($_REQUEST->valid('moveField') && $this->may($USER, EDIT)) {
            $dir = $_REQUEST['moveField'];
            $which = $_REQUEST['field'];

            $uinfoFields = @$CONFIG->userinfo->Fields;
            if(!is_array($uinfoFields)) $uinfoFields = array();
            $last = false;
            $a = array();
            foreach($uinfoFields as $name => $uf) {
                if($last == false) {
                    $last = array($name, $uf);
                    continue;
                }

                if(($dir == 'up' && $name == $which) || ($dir == 'down' && $last[0] == $which)) {
                    $a[$name] = $uf;
                } else {
                    $a[$last[0]] = $last[1];
                    $last = array($name, $uf);
                }
            }
            if($last)
                $a[$last[0]] = $last[1];
            $CONFIG->userinfo->Fields = $a;
        }

        /**
         * Delete a field
         */
        elseif($_REQUEST['deleteField'] !== false && $this->may($USER, DELETE)) {
            if(!is_array($a = $CONFIG->userinfo->Fields)) {
                $a = array();
            }

            $na = array();
            foreach($a as $name => $b) {
                if($name != $_REQUEST['deleteField']) $na[$name] = $b;
                else {
                    if(in_array($b['type'], array('image', 'file'))) {
                        $otd = $Controller->get($DB->userinfo->asList(array('prop' => $_REQUEST['deleteField']), 'val'));
                        foreach($otd as $f)
                            if(is_a($f, 'File') && strpos($f->path, $this->rootDir().'/UInfoFiles') === 0)
                                $f->delete();
                    }
                    $DB->userinfo->delete(array('prop' => $_REQUEST['deleteField']));
                    Flash::create(__('Field removed'));
                    break;
                }
            }

            $CONFIG->userinfo->Fields = $na;
        }

        if($_REQUEST->valid('editField') && in_array($_REQUEST['editField'], $this->Fields)) {
            $this->setContent('header', __('Edit field'));
            $this->setContent('main', $this->fieldForm($_REQUEST['editField']));
        }
        else {
            $this->setContent('header', __('User information-fields'));
            $this->setContent('main', $this->fieldSettings());
        }
        $Templates->admin->render();
    }

    /**
     * Make a form to edit an information field
     * @param $field The field name (or 'new')
     * @return string The rendered form
     */
    function fieldForm($field=false) {
        global $CONFIG;
        $uinfoFields = @$CONFIG->userinfo->Fields;
        if($field) $info = @$uinfoFields[$field];
        else $info = array();
        $form = new Form((!$field ? 'newFieldSubm' : 'editFieldSubm'), url(null, 'id'));

        return $form->collection(
                    new Fieldset(__('Create a new user information-field'),
                        (!$field ? null : new Hidden('oldname', $field)),
                        new Input(__('Label'), 'fieldlabel', @$info['label'], 'nonempty', __('The visible name of the field')),
                        new Select(__('Type'), 'fieldtype', array('string' => __('Text'), 'image' => __('Image'), 'file' => __('File')), @$info['type'], false, false, false, __('The field type')),
                        new Input(__('Validation'), 'fieldvalidation', @$info['validation'], false, __('Leave empty for no validation'))
                    )
                );
    }

    /**
     * Display the form for editing user information for a user
     * @param $id The user ID
     * @return FieldSet
     */
    function edit($id, $output_class='FieldSet') {
        global $Controller, $CONFIG;

        if($id != 'new') {
            $user = $Controller->$id;
            if(!is_a($user, 'User')) return false;
        }
        @$info = $user->userinfo;
        $uinfoFields = @$CONFIG->userinfo->Fields;
        if(!is_array($uinfoFields)) $uinfoFields = array();

        $uinfo = array();
        foreach($uinfoFields as $name => $uf) {
            switch($uf['type']) {
                case 'image':
                    $uf['validation'] = trim('valid_image '.$uf['validation']);
                    $uinfo[] = new ImagePicker($uf['label'], 'uinfo['.$name.']', (@$_REQUEST['uinfo'][$name]?$_REQUEST['uinfo'][$name]:($new?'':@$info[$name])), $uf['validation'], $uf['description'], true, ($user?$user:false));
                    break;
                case 'file':
                    $uinfo[] = new Li(new FileUpload($uf['label'], 'uinfo['.$name.']', $uf['validation'], $uf['description']), ($new || !@$info[$name]?'':'<a href="'.url(array('id' => @$info[$name])).'">'.__('Show current').'</a>'));
                    break;
                case 'text':
                default:
                    $uinfo[] = new Input($uf['label'], 'uinfo['.$name.']', (@$_REQUEST['uinfo'][$name]?$_REQUEST['uinfo'][$name]:($new?'':@$info[$name])), $uf['validation'], $uf['description']);
                    break;
            }
        }

        return (empty($uinfo)?null:new $output_class(__('User information'), $uinfo));
    }

    /**
     * Saves the user data as information about the user
     * @param $id
     * @return unknown_type
     */
    function save($id) {
        global $Controller, $USER, $CONFIG;

        $_REQUEST->setType('uinfo', 'string', true);
        $user = $Controller->{(string)$id}(OVERRIDE);
        if(!$user || !$user->mayI(EDIT)) return false;

        $info = $user->userinfo;
        $uinfoFields = @$CONFIG->userinfo->Fields;
        if(!is_array($uinfoFields)) $uinfoFields = array();
        $validData = $info;

        foreach($uinfoFields as $name => $uf) {
            if($uf['type'] == 'file') {
                if(!isset($_FILES['uinfo']['name'][$name]) ||
                    $_FILES['uinfo']['error'][$name]) continue;
                $ext = end(explode('.', $_FILES['uinfo']['name'][$name]));
            }
            elseif(@$_REQUEST['uinfo'][$name] == false && @$_REQUEST['uinfo'][$name] !== '' && @$_REQUEST['uinfo'][$name] !== '0') continue;
            switch($uf['type']) {
                case 'file':
                    if($uf['type'] == 'file') {
                        if(!in_array($ext, $CONFIG->Files->filter)) {
                            Flash::create(__('Invalid file type'));
                            break;
                        }
                        if(!is_dir($path=self::rootDir().'/UInfoFiles')) mkdir($path, '0770');
                    }

                    $filename = $id.'_'.time().'.'.$ext;

                    if(isset($info[$name]) && is_numeric($info[$name]) && is_a($f = $Controller->{$info[$name]}, 'File')) {
                        if($_FILES['uinfo']['error'][$name] == UPLOAD_ERR_OK) {
                            $f->delete();
                        }
                    }
                    $fpath = $path.'/'.$filename;
                    if($_FILES['uinfo']['error'][$name] !== UPLOAD_ERR_OK || !move_uploaded_file($_FILES['uinfo']['tmp_name'][$name], $fpath)) {
                        Flash::create(__('There was a problem with the file upload'), 'warning');
                        continue;
                    }
                    else $file = new File($fpath);
                    $validData[$name] = $file->ID;
                    break;
                default:
                    $validData[$name] = $_REQUEST['uinfo'][$name];
            }
        }
        $user->userinfo = $validData;
    }

    function rootDir() {
        return realpath(PRIV_PATH.'/../private_files');
    }
    /**
     * Display the page for editing the user information fields.
     * Most of the actions of the modules are here
     * @return string
     */
    private function fieldSettings() {
        global $USER, $CONFIG, $DB;

        /**
         * Render page
         */

        $uinfoFields = @$CONFIG->userinfo->Fields;
        if(!is_array($uinfoFields)) $uinfoFields = array();
        if(count($uinfoFields)>0) {
            $fields = '<ol class="list">';
            $i=0;
            foreach($uinfoFields as $name => $uf) {
                $fields .= '<li class="'.($i%2?'odd':'even').'"><span class="fixed-width">'.$uf['label'].'</span><div class="tools">'
                .($this->may($USER, DELETE)?icon('small/delete', __('Delete'), url(array('deleteField' => $name), array('id'))):'')
                .($this->may($USER, EDIT)?icon('small/textfield_rename', 'Edit', url(array('editField' => $name), 'id'))
                    .icon('small/arrow_up', __('Move up'), url(array('moveField' => 'up', 'field' => $name), 'id'))
                    .icon('small/arrow_down', __('Move down'), url(array('moveField' => 'down', 'field' => $name), 'id')):'')
                .'</div></li>';
                $i++;
            }
            $fields .= '</ol>';
        } else {
            $fields = __('No fields');
        }

        return (!$this->mayI(EDIT)?null:$this->fieldForm())
            .'<hr />'
            .$fields;
    }
}

?>
