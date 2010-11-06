<?
/**
 * This file contains the event logger
 * @package Base
 */

/**
 * The logger of solidba.se
 * @package Base
 */
class Log{
    /**
     * Constructor
     */
    function __construct($id){
        parent::__construct($id);
        global $CONFIG;
    }

    /**
     * Write to log. If the level of the log message is lower than the limit set in the configuration, the message is ignored.
     * <code>
     * Log::write('Configuration changed', 2);
     * </code>
     * @param string $msg The message to log. The code will also automatically record time, active user, IP and source of message.
     * @param integer $lvl The level of importance of the message. The higher, the more important
     * @return void
     */
    function write($msg, $lvl=1){
        global $DB, $CONFIG, $USER;
        if((int)@$CONFIG->Logging->Filter_level > $lvl) return;
        
        $CONFIG->Logging->setType('Type', 'select', array('none' => 'None', 'db' => 'DB', 'file' => 'File'));
        $src='?';
        if(isset($this)) $src = get_class($this).'('.$this->ID.')';
        switch(strtolower(@$CONFIG->Logging->Type)){
            case 'db':
                $DB->log->insert(array('remote_addr' => $_SERVER['REMOTE_ADDR'],'user' => $USER->ID, 'level' => $lvl, 'source' => $src, '#!time' => 'NOW()','message' => $msg));
                break;
            case 'file':
                $r=fopen(PRIV_PATH.'/log', 'ab');
                fwrite($r, join("\t", array(date("Y-m-d H:i:s"),$src,$lvl,$_SERVER['REMOTE_ADDR'],$USER->ID,str_replace("\t", '', $msg))));
                fclose($r);
                break;
        }
    }
}
?>