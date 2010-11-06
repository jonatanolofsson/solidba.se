<?php

/**
 * PermissionEditor
 * @author Jonatan Olofsson [joolo]
 * @version 1.0
 * @license http://creativecommons.org/licenses/by-nc/3.0/ Creative Commons Attribution-Noncommercial 3.0 Unported License
 * @package Privileges
 */
/**
 * The permissioneditor-class provides a user interface for handling the permissions
 * @package Privileges
 *
 */
class PermissionEditor extends Page{
    private $edit = false;
    private $DBTable = 'privileges';
    public $privilegeGroup = 'Administrationpages';
    static public function installable() {return __CLASS__;}
    static public function uninstallable() {return __CLASS__;}

    /**
     * Sets up the object
     * @param integer $id The ID of the object
     * @return void
     */
    function __construct($id=false){

        parent::__construct($id);
        global $CURRENT, $DB, $CONFIG;

        /**
         * User input types
         */
        $_REQUEST->setType('edit', 'string');

        $this->alias = $alias = 'permissionEditor';
        $this->icon = 'small/key';
        $this->suggestName('Edit permissions');

        if($CURRENT && $CURRENT->isMe('pageEditor')) $this->link = array('id'=> $this->ID, 'edit' => $_REQUEST['edit']);
        else $this->link = array('id'=> $this->ID);

        $this->deletable = false;

        MenuEditor::registerEditor('#.#',$alias,$this->icon, 'Edit permissions',array('id' => $this->ID),'edit',EDIT_PRIVILEGES);
    }

    /**
     * Override original may() to allow the user access to the tool for the objects for
     * which it has permission to EDIT_PRIVILEGES.
     * I other cases, fallback to the original may()
     */
    function may($u, $lvl) {
        global $Controller, $ID, $USER;
        if(($lvl & READ) && $_REQUEST['edit']) {
            if(
                $USER->ID !== NOBODY
                && $USER->ID == $_REQUEST['edit']
                )
            {
                return true;
            }
            elseif(
                $ID == $this->ID
                && $_REQUEST['edit'] != $this->ID
                && $Controller->{$_REQUEST['edit']}(EDIT_PRIVILEGES)
                )
            {
                return true;
            }
        }
        return parent::may($u, $lvl);
    }

