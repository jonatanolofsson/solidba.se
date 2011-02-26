<?php
/**
 * @author Jonatan Olofsson [joolo]
 * @version 1.0
 * @license http://creativecommons.org/licenses/by-nc/3.0/ Creative Commons Attribution-Noncommercial 3.0 Unported License
 * @package GUI
 */

/**
 * Creates a select dropdown-box
 * @package GUI
 */
class Select extends __FormField{
    public $data;
    public $multiple;
    public $startEmpty,$nojs,$jsparams;

    /**
     * Sets up internal variables with the arguments given
     * @param string $label The (human-readable) label for the form element
     * @param string $name The (machine-readable) name of the element
     * @param array $data An associative array with a $value => $text relationship
     * @param string|array $selected The value of the selected element(s)
     * @param bool $multiple Adds the "multiple" option to the select-box, making it possible to select multiple values.
     * @param string $startEmpty Pass a string to provide an empty default element at the top of the dropdown-box
     * @param string $validate Adds a validation class to the element
     * @param string $description Add a description to the form element
     * @return void
     */
    function __construct($label, $name, $data, $selected=false, $multiple=false, $startEmpty = false, $validate = false, $description=false, $class=false, $nojs = false, $jsparams=''){
        $this->label = $label;
        $this->name = $name;
        $this->data = $data;
        $this->selected = (is_array($selected)||is_bool($selected)?$selected:array($selected));
        $this->multiple = $multiple;
        $this->startEmpty = $startEmpty;
        $this->validate = $validate;
        $this->description = $description;
        $this->class = $class;
        $this->nojs = $nojs;
        $this->jsparams = $jsparams;
    }

    function render(){
        $id = idfy($this->name);

        if($this->multiple) {
            JS::loadJQuery(true);
            JS::lib('jquery/plugins/localisation/jquery.localisation-min');
            JS::lib('jquery/plugins/scrollTo/jquery.scrollTo-min');
            JS::lib('jquery/ui.multiselect');
            if(!$this->nojs) {
/*FIXME: Translation
                JS::raw('$(function(){$.localise("ui-multiselect", {language: "en", path: "lib/js/locale/"});});');
*/
                JS::raw('$(function(){$("#'.$id.'").multiselect({'.$this->jsparams.'});});');
            }
            Head::add('ui.multiselect', 'css-lib');
            if($this->class) $this->class .= ' ';
            $this->class .= 'multiselect';
        }

        $r= ($this->label === false ? '':'<label for="'.$id.'">'.$this->label.'</label>').'<select id="'.$id.'" name="'.$this->name.($this->multiple?'[]" multiple="multiple"':'"').' class="'.$this->validate.($this->validate?' ':'').$this->class.'">';
        if($this->startEmpty) $r.= '<option value="">'.($this->startEmpty!==true?$this->startEmpty:'').'</option>';
        if(is_array($this->data)) {
            foreach($this->data as $value => $text) {
                if(is_array($text)) {
                    if(isset($text['id'])) {
                        $r.= $this->inflatedGroup($text);
                    } else {
                        $r.= $this->optgroup($value, $text);
                    }
                }
                else {
                    if(is_bool($this->selected)) $s = $this->selected;
                    else {
                        $match_pos = array_search($value, $this->selected, true);
                        if(!$match_pos) $match_pos = array_search($value, $this->selected);
                        $match = ($match_pos === false ? false : $this->selected[$match_pos]);
                        $s = (strcmp($match, $value)===0);
                    }
                    $r.= '<option value="'.$value.'"'
                            .($s? ' selected="selected"':'')
                            .'>'.$text.'</option>';
                }
            }
        }
        $r.= '</select>'
                    .(strpos($this->validate, 'required')!==false?'<span class="reqstar">*</span>':'')
                    .($this->description?'<span class="description">'.$this->description.'</span>'
        //FIXME: &nbsp; is a styling bugfix for floating the multi-select
        :($this->multiple?'&nbsp;':''));
        return '<span class="formelem">'.$r.'</span>';
    }

    function optgroup($title, $values) {
        $r = '<optgroup label="'.$title.'">';
        foreach($values as $value => $text) {
            if(is_array($text)) $r.= $this->optgroup($value, $text);
            else {
                if(is_bool($this->selected)) $s = $this->selected;
                else {
                    $match_pos = array_search($value, $this->selected, true);
                    if(!$match_pos) $match_pos = array_search($value, $this->selected);
                    $match = ($match_pos === false ? false : $this->selected[$match_pos]);
                    $s = (strcmp($match, $value)===0);
                }
                $r.= '<option value="'.$value.'"'
                        .($s? ' selected="selected"':'')
                        .'>'.$text.'</option>';
            }
        }
        $r.='</optgroup>';
        return $r;
    }

    function inflatedGroup($posts, $level=0) {
        global $Controller;
        if(isset($posts['id'])) $posts = array($posts);
        $r = '';
        foreach($posts as $post) {
            if(!($post['id'] && $Obj = $Controller->{(string)$post['id']})) continue;

            $value = $post['id'];

            if(is_bool($this->selected)) $s = $this->selected;
            else {
                $match_pos = array_search($value, $this->selected, true);
                if(!$match_pos) $match_pos = array_search($value, $this->selected);
                $match = ($match_pos === false ? false : $this->selected[$match_pos]);
                $s = (strcmp($match, $value)===0);
            }
            $r.= '<option value="'.$value.'"'
                    .($s? ' selected="selected"':'')
                    .'>'.($level>0?str_repeat('-', $level).'&rsaquo;':'').$Obj.'</option>';
            if(isset($post['children'])) $r.= $this->inflatedGroup($post['children'], $level+1);
        }
        return $r;
    }
}
?>
