<?php

/**
 * Folder
 *
 * @author Jonatan Olofsson [joolo]
 * @version 1.0
 * @license http://creativecommons.org/licenses/by-nc/3.0/ Creative Commons Attribution-Noncommercial 3.0 Unported License
 * @package Filesystem
 */

/**
 * The folder class represents a folder in the private files directory
 * @package Filesystem
 */
class Folder extends File{
    private $Files=array();
    private $Folders=array();
    protected $Type='Folder';

    /**
     * Redirects to File::__construct()
     * @see File#__construct()
     */
    function __construct($fullpath=false, $parent=false) {
        parent::__construct($fullpath, $parent);
        $this->icon = 'small/folder_picture';
    }

    /**
     * retrieve data from the object
     * @see lib/File#__get($property)
     */
    function __get($property) {
        if(in_array($property, array('Files', 'Folders'))) {
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
        return @$this->Files[$name];
    }

    /**
     * Returns a subfolder
     * @param string $name The name of the folder
     * @return Folder
     */
    function folder($name) {
        $this->loadStructure();
        return @$this->Folders[$name];
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
        $files = $dirObj->__get('Files');
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
        $_REQUEST->setType('del', 'numeric');
        $_REQUEST->setType('fname', 'string');
        $_REQUEST->setType('action', 'string');
        $_REQUEST->setType('popup', 'string');
        $_REQUEST->setType('filter', 'string');
        $_REQUEST->setType('referrer', 'string');
        $_REQUEST->setType('uploadToFolder', 'any');
        $_REQUEST->setType('uncompress', 'any');
        $_REQUEST->addType('edit', 'numeric');

        if(!$this->may($USER, READ)) errorPage(401);
        else {
            if($_REQUEST['del'] && $v = $Controller->{$_REQUEST['del']}(DELETE)) {
                $pid = @$this->Dir->ID;
                $v->delete();
                if($_REQUEST['del'] == $ID) redirect($pid);
                Flash::create(__('The file/directory was deleted'));
            } elseif($_REQUEST->nonempty('fname')
                    && strposa($_REQUEST['fname'], array('..', '/', '\\')) === false
                    && $this->may($USER, EDIT)) {
                if(@mkdir($this->path.'/'.$_REQUEST['fname'], 0700)) {
                    Flash::create('The folder was created successfully');
                } else {
                    Flash::create('There was a problem creating the directory. Check permissions and the name');
                }
            } elseif($_REQUEST['uploadToFolder'] && isset($_FILES['uFiles'])
                    && $this->may($USER, EDIT)) {

                $u=false;
                $ue=false;
                $extensions = $CONFIG->Files->filter;
                foreach($_FILES['uFiles']['error'] as $i => $e) {
                    $parts = explode('.', $_FILES['uFiles']['name'][$i]);
                    $extension = array_pop($parts);
                    if($e == UPLOAD_ERR_NO_FILE) continue;

                    $newPath = $this->path.'/'.$_FILES['uFiles']['name'][$i];
                    if($e == UPLOAD_ERR_OK) {
                        if($_REQUEST['uncompress'] && in_array(strtolower(strrchr($_FILES['uFiles']['name'][$i], '.')), array('.tar', '.gz', '.tgz', '.bz2', '.tbz', '.zip', '.ar', '.deb')))
                        {
                            $tmpfile = $_FILES['uFiles']['tmp_name'][$i].$_FILES['uFiles']['name'][$i];
                            rename($_FILES['uFiles']['tmp_name'][$i], $tmpfile);
                            $u = true;
                            require_once "File/Archive.php";
                            error_reporting(E_ALL);
                            $curdir = getcwd();
                            chdir($this->path);
                            //FIXME: FIXME!
                            if(@File_Archive::extract(
                                File_Archive::filter(
                                    File_Archive::predExtension($extensions),
                                    File_Archive::read($tmpfile.'/*')
                                ),
                                File_Archive::toFiles()
                            ) == null) {
                                $ue = true;
                            }
                            else {
                                Flash::create(__('Extraction failed'));
                            }
                            chdir($curdir);
                        }
                        elseif(!in_array(strtolower($extension), $extensions))
                        {
                            Flash::create(__('Invalid format:').' '.$_FILES['uFiles']['name'][$i], 'warning');
                            continue;
                        }
                        else {
                            $u = (bool)@move_uploaded_file($_FILES['uFiles']['tmp_name'][$i], $newPath);
                        }
                    }
                    if(!$u) {
                        Flash::create(__('Upload of file').' "'.$_FILES['uFiles']['name'][$i].'" '.__('failed').' ('.($e?$e:__('Check permissions')).')', 'warning');
                    }
                }
                if($u) {
                    $this->loadStructure(true);
                    Flash::create(__('Your file(s) were uploaded'));
                }
                if($ue) {
                    $this->loadStructure(true);
                    Flash::create(__('Your file(s) were uploaded and extracted'));
                }
                $_REQUEST->clear('uploadToFolder', 'action');
            }
            if(!in_array($CMPRExtension = $CONFIG->Files->compression_format, array('tar', 'gz', 'tgz', 'tbz', 'zip', 'ar', 'deb'))){
                $CONFIG->Files->compression_format = $CMPRExtension = 'zip';
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
                    $r = '<div class="nav"><a href="'.url(array('id' => 'files'), array('popup', 'filter')).'">'.icon('small/sitemap_color').__('Overview').'</a></div>';
                    $this->content = array("main" => $r.$this->genHTML());
                    break;
            }
            if($this->mayI(EDIT))
            {
                switch($_REQUEST['action']) { // Actions that require EDIT privileges
                    case 'move':
                        return parent::run();
                    case 'upload':
                        $this->content = array('header' => __('Upload files to').' '.$this->filename, 'main' => $this->uploadPage());
                        break;
                    case 'newFolder':
                        $this->content = array('header' => __('Create new subfolder'), 'main' => $this->newFolder());
                        break;
                    case 'moveok':
                        Flash::create(__('The file/folder was successfully moved'), 'confirmation');
                        break;
                }
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
            $this->Files[$File->ID] = $File;
        }
        elseif(is_dir($File->path)) {
            $this->Folders[$File->ID] = $File;
        }
        $this->names[] = $File->basename;
    }

    /**
     * Display the page for creation of a new subfolder
     * @return void
     */
    function newFolder() {
        $_REQUEST->clear('action');
        $_GET->clear('action');
        $form = new Form('newFolder', url(null, true), __('Create'));
        return '<div class="nav"><a href="'.url(null, true).'">'.icon('small/arrow_left').__('Back to folder').'</a></div>'
        .$form->collection(new Fieldset(__('New folder'),
            new Input(__('Folder name'), 'fname')));
    }

    /**
     * Display the page for file uploading
     * @return void
     */
    function uploadPage() {
        $form = new Form('uploadToFolder', url(null, true));
        return $form->collection(
            new Fieldset(__('Select files'),
                new FileUpload(__('File to upload'), 'uFiles[]'),
                new FileUpload(__('File to upload'), 'uFiles[]'),
                new FileUpload(__('File to upload'), 'uFiles[]'),
                new FileUpload(__('File to upload'), 'uFiles[]'),
                new CheckBox(__('Uncompress compressed files'), 'uncompress', false)
            )
        );
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
            if($type == 'File' && @!in_array(strtolower($info['extension']), $extensions)) {
                continue;
            }
            if($type != false) {
                $File = new $type($fullpath, $this);
                $this->{$type.'s'}[$File->basename] = $File;

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
        natcasesort($this->Files);
        natcasesort($this->Folders);
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

    /**
     * Generate the XHTML/CSS to administrate the folder
     * @return void
     */
    function genHTML(){
        $this->loadStructure();
        $this->fileExtCSS();
        global $CONFIG, $Controller;

        Head::add($CONFIG->UI->jQuery_theme.'/jquery-ui-*', 'css-lib');
        $r = '';
        $r .= '<div class="ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all"><span class="fixed-width">';
        if($this->Dir !== 0) {
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
            $r .= $this->Name;
        } else {
            $r .= __('Root folder');
        }
        $r	.=	'</span><div class="tools">'
                    .($this->mayI(EDIT_PRIVILEGES)?icon('small/key', __('Edit permissions'), url(array('id' => 'PermissionEditor', 'edit' => $this->ID, 'referrer' => $this->ID), array('popup', 'filter'))):'')
                    .($this->mayI(EDIT)?icon('small/folder_add', __('Create subfolder'), url(array('id' => $this->ID, 'action' => 'newFolder'), array('popup', 'filter'))):'')
                    .(($this->mayI(DELETE) && $this->Dir !== 0)?icon('small/delete', __('Delete'), url(array('id' => $this->ID, 'del' => $this->ID), array('popup', 'filter'))):'')
                    .($this->mayI(EDIT)?icon('large/3uparrow-16', __('Upload to folder'), url(array('id' => $this->ID, 'action' => 'upload'), array('popup', 'filter'))):'')
                    .icon('large/down-16', __('Download'), url(array('id' => $this->ID, 'action' => 'download'), array('popup', 'filter')))
                .'</div></div>';
        $r .= '<ul class="filetree">';
        $i=0;
        foreach($this->Folders as $cur) {
            if(!$cur->mayI(READ)) continue;
            $r .= '<li class="'.($i%2?'odd':'even').' directory"><span class="fixed-width"><a href="'.url(array('id' => $cur->ID), array('popup', 'filter')).'" title="'.__('Open folder').'">'.$cur.'</a></span><div class="tools">'
                .($cur->mayI(EDIT_PRIVILEGES)?icon('small/key', __('Edit permissions'), url(array('id' => 'PermissionEditor', 'edit' => $cur->ID, 'referrer' => $this->ID), array('popup', 'filter'))):'')
                .($cur->mayI(EDIT)?icon('small/door_in', __('Move'), url(array('id' => $cur->ID, 'action' => 'move'), array('popup', 'filter'))):'')
                .($cur->mayI(DELETE)?icon('small/delete', __('Delete'), url(array('del' => $cur->ID), array('id', 'popup', 'filter'))):'')
                .($cur->mayI(EDIT)?icon('small/folder_add', __('Create subfolder'), url(array('id' => $cur->ID, 'action' => 'newFolder'), array('popup', 'filter'))):'')
                .($cur->mayI(EDIT)?icon('large/3uparrow-16', __('Upload to folder'), url(array('id' => $cur->ID, 'action' => 'upload'), array('popup', 'filter'))):'')
                .icon('large/down-16', __('Download'), url(array('id' => $cur->ID, 'action' => 'download'), array('popup', 'filter')))
            .'</div>';
            $r .= '</li>';
            $i++;
        }
        global $SITE;
        if($_REQUEST['popup']) {
            Head::add("function select(id) {try{window.opener.fileCallback(id,'{$_REQUEST['popup']}');} catch(err) {}window.close();}", 'js-raw');
        }
        foreach($this->Files as $cur) {
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
                Head::add('#imgpreview{
    position:absolute;
    border:1px solid #ccc;
    background:#333;
    padding:5px;
    display:none;
    color:#fff;}', 'css-raw');
                $r .= ' class="imagepreview" rel="/'.$cur->ID.'?mw=100">'.$cur->basename;
                if(!$_REQUEST['popup']) $r .= '</span>';
            }
            else $r .= '>'.$cur->basename;
            if($_REQUEST['popup']) $r .= '</a>';
            $r .='</span><div class="tools">'
                .(($cur->mayI(EDIT))?icon('small/pencil', __('Edit file'), url(array('id' => $cur->ID, 'action' => 'edit'), array('popup', 'filter'))):'')
                .($cur->mayI(EDIT_PRIVILEGES)?icon('small/key', __('Edit permissions'), url(array('id' => 'PermissionEditor', 'edit' => $cur->ID, 'referrer' => $this->ID), array('popup', 'filter'))):'')
                .($cur->mayI(EDIT)?icon('small/door_in', __('Move'), url(array('id' => $cur->ID, 'action' => 'move'), array('popup', 'filter'))):'')
                .($cur->mayI(DELETE)?icon('small/delete', __('Delete'), url(array('del' => $cur->ID), array('id', 'popup', 'filter'))):'')
                .icon('large/down-16', __('Download'), url(array('id' => $cur->ID, 'action' => 'download'), array('popup', 'filter')))
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
        if($this->Dir !== 0 && $this->may($USER, DELETE)) {
            $this->loadStructure();
            foreach($this->Files as $file) {
                $file->delete();
            }
            foreach($this->Folders as $folder) {
                $folder->delete();
            }
            rmdir($this->path);
            parent::delete();
        }
    }
}

?>
