<?php

/**
 * @author Jonatan Olofsson [joolo]
 * @version 1.1
 * @license http://creativecommons.org/licenses/by-nc/3.0/ Creative Commons Attribution-Noncommercial 3.0 Unported License
 * @package Privileges
 */
/**
 * Provides the default solidba.se interface for administrating groups
 * @package Privileges
 */
class AdminGroups extends Page{
    public $privilegeGroup = 'Administrationpages';

    /**
     * Setup the page
     * @param integer $id The object's ID
     * @return void
     */
    function __construct($id=false) {
        parent::__construct($id);
        $this->alias = 'adminGroups';
        $this->suggestName('Groups');

        $this->icon = 'small/group_edit';
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
        $redo = false;

        /**
         * User input types
         */
        $_REQUEST->setType('newGroup', 'any');
        $_REQUEST->setType('gtype', 'string');
        $_REQUEST->setType('groupname', 'string');
        $_REQUEST->setType('groupdesc', 'string');
        $_REQUEST->setType('delgroup', 'string');
        $_REQUEST->setType('editGroup', 'any');
        $_REQUEST->setType('edit','numeric');
        $_REQUEST->setType('adm','numeric');
        $_REQUEST->setType('madd','numeric');
        $_REQUEST->setType('stats','numeric');

        /**
         * Creation of a new group
         */
        if($_REQUEST['newGroup'] && $this->may($USER, EDIT)) {
            if(!$DB->exists("SELECT spine.id FROM spine, metadata WHERE spine.id = metadata.id AND spine.class='Group' AND metadata.field='Name' AND metadata.value='".Database::escape($_REQUEST['edit'])."' AND metadata.metameta='".Database::escape($_REQUEST['lang'])."'")) {
                if($_REQUEST->nonempty('groupname')) {
                    $g = $Controller->newObj('Group');
                    $g->Name = $_REQUEST['groupname'];
                    $g->GroupType = $_REQUEST['gtype'];
                    $Controller->forceReload($g);
                    $g->description = $_REQUEST['groupdesc'];
                    Flash::create(__('A new group was created'), 'confirmation');
                }
                else {
                    Flash::create(__('Groupname empty. Try again'));
                }
            } else {
                Flash::create(__('Group name busy. Try again'), 'warning');
            }
        }
        /**
         * Deletion of a group
         */
        elseif($_REQUEST['delgroup']) {
            if($this->may($USER, DELETE)) {
                $g = $Controller->{$_REQUEST['delgroup']};
                if(is_a($g, 'Group')) {
                    $g->delete();
                    Flash::create(__('The group was deleted and all privileges were removed'));
                }
            }
        }

        /**
         * Add a page to the menu
         */
        elseif($_REQUEST['madd']) {
            if($Controller->menuEditor->mayI(EDIT) && $obj = $Controller->{$_REQUEST['madd']}('Group')) {
                $obj->move('last');
                redirect(url(array('id' => 'menuEditor', 'status' => 'ok')));
            }
        }

        /**
         * Here, the page request and permissions decide what should be shown to the user
         */
        if($_REQUEST['stats'] && $a = $Controller->{$_REQUEST['stats']}(OVERRIDE, 'Group')) {
            $this->setContent('header', __('Group statistics').': '.$a->Name);
            $this->setContent('main', $this->groupStatistics($a));
        }elseif($_REQUEST['adm'] && $this->may($USER, EDIT) && !in_array($_REQUEST['adm'], array(EVERYBODY_GROUP, MEMBER_GROUP)) && $a = $Controller->{$_REQUEST['adm']}('Group')) {
            $this->content = array('header' => __('Members of').' <i>'.$a->Name.'</i>', 'main' => $this->memberAdm($_REQUEST['adm']));
        }
        elseif($_REQUEST['edit'] && $this->may($USER, EDIT) && ($redo || !$_REQUEST['editGroup'])) {
            $this->edit($_REQUEST['edit']);
        } else {
            $this->content = array('header' => __('Groups'), 'main' => $this->selectGroup());
        }

        $Templates->admin->render();
    }

