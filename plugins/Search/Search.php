<?php
/**
 * @author Kalle Karlsson [kakar]
 * @version 1.0
 * @package Content
 */
/**
 * Search function for Yweb.
 * Contains search-functions for the Database.
 * Returns results in list-format for AJAX-search or on separate page.
 * @package Content
 */
class Search extends Page {
    private $pageNum = 0;
    private $eventNum = 0;
    private $commentNum = 0;
    private $userNum = 0;
    private $fileNum = 0;
    private $mailNum = 0;
    private $q = false;
    private $words = array();
    static $VERSION = 1;
    static public function installable() {return __CLASS__;}
    //static public function uninstallable() {return __CLASS__;}
    static public function upgradable() {return __CLASS__;}

    function install() {
        global $CONFIG;
        $CONFIG->Search->setType('maxwords', 'number');
        $CONFIG->Search->MaxWords = 20;
        $CONFIG->Search->setDescription('MaxWords', 'Maximum number of search terms allowed');
    }

    function upgrade() {}

    function __construct($id=false){
        parent::__construct($id);
        $this->alias = 'search';
        $this->suggestName('Search', 'en');
        $this->deletable = false;
    }

    function __toString(){
        return 'Search';
    }

    function run() {
        global $Controller, $Templates;

        $_REQUEST->setType('q', 'string');
        $_REQUEST->setType('r', 'string');
//		dump($this->categoryList());
        $this->setContent('menu', $this->categoryList().$this->searchTips());
        if($_REQUEST['q']){
            $this->queryFormat($_REQUEST['q']);
            if($this->q) {
                if($_REQUEST['r'] == 'shortcuts') {
                    echo $this->searchShortcuts();
                } else {
                    $this->fullsearch();
                    $Templates->yweb('empty')->render();
                }
            } else {
                if(!$_REQUEST['r']) {
                    $this->setContent('main', $this->getSearchbar(-1));
                    $Templates->yweb('empty')->render();
                }
            }
        } else {
            $this->setContent('main', $this->getSearchbar());
            $Templates->yweb('empty')->render();
        }
    }

