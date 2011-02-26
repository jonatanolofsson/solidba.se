<?php
/**
 * @author Jonatan Olofsson [joolo]
 * @version 1.0
 * @license http://creativecommons.org/licenses/by-nc/3.0/ Creative Commons Attribution-Noncommercial 3.0 Unported License
 * @package GUI
 */

/**
 * Creates a ImagePicker field
 * @author Kalle Karlsson
 * @package GUI
 */
class ImagePicker extends __FormField {
    public $preview;
    public $dir;

    /**
     * Sets up internal variables with the arguments given
     * @param string $label The (human-readable) label for the form element
     * @param string $name The (machine-readable) name of the element
     * @param array $data The url to the image-object
     * @param string $validate Adds a validation class to the element
     * @param string $description Add a description to the form element
     * @param bool $preview Adds a preview of the choosen image
     * @return void
     */
    function __construct($label, $name, $value='', $validate=false, $description=false, $preview=true, $dir=false, $class=false) {
        $this->label = $label;
        $this->value = $value;
        $this->name = $name;
        $this->validate = $validate;
        $this->preview = $preview;
        $this->description = $description;
        $this->class = $class;

        global $Controller;
        if($dir && (is_a($dir, 'User') || is_a($dir, 'Group'))) {
            $dir = Files::userDir($dir);
        }
        if($dir) {
            if(!is_object($dir)) $dir = $Controller->{(string)$dir};
            if(!$dir->mayI(READ)) $dir = false;
            $this->dir = $dir->ID;
        } else $this->dir = false;
    }

    function render() {
        $id = idfy($this->name);
        JS::loadjQuery(false);
        JS::lib('imgPicker');
        JS::raw('setupPreview("'.$id.'");');
        //FIXME: Flytta styling till stylesheet!
        return '<span class="formelem">'.($this->label === false ? '':'<label for="'.$id.'">'.$this->label.'</label>').'<input name="'.$this->name.'" id="'.$id.'" class="text'.($this->validate?' '.$this->validate:'').'" value="'.$this->value.'" type="hidden" />'
        .'<div class="tools">'.icon('small/cross', __('Remove'), "javascript:removePreview('$id');", $id."remicon").icon('small/folder_picture', __('Browse picture'), "javascript:explore('$id', ".($this->dir ? $this->dir : 'false').");").'</div>'
                    .(strpos($this->validate, 'required')!==false?'<span class="reqstar">*</span>':'')
        .($this->description?'<span class="description">'.$this->description.'</span>':'').($this->preview?'<div id="'.$id.'prev" style="margin:10px 0 5px 150px;"><img id="'.$id.'img" src="index.php?id='.$this->value.'&mw=300" /></div>':'')
        .'</span>';
    }
}
?>
