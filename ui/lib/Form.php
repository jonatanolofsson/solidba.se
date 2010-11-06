<?php
/**
 * @author Jonatan Olofsson [joolo]
 * @version 1.0
 * @license http://creativecommons.org/licenses/by-nc/3.0/ Creative Commons Attribution-Noncommercial 3.0 Unported License
 * @package GUI
 */
/**
 * This class helps the programmer to quickly render a
 * fully-featured standardized form with little effort
 * @package GUI
 * @todo Form validation
 */
class Form{
    public $action;
    public $id;
    public $submittext;
    public $method='post';
    public $validate = true;

    /**
     * The form is constructed with a few (optional) parameters
     * @param string $id
     * @param string $action The action URL
     * @param string|bool$submittext The text of the submitbutton. Set to false to disable submitbutton
     * @param string $method The form's method (GET/POST)
     * @return void
     */
    function __construct($id=false, $action = false, $submittext='', $method='post', $jQueryValidation=true) {
        $this->action = $action;
        $this->id = ($id?$id:uniqid('fid'));
        $this->submittext = $submittext;
        $this->method = ($method?$method:'post');
        $this->validate = $jQueryValidation;
    }

    /**
     * Quick way to make a form.
     * First argument is form action.
     * Second is the label of the submit button.
     * The rest of the arguments will be sent to Form::collection and the result returned
     * @return string
     *
     */
    function quick() {
        $args = func_get_args();
        $form = new Form(false, array_shift($args), array_shift($args));
        return $form->collection($args);
    }

    /**
     * Arrange and flatten the incoming elements
     * @param array $args
     * @return array
     */
    function __flatten($args){
        $res = array();
        foreach($args as $a) {
            if(is_array($a)) $res = array_merge($res, $this->__flatten($a));
            else $res[] = $a;
        }
        return $res;
    }
    /**
     * Creates the form for outputting
     * Usage example
     * <code>
    *	$form = new Form('uploadToFolder', url(null, 'id'));
    *	return $form->collection(
    *		new Fieldset(__('Select files'),
    *			new FileUpload(__('File to upload'), 'uFiles[]'),
    *			new CheckBox(__('Uncompress compressed files'), 'uncompress', false)
    *		),
    *		new Fieldset(__('Select another file'),
    *			new FileUpload(__('File to upload'), 'uFiles[]'),
    *			new CheckBox(__('Uncompress compressed files'), 'uncompress', false)
    *		)
    *	);
     * </code>
     * @param The objects that make up the form
     * @return string
     */
    public function collection() {
        $args = func_get_args();
        $args = $this->__flatten($args);
        $rt='';
        foreach($args as $arg){
            if(is_object($arg) && method_exists($arg, 'render'))
                $rt .= $arg->render();
            elseif(is_string($arg)) $rt .= $arg;
        }

        $r = '<form action="'.(empty($this->action)
                                        ?url()
                                        :(is_array($this->action)
                                            ?url($this->action)
                                            :$this->action))
                                .'" method="'.$this->method.'"'.(stripos($rt, 'type="file"')!==false?' enctype="multipart/form-data"':'');
        if(!$this->id) $r.= '>';
        else $r .= ' id="'.$this->id.'"><div style="display: none;"><input type="hidden" value="'.time().'" name="'.$this->id.'" /></div>';
        $r .= $rt;
        if($this->submittext !== false) $r .= '<div class="submit"><input type="submit" class="submit linkbutton_yellow" name="save" value="'.($this->submittext?$this->submittext:__('Submit')).'" /></div>';
        $r.='</form>';

        if($this->validate)
        {
            JS::lib('jquery/jquery-validate/jquery.validate.min');
            JS::raw('$(function(){$("#'.$this->id.'").validate();});');
        }
        return $r;
    }

    public function set() {
        $args = func_get_args();
        return $this->collection(new Set($args));
    }

    public function Tabber() {
        $args = func_get_args();
        return $this->collection(new Tabber($args));
    }

    public function open() {
        return '<form action="'.(empty($this->action)
                                        ?url()
                                        :(is_array($this->action)
                                            ?url($this->action)
                                            :$this->action))
                                .'" method="'.$this->method.'">';
    }

    public function close() {
        $r='';
        if($this->submittext !== false) $r .= '<div class="submit"><input type="submit" class="submit" name="save" value="'.($this->submittext?$this->submittext:__('Submit')).'" /></div>';
        $r.='</form>';
        return $r;
    }
}

