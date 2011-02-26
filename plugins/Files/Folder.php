<?php

/**
 * Folder
 *
 * @author Jonatan Olofsson [joolo]
 * @version 1.0
 * @license http://creativecommons.org/licenses/by-nc/3.0/ Creative Commons Attribution-Noncommercial 3.0 Unported License
 * @package filesystem
 */

/**
 * The folder class represents a folder in the private files directory
 * @package filesystem
 */
class Folder extends File{
    private $files=array();
    private $folders=array();
    protected $Type='Folder';

    public $editable = array(
        'Upload' => EDIT,
        'PermissionEditor' => EDIT_PRIVILEGES,
        'SubDir' => EDIT
    );

    /**
     * Redirects to File::__construct()
     * @see File#__construct()
     */
    function __construct($fullpath=false, $parent=false) {
        parent::__construct($fullpath, $parent);
        $this->icon = 'small/folder_picture';
        global $Controller;
        if(!$this->isRoot()) {
            $this->editable['FileMover'] = EDIT;
            $this->editable['Delete'] = DELETE;
            $this->alias = 'fileRoot';
        }
    }

    function isRoot() {
        return ($this->path == $this->rootDir());
    }

    function __toString() {
        if($this->isRoot()) return __('Root directory');
        else return parent::__toString();
    }

    /**
     * retrieve data from the object
     * @see lib/File#__get($property)
     */
    function __get($property) {
        if(in_array($property, array('files', 'folders'))) {
            $this->loadStructure();
            return $this->$property;
        } else return parent::__get($property);
    }

    /**
     * Returns a file from the folder
     * @param string $name Filename
     * @return File
     */
    function file($name) {
        $this->loadStructure();
        return @$this->files[$name];
    }

    /**
     * Returns a subfolder
     * @param string $name The name of the folder
     * @return Folder
     */
    function folder($name) {
        $this->loadStructure();
        return @$this->folders[$name];
    }



    /**
     * Display a box with the contents of a folder
     * @param string The name of the folder
     * @param int Maximum number of files shown
     * @param bool Show newest first
     * @return string
     */
    static function dirBox($dir, $max=false, $sortNewest=true) {
        $dirObj = new Folder($dir);
        $files = $dirObj->__get('files');
        if($sortNewest) {
            uasort($files, create_function('$a,$b', 'return (($a->edited)<($b->edited)?-1:($a->edited == $b->edited?0:1));'));
        }
        self::fileExtCSS();
        echo '<div class="box dirbox"><ul>';
        $i=1;
        foreach($files as $file) {
            if($max && $i>$max) break;
            if($file->mayI(READ)) {
                echo '<li class="file ext_'.$file->extension.'">'.$file->Name.'</li>';
                $i++;
            }
        }
        echo '</ul></div>';
    }

    /**
     * Execute action when called for explicitly by the user
     *
     * This function also contains the actions available in the interface provided, including file
     * uploading, compressed file extraction and the creation of folders.
     * @return void
     */
    function run(){
    global $Templates, $USER, $Controller, $ID, $CONFIG;
        /**
         * User input types
         */
        $_REQUEST->setType('action', 'string');
        $_REQUEST->setType('popup', 'string');
        $_REQUEST->setType('filter', 'string');

        if(!$this->may($USER, READ)) errorPage(401);
        else {
            if(!in_array($CMPRExtension = $CONFIG->files->compression_format, array('tar', 'gz', 'tgz', 'tbz', 'zip', 'ar', 'deb'))){
                $CONFIG->files->compression_format = $CMPRExtension = 'zip';
            }
            $render = true;
            switch($_REQUEST['action']) { // All users
                case 'download':
                    global $PREVENT_CSIZE_HEADER;
                    $PREVENT_CSIZE_HEADER = true;
                    while(ob_get_level()) echo ob_get_clean();
                    require_once "File/Archive.php";
                    File_Archive::extract(
                        $this->path,
                        File_Archive::toArchive(
                            $this->filename.'.'.$CMPRExtension,
                            File_Archive::toOutput()
                        )
                    );
                    die();
                default:
                    $this->setContent("main", $this->genHTML());
                    break;
            }
            if($render) {
                $t = 'admin';
                if($_REQUEST['popup'])
                    $t = 'popup';

                $Templates->$t->render();
            }
        }
    }

