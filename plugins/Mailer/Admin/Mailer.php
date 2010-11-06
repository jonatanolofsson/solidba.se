<?php
class Mailer extends page {
    public $privilegeGroup = 'Administrationpages';
    static public function installable() {return __CLASS__;}
    static public function uninstallable() {return __CLASS__;}

    function install() {
        $o = $Controller->newObj('Mailer')->move('last', 'adminMenu');
        Settings::registerSetting('email_sender', 'text', false, 3);
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
        $this->suggestName('Mailer');
        $this->alias = 'mailer';

        $this->icon = 'small/email';
        $this->deletable = false;
    }

    function run() {
        if(!$this->mayI(READ|EDIT)) return false;
        global $USER, $Controller, $DB, $Templates, $SITE;

        $_POST->setType('newMail', 'numeric');
        $_POST->setType('from', 'numeric');
        $_POST->setType('recipients', 'numeric', true);
        $_POST->setType('subject', 'string');
        $_POST->setType('message', 'any');
        $_POST->setType('personal', 'string');
        $_POST->setType('sendd', 'string');
        $_POST->setType('sendt', 'string');
        $_REQUEST->setType('to', 'numeric');
        $_REQUEST->setType('eedit', 'numeric');
        $_REQUEST->setType('edelete', 'numeric');
        $_POST->setType('save', 'string');
        $_POST->setType('approve', 'string');
        $_POST->setType('continue', 'string');
        $_POST->setType('saveac', 'string');
        $_POST->setType('bypass', 'any');

        if($_REQUEST['eedit']) {
            if($_POST['save'] || $_POST['saveac']) {
                $msg = $DB->massmail->getRow(array('msg_id' => $_REQUEST['eedit']));
                if($msg && ($msg['author'] == $USER->ID || $this->mayI(EDIT))) {
                    if($_POST->valid('recipients', 'subject', 'message')) {
                            $approved = ($_POST['approve']&&$this->mayI(EDIT));
                            $DB->massmail->update(array(
                                'author' => $USER->ID,
                                '#!written'.($_REQUEST['save']?'':'NO_INSERT') => 'UNIX_TIMESTAMP()',
                                'from' => $_POST['from'],
                                'recipients' => $_POST['recipients'],
                                'subject' => $_POST['subject'],
                                'message' => $_POST['message'],
                                'personal' => ($_POST['personal']?'yes':'no'),
                                'approved' => ($approved?$USER->ID:'0'),
                                'send' => strtotime($_POST['sendd'].' '.$_POST['sendt']),
                                'override_membercheck' => ($_POST['bypass'] && $Controller->{(string)ADMIN_GROUP}(OVERRIDE)->isMember($USER))
                            ), array('msg_id' => $msg['msg_id']));
                                    
                            if(!($_POST['personal'] || $approved || $Controller->{(string)ADMIN_GROUP}(OVERRIDE)->isMember($USER))) new Notification(
                                __('New email'),
                                __('A new email has been queued on ').url(array('id' => 'mailer')),
                                $Controller->{ADMIN_GROUP}(OVERRIDE)
                            );
                            
                            $_POST->clear('newMail', 'from','recipients','subject','message','personal','send', 'bypass');
                            
                            if($_POST['save']) {
                                Flash::create(__('Changes were saved'), 'confirmation');
                            }
                            else {
                                Flash::create(__('Email saved and queued for sending'), 'confirmation');
                            }
                            $_POST->clear('from','recipients','subject','message','personal','send');
                    } else Flash::create(__('Invalid email. Please try again'), 'warning');
                }
            }
            if(($_POST['saveac'] || $_POST['continue']) && $this->mayI(EDIT)) {
                if($_POST['continue']) $_POST->clear('from','recipients','subject','message','personal','send');
                $_REQUEST['eedit'] = $DB->massmail->getCell(array('approved' => '0', 'personal' => 'no', 'msg_id!' => $_REQUEST['eedit']), 'msg_id', 'written ASC');
            } elseif($_POST['save']) $_REQUEST->clear('eedit');
        } elseif($_REQUEST['edelete']) {
            $msg = $DB->massmail->getRow(array('msg_id' => $_REQUEST['edelete']));
            if($msg && ($msg['author'] == $USER->ID || $this->mayI(DELETE))) {
                $DB->massmail->delete(array('msg_id' => $msg['msg_id']));
                Flash::create(__('Email deleted'), 'warning');
            }
            unset($msg);
        }

        if($_POST['newMail']) {
            if($_POST->validNotEmpty('recipients', 'subject', 'message')) {
                $approved = ($_POST['approve']&&$this->mayI(EDIT));
                $DB->massmail->insert(array(
                    'author' => $USER->ID,
                    '#!written' => 'UNIX_TIMESTAMP()',
                    'from' => $_POST['from'],
                    'recipients' => $_POST['recipients'],
                    'subject' => $_POST['subject'],
                    'message' => $_POST['message'],
                    'personal' => ($_POST['personal']?'yes':'no'),
                    'approved' => ($approved?$USER->ID:'0'),
                    'send' => ($_POST['send']?strtotime($_POST['send']):time()),
                    'override_membercheck' => ($_POST['bypass'] && $Controller->{(string)ADMIN_GROUP}(OVERRIDE)->isMember($USER))
                ));
                        
                if(!($_POST['personal'] || $approved || $Controller->{(string)ADMIN_GROUP}(OVERRIDE)->isMember($USER))) new Notification(
                    __('New email'),
                    __('A new email has been queued on ').url(array('id' => 'mailer')),
                    $Controller->{ADMIN_GROUP}(OVERRIDE)
                );
                $_POST->clear('newMail', 'from','recipients','subject','message','personal','send', 'bypass');
                if($this->mayI(EDIT)) {
                    if($_REQUEST['approve']) Flash::create(__('Email saved and approved for sending'), 'confirmation');
                    else Flash::create(__('Email saved'), 'confirmation');
                }
                else Flash::create(__('Email has been queued for approval'), 'confirmation');
            } else Flash::create(__('Invalid email. Please try again'), 'warning');
        }

        $recipients = $Controller->get($DB->spine->asList(array('class' => 'Group'), 'id'), OVERRIDE);
        foreach($recipients as &$name) $name = $name->Name;
        asort($recipients);


        if($_REQUEST['eedit']) $msg = $DB->massmail->getRow(array('msg_id' => $_REQUEST['eedit']));
        if($_REQUEST['eedit'] && $msg && ($msg['author'] == $USER->ID || $this->mayI(EDIT))) {
            if($msg['sent']) {
                $this->setContent('header', $msg['subject']);
                $r = '<div class="nav"><a href="'.url(null, 'id').'">'.icon('small/arrow_left').__('Back').'</a></div>'
                    .'<ul>'
                    .'<li><span class="label">'.__('Author').': </span>'.$Controller->{$msg['author']}->link().'</li>'
                    .'<li><span class="label">'.__('From').': </span>'.($msg['from']?$Controller->{$msg['from']}:__('Default')).'</li>'
                    .'<li><span class="label">'.__('Recipients').': </span>';
                $recipients = $Controller->get($msg['recipients']);
                $recs = array();
                foreach($recipients as $re) $recs[]= $re->link();
                $r .= join(', ', $recs)
                    .'</li>'
                    .'<li><span class="label">'.__('Sent').': </span>'.strftime('%e/%l, %R', $msg['sent']).'</li>'
                    .'<li><span class="label">'.__('Subject').': </span>'.$msg['subject'].'</li>'
                    .'<li><span class="label">'.__('Message').': </span><div class="message">'.$msg['message'].'</div></li>'
                    .'</ul>';
                $this->setContent('main', $r);
            } else {
                $valid_senders = false;
                if($Controller->{ADMIN_GROUP}(OVERRIDE)->isMember($USER))
                    $g = $Controller->getClass('Group', OVERRIDE, false, false);
                elseif($msg['author'] != $USER->ID && $author = $Controller->{$msg['author']}('User'))
                    $g = $author->groups + $USER->groups;
                else 
                    $g = $USER->groups;
                    
                $valid_senders = array();
                foreach($g as $gr) if($gr->getEmail()) $valid_senders[$gr->ID] = $gr->Name;
                asort($valid_senders);
    
                unset($valid_senders[EVERYBODY_GROUP]);
                unset($valid_senders[MEMBER_GROUP]);
                
                JS::loadjQuery(false);
                Head::add('$(function(){$(\'#recslide\').css("cursor", "pointer").toggle(function(){$(\'#recipients\').animate({height: 200}, 500)},function(){$(\'#recipients\').animate({height: 50}, 500)});});', 'js-raw');
                $eform = new Form('editMail', url(null, 'id'), false);
                $this->setContent('header', __('Edit email: ').$msg['subject']);
                
                $recip=(@$msg['recipients'][0]?$Controller->{$msg['recipients'][0]}(OVERRIDE, 'Page'):false);
                $this->setContent('main','<div class="nav"><a href="'.url(null, 'id').'">'.icon('small/arrow_left').__('Back').'</a></div>'.
                    $eform->set(
                        new Hidden('eedit', $_REQUEST['eedit']),
                        (($msg['approved'] && !$this->mayI(EDIT))?
                            __('This email has been approved for sending. If you edit it, the approval will be lost.')
                        :null),
                        new Select(__('From'), 'from', $valid_senders, ($_POST['from']?$_POST['from']:$msg['from']), false, __('Default')),
                            (is_a($recip,'Group')
                                ?new FormText(__('Recipients'),
                                        new Hidden('recipients[]', $msg['recipients'])
                                        .__('Posters on').': '.$recip->link()
                                    )
                                :new Select(__('Recipients'), 'recipients', $recipients, ($_POST['recipients']?$_POST['recipients']:$msg['recipients']), true, false, 'notempty')
                            ),
                        new Input(__('Subject'), 'subject', ($_POST['subject']?$_POST['subject']:$msg['subject'])),
                        new HTMLField(__('Message'), 'message', ($_POST['message']?$_POST['message']:$msg['message'])),
                        new Li(
                            new Datepicker(__('Send'), 'sendd', ($_POST['sendd']?$_POST['sendd']:date('Y-m-d',$msg['send']))),
                            new Timepickr(false, 'sendt', ($_POST['sendt']?$_POST['sendt']:date('h:i',$msg['send'])))
                        ),
                        new Checkbox(__('Personal draft'), 'personal', ($_POST['personal']?$_POST['personal']:$msg['personal'])==='yes'),
                        new Checkbox(__('Approve'), 'approve',($_POST['approve']?$_POST['approve']>0:$msg['approved']>0)),
                        ($Controller->{(string)ADMIN_GROUP}(OVERRIDE)->isMember($USER)
                            ?new Checkbox(__('Bypass member check'), 'bypass',($_POST['bypass']?$_POST['bypass']>0:$msg['override_membercheck']>0))
                            :null
                        ),
                        new Li(
                            new Submit(__('Save'), 'save'),
                            ($this->mayI(EDIT)?new Submit(__('Save and continue'), 'saveac'):null),
                            ($this->mayI(EDIT)?new Submit(__('Continue'), 'continue'):null)
                        )
                    )
                );
            }
        } else {
            if($Controller->{ADMIN_GROUP}(OVERRIDE)->isMember($USER)) $g = $Controller->getClass('Group', OVERRIDE, false, false);
            else $g = $USER->groups;
            $valid_senders = array();
            foreach($g as $gr) if($gr->getEmail()) $valid_senders[$gr->ID] = $gr->Name;
            asort($valid_senders);

            unset($valid_senders[EVERYBODY_GROUP]);
            unset($valid_senders[MEMBER_GROUP]);
            JS::loadjQuery(false);
            $nform = new Form('newMail', url(null, array('id', 'to')));
            $this->setContent('header', __('Email'));
            $o=($_REQUEST['to']?$Controller->{$_REQUEST['to']}(EDIT, 'Page'):false);
            $this->setContent('main',
                new Tabber('mail',
                    new EmptyTab(__('New mail'),
                        $nform->set(
                            ($valid_senders
                                ? new Select(__('From'), 'from', $valid_senders, $_POST['from'], false, __('Default'))
                                : new Hidden('from', "")
                            ),
                            ($_REQUEST['to'] && $o
                                ?new FormText(__('Recipients'),
                                        new Hidden('recipients[]', $_REQUEST['to'])
                                        .__('Posters on').': '.$o->link()
                                    )
                                :new Select(__('Recipients'), 'recipients', $recipients, $_POST['recipients'], true, false, 'notempty')
                            ),
                            new Input(__('Subject'), 'subject', $_POST['subject'], 'required'),
                            new HTMLField(__('Message'), 'message', $_POST['message']),
                            new Li(
                                new Datepicker(__('Send'), 'sendd', $_POST['sendd']),
                                new Timepickr(false, 'sendt', $_POST['sendt'])
                            ),
                            new Checkbox(__('Personal draft'), 'personal', $_POST['personal']),
                            ($Controller->{(string)ADMIN_GROUP}(OVERRIDE)->isMember($USER)
                                ?new Checkbox(__('Bypass member check'), 'bypass',$_POST['bypass'])
                                :null
                            ),
                            ($this->mayI(EDIT)?new Checkbox(__('Approve'), 'approve',$_REQUEST['approve']>0):null)
                        )
                    ),
                    new Tab(__('Personal drafts'),
                        $this->listEmails('personal')
                    ),
                    new Tab(__('Manage emails'),
                        $this->listEmails()
                    ),
                    ($this->mayI(EDIT)
                        ?	new Tab(__('Approve'),
                                $this->listEmails('new')
                            )
                        :null
                    )
                )
            );
        }
        $Templates->render();
    }