    /**
     * Returns a list of shortcuts for the AJAX-search
     * @return string
     */
    function searchShortcuts() {
        global $Controller, $CONFIG;

        $pageResults = $this->search('pages', 5);
        $eventResults = $this->search('events', 5);
/* 		/$commentResults = $this->search('comments', 5); */
        $userResults = $this->search('users', 5);
        $fileResults = $this->search('files', 5);
        $totalResults = $this->pageNum + $this->eventNum + $this->commentNum + $this->userNum + $this->fileNum;

        $r = '<ul class="gs-results">';
        $r .= $this->extras();

        if($totalResults > 0) {
            // Pages
            if($this->pageNum > 0) {
                $r .= '<li class="resultCat"><span class="text">'.__('Pages').'</span><span class="results">'.$this->pageNum.' '.__('results').'</span></li>';
                foreach($pageResults as $item) {
                    $page = $Controller->$item['id'](OVERRIDE);
                    $r .= '<li class="keynav"><a href="'.url(array('id' => $item['id'])).'">'.icon('large/webexport-32').'<span class="text"><h2>'.$page->Name.'</h2><p>'.$page->description.'</p></span></a></li>';
                }
            }

            // Events
            if($this->eventNum > 0) {
                $r .= '<li class="resultCat"><span class="text">'.__('Events').'</span><span class="results">'.$this->eventNum.' '.__('results').'</span></li>';
                foreach($eventResults as $item) {
                    $event = $Controller->$item['id'](OVERRIDE);
                    $r .= '<li class="keynav"><a href="#">'.icon('large/contents-32').'<span class="text"><h2>'.$event->Title.'</h2><p>'.substr($event->text,0,50).'...</p></span></a></li>';
                }
            }

            // Users and Groups
            if($this->userNum > 0) {
                $r .= '<li class="resultCat"><span class="text">'.__('Users and Groups').'</span><span class="results">'.$this->userNum.' '.__('results').'</span></li>';
                foreach($userResults as $item) {
                    if($user = $Controller->$item['id'](READ,'User')){
                        $groupstr = '';
                        foreach ($user->groups as $group) {
                            if (!empty($groupstr)) {
                                $groupstr .= ' / ';
                            }
                            $groupstr .= $group->Name;
                        }
                        $r .= '<li class="keynav"><a href="'.url(array('id' => $item['id'])).'">'.($user->getImage()?$user->getImage(32,32):icon('large/identity-32')).'<span class="text"><h2>'.$user->__toString().'</h2><p>'.$groupstr.'</p></span></a></li>';
                    } else if($group = $Controller->$item['id'](READ,'Group')) {
                        $r .= '<li class="keynav"><a href="'.url(array('id' => $item['id'])).'">'.icon('large/groupevent-32').'<span class="text"><h2>'.$group->Name.'</h2><p>'.__('Group').'</p></span></a></li>';
                    }
                }
            }

            // Files
            if($this->fileNum > 0) {
                $r .= '<li class="resultCat"><span class="text">'.__('Files').'</span><span class="results">'.$this->fileNum.' '.__('results').'</span></li>';
                foreach($fileResults as $item) {
                    $file = $Controller->$item['id'](OVERRIDE);
                    $r .= '<li class="keynav"><a href="'.url(array('id' => $item['id'])).'">';
                    if(is_a($file, 'Folder')) {
                        $r .= icon('large/folder-32');
                        $ftype = 'Folder';
                    } else if(in_array(strtolower($file->extension), $CONFIG->extensions->{'images'})) {
                        $r .= '<div class="pic">'.$file->htmltag(false, array('mw' => 32, 'mh' => 32)).'</div>';
                        $ftype = 'Image';
                    } else if(in_array(strtolower($file->extension), $CONFIG->extensions->{'documents'})) {
                        $r .= icon('large/mail_new-32');
                        $ftype = 'Document';
                    } else {
                        $r .= icon('large/attach-32');
                        $ftype = 'Other file';
                    }
                    $r .= '<span class="text"><h2>'.$file->Name.'</h2><p>'.$ftype.'</p></span></a></li>';
                }
            }

            // Add ViewAll link to fullsearch page
            $r .= '<li class="viewall"><a href="'.url(array('id' => $this->alias, 'q' => $_REQUEST['q'])).'">'.__('View all results').'</a></li>';
        } else {
            $r .= '<li class="resultCat"></li><li class="viewall"><a href="'.url(array('id' => $this->alias, 'q' => $_REQUEST['q'])).'">'.__('No shortcut found').'. '.__('Perform a fullsearch').'</a></li>';
        }
        $r .= '</ul>';
        return $r;
    }


    function getSearchbar($resultNum=false){
        JS::loadjQuery(false);
        Head::add('templates/yweb/js/search.js','js-url');
        $r = '<h1>'.__('Search Results').'</h1>
                    <div id="searchbar">
                        <form id="sb-form" action="." method="get" class="search">
                            <fieldset>
                                <input type="hidden" name="id" value="search" />
                                <label for="sb-searchtext"></label>
                                <input type="text" name="q" id="sb-searchtext" placeholder="'.__('Search').'" value="'.$_REQUEST['q'].'" />
                                <span id="sb-action" class="search-clear"></span>
                            </fieldset>
                        </form>
                        <p>';
        if($resultNum) {
            if($resultNum == -1) $r .=  __('To many search terms. Remove some words and try again.');
            else $r .= $resultNum.' '.__('results found for').' <b>'.$_REQUEST['q'].'</b>';
        }
        $r .='</p></div>';
        return $r;
    }