/**
 * The abstract class which all form elements extend
 * Contains property definitions, output functions and the demand
 * for a render()-function in each form object.
 * @package GUI
 */
abstract class __FormField{
    public $label;
    public $name;
    public $value;
    public $type;
    public $validate;
    public $description;
    public $class;
    private $out;

    /**
     * Each form element must contain a function
     * named render() to comply with the abstract class
     * Returns the HTML-representation of the form element
     * @return string
     */
    abstract function render();

    /**
     * Simplifies output of form
     * @return unknown_type
     */
    function __toString() {
        return $this->render();
    }
}

/**
 * A classical textbox
 * @package GUI
 */
class Input extends __FormField{
    /**
     * Sets up internal variables with the arguments given
     * @param string $label The (human-readable) label for the form element
     * @param string $name The (machine-readable) name of the element
     * @param string $value The initial contents of the element
     * @param string $validate Adds a validation class to the element
     * @param string $description Add a description to the form element
     * @return void
     */
    function __construct($label, $name, $value='', $validate=false, $description=false, $class = false){
        $this->label = $label;
        $this->name = $name;
        $this->value = $value;
        $this->validate = $validate;
        $this->description = $description;
        $this->class = $class;
    }

    /**
     * (non-PHPdoc)
     * @see solidbase/lib/__FormField#render()
     */
    function render(){
        $id = idfy($this->name);
        return ($this->label === false ? '':'<label for="'.$id.'">'.$this->label.'</label>').'<input name="'.$this->name.'" id="'.$id.'" class="text'.($this->validate?' '.$this->validate:'').($this->class?' '.$this->class:'').'" value="'.$this->value.'" />'
                    .(strpos($this->validate, 'required')!==false?'<span class="reqstar">*</span>':'')
                    .($this->description?'<span class="description">'.$this->description.'</span>':'');
    }
}

/**
 * Creates a submit button
 * @package GUI
 *
 */
class Submit extends __FormField{
    /**
     * Sets up internal variables with the arguments given
     * @param string $label The (human-readable) label for the form element
     * @param string $name The (machine-readable) name of the element
     * @return void
     */
    function __construct($label, $name='submit', $class = false){
        $this->label = $label;
        $this->name = $name;
        $this->class = $class;
    }
    /**
     * (non-PHPdoc)
     * @see solidbase/lib/__FormField#render()
     */
    function render(){
        $id = idfy($this->name);
        return '<input name="'.$this->name.'" type="submit" id="'.$id.'" class="submit'.($this->class?' '.$this->class:'').'" value="'.$this->label.'" />'
                    .(strpos($this->validate, 'required')!==false?'<span class="reqstar">*</span>':'');
    }
}

/**
 * Creates an upload field
 * @package GUI
 */
class FileUpload extends __FormField{
    /**
     * Sets up internal variables with the arguments given
     * @param string $label The (human-readable) label for the form element
     * @param string $name The (machine-readable) name of the element
     * @param string $validate Adds a validation class to the element
     * @param string $description Add a description to the form element
     * @return void
     */
    function __construct($label, $name, $validate=false, $description=false, $class=false){
        $this->label = $label;
        $this->name = $name;
        $this->validate = $validate;
        $this->description = $description;
        $this->class = $class;
    }
    /**
     * (non-PHPdoc)
     * @see solidbase/lib/__FormField#render()
     */
    function render(){
        $id = idfy($this->name);
        return ($this->label === false ? '':'<label for="'.$id.'">'.$this->label.'</label>').'<input name="'.$this->name.'" id="'.$id.'" type="file" class="file'.($this->validate?' '.$this->validate:'').($this->validate?' ':'').$this->class.'" />'
                    .(strpos($this->validate, 'required')!==false?'<span class="reqstar">*</span>':'')
                    .($this->description?'<span class="description">'.$this->description.'</span>':'');
    }
}

/**
 * Creates a password field
 * @package GUI
 */
