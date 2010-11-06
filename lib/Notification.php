<?php
class Notification {
    public $subject;
    public $message;
    public $recipients;
    
    function __construct($subject = false, $msg = false, $recipients = false, $send = true) {
        $this->subject = $subject;
        $this->message = $msg;
        $this->recipients = $recipients;
        if($send) $this->send();
    }
    
    function send() {
        global $Controller;
        if(!($this->subject && $this->message && $this->recipients)) return false;
        if(!is_array($this->recipients)) $this->recipients = array($this->recipients);
        
        foreach($this->recipients as $obj) {
            if(!is_object($obj)) $obj = $Controller->get($obj, OVERRIDE, false, false);
            $obj->mail($this->html($this->message), array('Subject' => $this->subject), $this->message);
        }
    }
    
    function html($message) {
        return nl2br($message);
    }
}