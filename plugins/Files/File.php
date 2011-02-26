<?php

/**
 * @author Jonatan Olofsson [joolo]
 * @version 1.0
 * @license http://creativecommons.org/licenses/by-nc/3.0/ Creative Commons Attribution-Noncommercial 3.0 Unported License
 * @package Filesystem
 */

/**
 * This class represents a (single) file in the filesystem. It is used in conjunction with
 * the Folder class to represent files in the private foldersystem
 * @package Filesystem
 */
class File extends Page{
    protected $_path = false;
    protected $Type='File';
    private $_dirname = false;
    private $_basename = false;
    private $_extension = false;
    private $_filename = false;
    private $_self = false;
    public $privilegeGroup = 'hide';
    private $_edited;
    static private $DBTable = 'files';


    public $editable = array(
        'PermissionEditor' => EDIT_PRIVILEGES
    );

    static function installable(){return __CLASS__;}
    static function uninstallable(){return __CLASS__;}
    /**
     * The class is loaded either with an id (from the spine table) or the path to the file
     * In the first case, when it is beeing loaded from the Controller, $parent actually contains the alias
     * of the file itself.
     * The function cascades the __construct call to it's parent
     * @param mixed $fullpath Contains id or path
     * @param mixed $parent Contains (possibly) parent folder
     * @return void
     */
    function __construct($fullpath=false, $parent=false){
    global $DB, $Controller, $CONFIG;
        $id = false;
        if(is_numeric($fullpath)) {
            $id = $fullpath;
        } else {
            if($parent === false) {
                if($fullpath === $this->rootDir()) {
                    $id = $Controller->fileRoot(OVERRIDE)->ID;
                } elseif(substr($fullpath, 0, strlen($this->rootDir())) == $this->rootDir()) {
                    $parent = Folder::open(dirname($fullpath));
                } else return false;
                $this->_path = $fullpath;
            }
            if(!$id) {
                if(!($id = $DB->files->getCell(array('parent' => ($parent===0?0:$parent->ID),
                                                    'name' => pathinfo($fullpath, PATHINFO_BASENAME)), 'id'))) {
                    $id = $DB->spine->insert(array('class' => $this->Type));
                    $DB->files->insert(array('parent' => ($parent===0?0:$parent->ID),
                                                    'name' => pathinfo($fullpath, PATHINFO_BASENAME),
                                                    'id' => $id));
                }
            }
        }
        parent::__construct($id);

        if($this->isImage()) {
            $this->editable['ImageEditor'] = EDIT;
        }
        Short::registerFilter(array(__CLASS__, 'contentFilter'));
    }

    function __get($property) {
        switch($property) {
            case 'self':
                if($this->_self === false) {
                    global $DB;
                    $this->_self = $DB->files->{$this->ID};
                    if(!$this->_self || !$this->_self['parent']){
                        $this->_self = array('id' => $this->ID, 'parent' => false, 'name' => 'root');
                        $this->alias = 'fileRoot';
                    }
                }
                return $this->_self;
            case 'DirID':
                return $this->self['parent'];
            case 'Dir':
                if(!$this->DirID) return false;
                global $Controller;
                return $Controller->{$this->DirID}(OVERRIDE);
            case 'path':
                if(!$this->_path) {
                    if($this->DirID && $this->Dir) {
                        $this->_path = $this->Dir->path.'/'.$this->self['name'];
                    } else {
                        $this->_path = $this->rootDir();
                    }
                }
                return $this->_path;
            case 'filename':
            case 'dirname':
            case 'extension':
            case 'basename':
                if($this->_filename === false) $this->expandPath();
                return $this->{'_'.$property};
            case 'edited':
                if($this->_edited === null)
                    $this->_edited = filemtime($this->path);
                return $this->_edited;
            case 'Type': return $this->Type;
            case 'Name': return $this->__toString();
            default:
                return parent::__get($property);
        }

    }

