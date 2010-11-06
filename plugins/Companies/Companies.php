<?php
/**
 * @author Kalle Karlsson [kakar]
 * @version 1.1
 * @package Content
 */
/**
 * This class handles the sorting and formating of the companies
 * @package Content
 */
class Companies{
    private static $sizeMax = 230;
    private static $sizeStep = 20;

    /**
     * Returns a sorted HTML-list of reqested companies
     * @param string $cat Reqested type of companies (main | sub)
     * @return string
     */
    function sortedList($cat, $returnArray=false){
        global $DB, $Controller;
        $_REQUEST->setType('w','numeric');
        $req = $DB->{'spine,metadata'}->asArray(array('spine.class' => 'Company', 'metadata.field' => 'type', 'metadata.value' => $cat));
        if(!$req) return '';
        foreach($req as $company) $companies[] = $Controller->$company['id'](OVERRIDE);
        usort($companies, "Companies::cmp");
        if($returnArray) return $companies;
        $size = self::$sizeMax;
        $list = '<ul class="companies_'.$cat.'">';
        foreach($companies as $company){
/* 			if($cat == "sub") $size -= self::$sizeStep; */
            if($company->redirect)
                $list .= '<li><a href="'.$company->URL.'"><img src="'.url(array('id' => $company->logo, 'w' => $size)).'" alt="'.$company->name.'" /></a></li>';
            else
                $list .= '<li><a href="'.url(array('id' => $company->ID)).'"><img src="'.url(array('id' => $company->logo, 'w' => $size)).'" alt="'.$company->name.'" /></a></li>';
/* 			./index.php?id='.$company->logo.'&amp;w='.$size */
        }
        $list .= '</ul>';
        return $list;
    }

    function viewAds() {
        //FIXME:Move setting to CompanyEditor
        $slider = true;
        $cycletime = 5000;
        $r = '';
        $ms = self::sortedList('main');
        $s = self::sortedList('sub');
        if($ms) $r .= '<div class="col first four company"><h2>'.__('Main sponsors').'</h2>'
            .$ms
            .'</div>';
        if($s) $r .= '<div class="col first four company"><h2>'.__('Sponsors').'</h2>'
            .$s
            .'</div>';
        if($slider){
            JS::lib('jquery/jquery.cycle.all.min');
            JS::raw('$(function(){
                        $(".companies_sub").css({height:"230px"}).children("li").css({top:0,left:0,position:"fixed"});
                        $(".companies_sub").cycle({fx:"scrollHorz",timeout:'.$cycletime.'});
                    });');
        }
        return $r;
    }

    function getAds() {
        $ms = self::sortedList('main',true);
        $s = self::sortedList('sub',true);
        return array_merge($ms,$s);
    }

    /**
     * Function for comparing weights of two companies
     * Used by the usort() function
     * @param object $a $b Company-objects to compare
     * @return number
     */
    static function cmp($a, $b){
        if ((int)$a->weight == (int)$b->weight) return 0;
        return ((int)$a->weight < (int)$b->weight) ? -1 : 1;
    }

    /**
     * Function for checking if company is a main-company
     * @param object $company Object to check
     * @return bool
     */
    static function isMain($company){
        return($company->type == 'main');
    }

    /**
     * Function for checking if company is a sub-company
     * @param object $company Object to check
     * @return bool
     */
    static function isSub($company){
        return($company->type == 'sub');
    }
}
?>
