<?php

class Upload extends Page {
    private $that = false;

    static public $edit_icon = 'large/3uparrow-16';
    static public $edit_text = 'Upload to folder';

    function canEdit($obj) {
        return is_a($obj, 'Folder');
    }

    function __construct($obj){
        parent::__construct($obj->ID);
        $this->that = $obj;
    }

    /**
     * (non-PHPdoc)
     * @see lib/Page#run()
     */
    function run() {
        if($this->saveChanges()) redirect(-1);

        $this->setContent('main',
            $this->uploadPage()
        );

        global $Templates;
        $Templates->render();
    }

    function saveChanges() {
        global $CONFIG;
        $_REQUEST->setType('uncompress', 'any');
        if(isset($_FILES['uFiles']) && $this->may($USER, EDIT)) {

            $u=false;
            $ue=false;
            $extensions = $CONFIG->Files->filter;
            foreach($_FILES['uFiles']['error'] as $i => $e) {
                $parts = explode('.', $_FILES['uFiles']['name'][$i]);
                $extension = array_pop($parts);
                if($e == UPLOAD_ERR_NO_FILE) continue;

                $newPath = $this->that->path.'/'.$_FILES['uFiles']['name'][$i];
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
                            Flash::queue(__('Extraction failed'));
                        }
                        chdir($curdir);
                    }
                    elseif(!in_array(strtolower($extension), $extensions))
                    {
                        Flash::queue(__('Invalid format:').' '.$_FILES['uFiles']['name'][$i], 'warning');
                        continue;
                    }
                    else {
                        $u = (bool)@move_uploaded_file($_FILES['uFiles']['tmp_name'][$i], $newPath);
                    }
                }
                if(!$u) {
                    Flash::queue(__('Upload of file').' "'.$_FILES['uFiles']['name'][$i].'" '.__('failed').' ('.($e?$e:__('Check permissions')).')', 'warning');
                }
            }
            if($u) {
                $this->loadStructure(true);
                Flash::queue(__('Your file(s) were uploaded'));
                return true;
            }
            if($ue) {
                $this->loadStructure(true);
                Flash::queue(__('Your file(s) were uploaded and extracted'));
                return true;
            }
            return false;
        }
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
}
?>