class Password extends __FormField{
    /**
     * Sets up internal variables with the arguments given
     * @param string $label The (human-readable) label for the form element
     * @param string $name The (machine-readable) name of the element
     * @param string $value The initial contents of the element
     * @param string $validate Adds a validation class to the element
     * @param string $description Add a description to the form element
     * @return void
     */
    function __construct($label, $name, $value='', $validate=false, $description=false, $class = false){
        $this->label = $label;
        $this->name = $name;
        $this->value = $value;
        $this->validate = $validate;
        $this->description = $description;
        $this->class = $class;
    }
    /**
     * (non-PHPdoc)
     * @see solidbase/lib/__FormField#render()
     */
    function render(){
        $id = idfy($this->name);
        return ($this->label === false ? '':'<label for="'.$id.'">'.$this->label.'</label>').'<input name="'.$this->name.'" type="password" id="'.$id.'" class="password'.($this->validate?' '.$this->validate:'').'" value="'.$this->value.'" />'
                    .(strpos($this->validate, 'required')!==false?'<span class="reqstar">*</span>':'')
                    .($this->description?'<span class="description">'.$this->description.'</span>':'');
    }
}

/**
 * Creates a hidden field
 * @package GUI
 */
class Hidden extends __FormField{
    /**
     * Sets up internal variables with the arguments given
     * @param string $name The (machine-readable) name of the element
     * @param string $value The initial contents of the element
     * @return void
     */
    function __construct($name, $value=''){
        $this->name = $name;
        $this->value = $value;
    }
    /**
     * (non-PHPdoc)
     * @see solidbase/lib/__FormField#render()
     */
    function render(){
        $id = idfy($this->name);
        return '<input name="'.$this->name.'" id="'.$id.'" type="hidden" value="'.$this->value.'" />';
    }
}
/**
 * Creates a select dropdown-box
 * @package GUI
 */
class Select extends __FormField{
    public $data;
    public $multiple;
    public $startEmpty;

    /**
     * Sets up internal variables with the arguments given
     * @param string $label The (human-readable) label for the form element
     * @param string $name The (machine-readable) name of the element
     * @param array $data An associative array with a $value => $text relationship
     * @param string|array $selected The value of the selected element(s)
     * @param bool $multiple Adds the "multiple" option to the select-box, making it possible to select multiple values
     * @param string $startEmpty Pass a string to provide an empty default element at the top of the dropdown-box
     * @param string $validate Adds a validation class to the element
     * @param string $description Add a description to the form element
     * @return void
     */
    function __construct($label, $name, $data, $selected=false, $multiple=false, $startEmpty = false, $validate = false, $description=false, $class=false){
        $this->label = $label;
        $this->name = $name;
        $this->data = $data;
        $this->selected = (is_array($selected)?$selected:array($selected));
        $this->multiple = $multiple;
        $this->startEmpty = $startEmpty;
        $this->validate = $validate;
        $this->description = $description;
        $this->class = $class;
    }

    function render(){
        $id = idfy($this->name);

        if($this->multiple) {
            JS::lib('jquery/jquery.multiSelect');
            Head::add('multiSelect/jquery.multiSelect', 'css-lib');
            Head::add('$(function(){$(".multiselect").multiSelect({selectAll: false, noneSelected: "'.__('Select').'",oneOrMoreSelected: "% '.__(' selected').'"});});', 'js-raw');
            if($this->class) $this->class .= ' ';
            $this->class .= 'multiselect';
        }

        $r= ($this->label === false ? '':'<label for="'.$id.'">'.$this->label.'</label>').'<select id="'.$id.'" name="'.$this->name.($this->multiple?'[]" multiple="multiple"':'"').' class="'.$this->validate.($this->validate?' ':'').$this->class.'">';
        if($this->startEmpty) $r.= '<option value="">'.($this->startEmpty!==true?$this->startEmpty:'').'</option>';
        if(is_array($this->data)) {
            foreach($this->data as $value => $text) {
                $match_pos = array_search($value, $this->selected, true);
                if(!$match_pos) $match_pos = array_search($value, $this->selected);
                $match = ($match_pos === false ? false : $this->selected[$match_pos]);
                if(is_array($text)) {
                    if(isset($text['id'])) {
                        $r.= $this->inflatedGroup($text);
                    } else {
                        $r.= $this->optgroup($value, $text);
                    }
                }
                else $r.= '<option value="'.$value.'"'
                            .(strcmp($match, $value)===0? ' selected="selected"':'')
                            .'>'.$text.'</option>';
            }
        }
        $r.= '</select>'
                    .(strpos($this->validate, 'required')!==false?'<span class="reqstar">*</span>':'')
                    .($this->description?'<span class="description">'.$this->description.'</span>'
        //FIXME: &nbsp; is a styling bugfix for floating the multi-select
        :($this->multiple?'&nbsp;':''));
        return $r;
    }