    function groupStatistics($group) {
        $_REQUEST->setType('viewMembers', 'any');
        $r = '';

        $directMembers = $group->memberUsers(true, true);
        $allMembers = $group->memberUsers(false, true);

        $statsTable = new Table(
            new tablerow(__('Direct users').': ', count($directMembers)),
            new tablerow(__('All users').': ', $memcount = count($allMembers))
        );

        $r .= $statsTable;

        if($_REQUEST['viewMembers']) {
            global $Controller;
            Base::preload(array_slice($allMembers, 0, 100), false);
            $pagination = Pagination::getRange(100, $memcount);
            $Count = 0;
            $activeMembers = array();
            foreach($allMembers as $member_id) {
                if($pagination['range']['start'] > $Count++) continue;
                $u = $Controller->get($member_id, OVERRIDE, false, false);
                if($u->isActiveUser()) {
                    $activeMembers[] = $u;
                }
                if($Count >= $pagination['range']['stop']) break;
            }
            $r .= listify($activeMembers).$pagination['links'];
        } else {
            $r .= '<a href="'.url(array('viewMembers' => '1'), array('id', 'stats')).'">'.__('View members').'</a>';
        }


        return $r;
    }

    /**
     * Displays the page form member administration of a group
     * @param integer $group The group which to edit
     * @return string
     */
    private function memberAdm($group) {
        global $DB, $Controller, $USER;
        $r='';
        $g = $Controller->$group(OVERRIDE);
        $volpre = ($g->GroupType == 'volpre');
        if(!$g) return;

        $_REQUEST->setType('rem', 'numeric');
        $_REQUEST->setType('add', 'numeric');
        $_REQUEST->setType('nGM', 'anything');
        $_REQUEST->setType('keyword', 'string');
        $_REQUEST->setType('group_action', '#(copy|reset)_members#');
        $_REQUEST->setType('gid', 'numeric');
        $_REQUEST->setType('copy_to_group', 'numeric');

        if($_REQUEST['add']) {
            if($g->addMember($_REQUEST['add'])) {
                if($_REQUEST['nGM'])
                    Flash::create(__('Group added as subgroup'));
                else
                    Flash::create(__('User added to group'), 'confirmation');
            } else Flash::create(__('Action failed'), 'warning');
        } elseif($_REQUEST->valid('rem')) {
            if($g->removeMember($_REQUEST['rem']))
                Flash::create(__('User removed from group'), 'confirmation');
            else
                Flash::create(__('User could not be removed from group'), 'warning');
        }

        if($_REQUEST['group_action'] && $_REQUEST['gid']) {
            if($_REQUEST['group_action'] == 'reset_members') {
                if($rgroup = $Controller->{$_REQUEST['gid']}('Group')) {
                    $rgroup->resetMembers();
                    Flash::create(__('Members removed'), 'confirmation');
                }
            }
            elseif($_REQUEST['group_action'] == 'copy_members') {
                if(($from_group = $Controller->{$_REQUEST['gid']}('Group'))
                    && ($to_group = $Controller->{$_REQUEST['copy_to_group']}('Group'))) {
                    $to_group->addMembers($from_group->MEMBERS);
                    Flash::create(__('Members copied'), 'confirmation');
                }
            }
        }

        $res = $DB->group_members->get(array('group' => $group));

        $gform = new Form('nGM', url(null, array('id', 'adm')), false);
        $groups = $DB->{'spine,metadata'}->asList(array('spine.class' => 'Group', 'metadata.field' => 'Name', 'metadata.metameta' => $USER->settings['language'], 'spine.id!' => array_merge(array($g->ID), $g->groups(false, 1))), 'spine.id,metadata.value', false, true, 'metadata.value');
        $uform = new Form('findUser', url(null, array('id', 'adm')), false);
        $r = '
        <div>
        '.new Accordion(
            __('Users'),
            $uform->collection(
                new Set(
                    new Li(
                        new Input(__('Find user'), 'keyword', $_REQUEST['keyword']),
                        new Submit(__('Search'))
                    )
                )
            ).$this->userSearchResults($volpre),
            __('Groups'),
            $gform->collection(
                new Set(
                    new Li(	new Select(__('Choose group'), 'add', $groups, false, false, __('Choose group')),
                            new Submit(($volpre?__('Remove'):__('Add'))))
                )
            )).'
        </div>';
        $i=0;
        $mem = array('','');
        $count = array(0,0);
        while($row = $DB->fetchAssoc($res)){
            $o = $Controller->{$row['user']};
            ++$count[(is_a($o, 'Group'))];
            $mem[(is_a($o, 'Group'))] .= '<li class="'.($i%2?'odd':'even').'"><span class="fixed-width">'.$o.'</span><div class="tools">'
                .($volpre ?	icon('small/add', __('Add user to group'), url(array('add' => $row['user']), array('id', 'adm')))
                        :	icon('small/delete', __('Remove user from group'), url(array('rem' => $row['user']), array('id', 'adm')))
                ).'</div></li>';
            $i++;
        }
        if($volpre) $r .= '<h1>'.__('Note!').'</h1>'. __('Non-members are displayed for preselected groups!');
        if(!empty($mem[0])) $r .= '<h2>'.($volpre? __('Non-member users') : __('Member users')).' ('.$count[0].')'.'</h2><ul class="list">'.$mem[0] .'</ul>';
        if(!empty($mem[1])) $r .= '<h2>'.__('Subgroups').' ('.$count[1].')'.'</h2><ul class="list">'.$mem[1].'</ul>';

