<?php

/**
 * @author Jonatan Olofsson [joolo]
 * @version 1.0
 * @license http://creativecommons.org/licenses/by-nc/3.0/ Creative Commons Attribution-Noncommercial 3.0 Unported License
 * @package Privileges
 */
/**
 * The usereditor provides a tool to edit users
 * @package Privileges
 */
class UserEditor extends Page{
    private $that = false;

    static public $edit_icon = 'small/user_edit';
    static public $edit_text = 'Edit user';

    function canEdit($obj) {
        return is_a($obj, 'User');
    }

    /**
     * Sets up the object
     * @param integer $id The ID of the object
     * @return void
     */
    function __construct($obj) {
        parent::__construct($obj->ID);
        $this->that = $obj;
    }

    /**
     * Contains actions and pageview-logic
     * @return void
     */
    function run(){
        global $Templates, $USER, $CONFIG, $Controller, $DB;
        if($this->saveChanges()) redirect(array('id' => $this->that->ID, 'saved' => 1));

        global $Templates;
        $this->setContent('main',
            Form::quick(false, null,
                $this->editTab()
            )
        );
        $Templates->render();
    }

    function saveTerms() {
        if($terms = $_POST['terms']) {
            $languages = (array)$CONFIG->Site->languages;
            $tid = $DB->aliases->getCell(array('alias' => 'Terms', 'id'));
            foreach($languages as $l){
                $tpage = new Terms($tid, $l);
                $tpage->saveContent(array('Terms' => $terms[$l]));
            }
            Flash::create(__('Your changes have been saved'), 'confirmation');
            $_REQUEST->clear('view');
        }
    }

    function saveChanges() {
        if(!is_a($this->that, 'User')) return null;



        /**
         * Delete user
         */
        if($_REQUEST->numeric('del') && $this->that->mayI(DELETE)) {
            $Controller->{$_REQUEST['del']}(OVERRIDE)->delete();
            Flash::queue(__('User was deleted'));
            redirect(url());
        }

        global $Controller, $DB;
        $_POST->setType('username', 'string');
        $_POST->setType('password1', 'string');
        $_POST->setType('password2', 'string');
        $_POST->setType('volgroups', 'numeric', true);
        $changes = false;
        /**
         * Save the user
         */
        if($_POST['username'] && $_POST['username'] != $this->that->username) {
            if($DB->users->exists(array('username' => $_POST['username'],'id!' => $this->that->ID))) {
                Flash::create(__('Username is already in use'), 'warning');
                return false;
            } else {
                $user->username = $_POST['username'];
                $changes = true;
            }
        }
        if($_POST['password1']) {
            if($_POST['password1'] === $_POST['password2']) {
                $user->password = $_POST['password1'];
                $changes = true;
            }
            else {
                Flash::create(__("The passwords don't match. Try again"), 'warning');
                return false;
            }
        }

        $vgs = (array)$_POST['volgroups'];
        $volkeys = $DB->{'spine,metadata'}->asList(array('spine.class' => 'Group', 'metadata.field' => 'GroupType', 'metadata.value' => array('vol', 'volpre')), 'spine.id');
        $volgroups = $Controller->get($volkeys, OVERRIDE);
        asort($volgroups);

        /**
         * Save group data
         */
        foreach($volgroups as $vg) {
            if(in_array($vg->ID, $vgs)) {
                if($vg->addMember($this->that)) {
                    $changes = true;
                }
            } else {
                if($vg->removeMember($this->that)) {
                    $changes = true;
                }
            }
        }

        $changes = (UInfoFields::save($this->that->ID)||$changes);
        $Controller->forceReload($this->that);
        if($changes) Flash::create(__('Your changes were saved'));
        return $changes;
    }

    /**
     * View the page for editing the user terms
     * @return string
     */
    function changeTerms() {
        global $CONFIG, $DB;
        $languages = google::languages((array)$CONFIG->Site->languages);
        $terms = $DB->{'aliases,content'}->asList(array('aliases.alias' => 'Terms', 'content.section' => 'Terms'), 'content.language,content.content', false, true);
        reset($terms);
        $norm = array('lang' => key($terms), 'text' => current($terms));
        $lTabs = array();
        foreach($languages as $l => $lang) {
            $lTabs[] = new Tab($lang,
                new htmlfield(__('Terms'), 'terms['.$l.']', (isset($terms[$l])?@$terms[$l]:google::translate(@$norm['text'], @$norm['lang'], $l)))
            );
        }

        $form = new Form('saveTerms');
        return '<div class="nav"><a href="'.url(null, 'id').'">'.icon('small/arrow_left').__('Back').'</a></div>'
                .$form->collection(new Tabber('tT', $lTabs));
    }