    function optgroup($title, $values) {
        $r = '<optgroup label="'.$title.'">';
        foreach($values as $value => $text) {
            $match_pos = array_search($value, $this->selected, true);
            if(!$match_pos) $match_pos = array_search($value, $this->selected);
            $match = ($match_pos === false ? false : $this->selected[$match_pos]);
            if(is_array($text)) $r.= $this->optgroup($value, $text);
            else $r.= '<option value="'.$value.'"'
                        .(strcmp($match, $value)===0? ' selected="selected"':'')
                        .'>'.$text.'</option>';
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

            $match_pos = array_search($value, $this->selected, true);
            if(!$match_pos) $match_pos = array_search($value, $this->selected);
            $match = ($match_pos === false ? false : $this->selected[$match_pos]);
            if(isset($post['children'])) $r.= $this->inflatedGroup($post['children'], $level+1);
            else $r.= '<option value="'.$value.'"'
                        .(strcmp($match, $value)===0? ' selected="selected"':'')
                        .'>'.($level>0?str_repeat('&nbsp;', $level).'&rsaquo;':'').$Obj.'</option>';
        }
        return $r;
    }
}


/**
 * Creates and initializes a tinyMCE editing area on top of a textfield
 * @package GUI
 */
class HTMLField extends __FormField{
    /**
     * Sets up internal variables with the arguments given
     * @param string $label The (human-readable) label for the form element
     * @param string $name The (machine-readable) name of the element
     * @param string $value The initial contents of the element
     * @param string $validate Adds a validation class to the element
     * @param string $description Add a description to the form element
     * @return void
     */
    function __construct($label, $name, $value='', $validate=false, $description=false, $class=false){
        $this->label = $label;
        $this->name = $name;
        $this->value = $value;
        $this->validate = $validate;
        $this->description = $description;
        $this->class = $class;
    }

    function render(){
        $id = idfy($this->name);
        Head::add('jquery-1*', 'js-lib');
        Head::add('3rdParty/tiny_mce/tiny_mce_gzip.js', 'js-url', true, false, false);
        Head::add('tinyMCEGZinit', 'js-lib', true, false, false);
        //Head::add('3rdParty/tiny_mce/tiny_mce.js', 'js-url', true, false, false);
        Head::add('tinyMCEinit', 'js-lib', true, false, false);
        return ($this->label === false ? '':'<label for="'.$id.'">'.$this->label.'</label>').'<div class="tinyMCEtoggle" style="text-align: right; width: 100%; display: hidden;">
            <span class="editor_type">'.__('Select editor').': </span><select name="editor_type" class="editor_type">
                <option value="simple">'.__('Simple').'</option>
                <option value="advanced">'.__('Advanced').'</option>
                <option value="off">'.__('Off').'</option>
            </select></div>
            <textarea id="'.$id.'" name="'.$this->name.'" class="textarea mceEditor'.($this->validate?' '.$this->validate:'').($this->validate?' ':'').$this->class.'" rows="8" cols="70">'.$this->value.'</textarea>'
                    .(strpos($this->validate, 'required')!==false?'<span class="reqstar">*</span>':'')
                .($this->description?'<span class="description">'.$this->description.'</span>':'');
    }
}

/**
 * Creates a textarea
 * @package GUI
 */
class TextArea extends __FormField{
    public $rows = 8;
    public $cols = 70;
    /**
     * Sets up internal variables with the arguments given
     * @param string $label The (human-readable) label for the form element
     * @param string $name The (machine-readable) name of the element
     * @param string $value The initial contents of the element
     * @param string $validate Adds a validation class to the element
     * @param string $description Add a description to the form element
     * @return void
     */
    function __construct($label, $name, $value='', $validate=false, $description=false, $class = false, $rows=8, $cols=70){
        $this->label = $label;
        $this->name = $name;
        $this->value = $value;
        $this->validate = $validate;
        $this->description = $description;
        $this->class = $class;
        $this->rows = $rows;
        $this->cols = $cols;
    }

    function render(){
        $id = idfy($this->name);
        return ($this->label === false ? '':'<label for="'.$id.'">'.$this->label.'</label>').'<textarea id="'.$id.'" name="'.$this->name.'" class="textarea'.($this->validate?' '.$this->validate:'').($this->class?' '.$this->class:'').'" rows="'.$this->rows.'" cols="'.$this->cols.'">'.$this->value.'</textarea>'
                    .(strpos($this->validate, 'required')!==false?'<span class="reqstar">*</span>':'')
                    .($this->description?'<span class="description">'.$this->description.'</span>':'');
    }
}