    /**
     * Adds a file or folder to the internal list of folder contents
     * @param File|Folder $File The object representing the file or folder to add
     * @return void
     */
    function add($File){
        if(is_file($File->path)) {
            $this->files[$File->ID] = $File;
        }
        elseif(is_dir($File->path)) {
            $this->folders[$File->ID] = $File;
        }
        $this->names[] = $File->basename;
    }

    /**
     * Load the actual (filesystem) structure and contents of the folder and syncronize it with the database
     * @param bool $reload Force reload
     * @return unknown_type
     */
    function loadStructure($reload=false) {
        if($this->loaded && !$reload) return;
        $this->loaded = true;
        global $USER, $Controller, $CONFIG, $DB;

        if(!$this->path) return false;
        $extensions = $CONFIG->Files->filter;
        $d = dir($this->path);
        $realNames = array();
        while(false !== ($f = $d->read())) {
            if($f[0] == '.') continue;
            $fullpath = $this->path.'/'.$f;
            $type = (is_file($fullpath)?'File':(is_dir($fullpath)?'Folder':false));
            $info = pathinfo($fullpath);
            if($type == 'File' && !in_array(strtolower($info['extension']), $extensions)) {
                continue;
            }
            if($type != false) {
                $File = new $type($fullpath, $this);
                $this->{strtolower($type).'s'}[$File->basename] = $File;

                $realNames[] = $File->basename;
            }
        }
        $d->close();
        if(empty($realNames))
            $clean = $DB->files->asList(array('parent' => $this->ID));
        else
            $clean = $DB->files->asList(array('parent' => $this->ID, 'name!' => $realNames));
        if(!empty($clean) && is_array($clean)) {
            $DB->files->delete(array('id' => $clean));
            $DB->spine->delete(array('id' => $clean));
        }
        natcasesort($this->files);
        natcasesort($this->folders);
    }
    private $loaded=false;

