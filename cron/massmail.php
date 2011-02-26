<?php
// Konfiguration:
error_reporting(E_ALL);$i=0;
chdir('..');
//FIXME: Korrigera sökväg efter flytt
include './lib/init.php';

$Messages_Resource = $DB->massmail->get(array('approved!' => 0, 'personal' => 'no', 'sent' => 0, '#!send<=' => 'UNIX_TIMESTAMP()'));

while(false !== ($Message = Database::fetchAssoc($Messages_Resource)))
{
    try {
        $sent_to = array();
        $Message['message'] = str_replace(array('"?id=', '"/'), '"https://www.ysektionen.se/', $Message['message']);
        if(!is_array($Message['recipients'])) continue;
        foreach($Message['recipients'] as $RecipientGroup)
        {
            $RecipientGroup = $Controller->get($RecipientGroup, OVERRIDE, false, false);
            switch(true) {
                case is_a($RecipientGroup, 'Group'):
                    $Recipients = $RecipientGroup->memberUsers(false, true);
                    break;
                case is_a($RecipientGroup, 'Page'):
                    $Recipients = $RecipientGroup->Form->getPosterIDs();
                    break;
                default: continue 2;
            }
            $Recipients = array_unique($Recipients);
            foreach($Recipients as $Recipient) {
                if(in_array($Recipient, $sent_to)) continue;
                $sent_to[] = $Recipient;
                $Recipient = $Controller->get($Recipient, OVERRIDE, false, false);
                if(!is_object($Recipient) || !is_a($Recipient, 'User')) continue;
                if(!$Message['override_membercheck'] && !($Recipient->isActive())) continue;

                $namn['full']   = @$Recipient->userinfo['cn'];
                $namn['first']  = @$Recipient->userinfo['givenName'];
                $namn['sur']    = @$Recipient->userinfo['sn'];
                $msg = str_replace(array('{name}', '{firstname}', '{surname}'), array($namn['full'], $namn['first'], $namn['sur']), $Message['message']);
                $text = html_entity_decode(strip_tags(preg_replace('#<(p|br|/p)[^>]*>#i', "\n", $msg)), ENT_COMPAT, 'UTF-8');

                $hdrs = array();
                if($Sender = $Controller->{(string)$Message['from']}(OVERRIDE)) {
                    $hdrs['From'] = $Sender.' <'.($Sender->getEmail()? $Sender->getEmail() : $CONFIG->Mail->Sender_email).'>';
                }
                $hdrs['Subject'] = $Message['subject'];
                $Recipient->mail(MailTools::template($msg), $hdrs, $text);
                //echo ++$i.": ".$Recipient.", ".memory_get_usage()."\n";
                $Recipient = false;
            }
        }
    } catch(Exception $e) {}

    $DB->massmail->update(array('#!sent' => 'UNIX_TIMESTAMP()'), array('msg_id' => $Message['msg_id']));
}
?>