/**
 * Creates a single Checkbox
 * @package GUI
 */
class Checkbox extends __FormField{
    public $checked = false;
    /**
     * Sets up internal variables with the arguments given
     * @param string $label The (human-readable) label for the form element
     * @param string $name The (machine-readable) name of the element
     * @param string $value The initial contents of the element
     * @param string $validate Adds a validation class to the element
     * @param string $description Add a description to the form element
     * @return void
     */
    function __construct($label, $name, $checked = false, $validate=false, $description=false, $class = false){
        $this->label = $label;
        $this->name = $name;
        $this->checked = $checked;
        $this->validate = $validate;
        $this->description = $description;
        $this->class = $class;
    }

    function render(){
        $id = idfy($this->name);
        return ($this->label === false ? '':'<label for="'.$id.'">'.$this->label.'</label>').'<input name="'.$this->name.'" value="'.$this->name.'" id="'.$id.'" type="'.get_class().'" class="'.__CLASS__.($this->validate?' '.$this->validate:'').($this->class?' '.$this->class:'').'"'.($this->checked?' checked="checked"':'').' />'
                    .(strpos($this->validate, 'required')!==false?'<span class="reqstar">*</span>':'')
                    .($this->description?'<span class="description">'.$this->description.'</span>':'');
    }
}
/**
 * Creates a single Checkbox
 * @package GUI
 */
class Minicheck extends __FormField{
    public $checked = false;
    /**
     * Sets up internal variables with the arguments given
     * @param string $label The (human-readable) label for the form element
     * @param string $name The (machine-readable) name of the element
     * @param string $value The initial contents of the element
     * @param string $validate Adds a validation class to the element
     * @param string $description Add a description to the form element
     * @return void
     */
    function __construct($label, $name, $checked = false, $validate=false,$value=false,$class=false){
        $this->label = $label;
        $this->name = $name;
        $this->checked = $checked;
        $this->validate = $validate;
        if($value === false) $value = $this->name;
        $this->value = $value;
        $this->class = $class;

    }

    function render(){
        $id = idfy($this->name);
        return '<input name="'.$this->name.'" value="'.$this->value.'" id="'.$id.'" type="Checkbox" class="'.__CLASS__.($this->validate?' '.$this->validate:'').($this->class?' '.$this->class:'').'"'.($this->checked?' checked="checked"':'').' />'
            .($this->label === false ? '':'<label for="'.$id.'" class="Minicheck">'.$this->label.'</label>')
                    .(strpos($this->validate, 'required')!==false?'<span class="reqstar">*</span>':'')
                    .($this->description?'<span class="description">'.$this->description.'</span>':'');
    }
}

/**
 * Creates a single Radio button
 * @package GUI
 */
class Radio extends Checkbox{}

/**
 * Creates a set of Checkboxes
 * @package GUI
 */
class CheckSet extends __FormField{
    public $cols;
    public $selected;

    /**
     * Sets up internal variables with the arguments given
     * @param string $label The (human-readable) label for the form element
     * @param string $name The (machine-readable) name of the element
     * @param array $data An associative array with a $value => $text relationship
     * @param $selected Which box(es) that should be selected
     * @param integer $cols Number of columns that should be presented
     * @return void
     */
    function __construct($label, $name, $data, $selected = false, $cols = 3, $validate=false, $class = false){
        $this->label = $label;
        $this->name = $name;
        $this->data = $data;
        $this->selected = $selected;
        $this->cols = $cols;
        $this->validate = $validate;
        $this->class = $class;
    }

