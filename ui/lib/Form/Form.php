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
                                        ?url(false, true)
                                        :(is_array($this->action)
                                            ?url($this->action)
                                            :$this->action))
                                .'" method="'.$this->method.'"'.(stripos($rt, 'type="file"')!==false?' enctype="multipart/form-data"':'');
        if(!$this->id) $r.= '>';
        else $r .= ' id="'.$this->id.'"><div style="display: none;"><input type="hidden" value="'.time().'" name="'.$this->id.'" /></div>';
        $r .= '<div class="formdiv">'.$rt.'</div>';
        if($this->submittext !== false) $r .= '<div class="submit">'.new Submit(($this->submittext?$this->submittext:__('Submit'))).'</div>';
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
?>
