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
class GroupAdmin extends Page{
    private $that = false;

    static public $edit_icon = 'small/group_edit';
    static public $edit_text = 'Edit group';

    function __construct($obj) {
        parent::__construct($obj->ID);
        $this->that = $obj;
    }

    function canEdit($obj) {
        return is_a($obj, 'Group');
    }

    /**
     * In this function, most actions of the module are carried out and the page generation is started, distibuted and rendered.
     * @return void
     * @see solidbase/lib/Page#run()
     */
    function run(){
        global $Templates, $USER, $CONFIG, $Controller, $DB;
        if(!$this->may($USER, READ|EDIT)) { errorPage('401'); return false; }
        if($this->saveChanges()) redirect(array('id' => $this->that->ID));

        global $Templates;
        $this->setContent('header', __('Editing').': <i>'.$this->that.'</i>');
        $this->setContent('main',
            new Formsection(__('Members'),$this->memberTab())
            .new Formsection('Edit',
                Form::quick(false, null,
                    $this->editTab()
                )
            )
        );

        $Templates->admin->render();
    }

    function saveChanges() {
        $changes = false;

        $_REQUEST->setType('delgroup', 'string');
        $_REQUEST->setType('editGroup', 'any');

        /**
         * Deletion of a group
         */
        if($_REQUEST['delgroup']) {
            if($this->that->mayI(DELETE)) {
                $g = $Controller->{$_REQUEST['delgroup']};
                if(is_a($g, 'Group')) {
                    $this->that->delete();
                    Flash::queue(__('The group was deleted and all privileges were removed'));
                    redirect(url());
                }
            }
        }

        $_POST->setType('presentation', 'any');
        if($_POST['presentation']) {
            $this->saveContent(array('presentation' => $_POST['presentation']));
        }


        $_REQUEST->setType('rem', 'numeric');
        $_REQUEST->setType('add', 'numeric');
        if($_REQUEST['add']) {
            if($this->that->addMember($_REQUEST['add'])) {
                if($_REQUEST['nGM'])
                    Flash::create(__('Group added as subgroup'));
                else
                    Flash::create(__('User added to group'), 'confirmation');
            } else Flash::create(__('Action failed'), 'warning');
        } elseif($_REQUEST['rem']) {
            if($this->that->removeMember($_REQUEST['rem'])) {
                Flash::create(__('User removed from group'), 'confirmation');
            }
            else {
                Flash::create(__('User could not be removed from group'), 'warning');
            }
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


        $_POST->setType('gtype', 'string');
        $_POST->setType('gimage', 'numeric');
        $_POST->setType('dispmembers', 'bool');
        if($_POST['gtype']) {
            $this->that->GroupType = $_POST['gtype'];
            $this->that->DisplayMembers = $_POST['dispmembers'];
            $this->that->Image = $_POST['gimage'];
        }
    }

    function groupStatistics() {
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
                if($u->isActive()) {
                    $activeMembers[] = $u;
                }
                if($Count >= $pagination['range']['stop']) break;
            }
            $r .= listify($activeMembers).$pagination['links'];
        } else {
            $r .= '<a href="'.url(array('viewMembers' => '1'), array('edit', 'with', 'stats')).'">'.__('View members').'</a>';
        }