    function listEmails($view=false) {
        global $USER, $Controller, $DB;
        if($view == 'new') {
            $resource = $DB->massmail->get(array('approved' => '0', 'personal' => 'no'), false, false, 'written DESC');
        } elseif($view == 'personal'){
            $resource = $DB->massmail->get(array('personal' => 'yes'), false, false, 'written DESC');
        } else {
            $resource = $DB->massmail->get(array('approved>' => '0', 'personal' => 'no', 'author'.($this->mayI(EDIT)?'NO_SELECT':'') => $USER->ID), false, false, 'written DESC');
        }
        $r='';
        if(mysql_num_rows($resource)) {
            $table = new Table(new tableheader(__('Author'), __('From'), __('Recipients'), __('Subject')));
            $i=0;
            while($email = Database::fetchAssoc($resource)) {
                $recipients = $Controller->get($email['recipients']);
                $recs = array();
                foreach($recipients as $re) $recs[]= $re->link();
                
                $table->append(new tablerow(
                    $Controller->{$email['author']}->link(),
                    ($email['from']?$Controller->{$email['from']}:__('Default')),
                    join(', ', $recs),
                    '<a href="'.url(array('eedit' => $email['msg_id']), 'id').'">'.$email['subject'].'</a>',
                    '<span class="tools">'
                .icon(($email['sent']?'small/eye':'small/email_edit'), __(($email['sent']?'View':'Edit')), url(array('eedit' => $email['msg_id']), 'id'))
                .icon('small/delete', __('Delete'), url(array('edelete' => $email['msg_id']), 'id'))
                .($email['sent']?icon('large/network-16', __('Sent')):
                    ($email['approved']?icon('small/tick', __('Approved')):''))
                .'</span>'));
            }
            $r.=$table;
            return $r;
        }
        else return __('Empty');
    }
}
?>