    function editTab() {
        $_REQUEST->addType('view', 'string');
        $_REQUEST->addType('lang', 'string');
        $_REQUEST->setType('ch', 'numeric');
        global $Controller, $CONFIG, $DB, $USER;
        $this->setContent('header', __('Editing user').': '.$this->that);
        $form = new Form('editUser');

        /**
         * User settings
         */
        global $SITE;

        $settingsform = $this->that->settings->getFormElements('usersettings');


        /**
         * Load voluntary groups
         * @var groups
         */
        $volkeys = $DB->{'spine,metadata'}->asList(array('spine.class' => 'Group', 'metadata.field' => 'GroupType', 'metadata.value' => array('vol', 'volpre')), 'spine.id');
        $volgroups = $Controller->get($volkeys, OVERRIDE);
        propsort($volgroups, 'Name');

        /**
         * Group membership page
         */
        $groups = $this->that->groups();
        $gTypes = array('vol' => array(), 'assigned' => array());
        foreach($groups as $group) {
            if(!$group->isMember($this->that)) continue;
            switch($group->GroupType) {
                case 'vol':
                case 'volpre':
                    $gTypes['vol'][] = $group->ID;
                    break;
                default:
                    $gTypes['assigned']['g'.$group->ID] = $group;
                    break;
            }
        }

        $checked = array();
        foreach($volgroups as $vg) {
            if(in_array($vg->ID, $gTypes['vol'])) $checked[] = $vg->ID;
        }

        asort($gTypes['assigned']);

        return array(
            (@$this->that->password !== 'LDAP'
                ? new Formsection(__('Login information'),
                        new Input(__('Username'), 'username', @$this->that->username),
                        new Password(__('Password'), 'password1'),
                        new Password(__('Password again'), 'password2')
                  )
                : null),
            new Formsection(__('Presentation'),
                new HTMLField(false, 'presentation', $this->that->getContent('presentation'))
            ),
            ($settingsform
                ? new Formsection(__('User settings'),
                    $settingsform
                  )
                : null
            ),
            @UInfoFields::edit($id, 'Tab'),
            new Formsection(__('Group membership'),
                new Fieldset(__('Assigned groups'), listify(arrayExtract($gTypes['assigned'], 'link', false, true))),
                (empty($volgroups)
                    ? null
                    : new checkset(__('Voluntary groups'), 'volgroups', $volgroups, $checked))
            )
        );
    }

    /**
     * Displays the page for editing a user
     * @param integer $id The id of the user to be edited
     * @return string
     */
    private function newUser() {
        global $CONFIG, $Controller;
        $form = new Form('newUser', url(null, 'id'));
        return '<div class="nav"><a href="'.url(null, 'id').'">'.icon('small/arrow_left').__('Back').'</a></div>'.$form->collection(
                    new Fieldset(__('Login information'),
                        new Hidden('edit', 'new'),
                        new Input(__('Username'), 'username'),
                        new Password(__('Password'), 'password1'),
                        new Password(__('Password again'), 'password2')
                    )
                );
    }

    /**
     * View the user-search form
     * @return string
     */
    private function findUser(){
        global $USER;
        $r = '';
        if($this->may($USER, EDIT))
            $r = '<div class="ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all">'
                .'<a href="'.url(array('view' => 'new'), 'id').'">'.icon('large/identity-32').__('Add user').'</a>&nbsp;&nbsp;'
                .'<a href="'.url(array('view' => 'terms'), 'id').'">'.icon('large/view_choose-32').__('Terms and conditions').'</a></div>';
        $uform = new Form('findUser', url(null, array('id')), false);
        return $r. $uform->collection(
            new Set(
                new Li(	new Input(__('Find user'), 'keyword', $_REQUEST['keyword']),
                        new Submit(__('Search')))
            )
        ).$this->userSearchResults();
    }

    /**
     * Display the results of a user search
     * @return string
     */
    function userSearchResults(){
        global  $DB, $Controller, $USER;
        if($_REQUEST['keyword'])
        {
            $results = array_unique(array_merge(
                        $DB->users->asList(array('username~' => '%'.$_REQUEST['keyword'].'%'), 'id', false, false, 'username', 'id'),
                        $DB->userinfo->asList(array('val~' => $_REQUEST['keyword']), 'id', false, false, false, 'id')
                    ));

            if(count($results) == 0) {
                return __('No results');
            }
            else {
                $r = '';
                $r .= '<h3>'.__('Search results').'</h3>';
                $r .= '<ul class="list">';
                $i=0;
                foreach($results as $id) {
                    if($id == NOBODY) continue;
                    $user = $Controller->{$id}('User', READ);
                    if($user) {
                        $r .= '<li class="'.($i%2?'odd':'even').'"><span class="fixed-width">'.$user.'</span><div class="tools">'
                        .($id == $USER->ID
                            || $user->memberOf(ADMIN_GROUP)
                            || !$this->may($USER, DELETE)?'':icon('small/delete', __('Delete'), url(array('del' => $user->ID), array('id', 'edit'))))
                        .($this->may($USER, EDIT)?icon('small/user_edit', __('Edit user'), url(array('edit' => $user->ID), array('id','edit'))):'')
                        .'</div></li>';
                        $i++;
                    }
                }
                $r .= '</ul>';
            }
        } else {

            $perpage = 100;
            $total = $DB->users->count(array('id!' => NOBODY));
            $pager = Pagination::getRange($perpage, $total);
            $users = $Controller->get($DB->users->asList(array('id!' => NOBODY), 'id', $pager['range']['start'].', '.$perpage, false, 'username'));
            natcasesort($users);

            $r='';
            if(count($users)>0) {
                $r='<ul class="limitheight flul">';
                $pre = false;
                $i=0;
                foreach($users as $user) {
                    $us = (string)$user;
                    if(strlen($us) == 0) $us = ' ';
                    if(strtoupper($us[0]) !== $pre) {
                        if($pre !== false) $r.='</ul>';
                        $pre = strtoupper($us[0]);
                        $r.='<li class="fletter">'.$pre.'<ul>';
                        $i=0;
                    }
                    $r .= '<li class="'.($i%2?'odd':'even').'"><span class="fixed-width">'.$us.'</span><div class="tools">'.($user->memberOf(ADMIN_GROUP)?'':icon('small/delete', __('Delete'), url(array('del' => $user->ID), array('id', 'edit')))).icon('small/user_edit', __('Edit user'), url(array('edit' => $user->ID), array('id','edit'))).'</div></li>';
                    $i++;
                }
                $r .= '</ul></li></ul>';
                if($total > $perpage) $r .= $pager['links'];
            }
        }
        return $r;
    }
}

?>
