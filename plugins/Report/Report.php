<?php
class Report extends Base{
    static function installable(){return __CLASS__;}
/* 	protected $KeepRevisions = false; */

    function install() {
        global $Controller;
        $Controller->newObj('Report')->setAlias('Report');
        //FIXME: Add privlidge setting
    }

    function __construct($id, $lang=false){
        parent::__construct($id, $lang);
        $this->alias = 'Report';
    }

    function run(){
        $this->sendFeedback();
        echo 'Report sent';
    }

    function sendFeedback(){
        global $Controller, $USER, $SITE;
        $_POST->setType('feedback','string');
        $_POST->setType('description','string');

        $report = '<h1>Feedback report</h1><p><b>Generated report from '.$SITE->Name.' user feedback form.</b></p>
            <table width="100%" border="1" cellspacing="0" cellpadding="5"><tbody>'
                .'<tr><td>'.__('Feedback').     '</td><td>'.$_POST['feedback'].'</td></tr>'
                .'<tr><td>'.__('Description').  '</td><td>'.$_POST['description'].'</td></tr>'
                .'<tr><td>'.__('Page').         '</td><td>'.$_SERVER['HTTP_REFERER'].'</td></tr>'
                .'<tr><td>'.__('User').         '</td><td>'.$USER.' ('.$USER->getEmail().')'.'</td></tr>'
                .'<tr><td>'.__('User agent').   '</td><td>'.$_SERVER['HTTP_USER_AGENT'].'</td></tr>'
            .'</tbody></table>';

		$Controller->{(string)ADMIN_GROUP}(OVERRIDE)->mail(
            MailTools::template($report),
            MailTools::headers("Yweb".' <no-reply@ysektionen.se>', "Yweb Feedback Report")
        );
    }
}
?>