    /**
     * (non-PHPdoc)
     * @see solidbase/lib/__FormField#render()
     */
    function render(){
        $id = idfy($this->name);
        $vals = count($this->data);
        $rows = ceil($vals/$this->cols);
        $values = array_keys($this->data);
        $text = array_values($this->data);

        if($this->selected == false) $this->selected = array();
        if(is_string($this->selected)) $this->selected = explode(',',$this->selected);

        $r = '<fieldset id="'.$id.'" class="checkset'.($this->validate?' '.$this->validate:'').($this->class?' '.$this->class:'').'"><legend>'.$this->label.'</legend><table cellpadding="0" cellspacing="0" border="0">';

        if($vals) {
            for($i=0;$i<$rows;$i++) {
                $r .= '<tr>';
                for($j=0;$j<$this->cols;$j++) {
                    $r .= '<td>';
                    if(@!empty($values[$i+$j*$rows])) {
                        $r .= '<label><input type="Checkbox" class="Checkbox" name="'.$this->name.'[]" value="'.@$values[$i+$j*$rows].'"'.($this->selected === true || in_array($values[$i+$j*$rows], $this->selected)?' checked="checked"':'').' />'
                            .@$text[$i+$j*$rows].'</label>';
                    }
                    $r .= '</td>';
                }
                $r .= '</tr>';
            }
        }
        $r .= '</table></fieldset>';
        return $r;
    }
}

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
        Head::add('imgPicker', 'js-lib');
        Head::add('setupPreview("'.$id.'");', 'js-raw');
        //FIXME: Flytta styling till stylesheet!
        return ($this->label === false ? '':'<label for="'.$id.'">'.$this->label.'</label>').'<input name="'.$this->name.'" id="'.$id.'" class="text'.($this->validate?' '.$this->validate:'').'" value="'.$this->value.'" style="width:278px;margin-right:5px;" />'
        .'<div class="tools">'.icon('small/cross', __('Remove'), "javascript:removePreview('$id');", $id."remicon").icon('small/folder_picture', __('Browse picture'), "javascript:explore('$id', ".($this->dir ? $this->dir : 'false').");").'</div>'
                    .(strpos($this->validate, 'required')!==false?'<span class="reqstar">*</span>':'')
        .($this->description?'<span class="description">'.$this->description.'</span>':'').($this->preview?'<div id="'.$id.'prev" style="margin:10px 0 5px 150px;"><img id="'.$id.'img" src="index.php?id='.$this->value.'&mw=300" /></div>':'');
    }
}

/**
 * Creates a sortable list
 * @author Jonatan Olofsson [joolo]
 * @package GUI
 */
class Sortable extends __FormField {
    private $DBtable;

    function __construct($label, $name, $value='', $validate=false, $description=false, $class=false) {
        $this->label = $label;
        $this->name = $name;
        $this->value = $value;
        $this->validate = $validate;
        $this->description = $description;
        $this->class = $class;
    }

    function render() {
        $id = idfy($this->name);
        JS::loadjQuery();
        Head::add('$(function(){$(".sortable_list").sortable();});', 'js-raw');
        $val = (array)$this->value;
        array_walk($val, array($this, 'addHiddenFormField'));
        return ($this->label === false? '':'<label for"'.$id.'">'.$this->label.'</label>').listify($val, 'sortable_list'.($this->validate?' '.$this->validate:''))
                    .(strpos($this->validate, 'required')!==false?'<span class="reqstar">*</span>':'')
            .($this->description?'<span class="description">'.$this->description.'</span>':'');
    }

    function addHiddenFormField(&$txt, $key) {
        $txt = new Hidden($this->name.'[]', $key).$txt;
    }
}

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
        return $r;
    }
}

/**
 * Creates a jQuery UI Datepicker input field
 * @author Jonatan Olofsson
 *
 */
class Datepicker extends Input {
    var $format = 'yy-mm-dd';

    /**
     *
     * @param string $label The (human-readable) label for the form element
     * @param string $name The (machine-readable) name of the element
     * @param string $format The format of the input. Defaults to ISO 8601: yy-mm-dd
     * @param string $value The initial contents of the element
     * @param string $validate Adds a validation class to the element
     * @param string $description Add a description to the form element
     * @return void
     */
    function __construct($label, $name, $value = false, $validate=false, $description = false, $class = false) {
        $this->label = $label;
        $this->name = $name;
        $this->value = $value;
        $this->validate = $validate;
        $this->description = $description;
        $this->class = $class;
    }

    function render() {
        $id = idfy($this->name);
        if(is_array($this->value)) $value = Short::parseDateAndTime($this->value);
        global $CONFIG;
        JS::loadjQuery(false);
        JS::lib(array('jquery/date', 'jquery/jquery.datePicker'));
        JS::lib('jquery/jquery.bgiframe.min', true);
        Head::add('$(function(){$(".Datepicker").datePicker();});', 'js-raw');
        Head::add('datePicker', 'css-lib');
        return ($this->label === false ? '':'<label for="'.$id.'">'.$this->label.'</label>').'<input name="'.$this->name.'" id="'.$id.'" class="Datepicker'.($this->validate?' '.$this->validate:'').($this->class?' '.$this->class:'').'" value="'.(is_numeric($this->value)?date('Y-m-d', $this->value):$this->value).'" />'
                    .(strpos($this->validate, 'required')!==false?'<span class="reqstar">*</span>':'')
                    .($this->description?'<span class="description">'.$this->description.'</span>':'');
    }
}

