<?php
/**
 * @author Kalle Karlsson [kakar]
 * @version 1.0
 * @package Content
 */
/**
 * Gallery class
 * Handles the display of the content in a gallery directory (and subdirectories)
 * It dosen't use a database to store info, for easy maintenance.
 */

class Gallery extends Page{
    static $VERSION = 1;
    static public function installable() {return __CLASS__;}
    //static public function uninstallable() {return __CLASS__;}
    static public function upgradable() {return __CLASS__;}

    private $galleryDirLocal;
    private $galleryDirPublic;
    private $thumbDirLocal;
    private $thumbDirPublic;

    //Paths to ignore
    private $ignore = array('.', '..', 'thumbs', 'Thumbs.db');

    private $itemsAPage;
    private $thumbMaxSize = 125;
    private $maxMenuLevel = 4;

    private $dirs;
    private $activeDirs;
    private $albumName;
    private $ualbumName;
    private $page;

    function install() {
        global $CONFIG;

        $CONFIG->Gallery->setDescription('Gallery_Path', 'Local path to the archive');
        $CONFIG->Gallery->setDescription('Gallery_webpath', 'Webpath to the archive');
        $CONFIG->Gallery->setDescription('Thumbnail_Path', 'Local path to the thumb directory. If blank, thumbs will be stored in the gallery dir.');
        $CONFIG->Gallery->setDescription('Thumbnail_webpath', 'Local path to the thumb directory. If blank, thumbs will be stored in the gallery dir.');
        $CONFIG->Gallery->setDescription('Items_a_Page', 'How many items should be shown on each page');
        $CONFIG->Gallery->Items_a_Page = 20;
        $CONFIG->Gallery->setDescription('Items_a_Page', 'Number of items displayed on a page');
        $this->alias = 'gallery';
    }

    function upgrade() {}

    function __construct($id=false, $language=false){
        global $CONFIG;
        parent::__construct($id, $language);
        $this->suggestName('Gallery');
        $this->itemsAPage		= $CONFIG->Gallery->Items_a_Page;
        $this->galleryDirLocal 	= $CONFIG->Gallery->Gallery_Path;
        $this->thumbDirLocal 	= $CONFIG->Gallery->Thumbnail_Path;
        $this->thumbDirPublic 	= $CONFIG->Gallery->Thumbnail_webpath;
        $this->galleryDirPublic = '/'.$this->alias.'/';
    }

    function run(){
        global $Templates, $CONFIG;
        $_GET->setType('path', 'string');
        $_GET->setType('page', 'numeric');
        $this->page = $_GET['page'];
        if(!$this->page || !is_numeric($this->page) || $this->page < 1) $this->page = 1;

        //JS::loadjQuery(false);
        //JS::lib('jquery/jquery.timer*');
        //JS::lib('jquery/jquery.lightbox*');
        //Head::add('lightbox/jquery.lightbox-0.5.css', 'css-lib');
        //FIXME: Yweb-sökväg!!!!!!!
        //Head::add('/templates/yweb/js/subnav.js', 'js-url');
        Head::add('/templates/yweb/gallery.css', 'css-url');

        $path = ($_REQUEST['path']?$_REQUEST['path']:@substr($_SERVER['REQUEST_URI'], strlen($this->galleryDirPublic)));
        $upath = urldecode($path);
        $path = deutf8($upath);


        if(substr($path, 0, strlen($this->thumbDirPublic)) == $this->thumbDirPublic) {
            $rpath = realpath($this->thumbDirLocal.substr($upath, strlen($this->thumbDirPublic)));
        } else {
            $rpath = realpath($this->getPathLocal().$path);
        }

        if(is_file($rpath)) {
            if(strpos($rpath, $this->getPathLocal()) === 0
            || strpos($rpath, $this->getThumbPathLocal()) === 0) {
                File::stream($rpath, !isImage($rpath));
            } else errorPage(401);
        } else {
            $this->albumName = $path;
            $this->ualbumName = $upath;
            $this->setContent('menu', $this->submenu());
            $this->setContent('main',$this->displayGallery());
            $Templates->yweb('empty')->render();
        }
    }

