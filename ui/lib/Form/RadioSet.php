<?php
/**
 * @author Jonatan Olofsson [joolo]
 * @version 1.0
 * @license http://creativecommons.org/licenses/by-nc/3.0/ Creative Commons Attribution-Noncommercial 3.0 Unported License
 * @package GUI
 */

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

        $r = '<span class="formelem"><fieldset id="'.$id.'" class="radioset'.($this->validate?' '.$this->validate:'').($this->class?' '.$this->class:'').'"><legend>'.$this->label.'</legend><table cellpadding="0" cellspacing="0" border="0">';
        for($i=0;$i<$rows;$i++) {
            $r .= '<tr>';
            for($j=0;$j<$this->cols;$j++) {
                $r .= '<td>';
                if(@!empty($values[$i+$j*$rows])) {
                    $r .= '<label><input type="radio" class="radio" name="'.$this->name.'" value="'.@$values[$i+$j*$rows].'"'.(($values[$i+$j*$rows] == $this->selected)?' checked="checked"':'').' />'
                        .@$text[$i+$j*$rows].'</label>';
                }
                $r .= '</td>';
            }
            $r .= '</tr>';
        }
        $r .= '</table></fieldset></span>';
        return $r;
    }
}
?>