    /**
     * Fullsearch page
     * Preforms a search and formats the results for the fullsearch page.
     */
    function fullsearch() {
        global $Controller, $DB, $USER, $CONFIG, $Templates;

        $pageResults = $this->search('pages');
        $eventResults = $this->search('events');
        $commentResults = $this->search('comments');
        $userResults = $this->search('users');
        $fileResults = $this->search('files');

        $totalResults = $this->pageNum + $this->eventNum + $this->userNum + $this->fileNum;
        $results = '<div id="results-main"><div class="section" id="results-pages">';

        // Pages
        if($this->pageNum > 0) {
            $results .= '<h2 class="label"><span class="text">'.__('Pages').'</span><span class="label-icon expanded"></span><span class="results">'.$this->pageNum.' '.__('results').'</span></h2><div id="page-list"><ul>';

            foreach($pageResults as $item) {
                $page = $Controller->{(string)$item['id']}(OVERRIDE);

                // Adds breadcrumbs to the page title
                // FIXME: hard coded title
                $crumbs='';
                if(count($page->parents)>0){
                    foreach($page->parents as $p){
                        switch($p->Name){
                            case 'main_menu':
                                break;
                            case 'adminMenu':
                                $crumbs .= __('Admin pages').' - ';
                                break;
                            default:
                                $crumbs = $p->Name.' - '.$crumbs;
                        }
                    }
                }
                $crumbs='Yweb - '.$crumbs;

                $results .= '<li><a href="'.url($item).'">'.$crumbs.$page->Name.'</a><p>'.($page->description?$page->description:'&nbsp;').'</p></li>';
            }
            $results .= '</ul></div>';
        }
        $results .= '</div><div class="section" id="results-comments">';

        // Comments
        if($this->commentNum > 0) {
            $results .= '<h2 class="label"><span class="text">'.__('Comments').'</span><span class="label-icon expanded"></span><span class="results">'.$this->commentNum.' '.__('results').'</span></h2><div id="comment-list"><ul>';
            foreach($commentResults as $c) {
                $comment_text = $DB->comments->getCell(array('id' => $c['id'], 'cid' => $c['cid']), 'comment');
                $results .= '<li><a href="'.url(array('id' => $c['id'], '#' => $c['cid'])).'">Comment</a><p>'.strip_tags($comment_text).'</p></li>';
            }
            $results .= '</ul></div>';
        }
        $results .= '</div><div class="section" id="results-users">';

        // Users & Groups
        if($this->userNum > 0) {
            $results .= '<h2 class="label"><span class="text">'.__('Users and Groups').'</span><span class="label-icon expanded"></span><span class="results">'.$this->userNum.' '.__('results').'</span></h2><div id="user-list"><ul>';
            foreach($userResults as $item) {
                if($user = $Controller->$item['id'](READ,'User')){
/* 					dump($user); */
                    $groupstr = '';
                    foreach ($user->groups as $group) {
                        if (!empty($groupstr)) {
                            $groupstr .= ' / ';
                        }
                        $groupstr .= $group->Name;
                    }
                    $results .= '<li>'.($user->getImage()?$user->getImage(64,64):icon('large/identity-64')).'<span class="info"><h3>'.$user->link().'</h3><p>'.$groupstr.'</p></span></li>';
                } else if($group = $Controller->$item['id'](READ,'Group')) {
                    $results .= '<li><a href="'.url(array('id' => $item['id'])).'">'.icon('large/groupevent-64').'<span class="info"><h3>'.$group->Name.'</h3><p>'.__('Group').'</p></span></a></li>';
                }
            }
            $results .= '</ul></div>';
        }
        $results .= '</div><div class="section" id="results-files">';

        /* Files */
        if($this->fileNum > 0) {
            $results .= '<h2 class="label"><span class="text">'.__('Files').'</span><span class="label-icon expanded"></span><span class="results">'.$this->fileNum.' '.__('results').'</span></h2><div id="file-list"><ul>';

            foreach($fileResults as $item) {
                $file = $Controller->$item['id'](OVERRIDE);
                $results .= '<li><a href="'.url(array('id' => $file->ID)).'"><span class="pic">';
                if(is_a($file, 'Folder')) {
                    $results .= icon('large/folder-64');
                    $ftype = 'Folder';
                } else if(in_array(strtolower($file->extension), $CONFIG->extensions->{'images'})) {
                    $results .=  $file->htmltag(false, array('mw' => 64, 'mh' => 64));
                    $ftype = 'Image';
                } else if(in_array(strtolower($file->extension), $CONFIG->extensions->{'documents'})) {
                    $results .= icon('large/mail_new-64');
                    $ftype = 'Document';
                } else {
                    $results .= icon('large/attach-64');
                    $ftype = 'Other file';
                }
                $results .= '</span><span class="name">'.$file->Name.'</span><span class="desc">'.$ftype.'</span></a></li>';
            }
            $results .= '</ul></div>';
        }

        $results .= '</div></div>';
/* 		return $results; */
        $this->setContent('main', $this->getSearchbar($totalResults).$results);
    }