    /**
     * Main function for displaying the archive
     * @return string
     */
    function displayGallery() {
        global $Controller;
        $fileList = $this->getDirList($this->getPathLocal());
        $total = count($fileList);
        Head::add('var imgList = [ '.$this->getLightboxStr($fileList).' ]; $(".gallery a.image").lightBox({ txtImage: "'.__('Image').'", txtOf: "'.__('of').'", imageArray: imgList });', 'js-raw');

        $pageInfo = Pagination::getRange($this->itemsAPage, $total);
        $start = $pageInfo['range']['start'];
        $stop = $pageInfo['range']['stop'];

        $r = $this->getTitle();
        if($total > 0) $r .= '<p>'.($start+1).' - '.($stop>=$total? $total : $stop+1).' '.__('of').' '.$total.' '.__('objects').'</p>';

        $r .= '<hr /><p class="gallery">';
        $i = 0;

        if($total == 0) $r .= '<p>'.__('Empty folder').'</p>';
        else foreach($fileList as $file) {
            if($start <= $i && $i <= $stop) {
                $fname = utf8(htmlentities($file[1]));
                if($file[0] == 'file') {
                    switch($this->getMime($this->getPathLocal($file[1]))) {
                        case 'image':
                            $r .= '<a class="image" href="'.$this->galleryDirPublic.str_replace('%2F', '/', rawurlencode($this->ualbumName.($this->ualbumName?'/':'').$file[1])).'" title="'.htmlentities($this->getEXIF($file[1])).'">'.$this->getThumb($file[1]).'</a>';
                            break;
                        case 'pdf':
                            $r .= '<a href="'.$this->galleryDirPublic.str_replace('%2F', '/', rawurlencode($this->albumName.($this->ualbumName?'/':'').$fname)).'" title="'.$fname.'">'.icon('large/mail_new-64').'<span class="text">'.$fname.'</span></a>';
                            break;
                        case 'audio':
                            $r .= '<a href="'.$this->galleryDirPublic.str_replace('%2F', '/', rawurlencode($this->albumName.($this->albumName?'/':'').$fname)).'" title="'.$fname.'">'.icon('large/playsound-64').'<span class="text">'.$fname.'</span></a>';
                            break;
                        case 'movie':
                            $r .= '<a href="'.$this->galleryDirPublic.str_replace('%2F', '/', rawurlencode($this->albumName.($this->albumName?'/':'').$fname)).'" title="'.$fname.'">'.icon('large/camera-64').'<span class="text">'.$fname.'</span></a>';
                            break;
                        default:
                            $r .= '<a href="'.$this->galleryDirPublic.str_replace('%2F', '/', rawurlencode($this->albumName.($this->albumName?'/':'').$fname)).'" title="'.$fname.'">'.icon('large/attach-64').'<span class="text">'.$fname.'</span></a>';
                    }
                } elseif($file[0] == 'dir') {
                    $r .= '<a href="'.$this->galleryDirPublic.$this->ualbumName.($this->ualbumName?'/':'').$fname.'" title="'.$fname.'">'.icon('large/folder-64').'<span class="text">'.$fname.'</span></a>';
                }
            }
            $i++;
        }
        $r .= '</p><hr />';
        if($total > $this->itemsAPage) $r .= $pageInfo['links'];
        return $r;
    }