        $gp = $g->groups(true);
        if(!empty($gp)) $r.= '<p><h3>'.__('Additionally, this group is a subgroup to the following groups: ').'</h3>'.join(', ', $gp).'</p>';

        if(!empty($mem[0]) || !empty($mem[1])) {
            $available_groups = $Controller->getClass('Group');
            propsort($available_groups, 'Name');
            $r .= '<h2>'.__('Group actions').'</h2>'
                .Form::quick(url(false, array('id', 'adm')), __('Copy members'),
                    new Hidden('group_action', 'copy_members'),
                    new Hidden('gid', $g->ID),
                    new Li(new Select(__('Copy to group'), 'copy_to_group', $available_groups, false, false, __('Choose group'))))
                .Form::quick(url(false, array('id', 'adm')), __('Reset all memberships'),
                    '<h3>Reset membership</h3>',
                    new Hidden('group_action', 'reset_members'),
                    new Hidden('gid', $g->ID));
        }


        return '<div class="nav"><a href="'.url(null, 'id').'">'.icon('small/arrow_left').__('Back').'</a></div>'.$r;
    }

    /**
     * Display the result of a search query among users
     * @return string
     */
    function userSearchResults($volpre){
        global  $DB, $Controller;
        if($_REQUEST->nonempty('keyword'))
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
                $r .= '<b>'.__('Search results').'</b>';
                $r .= '<ul class="list">';
                $i=0;
                foreach($results as $id) {
                    $user = $Controller->{$id}('User', READ);
                    if($user) {
                        $r .= '<li class="'.($i%2?'odd':'even').'"><span class="fixed-width">'.$user.'</span><div class="tools">'
                            .	($volpre 	? 	icon('small/delete', __('Remove user from group'), url(array('rem' => $user->ID), array('id','adm','keyword')))
                                            :	icon('small/add', __('Add user to group'), url(array('add' => $user->ID), array('id','adm','keyword'))))
                        .'</li>';
                        $i++;
                    }
                }
                $r .= '</ul>';
                return $r;
            }
        } else return "";
    }

    /**
     * Displays a list of the groups that can be administered
     * @return string
     */
    private function selectGroup() {
        global $DB, $Controller, $USER;

        $groups = $DB->spine->asList(array('class' => 'Group'), 'id');

        $g='';
        if(count($groups)>0) {
            $g='<ul class="flul">';
            $pre = false;
            $i=0;
            $groupObjs = $Controller->get($groups, OVERRIDE);
            uasort($groupObjs, create_function('$a,$b', 'return strnatcasecmp($a->Name, $b->Name);'));
            foreach($groupObjs as $id => $group) {
                $groupName = $group->Name;
                if(strtolower(@$groupName[0]) !== $pre) {
                    if($pre !== false) $g.='</ul></li>';
                    $pre = strtolower(@$groupName[0]);
                    $g.='<li class="fletter">'.strtoupper(@$groupName[0]).'<ul>';
                    $i=0;
                }
                $g .= '<li class="'.($i%2?'odd':'even').'"><span class="fixed-width">'.$groupName.'</span><div class="tools">'
                .(in_array($id, array(EVERYBODY_GROUP, MEMBER_GROUP)) || !$this->may($USER, EDIT)?'':
                    icon('small/group', __('Manage members'), url(array('adm' => $id), 'id')))
                .(!$this->may($USER, EDIT)?'':icon('small/pencil', __('Edit group'), url(array('edit' => $id), 'id')))
                .(in_array($id, array(ADMIN_GROUP, EVERYBODY_GROUP, MEMBER_GROUP))?
                    icon('small/lock', __('This group is locked')):
                    ($this->may($USER, DELETE)?icon('small/delete', __('Delete'), url(array('delgroup' => $id), 'id')):''))
                .($Controller->menuEditor->mayI(EDIT)
                    ? icon('small/page_add', __('Add group page to menu'), url(array('madd' => $id), 'id'))
                    : '')
                .icon('small/script', __('View group statistics'), url(array('stats' => $id), 'id'))
                .'</div></li>';
                $i++;
            }
            $g .= '</ul></li></ul>';
        }
        if($this->may($USER, EDIT)) {
        $form = new Form('newGroup', url(null, 'id'));
            return new Tabber('groups',
                new EmptyTab(__('Select group'),
                    $g
                ),
                new EmptyTab(__('Create group'),
                    $form->collection(
                        new Fieldset(__('New group'),
                            new Input(__('Name'), 'groupname'),
                            new select(__('Group type'), 'gtype', array('assigned' => __('Assigned'), 'vol' => __('Voluntary'), 'volpre' => __('Voluntary, preselected'))),
                            new TextArea(__('Description'), 'groupdesc')
                        )
                    )
                )
            );
        }
        else return $g;
    }

    /**
     * Displays the page for editing a group
     * @param integer $id The ID of the group which to edit
     * @return string
     */
    private function edit($id) {
        global $Controller;
        $obj = $Controller->{(string)$id}(EDIT);
        if(!$obj) return false;
        //FIXME: Duplicate groupnames possible
        PageEditor::saveChanges($obj);

        $_POST->setType('gtype', 'string');
        $_POST->setType('gimage', 'numeric');
        $_POST->setType('dispmembers', 'numeric');

        if($obj->mayI(EDIT)) {
            if($_POST['gtype']) {
                $obj->GroupType = $_POST['gtype'];
                $obj->DisplayMembers = isset($_POST['dispmembers']);
                $obj->Image = $_POST['gimage'];
            }
        }
        $_REQUEST->setType('ch', 'numeric');
        $rows = $obj->settings->getFormElements('groupsettings');
        if(!$rows) $rows = null;

        $groupTab = new Tab(__('Group settings'),
                        new select(__('Group type'), 'gtype', array('assigned' => __('Assigned'), 'vol' => __('Voluntary'), 'volpre' => __('Voluntary, preselected')), @$obj->GroupType),
                        new Checkbox(__('Display members'), 'dispmembers', @$obj->DisplayMembers),
                        new ImagePicker(__('Group picture'), 'gimage', @$obj->Image, false, false, true, $obj),
                        $rows
                    );
        $this->setContent('header', __('Editing').': <i>'.$obj.'</i>');
        $this->setContent('main', PageEditor::editor($id, $groupTab));
    }
}

?>
