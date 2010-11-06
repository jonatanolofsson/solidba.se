<?php

/// Custom LiU import class.
class LiUImport extends LDAPImport
{
    function __construct($id=false){
        parent::__construct($id);
        $this->setAlias('liuImport');
        $this->Comment = 'Y har programkod 6cyyy och tcyyy (de som började innan 2007), Yi har programkod 6cyyi och tcyyi. Exempel: För att söka efter alla som är registrerade på Yi termin 2: liuStudentProgramCode, 6cyyi-2-*';
    }

    protected function compareLDAP($attr,$value1,$value2) {
        $ret = 0;
        switch($attr) {
            case 'liuStudentProgramCode':
                // liuProgramCode is defined as [program]-[termin]-[date]
                // Where program == 6cyyy or tcyyy for Teknisk Fysik och Elektroteknik
                // 6cyyi, tcyyi == Yi
                //
                // date is vt2009, ht2009, vt2010 etc.
                //
                // We want the latest registration, therefore we need to grab the dates and compare them
                $parts = explode('-', $value1);
                $date1 = ((int) substr($parts[2],2,4)) * 10; //extract year and shift left
                if ($parts[2][0] == 'h' || $parts[2][0] == 'H') {
                    $date1 += 5;
                }

                $parts = explode('-', $value2);
                $date2 = ((int) substr($parts[2],2,4)) * 10; //extract year and shift left
                if ($parts[2][0] == 'h' || $parts[2][0] == 'H') {
                    $date2 += 5;
                }

                $ret = $date1 - $date2;
                break;
            default:
                break;
        }
        return $ret;
    }
}
?>