    /**
     * Makes an array of the content in a dir
     * @param string $dir Directory to be listed
     * @return array
     */
    function getDirList($dir) {
        $dir = deutf8($dir);
        $dirList= array();
        $dir = (strpos($dir, '..')!==false?false:glob($dir));
        if(is_array($dir)) $dir = @$dir[0];
        if($dir && is_dir($dir)) {
            if($dh= opendir($dir)) {
                while(($file = readdir($dh)) !== false) {
                    $fileType= filetype($dir . $file);
                    if($fileType == "dir" && !in_array($file, $this->ignore) && $file[0] != '.') {
                        $dirList[$file."?".$fileType] = array($fileType, $file, filesize($dir . $file), filemtime($dir . $file));
                    } elseif( $fileType == "file" && !in_array($file, $this->ignore) && $file[0] != '.') {
                        $dirList[$file."?".$fileType] = array($fileType, $file, filesize($dir . $file), filemtime($dir . $file));
                    }
                }
                closedir($dh);
            }
        } else {
            die('Error: '.$dir.': Not a directory');
        }
        return $this->sortDirList($dirList);
    }

    /**
     * Sorts the content of a directory acording to content type(file or dir) and filename(alfanumeric)
     * @param array $dirList Array with the content of a directory, with arraykey structure [filename?filetype]
     * @return array
     */
    function sortDirList($dirList) {
        function cmp($a, $b){
            $a= explode("?", $a);
            $b= explode("?", $b);

            if($a[1] == "file") {
                if($b[1] == "file") {
                    if($a[0] == $b[0]) return 0;
                    if($a[0] > $b[0]) return 1;
                    else return -1;
                } else return 1;
            } else {
                if($b[1] == "file")	return -1;
                else {
                    if($a[0] == $b[0]) return 0;
                    if($a[0] > $b[0]) return 1;
                    else return -1;
                }
            }
        }
        uksort($dirList, "cmp");
        return $dirList;
    }