    /**
     * Opens a directory or file from it's full path
     * @param $dir Full path to directory or file
     * @return object Folder or file accessed by it's path
     */
    function open($dir, $lvl=OVERRIDE, $u=false, $keep = true) {
        global $Controller;
        $dir = realpath($dir);
        if(!$dir) return false;
        if(!isset(self::$dMEM[$dir])) {
            if(is_dir($dir)) {
                $obj = new Folder($dir);
            } elseif(is_file($dir)) {
                $obj = new File($dir);
            } else return false;
            self::$dMEM[$dir] = $obj->ID;
        }
        if(isset(self::$dMEM[$dir])) {
            return $Controller->get(self::$dMEM[$dir], $lvl, $u, $keep);
        } else return false;
    }
    static private $dMEM = array();

    function rootDir() {
        global $CONFIG;
        if ($CONFIG->Files->rootDir && @realpath($CONFIG->Files->rootDir))
            return realpath($CONFIG->Files->rootDir);
        else
            return realpath(PRIV_PATH.'/Files');
    }

    /**
     * @param $tag
     * @return unknown_type
     */
    static function contentFilterHelper($tag) {
        $tag = $tag[0];
        $height = false;
        $width = false;
        $count = false;
        if(stristr($tag, '?id=')
            && preg_match('#height="(\d*)"#i', $tag, $height)
            && preg_match('#width="(\d*)"#i', $tag, $width)
            && $res = preg_replace('#src="[^"]*\?id=(\d*)(&(?:amp;)?h=\d*&(?:amp;)?w=\d*)?#i', 'src="?id=$1&amp;h='.$height[1].'&amp;w='.$width[1].'', $tag, 1, $count)) {
            return $res;
        } else return $tag;
    }

