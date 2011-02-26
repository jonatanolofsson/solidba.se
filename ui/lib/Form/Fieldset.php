<?php
/**
 * @author Jonatan Olofsson [joolo]
 * @version 1.0
 * @license http://creativecommons.org/licenses/by-nc/3.0/ Creative Commons Attribution-Noncommercial 3.0 Unported License
 * @package GUI
 */

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
?>
