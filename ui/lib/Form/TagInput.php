<?php
/**
 * @author Jonatan Olofsson [joolo]
 * @version 1.0
 * @license http://creativecommons.org/licenses/by-nc/3.0/ Creative Commons Attribution-Noncommercial 3.0 Unported License
 * @package GUI
 */

/**
 * Creates a TagInput field
 * @author Kalle Karlsson [kakar]
 * @package GUI
 */
class TagInput extends __FormField {
    private $available_values;

    function __construct($label, $name, $available_values='', $value='', $validate=false, $description=false, $class=false) {
        $this->label = $label;
        $this->name = $name;
        $this->value = $value;
        $this->available_values = $available_values;
        $this->validate = $validate;
        $this->description = $description;
        $this->class = $class;
    }

    function render() {
        $id = idfy($this->name);
        JS::loadjQuery(false);
        if($this->available_values) {
            JS::lib('jquery/jquery.bgiframe.min');
            JS::lib('jquery/jquery-plugin-ajaxqueue');
            JS::lib('jquery/jquery.autocomplete.min');
            Head::add('jquery.autocomplete', 'css-lib');
            JS::raw('$(function(){$("#'.$id.'").autocomplete(["'.join('","', $this->available_values).'"], {width: 320,max: 4,highlight: false,multiple: true,multipleSeparator: " ",scroll: true,scrollHeight: 300})});');
        }
        $r = ($this->label === false? '':'<label for="'.$id.'">'.$this->label.'</label>').'<input name="'.$this->name.'" id="'.$id.'" class="text tags'.($this->validate?' '.$this->validate:'').'" value="'.$this->value.'" />'
                    .($this->description?'<span class="description">'.$this->description.'</span>':'');
        return '<span class="formelem">'.$r.'</span>';
    }
}
?>