    static function contentFilter($content, $section=false) {
        return preg_replace_callback('#<img[^>]*>#i', array(__CLASS__, 'contentFilterHelper'), $content);
    }
    /**
     * Sets up the database table upon installation
     * @return void
     */
    function install() {
        global $DB, $USER, $CONFIG;
        $DB->query("CREATE TABLE IF NOT EXISTS `".self::$DBTable."` (
  `id` int(11) NOT NULL,
  `parent` int(11) NOT NULL,
  `name` varchar(255) character set utf8 NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `parent` (`parent`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;");

        $CONFIG->Files->setType('Imagecopies', 'text');
        $CONFIG->Files->setDescription('Imagecopies', 'Number of cached resized images that can exist at once. Defaults to 5 if no other numeric value is given.');
        $CONFIG->Files->setType('rootDir', 'text');
        $CONFIG->Files->setDescription('rootDir', 'Directory where to store uploaded files. Must be writable by the user running httpd.');
        $CONFIG->Files->setType('filter', 'CSV');
        $CONFIG->Files->setDescription('filter', 'Only show files with these extensions.');
        $CONFIG->extensions->setType('images', 'CSV');
        $CONFIG->extensions->setDescription('images', 'Comma separated list of image file formats');
        $CONFIG->extensions->setType('documents', 'CSV');
        $CONFIG->extensions->setDescription('documents', 'Comma separated list of document formats');
        $CONFIG->extensions->setType('plaintext', 'CSV');
        $CONFIG->extensions->setDescription('plaintext', 'Comma separated list of plaintext formats');

        $CONFIG->Files->filter = array('jpg', 'png', 'gif', 'bmp', 'tif', 'doc', 'm', 'xls', 'odt', 'pdf', 'rtf', 'txt');
        $CONFIG->extensions->images = array('jpg', 'png', 'gif', 'bmp', 'tif');
        $CONFIG->extensions->documents = array('xls', 'odt', 'pdf', 'rtf', 'txt');
        $CONFIG->extensions->plaintext = array('m','txt');

        if(!is_dir($this->rootDir())) mkdir($this->rootDir(), 0700);
        if(!is_dir($this->rootDir().'/.cache')) mkdir($this->rootDir().'/.cache', 0700);
        return true;
    }

    /**
     * Drops the database on uninstall
     * @return unknown_type
     */
    function uninstall() {
        global $DB, $USER;
        if(!$USER->may(INSTALL)) return false;
        $DB->dropTable($this->DBTable);
    }

    /**
     * Overload string representation
     * @see solidbase/lib/Base#__toString()
     */
    function __toString() {
        if($this->Dir && $this->Dir->path == realpath(self::rootDir().'/UserDirectory/') && is_numeric($this->basename)) {
            global $Controller;
            $obj = $Controller->{(string)$this->basename};
            return '['.get_class($obj).'] '. $obj;
        }
        return $this->basename;
    }

    function __set($property, $value) {
        if($property == 'path') {
            if(preg_match('#^'.$this->rootDir().'(/|$)#', $value)) {
                $this->_path = $value;
                $this->expandPath();
            }
        }elseif($property != 'Name') return parent::__set($property, $value);
    }

    /**
     * Generate a HTML-tag for the file, if the user is allowed to see the file
     * @return HTML
     */
    function htmltag($link = false, $imageparams = false) {
        if(!$this->mayI(READ)) return '';
        $r = '';
        if($link) {
            $r .= '<a href="'.url(array('id' => $this->ID)).'">';
        }

        if(isImage($this->path)) {
            $params = array('id' => $this->ID);
            if(is_array($imageparams)) {
                $attrs = array('w', 'h', 'mw', 'mh', 'tr', 'tg', 'tb', 'rot');
                foreach($attrs as $attr) {
                    if(@$imageparams[$attr]) {
                        $_REQUEST->setType($attr, 'numeric');
                        $params[$attr] = $imageparams[$attr];
                    }
                }
            }
            $r .= '<img src="'.url($params).'" alt="'.$this->Name.'" />';
        } else $r .= $this->__toString();

        if($link) {
            $r .= '</a>';
        }
        return $r;
    }

    /**
     * Find valid dimensions for an image to display
     * @param integer $width The wished width of the image
     * @param integer $height The wished height of the image
     * @param bool $force_scaling Wether to enforce proportional scaling
     * @return array An array with valid [width,height]
     * @todo Is this the optimal algorithm for deciding valid dimensions?
     */
    function getValidImageDimensions($width=false, $height=false, $force_proportions=false) {
        if(!isImage($this->path)) return false;

        if($force_proportions) $height = false;

        $currentDimensions = getimagesize($this->path);
        $cWidth = $currentDimensions[0];
        $cHeight = $currentDimensions[1];

        if(!$width && !$height) {
            $width = $cWidth;
            $height = $cHeight;
        } elseif(!$width) {
            $scale = $height / $cHeight;
            $width = $scale * $cWidth;
        } elseif(!$height) {
            $scale = $width / $cWidth;
            $height = $scale * $cHeight;
        }

        if($width > $cWidth) {
            $scale = $cWidth / $width;
            $width = $scale * $width;
            $height = $scale * $height;
        }
        if($height > $cHeight) {
            $scale = $cHeight / $height;
            $width = $scale * $width;
            $height = $scale * $height;
        }

        return array(round($width), round($height));
    }

    /**
     * Get a modified image from cache (or create it if it's not available in the cache)
     * @param integer $width The wished width of the image
     * @param integer $height The wished height of the image
     * @return array An array with valid [width,height]
     */
    function getConvertedImage($width, $height, $rot, $tr, $tg, $tb, $return_blob = false) {
        global $CONFIG;
        list($width, $height) = $this->getValidImageDimensions($width, $height);

        $cpDir = $this->rootDir();
        if(!is_dir($cpDir.'/.cache')) mkdir($cpDir.'/.cache', 0700);
        $cacheDir = realpath($cpDir.'/.cache');
        $cacheName = $this->ID . '_' . $width.'x'.$height . '_' . $tr . 'r' . $tg . 'g' . $tb . 'b' . '_' . 'r' . $rot . '_' . filemtime($this->path).'.'.$this->extension;
        $cachePath = $cacheDir.DIRECTORY_SEPARATOR.$cacheName;

        if(!is_file($cachePath)) {
            if(function_exists("gd_info")) {
                $olderFiles = glob($cacheDir.$this->ID . '_' . $width.'x'.$height . '_' . $tr . 'r' . $tg . 'g' . $tb . 'b' . '_' . 'r' . $rot . '_*');

                $copies = glob($cacheDir.$this->ID . '_*');
                $max = $CONFIG->Files->Imagecopies;
                if(!is_numeric($max)) $CONFIG->Files->Imagecopies = $max = 5;

                if(count($copies)>=$max) {
                    shuffle($copies);
                    $olderFiles += array_slice($copies, $max-1);
                }

                foreach($olderFiles as $oldFile) unlink($oldFile);

                /**
                * Create modified image
                **/
                $img = new Image($this->path);
                if($img->width > $width || $img->height > $height) $img->resize($width, $height);
                if($tr !== false || $tg !== false || $tb !== false) $img->tint($tr,$tg,$tb);
                if($rot != false) $img->rotate($rot);
                $img->save($cachePath);
            }
            else {
                $cachePath = $this->path;
            }
        } elseif($return_blob) $img = new Image($cachePath);

        if($return_blob) return array($cachePath, $img->blob());
        return $cachePath;
    }

    /**
     * Execute action when called for explicitly by the user
     * @return void
     */
    function run() {
        global $USER, $CONFIG, $Templates, $Controller;
        /**
         * User input types
         */
        $_REQUEST->setType('w', 'numeric');
        $_REQUEST->setType('h', 'numeric');
        $_REQUEST->setType('mw', 'numeric');
        $_REQUEST->setType('mh', 'numeric');
        $_REQUEST->setType('ok', 'string');
        $_REQUEST->setType('to', 'numeric');
        $_REQUEST->setType('fcontent', 'any');
        $_REQUEST->setType('action', 'string');
        $_REQUEST->setType('imgrot', 'numeric');
        $_REQUEST->setType('rot', 'numeric');

        if (@filesize($this->path)) {
            if($this->may($USER, READ)) {
                switch($_REQUEST['action']) {
                    case 'edit':
                        if($this->may($USER, EDIT)) {
                            if(in_array($this->extension, $CONFIG->extensions->plaintext)) {
                                /**
                                * Save changes
                                */
                                if($_REQUEST['editFile']) {
                                    file_put_contents($p, (mb_detect_encoding(file_get_contents($p)) == "UTF-8" ? utf8($_REQUEST['fcontent']) : deutf8($_REQUEST['fcontent'])));
                                    if($_REQUEST['mkcopy']) redirect(array('id' => $copy->ID, 'action' => 'edit', 'ok' => 'true'));
                                }

                                /**
                                 * Display page for editing plain text documents
                                 */
                                $tmp = new TextArea(__('File contents'), 'fcontent', utf8(file_get_contents($this->path)));
                                $tmp->class='large';
                                $formfields[] = $tmp;
                                unset($tmp);
                            }

                            $formfields[] = new Checkbox(__('Save as copy'), 'mkcopy', $_REQUEST['mkcopy']);

                            $nav = '<div class="nav"><a href="'.url(array('id' => $this->DirID)).'">'.icon('small/arrow_left').__('Back').'</a></div>';
                            $this->content = array('header' => __('Editing file').': '.$this->basename,
                                                    'main' => $nav . $form->collection(new Set($formfields)));
                            $t = 'admin';
                            if($_REQUEST['popup'])
                                $t = 'popup';

                            $Templates->$t->render();
                        } else errorPage(401);
                        break;
                    case 'download':
                    default:
                        if(strpos($this->path, $this->rootDir()) === 0) {
                            $p = $this->path;
                            $n = $this->basename;

                            if(isImage($this->path)
                                && (	$_REQUEST['w']
                                        || $_REQUEST['h']
                                        || $_REQUEST['mw']
                                        || $_REQUEST['mh']
                                        || isset($_REQUEST['tr'])
                                        || isset($_REQUEST['tg'])
                                        || isset($_REQUEST['tb'])
                                        || isset($_REQUEST['rot']))
                                    && function_exists("gd_info")) {


                                $s = getimagesize($this->path); // s(1) / s(0) = h / w
                                if($_REQUEST['mw'] && $s[0]>$_REQUEST['mw']) {
                                    $_REQUEST['h'] = round($s[1] * $_REQUEST['mw'] / $s[0]);
                                    $_REQUEST['w'] = round($_REQUEST['mw']);
                                }
                                if($_REQUEST['mh'] && $s[1]>$_REQUEST['mh']) {
                                    $_REQUEST['w'] = round($s[0] * $_REQUEST['mh'] / $s[1]);
                                    $_REQUEST['h'] = round($_REQUEST['mh']);
                                }

                                $p = $this->getConvertedImage($_REQUEST['w'],$_REQUEST['h'], $_REQUEST['rot'], $_REQUEST['tr'], $_REQUEST['tg'], $_REQUEST['tb'], false);
                                $n = pathinfo($p, PATHINFO_BASENAME);
                            }

                            $this->stream($p);
                        }
                }
            } else {
                while(ob_get_level()) @ob_end_clean();
                die();
            }
        }
    }

    /**
     * Expands the path into it's components
     * @return void
     */
    function expandPath(){
        $info = pathinfo($this->path);
        foreach($info as $key => $value) $this->{'_'.$key} = $value;
        $this->_extension = strtolower($this->_extension);
    }

    /**
     * Checks if the file extension is a known image-extension
     * @return bool
     */
    function isImage() {
        global $CONFIG;
        return (in_array(strtolower($this->extension), $CONFIG->extensions->images));
    }

    /**
     * Renames the file
     * @param string $newName New name
     * @return void
     */
    function rename($newName){
        if(preg_match("#".self::rootDir().'/UserDirectory/([^/]+)$#', $this->path)) return false;
        global $USER, $DB;
        if(!$this->may($USER, EDIT)) return false;
        if(!$newName) return false;
        if(strposa($newName, array('/', '\\'))) return false;
        $newPath = $this->dirname . DIRECTORY_SEPARATOR . $newName;
        if(file_exists($newPath)) return false;

        if(!rename($this->path, $newPath)) return false;

        $this->path = $newPath;
        $this->expandPath();
        $DB->files->{(string)$this->ID} = array('name' => $newName);
    }

    /**
     * Moves the file to a new directory
     * @param Folder $to Object or ID of the new parent directory
     * @return bool
     */
    function moveFile($to){
        global $Controller, $USER, $DB;
        if(!$to) return false;
        if(!is_object($to)) $to = $Controller->$to(EDIT);
        if(!$to || !is_a($to, 'Folder')) return false;
        if(!$this->may($USER, EDIT) || !$to->may($USER, EDIT)) return false;

        $pa = $to;
        do {
            if($pa->ID == $this->ID) return false;
        }while($pa = $pa->Dir);

        if(!($p = $to->path) || !$this->ID) return false;
        if(strpos($p, $this->rootDir()) !== 0) return false;
        if(!@rename($this->path, $p.'/'.$this->basename)) return false;
        $DB->files->{(string)$this->ID} = array('parent' => $to->ID);
        return true;
    }

    /**
     * Permission-test overload to allow inheriting permissions from parent folder
     * @see solidbase/lib/Base#may()
     */
    function may($u, $lvl) {
        global $Controller;
        if($Controller->{ADMIN_GROUP}(OVERRIDE)->isMember($u)) return true;
        if(!is_object($u)) $u = $Controller->get($u, OVERRIDE);
        $match = false;
        if(preg_match("#".self::rootDir().'/UserDirectory/([^/]+)(.*)#', $this->path, $match)) {
            if(strpos($match[2], '/') === false && !($lvl & (READ | EDIT))) {
                if(is_bool($r = base::may($u, $lvl))) return $r;
                else return false;
            }
            if(in_array($match[1], array(EVERYBODY_GROUP, MEMBER_GROUP, NOBODY))) return false;
            if($match[1] == $u->ID || in_array($match[1], $u->groupIds)) return true;
        }

        if(is_bool($r = base::may($u, $lvl))) return $r;
        elseif($this->Dir) {
            if(is_bool($r = $this->Dir->may($u, $lvl))) {
                return $r;
            }
        }
        //FIXME: Fulhack?
        if($_REQUEST->raw('popup')) return $r;
        $this->getMenuPos();
        if($this->parent) {
            $r = $this->parent->may($u,$lvl);
        }
        return $r;
    }

    /**
     * Delete the file
     * Cascades the call to parent
     * @see solidbase/lib/Page#delete()
     */
    function delete() {
        global $USER, $DB;
        if($this->may($USER, DELETE)) {
            if($this->Type == 'File') unlink($this->path);
            $DB->files->delete($this->ID);
            parent::delete();
        }
    }

    /**
     * Bypass the full deletion when Page::deleteFromMenu is called
     * @see lib/Page#deleteFromMenu()
     */
    function deleteFromMenu(){
        MenuItem::deleteFromMenu();
    }

    function stream($path = false, $force_download = false) {
        if(!$path) $path = deutf8($this->path);
        $mime = getMime($path);

        while(ob_get_level()>0) ob_end_clean();
        //ob_start('contentSize');
        //ob_start('ob_gzhandler');
        $last_modified_time = filemtime($path);
        $etag = md5_file($path);
        $length = $filesize = filesize($path);
        $offset = 0;


        if (@strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $last_modified_time ||
            @trim($_SERVER['HTTP_IF_NONE_MATCH']) == $etag) {
            header('HTTP/1.1 304 Not Modified');
            exit;
        }


        if ( isset($_SERVER['HTTP_RANGE']) ) {
            // if the HTTP_RANGE header is set we're dealing with partial content
            // Only supports a single range right now.
            // http://stackoverflow.com/questions/157318/resumable-downloads-when-using-php-to-send-the-file
            preg_match('/bytes=(\d+)-(\d+)?/', $_SERVER['HTTP_RANGE'], $range);
            $offset = intval($range[1]);
            $end 	= (@intval($range[2]) > 0 ? intval($range[2]) : $filesize);

            if($offset > $filesize-1 // offset starts at 0, hence filesize-1
                    || $end > $filesize
                    || $offset > $end) {
                header('HTTP/1.1 416 Requested Range Not Satisfiable');
                header('Content-Range: bytes */' . filelength); // Required in 416.
                exit;
            }
            $length = $end-$offset;
            header('HTTP/1.1 206 Partial Content');
            header('Content-Range: bytes ' . $offset . '-' . $end . '/' . $filesize);
        }

        header('Etag: "'.$etag.'"');
        header('Content-Length: '.$length);
        header('Content-Disposition: '.($_REQUEST['action'] === 'download'?'attachment; ':'').'filename="'.pathinfo($path, PATHINFO_BASENAME).'"');
        header('Content-Transfer-Encoding: binary');
        header('Last-Modified: '.gmdate('D, d M Y H:i:s', $last_modified_time).' GMT');
        header('Content-Type: '.$mime);


        if($_REQUEST['action'] == 'download' || $force_download) {
            header('Content-Type: application/force-download', false);
            header('Content-Type: application/download', false);
            header('Content-Description: File Transfer');
        }
        header('Expires: '.gmdate('D, d M Y H:i:s', time()+(2*24*3600)).' GMT');
        header('Cache-Control: private, max-age=172801, must-revalidate');
        header('Cache-Control: no-cache');
        header('Pragma: cache');

        $fp = fopen($path, 'rb');
        if ($fp === false)
            die(); // error!! FIXME
        fseek($fp,$offset);
        print(fread($fp,$length));
        fclose($fp);
        exit;
    }
}

?>
