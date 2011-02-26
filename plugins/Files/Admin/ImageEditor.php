<?php

class ImageEditor extends Page {
    private $that = false;

    static public $edit_icon = 'small/palette';
    static public $edit_text = 'Edit image';

    function canEdit($obj) {
        return (is_a($obj, 'File') && $obj->isImage());
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
            $this->imageEditor()
        );

        global $Templates;
        $Templates->render();
    }

    function saveChanges() {
        $_POST->setType('filename', 'string');
        $_POST->setType('cropimgx', 'numeric');
        $_POST->setType('cropimgy', 'numeric');
        $_POST->setType('cropimgw', 'numeric');
        $_POST->setType('cropimgh', 'numeric');
        $_REQUEST->setType('mkcopy', 'string');

        if($_REQUEST['filename']) {
            if($_REQUEST['mkcopy']) {
                if($_POST['filename'] != $this->basename && $_POST['filename']) {
                    if(!file_exists($this->dirname.'/'.$_POST['filename'])) {
                        $p = $this->dirname.'/'.$_POST['filename'];
                    } else {
                        Flash::queue(__('File exists. Please give another name or delete the conflicting file. Your changes were not saved.'), 'warning');
                        break;
                    }
                } else {
                    $nrofcopies = count(glob(substr($this->path, 0, -(strlen($this->extension) + 1)) . '_copy*'));
                    if($nrofcopies == 0) $nrofcopies = '';
                    $p = substr($this->path, 0, -(strlen($this->extension) + 1)) . '_copy'.($nrofcopies+1) . substr($this->path, -(strlen($this->extension) + 1));
                }
                touch($p);
                $copy = File::open($p);
            } else {
                if($_POST['filename'] != $this->basename) $this->rename($_POST['filename']);
                $p = $this->path;

                $img = new Image($this->path);

                if($_POST['cropimgw'] && $_POST['cropimgh']) {
                    $width = $img->width();
                    $s = $width / min($width, 400);
                    $img->crop(round($s*$_POST['cropimgx']), round($s*$_POST['cropimgy']), round($s*$_POST['cropimgw']), round($s*$_POST['cropimgh']));
                }
                if($_REQUEST['imgrot']) $img->rotate($_REQUEST['imgrot']);
                $img->save($p);
                Flash::queue(__('Your changes were saved'));
            }
        }
    }

    function imageEditor() {
        JS::loadjQuery();
        JS::lib('jquery/jquery.imgareaselect-*');
        JS::lib('imgTools');
        $size = getimagesize($this->that->path);
        return Form::quick(false, __('Save'),
            new Input(__('Filename'), 'filename', ($_POST['filename']?$_POST['filename']:$this->that->basename)),
            new Hidden('cropimgx', $_POST['cropimgx']),
            new Hidden('cropimgy', $_POST['cropimgy']),
            new Hidden('cropimgw', $_POST['cropimgw']),
            new Hidden('cropimgh', $_POST['cropimgh']),
            new Tabber('img',
                new Tab(__('Cropping'),
                    '<div id="imgcropper"><img style="float: left" id="originalImage" src="?id='.$this->that->ID.'&amp;w=400" /><div style="clear: both;"></div></div>'
                ),
                new Tab(
                    __('Resize'),
                    '<div id="imgresize"><div id="resval" style="clear: right;"><input name="resimgx" id="resimgx" size="4" value="'.$size[0].'" />x<input name="resimgy" id="resimgy" size="4" value="'.$size[1].'" /></div></div>'
                ),
                new Tab(__('Rotate'),
                    new Select(__('Specify rotation (CCW)'), 'imgrot', array('0' => __('None'),'90' => '90 &deg;','180' => '180 &deg;','270' => '270 &deg;'), $_REQUEST['imgrot']))
            )
        );
    }
}
?>
