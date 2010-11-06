<?php

/**
 * @author Jonatan Olofsson [joolo]
 * @version 1.0
 * @license http://creativecommons.org/licenses/by-nc/3.0/ Creative Commons Attribution-Noncommercial 3.0 Unported License
 * @package Pages
 */



/**
 * Represents a page of some sort
 * @package Pages
 */
class Page extends MenuItem{
    private $_Header=false;
    private $_title=false;
    private $_content = false;
    private $_modules = array();
    private $_template = false;
    private $_Form = false;
    private $_attachments = false;
    protected $_edit_link=false;
    public $privilegeGroup = 'Pages';
//	private $DBTable = 'pages';
    private $DBContentTable = 'content';
    static public function installable() {return __CLASS__;}
    static public function uninstallable() {return __CLASS__;}
    protected $KeepRevisions = true;
    protected $AutoTranslate = true;

    /**
     * Passes the construct call and loads information about the page
     */
    function __construct($id=false, $language=false){
        parent::__construct($id, $language);

        $this->_edit_link = url(array('id' => 'pageEditor', 'edit' => $id, 'view' => 'content', 'lang' => $this->_loadedLanguage), null, false);
    }

    /**
    * __get()
    * @desc Returns the property asked for, if allowed.
    * @access public
    * @param string $property The property asked for
    * @return mixed
    */
    function __get($property) {
        if($property == 'ID') return parent::__get($property);
        if(in_array($property, array('template','attachments'))) {
            $this->pageLoad();
        }
        if($property == 'author') return $this->getAuthor();
        elseif(in_array($property, array('content', 'template', 'edit_link', 'Form', 'attachments')))
        {
            if($property == 'content') $this->loadContent();
            if($property == 'template' && empty($this->_template)) return 'inherit';
            return @$this->{'_'.$property};
        }
        elseif(in_array($property, array('title', 'Header'))) {
            if(!$this->{'_'.$property}) return $this->Name;
        }
        else return parent::__get($property);
    }

    function metaget($what) {
        if($what == 'template') return $this->_template;
        else return parent::metaget($what);
    }

    /**
     * Sets the variables of the object and updates the database if nescessary.
     * Unrecognized properties are forwarded to it's parent
     * @param string $property The property which to change
     * @param mixed $value The new value of the property
     * @see solidbase/lib/Base#__set($property, $value)
    */
    function __set($property, $value) {
    global $USER, $DB;
        if(in_array($property, array('template', 'content', 'attachments'))) {
            if($this->ID) {
                $ipn = '_'.$property;
                if(in_array($property, array('template','attachments'))) {
                    $this->pageLoad();

                    if($this->$ipn !== false) {
                        if($value != @$this->$ipn && ($value || @$this->$ipn)) {
                            if(@$this->$ipn !== false && $this->may($USER, EDIT)) {
                                Metadata::$metameta = ($property == 'template'?false:$this->loadedLanguage);
                                Metadata::set($property, $value);
                            }
                        }
                    }

                    $this->$ipn = $value;
                }
                elseif($property == 'content')
                {
                    $this->$ipn = $value;
                }
            }
        }
        elseif(in_array($property, array('title', 'Header'))) {
            $this->{'_'.$property} = $value;
        } else {
            parent::__set($property, $value);
        }
    }

    function getAuthor($id_only=false) {
        global $Controller, $DB;
        if($this->_author === false) {
            $this->_author = $DB->updates->getCell(array('id' => $this->ID), 'editor', 'edited DESC');
        }
        if($id_only) return $this->_author;
        return $Controller->retrieve($this->_author, OVERRIDE);
    }
    private $_author=false;

    /**
     * Sets the content of a single section
     * @param string $section The name of the section
     * @param string $content The content to add
     */
    function setContent($section, $content) {
        if(!is_array($this->_content)) $this->_content = array();
        $this->_content[$section] = $content;
    }

    /**
     * Appends content to a single section
     * @param string $section name of the section to append to
     * @param string $content Content to append
     * @return void
     */
    function appendContent($section, $content) {
        $this->loadContent();
        if(!isset($this->_content[$section])) $this->_content[$section] = $content;
        else $this->_content[$section] .= $content;
    }

    /**
     * Saves the content of the page to the database
     * @param array $value Array of the content of the sections. If not provided, the existing content is used.
     * @return void
     */
    function saveContent($value, $forceNewRevision=false) {
        if(is_array($value)) {
            global $DB, $CONFIG;
            $this->loadContent();
            foreach($value as $section => $content) {
                if(is_array($a = $CONFIG->content->filters)) {
                    foreach($a as $callback) {
                        if(is_callable($callback)) {
                            $val = call_user_func($callback, $content, $section);
                            if($val !== false) {
                                $content = $val;
                            }
                        }
                    }
                }
                if($content != @$this->_content[$section]) {
                    $this->registerUpdate();
                    if($this->KeepRevisions && !$forceNewRevision && $CONFIG->content->revision_separationtime &&
                    ($lastRevision = $DB->content->getCell(array('id' => $this->ID, 'section' => $section, 'language' => $this->loadedLanguage), 'revision')) >= $time-60*$CONFIG->content->revision_separationtime) {
                        $DB->content->update(array('content' => $content, 'revision' => time()), array('id' => $this->ID, 'revision' => $lastRevision, 'section' => $section, 'language' => $this->loadedLanguage));
                    } else {
                        if(!$this->KeepRevisions) $DB->content->delete(array('id' => $this->ID, 'section' => $section), false);
                        $DB->content->insert(array('id' => $this->ID,'content' => $content, 'revision' => time(), 'section' => $section, 'language' => $this->loadedLanguage));
                    }
                    $this->_content[$section] = $content;
                }
            }
        }
    }