    /**
     * Function that searches specified tables in the database for results similar to the searchstring.
     * @param string $cat Which category to search for
     * @param number $maxnum Number of results to return
     * @return array
     */
    function search($cat, $maxnum=false) {
        global $DB,$USER;
        // READ THIS: This part is not obvious at all!
        // Language list, NB: reverse preferred order, fallback languages starting with least preferred language first continued in increasing relevance and primary language last
        $languagelist = "'".join("','",array_unique(array('en',$USER->settings['language'])))."'";
        switch($cat) {
            case 'pages':
            // FIXME: Make into a view or a stored procedure.
$result = @$DB->asArray("
SELECT t1.`id` AS id
FROM
        (
            `content` AS t1
            LEFT OUTER JOIN `content` t2 ON t1.`section` = t2.`section`
            AND t1.`id` = t2.`id`
            AND t1.`revision` < t2.`revision`
            AND field( t1.`language` , ".$languagelist." ) = field( t2.`language` , ".$languagelist." )
        )
    LEFT OUTER JOIN
        (
            `content` AS t3
            LEFT OUTER JOIN `content` t4 ON t3.`section` = t4.`section`
            AND t3.`id` = t4.`id`
            AND t3.`revision` < t4.`revision`
            AND field( t3.`language` , ".$languagelist." ) = field( t4.`language` , ".$languagelist." )
        )
    ON t1.`section` = t3.`section`
        AND t1.`id` = t3.`id`
        AND field( t1.`language` , ".$languagelist." ) < field( t3.`language` , ".$languagelist." )
WHERE t1.`content` ".$this->q."
    AND t2.id IS NULL
    AND t4.id IS NULL
    AND t3.id IS NULL"
, true);
                //ksort($result);
                //dump($result);
                if ($result === false)
                    $result = array();
                //dump($this->q);
                //dump($DB->escape(implode($this->words,' ')));

                $result = array_filter($result, array($this, "checkResult"));
                $this->pageNum = count($result);
                break;
            case 'pages-all': //FIXME: Kontrollera och implementera
                $result = $DB->asArray("SELECT t1.id, t1.revision
                                FROM content AS t1
                                LEFT JOIN content t2 ON t1.section = t2.section
                                AND t1.language = t2.language
                                AND t1.revision < t2.revision
                                WHERE t2.section IS NULL
                                AND t1.content ".$this->q."
                                ORDER BY t1.id ASC");
/* 				dump($result); */
                $this->pageNum = count($result);
                break;
            case 'events':
                $result = array();//$DB->asArray($DB->events->like($q, 'text', 'id'));
                $result = array_filter($result, array($this, "checkResult"));
                $this->eventNum = count($result);
                break;
            case 'comments': //FIXME: Kontrollera och implementera
                $result = $DB->asArray("SELECT id, cid FROM comments WHERE comment ".$this->q);
                $result = array_filter($result, array($this, "checkResult"));
                $this->commentNum = count($result);
                break;
            case 'users':
                $result = array_merge(
                        $DB->asArray("SELECT `spine`.`id` as id FROM `spine` JOIN `metadata` ON `metadata`.`id` = `spine`.`id` WHERE `spine`.`Class` = 'Group' AND `metadata`.`field` = 'Name' AND `metadata`.`value` " . $this->q . " GROUP BY `spine`.`id`"),
                        $DB->asArray("SELECT `id` FROM `users` WHERE `username` ".$this->q . " GROUP BY `id`"),
                        $DB->asArray("SELECT `id` FROM `userinfo` WHERE `val` ".$this->q . " GROUP BY `id`")
                    );
                $result = array_filter($result, array($this, "checkResult"));
                $this->userNum = count($result);
                break;
            case 'files':
                $result = $DB->asArray("SELECT id FROM files WHERE name ".$this->q);
                $result = array_filter($result, array($this, "checkResult"));
                $this->fileNum = count($result);
                break;
        }

        /* Downsize results if needed */
        if($maxnum && (count($result)>$maxnum)) {
            return array_slice($result, 0, $maxnum);
        } else {
            return $result;
        }
    }


    /**
     * Formats the query to an SQL-searchquery
     * @param string $q Search string from form
     * @return string
     */
    function queryFormat($q) {
        // FIXME: BROKEN, should be replaced
        global $CONFIG;
        $std_op = array('AND', 'OR' , 'NOT');
        $operators = array();
        $wordNum = 0;
        $q = trim($q);
//		$q = str_replace(array(' +','+'), ' ', $q);
//		$q = utf8($q);
//		$q = html_entity_decode($q);
        $this->words = explode(' ', $q);
/* 		dump($this->words); */
        if(count($this->words) > 1) {
            $op = false;
            $phrase = false;
            foreach($this->words as $w) {
                if(in_array($w, $std_op) && !$phrase) {
                    if($w == 'NOT') {
                        $operators[] = 'AND '.$w;
                    } else {
                        $operators[] = $w;
                    }
                    $op = true;
                } else if($phrase) {
                    if($w{strlen($w)-1} == '"') {
                        $phrase .= ' '.substr($w, 0, -1);
                        $words[] = $phrase;
                        $phrase = false;
                    } else {
                        $phrase .= ' '.$w;
                    }
                    $wordNum++;
                } else {
                    switch($w{0}) {
                        case '"':
                            $phrase = substr($w, 1);
                            $operators[] = 'AND';
                            break;
                        case '-':
                            $operators[] = 'AND NOT';
                            $w = substr($w,1);
                            break;
                        case '+':
                            $w = substr($w,1);
                        default:
                            if(!$op && !$phrase)
                                $operators[] = 'AND';
                            $op = false;
                            break;
                    }
                    $wordNum++;
                    if(!$phrase)
                        $words[] = $w;
                }
            }
/* 			dump($operators); */
            if(!$phrase) array_shift($operators);
        } else $words = $this->words;

        // Return if # terms is more than allowed
        if($wordNum > $CONFIG->Search->max_words) return false;

        array_walk($words, array($this, 'wildcardWrap'));
/* 		dump($words); */

        $sqlstr = 'LIKE '.$words[0];
        for($i=0; $i<count($words)-1; $i++){
            $sqlstr .= ' '.$operators[$i].' '.$words[$i+1];
        }

/* 		if(!$_REQUEST['r']) dump($sqlstr); */
        $this->q = $sqlstr;
    }


    /**
     * Wraps words for SQL wildcard searches
     * @param string $w String to be wraped
     */
    function wildcardWrap(&$w){
        global $DB;
        $w = "'%".$DB->escape($w)."%'";
    }


    /**
     * Checks if the user have permision to se the object
     * @param string $item ID of the object to be checked
     * @return bool
     */
    private function checkResult($item) {
        global $Controller;
        if (is_array($item))
        {
            if (!isset($item['id']))
                return false;
            $id = $item['id'];
        }
        else
        {
            $id = $item;
        }
        $obj = $Controller->{(string) $id}(OVERRIDE);
        //dump($Controller->{(string) $id}(OVERRIDE));
        if($Controller->{(string) $id}('User')) {
            if($obj->username == 'nobody') return false;
        } else if($Controller->{(string) $id}('Page') && $obj->parent) {
            if($obj->parent->alias == 'errorPages') return false;
        }
        return $obj->mayI(READ);
    }


    /**
     * Returns a list of search categories acording to user permisions
     * @return string
     */
    function categoryList() {
        /*
$cat = '<div id="search-categories"><ul>
                        <li id="category-all" class="selected">'.__('All categories').'</li>
                        <li id="category-pages">'.__('Pages').'</li>
                        <li id="category-events">'.__('Events').'</li>
                        <li id="category-comments">'.__('Comments').'</li>
                        <li id="category-users">'.__('Users and Groups').'</li>
                        <li id="category-files">'.__('Files').'</li>
                    </ul></div>';
*/
        $cat = '<div id="subnav"><h2>'.__('Category').'</h2><ul id="search-categories">
                    <li id="category-all" class="activeli"><a href="">'.__('All categories').'</a></li>
                    <li id="category-pages"><a href="">'.__('Pages').'</a></li>
                    <li id="category-events"><a href="">'.__('Events').'</a></li>
                    <li id="category-comments"><a href="">'.__('Comments').'</a></li>
                    <li id="category-users"><a href="">'.__('Users and Groups').'</a></li>
                    <li id="category-files"><a href="">'.__('Files').'</a></li>
                </ul></div>';
        return $cat;
    }


    /**
     * Return html for searchtips
     * @return string
     */
    function searchTips() {
        global $USER;
        $tips = '<div id="search-tips"><h1>'.__('Search Tips').'</h1>';
        if($USER->loadedLanguage == 'sv') {
        $tips .= '<h3>Automatiska "AND"-sökningar</h3><p>Sökningen hittar objekt innehållande samtliga ord. Det är inte nödvändigt att använda "+" framför, eller "AND" mellan, orden.</p>
                <h3>"OR"-sökningar</h3><p>Om du leter efter objekt innehållande ord A eller ord B kan operatorn "OR" användas mellan orden.</p>
                <h3>Begränsade "NOT"-sökningar</h3><p>Begränsa sökningen genom att välja bort resultat som innehåller ett ord. Gör detta genom att använda "-" framför, eller "NOT" mellan, orden.</p>
                <h3>Frassökningar</h3><p>Du kan söka efter fraser genom att skriva dem inom citattecken. Sökningen hittar alla objekt där orden i sökfrasen står tillsammans.</p>';

        } else {
        $tips .= '<h3>Automatic "AND" Queries</h3><p>By default, Search finds objects containing all searchs terms. It\'s not necessary to use "+" in front of, or "AND" in between, the words.</p>
                <h3>"OR" Searches</h3><p>To find objects containing either word A or word B, use the operator "OR" between the words.</p>
                <h3>Excluding "NOT" Queries</h3><p>You can limit your search by using the operator "-" in front of, or "NOT" in between, the words. The will exclude results containg the word.</p>
                <h3>Phrase Searches</h3><p>You can search for phrases by adding quotation marks. Words enclosed in double quotes appear together in all returned results.</p>';
    }
        $tips .= '</div>';
        return $tips;
    }

    function extras() {
        function arrayInArray($needles, $haystack) {
            foreach ($needles as $needle) {
                if(in_array($needle, $haystack)) return true;
            }
            return false;
        }

        $r = '';
        if(arrayInArray(array('temp','temperatur','varmt','kallt','väder','klimat'), $this->words)) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "http://www.vackertvader.se/weather/widgetv3?geonameid=2665577&size=160v3x&days=1");
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $text = curl_exec($ch);
            curl_close($ch);
            // Get temp
            $start = strpos($text,'class="cel') + 12;
            $end = strpos($text,'°') - $start + 2;
            $temp = substr($text, $start, $end);
            // Get icon
            $start = strpos($text,'src') + 5;
            $end = strpos($text,'.png') - $start + 4;
            $icon = 'http://www.vackertvader.se'.substr($text,$start,$end);
            $r .= '<li><a href="http://www.vackertvader.se/link%C3%B6ping-valla"><img src="'.$icon.'" height="32" width="32"><span class="text"><h2>'.$temp.'</h2><p>Väder - Linköping Valla</p></span></a></li>';
            /*
curl_setopt($ch, CURLOPT_URL, "http://termo.frryd.se/data/temp.txt");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $temp = curl_exec($ch);
            $temp = substr($temp, 0, strlen($temp)-2);
            curl_close($ch);
            $r .= '<li><a href="http://termo.frryd.se/">'.icon('large/knewstuff-32').'<span class="text"><h2>'.$temp.'&deg;C</h2><p>Temperartur - Linköping</p></span></a></li>';
*/
        } else if(arrayInArray(array('meningen','livet','universum','allting'), $this->words)) {
            $r .= '<li><a href="http://en.wikipedia.org/wiki/Meaning_of_life">'.icon('large/knewstuff-32').'<span class="text"><h2>42</h2><p>Svaret på "den yttersta frågan om meningen med livet, universium och allting" enligt Douglas Adams</p></span></a></li>';
        }
        return $r;
    }

}
?>
