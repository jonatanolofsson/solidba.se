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
    public $privilegeGroup = 'Administrationpages';

    /**
     * Sets up the object
     * @param integer $id The ID of the object
     * @return void
     */
    function __construct($id=false) {
        parent::__construct($id);
        $this->suggestName('Users', 'en');
        $this->suggestName('Användare', 'sv');
        $this->setAlias('userEditor');

        $this->icon = 'small/user';
        $this->deletable = false;
    }

    function may($u, $lvl) {
        global $USER;
        if($USER->ID != NOBODY && ($lvl & READ)) return true;
        else return parent::may($u, $lvl);
    }

    /**
     * Contains actions and pageview-logic
     * @return void
     */
    function run(){
        global $Templates, $USER, $CONFIG, $Controller, $DB;

        $redo = false;
        /**
         * User input types
         */
        $_REQUEST->addType('edit', array('numeric', '#^new$#'));
        $_REQUEST->setType('del', 'numeric');
        $_REQUEST->setType('view', 'string');
        $_REQUEST->setType('keyword', 'string');
        $_POST->setType('terms', 'any', true);

        if(!$_REQUEST['edit']) {
            if(!$this->mayI(EDIT)) redirect(url(array('edit' => $USER->ID), 'id'));
        }

        $new = ($_REQUEST['edit'] == 'new');

        if(!($this->mayI(EDIT) || ($_REQUEST->numeric('edit') && $Controller->{$_REQUEST['edit']}(EDIT)))) errorPage('401');

        $redo = self::saveChanges();

        /**
         * Delete user
         */
        if($_REQUEST->numeric('del') && $this->may($USER, DELETE) && $Controller->{$_REQUEST['del']}('User') !== false) {
            $Controller->{$_REQUEST['del']}(OVERRIDE)->delete();
            Flash::create(__('User was deleted'));
        }

        /**
         * Save terms
         */
        self::saveTerms();
        /**
         * Display page for editing user
         */
        if($_REQUEST['view'] == 'new' || ($new && $redo)) {
            $this->content = array('header' => __('New user'), 'main' => $this->newUser());
        }
        elseif(!$new && ($_REQUEST['edit'] && ($redo || !$_REQUEST['updUserinfo']))) {
            $this->setContent('main', $this->edit($_REQUEST['edit']));
        }
        elseif($_REQUEST['view'] == 'terms') {
            $this->setContent('header', __('Terms and conditions'));
            $this->setContent('main', $this->changeTerms());
        }
        /**
         * Find a user to edit
         */
        else {
            Head::add($CONFIG->UI->jQuery_theme.'/jquery-ui-*', 'css-lib');
            $this->content = array('header' => $this->title,
                'main' => $this->findUser());
        }

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
        $redo = false;
        global $Controller, $DB;
        $_POST->setType('editUser', 'numeric');
        $_POST->setType('username', 'string');
        $_POST->setType('password1', 'string');
        $_POST->setType('password2', 'string');
        $_POST->setType('volgroups', 'numeric', true);
        $_POST->setType('editUser', 'any');
        $_POST->setType('edit', array('numeric', '$new$'));

        $new = ($_POST['edit'] == 'new');
        /**
         * Save the user
         */
        if(($new && $Controller->userEditor->mayI(EDIT))
            || ($_POST['editUser']
                && $_POST['edit']
                && $Controller->{$_POST['edit']}('User', OVERRIDE)
                && ($Controller->userEditor->mayI(EDIT) || $Controller->{$_POST['edit']}(EDIT)))) {
            do {
                if($new) {
                    $user = $Controller->newObj('User');
                    $_POST['edit'] = $user->ID;
                }
                else {
                    $user = $Controller->{$_POST['edit']}(OVERRIDE);
                }
                if(!$user) return false;
                if($_POST['username'] && $_POST['username'] != $user->username) {
                    if($DB->users->exists(array('username' => $_POST['username'],'id!' => $user->ID))) {
                        $redo = true;
                        Flash::create(__('Username is already in use'), 'warning');
                        break;
                    } else {
                        if(!$user) break;
                        $user->username = $_POST['username'];
                    }
                }
                if($_POST['password1']) {
                    if($_POST['password1'] === $_POST['password2']) {
                        $user->password = $_POST['password1'];
                    }
                    else {
                        Flash::create(__("The passwords don't match. Try again"), 'warning');
                        $redo=true;
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
                        if(!$vg->isMember($user)) $vg->addMember($user);
                    } else {
                        $vg->removeMember($user);
                    }
                }

                UInfoFields::save($user->ID);
                $Controller->forceReload($user);
                if($new) Flash::create(__('A new user was created'));
                else Flash::create(__('Your changes were saved'));
            } while(false);
        }
        return $redo;
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

    function edit($id) {
        $_REQUEST->addType('view', 'string');
        $_REQUEST->addType('lang', 'string');
        $_REQUEST->setType('ch', 'numeric');
        global $Controller, $CONFIG, $DB, $USER;
        if(!is_object($id)) {
            $u = $Controller->{(string)$id}(EDIT, 'User');
        } else {
            $u = $id;
            $id = $u->ID;
            if(!$u->mayI(EDIT) || !is_a($u, 'User')) return false;
        }
        if(!$u) return false;
        $ch=false;
        if(in_array($_REQUEST['view'], array('content', 'revisions')) && $l = $_REQUEST['lang']) {
            if($ch = PageEditor::saveChanges($u)) {
                $_REQUEST->clear('view', 'lang');
            }
        }
        if($ch || $_REQUEST['ch']) Flash::create(__('Your changes were saved'), 'confirmation');

        if(in_array($_REQUEST['view'], array('content', 'revisions')) && $l = $_REQUEST['lang']) {
            $this->setContent('header', __('Editing user').': '.$u.' <i>['.google::languages($l).']</i>');

            $form = new Form('editPresentations');

            $groups = $u->groups;

            $presentations = array();
            foreach($groups as $g) {
                if(!in_array($g->ID, array(EVERYBODY_GROUP, MEMBER_GROUP)) && $g->DisplayMembers)
                    $presentations['g'.$g->ID] = $g->Name;
            }
            asort($presentations);
            $presentations = array_merge(array('g'.MEMBER_GROUP => $Controller->{(string)MEMBER_GROUP}->Name), $presentations);

            switch($_REQUEST['view']) {
                case 'content':
                    return PageEditor::contentEditor($u, $l, $presentations);
                    break;
                case 'revisions':
                    return PageEditor::viewRevisions($u, $l, $presentations);
                    break;
            }

        } else {
            $this->setContent('header', __('Editing user').': '.$u);
            $form = new Form('editUser');

            /**
             * Presentations page
             */
            $langCList = '';
            $languages = google::languages((array)$CONFIG->Site->languages);
            ksort($languages);
            $i=1;
            foreach($languages as $l => $lang) {
                $langCList .= '<li class="'.($i++%2?'odd':'even').'"><span class="fixed-width"><a href="'.url(array('view' => 'content', 'lang' => $l), array('id', 'edit'), false).'">'.__($lang).'</a></span><div class="tools">'.icon('small/disk_multiple', __('History'), url(array('view' => 'revisions', 'lang' => $l), array('id', 'edit'), false)).'</div></li>';
            }
            $langCList = '<ul class="list">'.$langCList.'</ul>';

            /**
             * User settings
             */
            global $SITE;

            $settingsform = $u->settings->getFormElements('usersettings');
            $userSettingsTab = ($settingsform?
                new Tab(__('User settings'), $settingsform)
            :null);


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
            $groups = $u->groups();
            $gTypes = array('vol' => array(), 'assigned' => array());
            foreach($groups as $group) {
                if(!$group->isMember($u)) continue;
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

            $gMemb = 	new Fieldset(__('Assigned groups'), listify($gTypes['assigned']))
                        .(empty($volgroups)?null:new checkset(__('Voluntary groups'), 'volgroups', $volgroups, $checked));

            return $form->collection(
                new Hidden('edit', $id),
                new Tabber('upane',
                    (@$u->password !== 'LDAP'?new Tab(__('Login information'),
                        new Input(__('Username'), 'username', @$u->username),
                        new Password(__('Password'), 'password1'),
                        new Password(__('Password again'), 'password2')):null),
                    @UInfoFields::edit($id, 'Tab'),
                    new Tab(__('Presentations'), $langCList),
                    $userSettingsTab,
                    new EmptyTab(__('Group membership'),$gMemb)
                )
            );
        }
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