/**
 * Creates a jQuery Timepickr input field
 * @author Jonatan Olofsson
 *
 */
class Timepickr extends Input {

    /**
     *
     * @param string $label The (human-readable) label for the form element
     * @param string $name The (machine-readable) name of the element
     * @param string $value The initial contents of the element
     * @param string $validate Adds a validation class to the element
     * @param string $description Add a description to the form element
     * @return void
     */
    function __construct($label, $name, $value=false, $validate=false, $description=false, $class = false) {
        $this->label = $label;
        $this->name = $name;
        $this->value = $value;
        $this->validate = $validate;
        $this->description = $description;
        $this->class = $class;
    }

    function render() {
        $id = idfy($this->name);
        if(is_array($this->value)) $value = Short::parseDateAndTime($this->value);
        global $CONFIG;
        JS::loadjQuery(true);
        JS::lib('jquery/jquery.timePicker');
        Head::add('timePicker', 'css-lib');

        Head::add('$(function(){$("input.time").timePicker();});', 'js-raw');

        return ($this->label === false ? '':'<label for="'.$id.'">'.$this->label.'</label>').'<input name="'.$this->name.'" id="'.$id.'" class="time'.($this->validate?' '.$this->validate:'').($this->class?' '.$this->class:'').'" value="'.(is_numeric($this->value)?date('H:i', $this->value):$this->value).'" />'
                    .(strpos($this->validate, 'required')!==false?'<span class="reqstar">*</span>':'')
                    .($this->description?'<span class="description">'.$this->description.'</span>':'');
    }
}

/**
 * Creates a set of Radio-buttons
 * @package GUI
 */
class RadioSet extends __FormField{
    public $cols;
    public $selected;

    /**
     * Sets up internal variables with the arguments given
     * @param string $label The (human-readable) label for the form element
     * @param string $name The (machine-readable) name of the element
     * @param array $data An associative array with a $value => $text relationship
     * @param $selected Which box(es) that should be selected
     * @param integer $cols Number of columns that should be presented
     * @return void
     */
    function __construct($label, $name, $data, $selected = false, $cols = 3, $validate=false, $class = false){
        $this->label = $label;
        $this->name = $name;
        $this->data = $data;
        $this->selected = $selected;
        $this->cols = $cols;
        $this->validate = $validate;
        $this->class = $class;
    }

    /**
     * (non-PHPdoc)
     * @see solidbase/lib/__FormField#render()
     */
    function render(){
        $id = idfy($this->name);
        $vals = count($this->data);
        $rows = ceil($vals/$this->cols);
        $values = array_keys($this->data);
        $text = array_values($this->data);

/*      Head::add('
.Radioset table {width:100%;}
.Radioset label {width:100%;}
.Radioset td    {width:33%;vertical-align:top;padding:1em;}', 'css-raw');
*/

        if($this->selected == false) $this->selected = array();

        $r = '<fieldset id="'.$id.'" class="Radioset'.($this->validate?' '.$this->validate:'').($this->class?' '.$this->class:'').'"><legend>'.$this->label.'</legend><table cellpadding="0" cellspacing="0" border="0">';
        for($i=0;$i<$rows;$i++) {
            $r .= '<tr>';
            for($j=0;$j<$this->cols;$j++) {
                $r .= '<td>';
                if(@!empty($values[$i+$j*$rows])) {
                    $r .= '<label><input type="Radio" class="Radio" name="'.$this->name.'" value="'.@$values[$i+$j*$rows].'"'.(($values[$i+$j*$rows] == $this->selected)?' checked="checked"':'').' />'
                        .@$text[$i+$j*$rows].'</label>';
                }
                $r .= '</td>';
            }
            $r .= '</tr>';
        }
        $r .= '</table></fieldset>';
        return $r;
    }
}

/**
 *
 *
 */
class FormText{
    var $label;
    var $text;
    /**
     * Constructor
     */
    function __construct($label, $text){
        $this->label = $label;
        $this->text = $text;
    }

    function render(){
        return ($this->label === false ? '':'<label>'.$this->label.'</label>')
            .($this->text?$this->text:'');
    }
}

/**
 * Creates a fielset wrapper for more input elements
 * @package GUI
 */