        return $r;
    }

    /**
     * Displays the page form member administration of a group
     * @param integer $group The group which to edit
     * @return string
     */
    private function memberTab() {
        global $DB, $Controller, $USER;
        $r='';
        $volpre = ($this->that->GroupType == 'volpre');
        if(!$this->that) return;

        $_REQUEST->setType('nGM', 'anything');
        $_REQUEST->setType('keyword', 'string');
        $_REQUEST->setType('group_action', '#(copy|reset)_members#');
        $_REQUEST->setType('gid', 'numeric');
        $_REQUEST->setType('copy_to_group', 'numeric');

        $gform = new Form('nGM', url(null, array('edit', 'with', 'adm')), false);
        $groups = $DB->{'spine,metadata'}->asList(array(
            'spine.class' => 'Group',
            'metadata.field' => 'Name',
            'metadata.metameta' => $USER->settings['language'],
            'spine.id!' => array_merge(array($this->that->ID), $this->that->groups(false, 1))
        ), 'spine.id,metadata.value', false, true, 'metadata.value');
        $uform = new Form('findUser', url(null, array('edit', 'with', 'adm')), false);


        $i=0;
        $mem = array('','');
        $count = array(0,0);

        $res = $DB->group_members->get(array('group' => $this->that->ID));
        while($row = $DB->fetchAssoc($res)){
            $o = $Controller->{$row['user']};
            ++$count[(is_a($o, 'Group'))];
            $mem[(is_a($o, 'Group'))] .= '<li class="'.($i%2?'odd':'even').'"><span class="fixed-width">'.$o.'</span><div class="tools">'
                .($volpre ?	icon('small/add', __('Add user to group'), url(array('add' => $row['user']), array('edit', 'with')))
                        :	icon('small/delete', __('Remove user from group'), url(array('rem' => $row['user']), array('edit', 'with')))
                ).'</div></li>';
            $i++;
        }
        if($volpre) $r .= '<h1>'.__('Note!').'</h1>'. __('Non-members are displayed for preselected groups!');
        if(!empty($mem[0])) $r .= '<h2>'.($volpre? __('Non-member users') : __('Member users')).' ('.$count[0].')'.'</h2><ul class="list">'.$mem[0] .'</ul>';
        if(!empty($mem[1])) $r .= '<h2>'.__('Subgroups').' ('.$count[1].')'.'</h2><ul class="list">'.$mem[1].'</ul>';

        $gp = $this->that->groups(true);
        if(!empty($gp)) $r.= '<p><h3>'.__('Additionally, this group is a subgroup to the following groups: ').'</h3>'.join(', ', $gp).'</p>';

        $group_actions = '';
        if(!empty($mem[0]) || !empty($mem[1])) {
            $available_groups = $Controller->getClass('Group');
            propsort($available_groups, 'Name');
            $group_actions = new Formsection(__('Group actions'),
                Form::quick(url(false, array('edit', 'with')), __('Copy members'),
                    new Hidden('group_action', 'copy_members'),
                    new Li(new Select(__('Copy to group'), 'copy_to_group', $available_groups, false, false, __('Choose group')))),
                Form::quick(url(false, array('edit', 'with')), __('Reset all memberships'),
                    '<h3>Reset membership</h3>',
                    new Hidden('group_action', 'reset_members')
                )
            );
        }


        return array(
            $r,
            new Accordion(
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
                )
            ),
            $group_actions
        );
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
                            .	($volpre 	? 	icon('small/delete', __('Remove user from group'), url(array('rem' => $user->ID), array('edit', 'with','adm','keyword')))
                                            :	icon('small/add', __('Add user to group'), url(array('add' => $user->ID), array('edit', 'with','adm','keyword'))))
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
                    icon('small/group', __('Manage members'), url(array('adm' => $id), 'edit', 'with')))
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
    private function editTab() {
        $rows = $this->that->settings->getFormElements('groupsettings');
        if(!$rows) $rows = null;

        return new Formsection(__('Group settings'),
            new select(__('Group type'), 'gtype', array('assigned' => __('Assigned'), 'vol' => __('Voluntary'), 'volpre' => __('Voluntary, preselected')), @$this->that->GroupType),
            new HTMLField(__('Group presentation'), 'presentation', $this->getContent('presentation')),
            new Checkbox(__('Display members'), 'dispmembers', @$this->that->DisplayMembers),
            new ImagePicker(__('Group picture'), 'gimage', @$this->that->Image, false, false, true, $this->that),
            $rows
        );
    }
}

?>
