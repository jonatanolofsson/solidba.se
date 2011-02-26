<?php
/**
 * @author Jonatan Olofsson [joolo]
 * @version 1.0
 * @license http://creativecommons.org/licenses/by-nc/3.0/ Creative Commons Attribution-Noncommercial 3.0 Unported License
 * @package GUI
 */

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

        $r = '<span class="formelem"><fieldset id="'.$id.'" class="checkset'.($this->validate?' '.$this->validate:'').($this->class?' '.$this->class:'').'"><legend>'.$this->label.'</legend><table cellpadding="0" cellspacing="0" border="0">';

        if($vals) {
            for($i=0;$i<$rows;$i++) {
                $r .= '<tr>';
                for($j=0;$j<$this->cols;$j++) {
                    $r .= '<td>';
                    if(@!empty($values[$i+$j*$rows])) {
                        $r .= '<label><input type="checkbox" class="Checkbox" name="'.$this->name.'[]" value="'.@$values[$i+$j*$rows].'"'.($this->selected === true || in_array($values[$i+$j*$rows], $this->selected)?' checked="checked"':'').' />'
                            .@$text[$i+$j*$rows].'</label>';
                    }
                    $r .= '</td>';
                }
                $r .= '</tr>';
            }
        }
        $r .= '</table></fieldset></span>';
        return $r;
    }
}
?>