    /**
     * Restore a previously saved revision
     * @param $section Which section to restore
     * @param $revision Which unixtime the revision was submitted
     * @return bool
     */
    function restoreRevision($section, $revision) {
        if($this->mayI(EDIT)) {
            global $DB;
            if(($restore = $DB->content->getCell(array('id' => $this->ID, 'section' => $section, 'revision' => $revision, 'language' => $this->loadedLanguage), 'content')) !== false) {
                $this->saveContent(array($section => $restore));
                Log::write('Revision '.$section.'::'.strftime('%c', $revision).' restored');
                return true;
            }
        }
        return false;
    }

    /**
     * Renders the template
     * @return unknown_type
     */
    function run($template=false){
        global $Templates, $Controller;

        $this->_Form = new UserForm($this->ID, $this->loadedLanguage);
        $attachments = explode(',', $this->__get('attachments'));
        if (is_array($attachments) && count($attachments) > 0) {
            foreach ($attachments as $att) {
                // Ignore id=0 and empty strings
                if (!$att)
                    continue;
                $o = $Controller->{(string) $att};
                if ($o)
                    $this->appendContent('main', (string) $o);
                else
                    $this->appendContent('main', 'missing attachment!');
            }
        }
        $this->appendContent('main', $this->_Form->render());
        $this->appendContent('main', Comments::displayComments($this->ID));
        $this->appendContent('main', $this->_Form->viewResult());
        if($template){
            $Templates->yweb($template)->render();
        } else {
            $Templates->render();
        }
    }

    /**
    * Loads a page from the database
    * @access public
    * @return void
    */
    function pageLoad($reload=false){
        global $USER;
        if(!$this->pLoaded || $reload) {
            if($this->ID == false) return false;
            $this->pLoaded = true;
            $this->getMetadata(array($this->_loadedLanguage, ''), false, array('template', 'attachments'));
        }
    }
    private $pLoaded=false;

    /**
    * Loads the contents of a page
    * @access public
    * @return void
    */
    function loadContent($force=false, $prohibit_translation = false){
        if($this->cLoaded && !$force) return;
    global $DB, $USER, $Templates, $CONFIG;
        if($this->ID == false) return;
        if($force) $this->_content = false;

        $this->cLoaded = true;
        if(is_array($this->_content)) {
            $skip = array_keys($this->_content);
            $m = $this->_content;
        } else {
            $skip = $m = array();
        }

        // READ THIS: This part is not obvious at all!
        // Language list, NB: reverse preferred order, fallback languages starting with least preferred language first continued in increasing relevance and primary language last
        $languagelist = "'".join("','",array_unique(array('en',$this->loadedLanguage)))."'";
        $this->_content = array_merge($m,
            (array)@$DB->asList("
SELECT t1.`section`,t1.`content`
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
WHERE t1.`id` = ".(int)$this->ID."
    AND t2.id IS NULL
    AND t4.id IS NULL
    AND t3.id IS NULL"
        .(empty($skip)?'':" AND t1.section NOT IN ('".join("','", $skip)."')")
, true));


        if($this->AutoTranslate && !$prohibit_translation && $USER->settings['language'] && $USER->settings['auto_Translate'] && $CONFIG->Site->auto_Translate === 'yes') {
            $diff = array_diff($Templates->current->sections, array_keys($this->_content));
            if(!empty($diff)) {
                $c = $DB->content->asList(array("id" => $this->ID, 'section' => $diff), 'section,content,language', false, true, "revision DESC");
                $sections = $content = $languages = array();
                foreach($c as $section => $data) {
                    if(!empty($data[0])) {
                        $sections[] = $section;
                        $content[] = $data[0];
                        $languages[] = $data[1];
                    }
                }
                if(!empty($sections) && $translation = google::translate($content, $languages, $this->loadedLanguage)) {
                    foreach($translation as &$tr) {
                        $tr = '<div class="warn_autotranslate">'.__('Warning: auto-translated text').'</div>'.$tr;
                    }
                    $this->_content = array_combine($sections, $translation);
                }
            }
        }
    }
    private $cLoaded = false;



    /**
     * Creates the crucial database tables on install
     * @return bool
     */
     function install() {
        global $DB;
/*		$DB->query("CREATE TABLE IF NOT EXISTS `".Page::$DBTable."` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `template` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `title` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;");*/
        $DB->query("CREATE TABLE IF NOT EXISTS `".Page::$DBContentTable."` (
  `id` mediumint(9) NOT NULL default '0',
  `section` varchar(255) character set utf8 NOT NULL default '',
  `content` text character set utf8 NOT NULL,
  `language` varchar(5) character set utf8 NOT NULL,
  `revision` int(11) NOT NULL,
  KEY `section` (`section`),
  KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;");
        return 1;


        $CONFIG->Site->setType('auto_Translate', 'select', array('no' => 'No', 'yes' => 'Yes'));
        $CONFIG->content->setDescription('revision_separationtime', 'Minutes of separation between two saves for them to be counted as same revision');

        $CONFIG->content->setType('filters', 'not_editable');
    }

    /**
     * Drops the database tables on uninstall
     * @return bool
     */
    function uninstall() {
        global $DB, $USER;
        if(!$USER->may(INSTALL)) return false;
//		$DB->dropTable(self::$DBTable);
        $DB->dropTable(self::$DBContentTable);
        return true;
    }

    /**
     * Deletes self and passes the call to parent class
     * @see solidbase/lib/MenuItem#delete()
     */
    function delete() {
        global $USER, $DB;
        if($this->may($USER, DELETE)) {
            $DB->metadata->delete($this->ID);
            $DB->content->delete($this->ID, false);
            $DB->spine->delete($this->ID);
            return parent::delete();
        } return false;
    }
}
?>