    /**
     * Creates the database table on installation
     * @return bool
     */
    function install() {
        global $DB, $USER;
        $DB->query('CREATE TABLE IF NOT EXISTS `'.$this->DBTable.'` (
  `id` int(11) NOT NULL,
  `beneficiary` int(11) NOT NULL,
  `privileges` mediumint(9) NOT NULL,
  KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;');
    }

    /**
     * Drops the database table on uninstall
     * @return bool
     */
    function uninstall() {
        global $DB, $USER;
        if(!$USER->may(INSTALL)) return false;
        $DB->dropTable($this->DBTable);
        return true;
    }

    /**
     * Contains actions and pageview-logic
     * @return void
     */
    function run(){
    global $Controller, $Templates, $DB, $USER;

        /**
         * User input types
         */
        $_REQUEST->setType('view', 'numeric');
        $_REQUEST->addType('edit', 'numeric');
        $_REQUEST->setType('rpl', 'any');
        $_REQUEST->setType('ovp', 'any');
        $_REQUEST->setType('edit', 'numeric');
        $_POST->setType('privileges', 'any', true);
        $_REQUEST->setType('nP', 'numeric');
        $_REQUEST->setType('nP', 'numeric');
        $_REQUEST->setType('referrer', 'string');
        $_REQUEST->setType('keyword', 'string');
        $_REQUEST->setType('pdel', 'numeric');

        if($this->may($USER, READ)) {
            /**
             * Display overview
             */
            if($_REQUEST->valid('view')) {
                $this->content = array('header' => __('Permission overview'),
                                        'main' => $this->overview($_REQUEST['view']));
            }
            elseif($_REQUEST->valid('edit') && $this->edit = $Controller->{$_REQUEST['edit']}(EDIT_PRIVILEGES)) {

                /**
                 * Save changes to an object's privileges
                 */
                if($_REQUEST['rpl']) {
                    if($this->edit->may($USER, EDIT_PRIVILEGES)) {
                        $priv = $DB->privileges->asList(array('id' => $this->edit->ID), 'beneficiary');
                        foreach($priv as $uid) {
                            $privileges = @$_POST['privileges'][$uid];
                            $access = 0;
                            if(isset($privileges['read'])) $access |= READ;
                            if(isset($privileges['edit'])) $access |= EDIT;
                            if(isset($privileges['ep'])) $access |= EDIT_PRIVILEGES;
                            if(isset($privileges['del'])) $access |= DELETE;
                            if(isset($privileges['pub'])) $access |= PUBLISH;
                            $DB->privileges->update(array('privileges' => $access), array('id' => $this->edit->ID, 'beneficiary' => $uid));
                            Flash::create(__('Privileges updated'));
                        }
                    }
                }
                /**
                 * Create a new privilege
                 */
                elseif($_REQUEST->valid('nP')) {
                    if($this->edit->may($USER, EDIT_PRIVILEGES)) {
                        $DB->privileges->insert(array('id' => $this->edit->ID, 'beneficiary' => $_REQUEST['nP']), false, true);
                        Flash::create(__('New privilege created'), 'confirmation');
                    }
                }
                /**
                 * Delete a privilege
                 */
                elseif($_REQUEST->valid('pdel')) {
                    if($this->edit->may($USER, EDIT_PRIVILEGES)) {
                        if($DB->privileges->exists(array('id' => $this->edit->ID, 'beneficiary' => $_REQUEST['pdel']))) {
                            $DB->privileges->delete(array('id' => $this->edit->ID, 'beneficiary' => $_REQUEST['pdel']));
                            Flash::create(__('Privilege deleted'), 'warning');
                        }
                    }
                }
                /**
                 * Update user/group privileges
                 */
                elseif($_REQUEST['uPerm']) {
                    if($this->may($USER, EDIT_PRIVILEGES)) {
                        $access = 0;
                        if(isset($_REQUEST['mayinstall'])) $access += INSTALL;
                        $DB->privileges->update(array('privileges' => $access), array('id' => $this->edit->ID, 'beneficiary' => $this->edit->ID), true);
                        Flash::create(__('Privileges updated'));
                    }
                }

            /**
             * Pageview logic
             */
                $this->content = array("header" => __('Edit permissions'), "main" => $this->editPermissions());
            }
            else {
                $this->content = array('header' => __('Permissions'), "main" => $this->viewSpine());
            }
            if($_REQUEST['popup']) $t = 'popup';
            else $t = 'admin';
            $Templates->$t->render();
        }
    }

    /**
     * Generates an overview over the permissions granted to a given user or group
     * @param integer $id ID of the user or group
     * @return string
     */
    private function overview($id) {
        global $Controller, $DB, $USER;
        $a = $Controller->{$id};
        if(is_a($a, 'User') || is_a($a, 'Group')) {
            if($_REQUEST->valid('pdel')) {
                if($Controller->{$_REQUEST['pdel']}->mayI(EDIT_PRIVILEGES)) {
                    if($DB->privileges->delete(array('id' => $_REQUEST['pdel'], 'beneficiary' => $id))) {
                        Flash::create(__('Privilege deleted'), 'warning');
                    }
                }
            }
            elseif($_POST['updatePrivileges'] && $_REQUEST['ovp']) {
                $priv = $DB->privileges->asList(array('benefittor' => $id), 'id');
                foreach($priv as $pid) {
                    if($o = $Controller->{(string)$pid}(EDIT_PRIVILEGES)) {
                        $privileges = @$_POST['privileges'][$pid];
                        $access = 0;
                        if(isset($privileges['read'])) $access |= READ;
                        if(isset($privileges['edit'])) $access |= EDIT;
                        if(isset($privileges['ep'])) $access |= EDIT_PRIVILEGES;
                        if(isset($privileges['del'])) $access |= DELETE;
                        if(isset($privileges['pub'])) $access |= PUBLISH;
                        $DB->privileges->update(array('privileges' => $access), array('id' => $pid, 'beneficiary' => $id));
                        Flash::create(__('Privileges updated'));
                    }
                }
            }
        $r = '<div class="nav"><a href="'.url(null, 'id').'">'.icon('small/arrow_left').__('Back to overview').'</a>'.
        (is_a($a, 'Page')?'<a href="'.url(array('id' => $a->ID)).'">'.icon('small/arrow_left').__('To page').'</a>':'').'</div>';
        $r.='<form action="'.url(null, array('id','view')).'" method="post">
        <fieldset><legend>'.__('Permissions for').' '.$a.'</legend><input type="hidden" name="ovp" value="1" /><table cellpadding="0" cellspacing="0" border="0" class="privilegeList">
    <thead>
        <tr>
            <th>'.__('Delete').'</th>
            <th>'.__('Resource').'</th>
            <th>'.icon('small/eye', __('Read')).'</th>
            <th>'.icon('small/page_edit', __('Edit')).'</th>
            <th>'.icon('small/thumb_up', __('Publish')).'</th>
            <th>'.icon('small/key', __('Edit privileges')).'</th>
            <th>'.icon('small/delete', __('Delete')).'</th>
        </tr>
    </thead>
    <tbody>
    ';
    $m = $DB->privileges->get(array('beneficiary' => $id), 'id,privileges');
    while($row = Database::fetchAssoc($m)) {
        if($obj = $Controller->{$row['id']}) {
    $r .= '		<tr>
                <td><a href="'.url(array('pdel' => $row['id']), array('id','edit', 'view')).'">'.icon('small/delete').'</a></td>
                <td>'.$obj.'</td>
                <td align="center"><input name="privileges['.$row['id'].'][read]" type="Checkbox" class="Checkbox"'.($m['privileges']&READ>0?' checked="checked"':'').' /></td>
                <td align="center"><input name="privileges['.$row['id'].'][edit]" type="Checkbox" class="Checkbox"'.($m['privileges']&EDIT>0?' checked="checked"':'').' /></td>
                <td align="center"><input name="privileges['.$row['id'].'][pub]" type="Checkbox" class="Checkbox"'.($m['privileges']&PUBLISH>0?' checked="checked"':'').' /></td>
                <td align="center"><input name="privileges['.$row['id'].'][ep]" type="Checkbox" class="Checkbox"'.($m['privileges']&EDIT_PRIVILEGES>0?' checked="checked"':'').' /></td>
                <td align="center"><input name="privileges['.$row['id'].'][del]" type="Checkbox" class="Checkbox"'.($m['privileges']&DELETE>0?' checked="checked"':'').' /></td>
            </tr>';
        }
    }
$r.= '
    </tbody>
    <tfoot>
        <tr>
            <td colspan="6"><input type="submit" name="updatePrivileges" value="'.__('Update').'" /></td>
        </tr>
    </tfoot>
</table></fieldset>';
            $r .= '</form>';
            return $r;
        } else {
            return __('No permission overview available');
        }
    }

    /**
     * Display the page for editing normal permissions
     * @return string
     */
    private function editPermissions(){
    global $DB, $Controller;
        $r = '';
        $r .= '<h2>['.get_class($this->edit).']: '.$this->edit.'</h2>';
        $r .= '<div class="nav">'
                .($_REQUEST['referrer']?'<a href="'.url(array('id' => $_REQUEST['referrer']), array('edit', 'filter', 'popup')).'">'.icon('small/arrow_left', __('Back')).__('Back').'</a>':'')
                .'<a href="'.url(null, 'id').'">'.icon('small/arrow_up').__('To overview').'</a>'
                .(is_a($this->edit, 'Page')?'<a href="'.url(array('id' => $this->edit->ID)).'">'.icon('small/arrow_left').__('To page').'</a>':'')
                .'</div>';
$gform = new Form('nGP', url(null, array('id', 'edit', 'referrer', 'filter', 'popup')), false);
$groups = $Controller->getClass('Group');
propsort($groups, 'Name');
$uform = new Form('findUser', url(null, array('id', 'edit', 'referrer', 'filter', 'popup')), false);
$r .= ''.new Accordion(
            __('User permission'),
            $uform->collection(
                new Set(
                    new Li(
                        new Input(__('Find user'), 'keyword', $_REQUEST['keyword']),
                        new Submit(__('Search'))
                    )
                )
            ).$this->userSearchResults(),
            __('Group permission'),
            $gform->collection(
                new Set(
                    new Li(	new Select(__('Choose group'), 'nP', $groups, false, false, __('Choose group')),
                            new Submit(__('Add')))
                )
            ));
$r .= $this->__REEDprivilegeList();
        return $r;
    }

    /**
     * Generate the table form of privileges
     * @return string
     */
    private function __REEDprivilegeList(){
    global $Controller, $DB;
        $privileges = $DB->privileges->asList(array("id" => $this->edit->ID), 'beneficiary,privileges', false, true);
        $beneficiaries = $Controller->get(array_keys($privileges), OVERRIDE);
        if(!$beneficiaries) return;
        $groups = array();
        foreach($beneficiaries as $b) {
            $groups[get_class($b)][] = $b;
        }
        $r='<form action="'.url(null, array('id', 'edit', 'referrer', 'filter', 'popup')).'" method="post">';
        foreach($groups as $class => $objs) {
$r .= '<fieldset><legend>'.__($class.'s').'</legend><input type="hidden" name="rpl" value="1" /><table cellpadding="0" cellspacing="0" border="0" class="privilegeList">
    <thead>
        <tr>
            <th>'.icon('large/editdelete-16', __('Delete')).'</th>
            <th>'.icon('large/groupevent-16', __('User/group')).'</th>
            <th>'.icon('small/eye', __('Read')).'</th>
            <th>'.icon('small/page_edit', __('Edit')).'</th>
            <th>'.icon('small/thumb_up', __('Publish')).'</th>
            <th>'.icon('small/key', __('Edit privileges')).'</th>
            <th>'.icon('small/delete', __('Delete')).'</th>
        </tr>
    </thead>
    <tbody>
    ';
            foreach($objs as $obj) {
                $operm = $privileges[$obj->ID];
    $r .= '		<tr>
                <td>'.icon('small/delete', __('Delete permission'), url(array('pdel' => $obj->ID), array('id', 'edit', 'referrer', 'filter', 'popup'))).'</a></td>
                <td>'.$obj.'</td>
                <td align="center"><input name="privileges['.$obj->ID.'][read]" type="Checkbox" class="Checkbox"'.(($operm & READ)?' checked="checked"':'').' /></td>
                <td align="center"><input name="privileges['.$obj->ID.'][edit]" type="Checkbox" class="Checkbox"'.(($operm & EDIT)?' checked="checked"':'').' /></td>
                <td align="center"><input name="privileges['.$obj->ID.'][pub]" type="Checkbox" class="Checkbox"'.(($operm & PUBLISH)?' checked="checked"':'').' /></td>
                <td align="center"><input name="privileges['.$obj->ID.'][ep]" type="Checkbox" class="Checkbox"'.(($operm & EDIT_PRIVILEGES)?' checked="checked"':'').' /></td>
                <td align="center"><input name="privileges['.$obj->ID.'][del]" type="Checkbox" class="Checkbox"'.(($operm & DELETE)?' checked="checked"':'').' /></td>
            </tr>';
            }
$r.= '
    </tbody>
    <tfoot>
        <tr>
            <td colspan="6"><input type="submit" value="'.__('Update').'" /></td>
        </tr>
    </tfoot>
</table></fieldset>';
        }
        $r .= '</form>';
        $head = '<h2>'.__('Edit permissions').'</h2>';
        return $head.$r;
    }

    /**
     * Display the results of a user search
     * @return string
     */
    function userSearchResults(){
        global  $DB, $Controller;
        if($_REQUEST['keyword'])
        {
            $results = array_merge(
                        $DB->asList($DB->userinfo->search($_REQUEST['keyword'], 'val', 'id')),
                        $DB->asList($DB->users->like($_REQUEST['keyword'], 'username', 'id'))
                    );
            if(count($results) == 0) {
                return __('No results');
            }
            else {
                $results = $Controller->get(array_unique($results));
                propsort($results, 'Name');
                $r = '';
                $r .= '<h4>'.__('Search results').'</h4>';
                $r .= '<ul class="flul">';
                $i=0;
                foreach($results as $user) {
                    if($user) {
                        $r .= '<li class="'.($i%2?'even':'odd').'">'.icon('small/add', __('Add privileges for this user'), url(array('nP' => $user->ID), array('id', 'edit', 'referrer', 'filter', 'popup', 'keyword'))).$user.'</li>';
                        $i++;
                    }
                }
                $r .= '</ul>';
                return $r;
            }
        } else return "";
    }

    /**
     * Overview the spine database table and display the overview page
     * @return string
     */
    function viewSpine(){
        global $DB, $Controller;
        $r='';

        $perpage = 250;
        $total = $DB->spine->count();
        $pager = Pagination::getRange($perpage, $total);
        $objects = $Controller->get($DB->spine->asList(null, 'id', $pager['range']['start'].','.$perpage), EDIT_PRIVILEGES);
        $groups = array();
        foreach($objects as $o) {
            $groups[$o->privilegeGroup][] = $o;
        }
        ksort($groups);
        $r.='<ul class="list">';
        foreach($groups as $group => $rows) {
            if($group == 'hide' || $group == 'hidden') continue;
            $r .= '<li>'.ucwords(__($group)).'<ul>';
            $i=0;
            natcasesort($rows);
            foreach($rows as $o) {
                $r .= '<li class="'.($i%2?'even':'odd').'"><span class="fixed-width">'.$o.'</span><div class="tools">'
                    .icon('small/key', __('Edit permissions for').' &quot;'.strip_tags($o).'&quot;', url(array("edit" => $o->ID), 'id'))
                    .((is_a($o, 'User') || is_a($o, 'Group'))?icon('small/magnifier', __('Overview permissions for').' &quot;'.strip_tags($o).'&quot;', url(array('view' => $o->ID), 'id')):'')
                        .(method_exists($o, 'run')?icon('small/bullet_go', __('Go to').' &quot;'.strip_tags($o).'&quot;', url(array('id' => $o->ID))):'')
                        .'</div></li>';
                $i++;
            }
            $r.='</ul></li>';
        }
        $r .= '</ul>';
        return $r.($total > $perpage ? $pager['links']:'');
    }
}
?>