    function fileExtCSS() {
Head::add('.directory {list-style-image: url(3rdParty/icons/small/folder.png);}
.file {list-style-image: url(/3rdParty/icons/small/page_white.png);}
.ext_jpg, .ext_jpeg, .ext_png, .ext_raw, .ext_psd, .ext_gif {list-style-image: url(/3rdParty/icons/small/picture.png);}
.ext_pdf {list-style-image: url(/3rdParty/icons/small/page_white_acrobat.png);}
.ext_doc {list-style-image: url(/3rdParty/icons/small/page_white_word.png);}
.ext_xls {list-style-image: url(/3rdParty/icons/small/page_white_excel.png);}
.ext_swf {list-style-image: url(/3rdParty/icons/small/page_white_flash.png);}
.ext_tar, .ext_gz, .ext_tgz, .ext_bz2, .ext_tbz, .ext_zip, .ext_ar, .ext_deb {list-style-image: url(/3rdParty/icons/small/page_white_compressed.png);}', 'css-raw');
    }

    function pcrumbs() {
        global $Controller;
        $r = '';
        $d = $this->Dir;
        $ancestors = array();
        while($d) {
            if(!$d->mayI(READ)) break;
            $ancestors[] = array('id' => $d->ID, 'name' => $d->Name);
            $d = $d->Dir;
        }
        $ancestors = array_reverse($ancestors);
        $rootID = @$Controller->fileRoot->ID;
        foreach($ancestors as $i => $a) {
            $r.='<a href="'.url(array('id' => $a['id']), array('popup', 'filter')).'">';
            if($a['id'] == $rootID) {
                $r .= icon('small/house');
            } else {
                $r.= $a['name'];
            }
            $r .= '</a>';
            if($a['id'] == $rootID) $r .=': / ';
            else $r .= ' / ';
        }
        if($this->ID == $rootID) {
            $r .= icon('small/house', __('Root directory')).__('Root directory');
        } else {
            $r.= $this->Name;
        }
        return $r;
    }

    /**
     * Generate the XHTML/CSS to administrate the folder
     * @return void
     */
    function genHTML(){
        $this->loadStructure();
        $this->fileExtCSS();
        global $CONFIG, $Controller;

        Head::add($CONFIG->UI->jQuery_theme.'/jquery-ui-*', 'css-lib');
        $r = '<div class="ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all">'
        .'<span class="fixed-width">'.$this->pcrumbs().'</span>'
        .Box::tools($this)
        .'</div>';

        $r .= '<ul class="filetree">';
        $i=0;
        foreach($this->folders as $cur) {
            if(!$cur->mayI(READ)) continue;
            $r .= '<li class="'.($i%2?'odd':'even').' directory">'
            .'<span class="fixed-width"><a href="'.url(array('id' => $cur->ID), array('popup', 'filter')).'" title="'.__('Open folder').'">'.$cur.'</a></span>'
            .Box::tools($cur)
            .icon('large/down-16', __('Download'), url(array('id' => $cur->ID, 'action' => 'download')))
            .'</li>';
            $i++;
        }
        $r .= '</ul>';
        global $SITE;
    	if($_REQUEST['popup']== "ckeditor") {
		//http://docs.cksource.com/CKEditor_3.x/Developers_Guide/File_Browser_(Uploader)/Custom_File_Browser

		Head::add("function getUrlParam(paramName)
		{
		  var reParam = new RegExp('(?:[\?&]|&amp;)' + paramName + '=([^&]+)', 'i') ;
		  var match = window.location.search.match(reParam) ;

		  return (match && match.length > 1) ? match[1] : '' ;
		}
		var funcNum = getUrlParam('CKEditorFuncNum');
		var fileUrl = 'https://www.ysektionen.se/';
		", 'js-raw');
		Head::add("function select(id) {try{window.opener.CKEDITOR.tools.callFunction(funcNum, 'https://www.ysektionen.se/'+id);} catch(err) {}window.close();}", 'js-raw');
	}else if($_REQUEST['popup']) {
            Head::add("function select(id) {try{window.opener.fileCallback(id,'{$_REQUEST['popup']}');} catch(err) {}window.close();}", 'js-raw');
        }
        foreach($this->files as $cur) {
            if(!$cur->mayI(READ)) continue;
            if($_REQUEST['filter']) {
                switch($_REQUEST['filter']) {
                    case 'images':
                    case 'documents':
                        if(!in_array(strtolower($cur->extension), $CONFIG->extensions->{$_REQUEST['filter']})) continue 2;
                        break;
                    default:
                        if(!stristr($cur->basename, $_REQUEST['filter'])) continue 2;
                }
            }
            $r .= '<li class="'.($i%2?'odd':'even').' file ext_'.$cur->extension.'"><span class="fixed-width"';
            if($_REQUEST['popup']) $r .= '></span><a href="javascript: select('.$cur->ID.');"';
            if($cur->isImage())
            {
                if(!$_REQUEST['popup']) $r .= '><span';
                JS::lib('jquery/imgPreview');
                Head::add('#imgpreview{'
    .'position:absolute;'
    .'border:1px solid #ccc;'
    .'background:#333;'
    .'padding:5px;'
    .'display:none;'
    .'color:#fff;}', 'css-raw');
                $r .= ' class="imagepreview" rel="/'.$cur->ID.'?mw=100">'.$cur->basename;
                if(!$_REQUEST['popup']) $r .= '</span>';
            }
            else $r .= '>'.$cur->basename;
            if($_REQUEST['popup']) $r .= '</a>';
            $r .='</span><div class="tools">'
                .Box::tools($cur)
            .'</div></li>';
            $i++;
        }
        return $r.'</ul>';
    }

    /**
     * Deletes the folder (recursively) and passes the call to parent
     * @see solidbase/lib/File#delete()
     */
    function delete() {
    global $USER;
        if($this->Dir !== 0 && $this->mayI(DELETE)) {
            $this->loadStructure();
            foreach($this->files as $file) {
                $file->delete();
            }
            foreach($this->folders as $folder) {
                $folder->delete();
            }
            rmdir($this->path);
            parent::delete();
        }
    }
}

?>