    /**
     * Creates a thumbnail of the image
     * @param string $file name of the file
     * @return string
     */
    function getThumb($file) {
        $image = new Image($this->getPathLocal($file));
        if(!in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), array('png', 'gif', 'jpg', 'jpeg'))) $file .= '.jpg';

        /* disabled because of problems with the safe emailer preg_replace queries
        if($image->thumbnail() !== false) {
            return $image->thumbnail($this->thumbMaxSize,$this->thumbMaxSize);
        } else {*/
            if(!file_exists($this->getThumbPathLocal($file))) {
                $w = $image->width(); $h = $image->height();
                if($w >= $h) $image->resize($this->thumbMaxSize);
                else $image->resize(false,$this->thumbMaxSize);
                if(!is_dir($this->getThumbPathLocal(''))) mkdir($this->getThumbPathLocal(''), 0755, true);
                $image->save($this->getThumbPathLocal($file));
            }
            $fname = htmlentities(utf8($file));
            return '<img src="'.$this->galleryDirPublic.str_replace('%2F', '/', rawurlencode($this->thumbDirPublic.$this->ualbumName.($this->ualbumName?'/':'').$fname)).'" alt="'.$fname.'" />';
        //}
    }


    /**
     * Returns the local path to a file in the gallery on the server
     * @param string $file name of the file
     * @return string
     */
    function getPathLocal($file='') {
        return $this->galleryDirLocal.$this->albumName.($this->albumName?'/':'').$file;
    }

    /**
     * Return the local path to the thumbnail of a file
     * @param string $file name of the file
     * @return string
     */
    function getThumbPathLocal($file='') {
        return $this->thumbDirLocal.$this->ualbumName.($this->ualbumName?'/':'').utf8($file);
    }

    /**
     *
     */
    function submenu() {
        return '';
        $this->activeDirs = explode('/',$this->albumName);
        $dirList = '<div id="subnav">';
        $dirList .= '<ul class="menu"><li>'.$this->link();
        //$dirList .= $this->array2List($this->dir2Array(rtrim($this->root.$this->galleryDirLocal,"/"), rtrim($this->galleryDirLocal,"/")));
        $dirList .= '</li></ul></div>';
        return $dirList;
    }


    function dir2Array($directory, $prevDir='', $level=0) {
        $array_items = array();
        if($dh = @opendir($directory)) {
            while(false !== ($file = readdir($dh))) {
                if($level <= $this->maxMenuLevel && !in_array($file, $this->ignore) && is_dir($directory.'/'.$file) && $file[0] != '.') {
                    $array_items = array_merge($array_items, $this->dir2Array($directory.'/'.$file, $file, ($level+1)));
                    $array_items[] = array('parent' => $prevDir, 'id' => utf8($file));
                }
            }
            closedir($dh);
        }
        return inflate($array_items);
    }

    function array2List($dirs, $level=0, $parents='') {
        $list = '<ul>';
        foreach($dirs as $dir){
            $list .= '<li';
            if($level < count($this->activeDirs) && $dir['id'] == $this->activeDirs[$level]) $list .= ' class="activeli"';
            $list .= '><a href="'.$this->galleryDirPublic.$parents.$dir['id'].'">'.$this->formatName($dir['id']).'</a>';
            if(array_key_exists('children',$dir)) $list .= $this->array2List($dir['children'],$level+1,$parents.$dir['id'].'/');
            $list .= '</li>';
        }
        $list .= '</ul>';

        return $list;
    }

    /**
     * Creats the album header with linked breadcrumbs
     * @return string
     */
    function getTitle() {
        $title = '<h1 class="title">';
        if(empty($this->ualbumName)) $title .= $this;
        else {
            $title .= $this->link().' :: ';
            $albums = explode("/", $this->ualbumName);
            $t = '';
            $parents = '';
            foreach($albums as $album) {
                if($parents.$album != $this->ualbumName) {
                    $aname = htmlentities($album, ENT_QUOTES, 'UTF-8');
                    $t .= ' - <a href="'.$this->galleryDirPublic.$parents.$album.'" title="'.__('Back to').' '.$aname.'">'.$aname.'</a>';
                } else {
                    $t .= ' - '.htmlentities($album);
                }
                $parents .= $album.'/';
            }
            $t = substr($t, 2);
            $title .= $t;
        }
        $title .= '</h1>';
        return $title;
    }

    /**
     * Detect mime type
     * @param $path Path to file to investigate
     * @return string Detected mime type
     */
    function getMime($path) {
        $path_parts = pathinfo($path);
        switch(strtolower($path_parts['extension'])) {
            case 'jpg':
            case 'jpe':
            case 'jpeg':
            case 'gif':
            case 'png':
            case 'tif':
            case 'tiff':
                return 'image';
            case 'mpg':
            case 'avi':
            case 'mov':
                return 'video';
            case 'mp3':
            case 'wma':
            case 'wav':
                return 'audio';
            case 'pdf':
                return 'pdf';
            default:
                return mime_content_type($path);
        }
    }

    function formatName($name) {
        if( strlen($name) > 20)
            $name = substr($name,0,17).'...';
        return htmlentities($name);
    }

    /**
     * Returns the EXIF-info from a file.
     * The file have to be a JPEG or TIFF to work
     * @param string $file name of file
     * @return string
     */
    function getEXIF($file) {
        return '';
        $exif = exif_read_data($this->getPathLocal($file));
        $r = __('Name').': '.$exif['FileName']."\n";
        $r .= __('Size').': '.$exif['COMPUTED']['Width'].' x '.$exif['COMPUTED']['Height'].' px'."\n";
        $size = floatval($exif['FileSize']);
        $size  /= pow(2,10);
        if($size > 1024) {
            $size /= pow(2,10);
            $r .= __('Filesize').': '.round($size,1).'MB'."\n";
        } else $r .= __('Filesize').': '.round($size,0).'KB'."\n";
        $r .= __('Last changed').': '.date('Y-m-d', $exif['FileDateTime']);
        return $r;
    }

    function getLightboxStr($fileList) {
        $list = '';
        foreach($fileList as $file) {
            if($file[0] == 'file' && $this->getMime($this->getPathLocal($file[1])) == 'image') {
                $list .= '["'.$this->galleryDirPublic.$this->albumName.($this->albumName?'/':'').$file[1].'","'.$file[1].'"],';
            }
        }
        return rtrim($list,',');
    }
}
?>
