<?php
/**
 * Replace email addresses with a flash image of the address
 * @author Jonatan Olofsson
 *
 */
class SafeEmails {
    static $DoNottouch = array('option', 'textarea');
    
    /**
     * Replaces all occurences of email addresses within a text 
     * that will not break the html. Addresses within form elements and
     * tags are not replaced.
     * 
     * @param string $text Text to search for emails
     * @return string The text with encrypted emails
     */
    function replace($text) {
        if(!$text) return $text;
        $email_regexp = '([A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4})';
        $text = preg_replace_callback('#<('.join('|', self::$DoNottouch).')[^>]*>.*?</\1>#is', array('dntHash', 'mailhash'), $text);
        $text = preg_replace_callback('#\<a[^\>]* href="mailto:'.$email_regexp.'"(?:[^>]*?)>([^\<]*)\<\/a\>'.'#is', array('SafeEmails', 'safeEmailsHelper'), $text);
        $text = preg_replace_callback('#<([^>]*?)'.$email_regexp.'(.*)>#is', array('dntHash', 'mailhash'), $text);
        $text = preg_replace_callback('#'.$email_regexp.'#is', array('SafeEmails', 'safeEmailsHelper'), $text);
        return dntHash::deHash($text);
    }
    
    /**
     * Replaces the emails with a flash text with the email
     * @param $match preg_replace match
     * @return string Flash html-code
     */
    function safeEmailsHelper($match) {
        JS::lib('flashurl', false, false);
        JS::loadjQuery(false, false);
        static $i=0;
        $i++;
        if(count($match) == 2) {
            $url = $match[0];
            $label = false;
        } else {
            $url = $match[1];
            $label = $match[2];
            if(isEmail($label)) $label = false;
        }
        $url = base64_encode($url);
        return '<span class="flashurl"><object id="flashurl'.$i.'" type="application/x-shockwave-flash" data="lib/swf/flashurl.swf"><param name="FlashVars" value="divID=flashurl'.$i.'&amp;encURL='.urlencode($url).($label?'&amp;urlLabel='.$label:'').'" /><param name="movie" value="/lib/swf/flashurl.swf" /><param name="wmode" value="transparent" /></object></span>';	
    }
}
?>