class FieldSet extends Set {

    /**
     * This takes as arguments the legend of the fieldset and all the containing input elements
     * Example:
     * <code>
    *	$form = new Form('uploadToFolder', url(null, 'id'));
    *	return $form->collection(
    *		new Fieldset(__('Select files'),
    *			new FileUpload(__('File to upload'), 'uFiles[]'),
    *			new CheckBox(__('Uncompress compressed files'), 'uncompress', false)
    *		),
    *		new Fieldset(__('Select another file'),
    *			new FileUpload(__('File to upload'), 'uFiles[]'),
    *			new CheckBox(__('Uncompress compressed files'), 'uncompress', false)
    *		)
    *	);
     * </code>
     * @param string $name The name of the fieldset
     * @param mixed $content The contents of the fieldset
     * @return void
     */
    function __construct(){
        $a = func_get_args();
        if(count($a)==1 && is_array($a[0])) $a = $a[0];
        $this->name = array_shift($a);
        $this->content = $this->__filter($a);
    }
    /**
     * Delivers the contents of the object, wrapped in a fieldset and an ordered list
     * @return void
     */
    function render(){
        $id = idfy($this->name);
        $r = '<fieldset id="'.$id.'"'.(empty($this->class)?'':' class="'.$this->class.'"').'><legend>'.$this->name.'</legend><ol class="set">';
        $hidden = '';
        $i=1;
        foreach($this->content as $c) {
            if(is_object($c) && method_exists($c, 'render')) {
                if(is_a($c, 'Hidden')) $hidden .= $c->render();
                else
                    $r .= '<li class="'.($i++%2?'evenform':'oddform').'">'.$c->render().'</li>';
            } elseif(is_string($c)) $r .= '<li class="'.($i++%2?'evenform':'oddform').'">'.$c.'</li>';
        }
        return $r.'</ol>'.$hidden.'</fieldset>';
    }
}

/**
 * Creates a set (without the field) to wrap the contents (using an ordered list)
 * @package GUI
 */
class Set{
    public $id;
    public $selected;
    public $disabled;
    public $name;
    public $deselectable;
    public $class;
    protected $content;

    /**
     * Accepts it's content as incoming arguments
     * @param mixed $content Contained elements
     * @return void
     */
    function __construct(){
        $a = func_get_args();
        $this->content = $this->__filter($a);
    }

    function count() {
        return count($this->content);
    }

    /**
     * Arrange and flatten the incoming elements
     * @param array $args
     * @return array
     */
    function __filter($args){
        $res = array();
        foreach($args as $a) {
            if(is_array($a)) $res = array_merge($res, $this->__filter($a));
            else $res[] = $a;
        }
        return $res;
    }

    /**
     * Simplify and standardize output
     * @return void
     */
    function __toString() {
        return $this->render();
    }

    /**
     * Add an element to the end of the set
     * @param mixed $what What's to be appended
     * @return void
     */
    function add($what) {
        $this->content[] = $what;
    }

    /**
     * Delivers the contents of the object, wrapped in an ordered list
     * @return void
     */
    function render(){
        $r = '<ol class="set'.($this->class?' '.$this->class:'').'">';
        $hidden = '';
        $i=1;
        foreach($this->content as $c) {
            if(is_object($c) && method_exists($c, 'render')) {
                if(is_a($c, 'Hidden')) $hidden .= $c->render();
                else
                    $r .= '<li class="'.($i++%2?'evenform':'oddform').'">'.$c->render().'</li>';
            } elseif(is_string($c)) $r .= '<li class="'.($i++%2?'evenform':'oddform').'">'.$c.'</li>';
        }
        return $r.'</ol>'.$hidden.'';
    }
}


/**
 * The Li(st) element sums up it's arguments and returns it as a single form element
 * @package GUI
 */
class Li {
    private $content;
    /**
     * Constructor
     * @access protected
     */
    function __construct(){
        $this->content = func_get_args();
    }

    function __toString() {
        return $this->render();
    }

    /**
     * Add an element to the end of the set
     * @param mixed $what What's to be appended
     * @return void
     */
    function add($what) {
        $this->content[] = $what;
    }

    function render(){
        $value = '';
        foreach($this->content as $a) $value .= (string)$a;
        if(strpos($value, '<span class="reqstar">*</span>'))
        {
            $value = str_replace($value, '<span class="reqstar">*</span>', '').'<span class="reqstar">*</span>';
        }
        return $value;
    }
}

?>
