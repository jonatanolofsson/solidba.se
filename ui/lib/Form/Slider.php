<?php
/**
 * @author Jonatan Olofsson [joolo]
 * @version 1.0
 * @license http://creativecommons.org/licenses/by-nc/3.0/ Creative Commons Attribution-Noncommercial 3.0 Unported License
 * @package GUI
 */

class Slider extends __FormField{
    var $interval, $return_interval, $selected, $nojs;
    function __construct($label, $name, $interval, $value = false, $return_interval = false, $validate = false, $description = false, $class = false, $nojs = false)
    {
        $this->label = $label;
        $this->name = $name;
        $this->interval = $interval;
        $this->value = $value;
        $this->return_interval = (bool)$return_interval;
        $this->validate = (bool)$this->validate;
        $this->description = $description;
        $this->class = $class;
        $this->nojs = $nojs;
    }

    /**
     * (non-PHPdoc)
     * @see solidbase/lib/__FormField#render()
     */
    function render(){
        $id = idfy($this->name);

        if($this->value === false) $sel = $this->interval;
        else
        {
            $sel = (array)$this->value;
            if(count($sel) == 1) $sel[1] = $sel[0];
        }

        Head::add(JQUERY_THEME.'/jquery-ui-1.8.7.custom', 'css-lib');
        if(!$this->nojs) {
            JS::loadjQuery(true);
            JS::raw('$(function(){$("#'.$id.' .slider").slider({'
            .'range:'.($this->return_interval?'true':'false').','
            .'min:'.$this->interval[0].','
            .'max:'.$this->interval[1].','
            .'values:['.($this->return_interval?$sel[0].','.$sel[1]:$sel[0]).'],'
            .'slide:function(event,ui){'
                .'$(event.target).closest(".slidercontainer")'
                    .'
                    .find(".val_low").text(ui.values[0]).end()'
                    .($this->return_interval?'
                    .find(".val_high").text(ui.values[1]).end()':'')
                    .'
                    .find(".sliderfield").val(ui.values[0]'
                        .($this->return_interval?'+":"+ui.values[1]':'')
                    .'
                    );'
            .'}});});');
        }
        JS::raw('$(".sliderfield").hide();');
        return '<span class="formelem"><div id="'.$id.'" class="slidercontainer">'.($this->label === false ? '':'<label for="'.$id.'">'.$this->label.'</label>')
                    .'<input name="'.$this->name.'" id="'.$id.'_value" class="sliderfield'.($this->validate?' '.$this->validate:'').($this->class?' '.$this->class:'').'" value="'.($this->return_interval?$sel[0].":".$sel[1]:$sel[0]).'" />'
                    .'<div class="slidervalues">'
                    .'<span class="slidertext val_low">'.$sel[0].'</span>'
                    .($this->return_interval?'<span class="slidertext sliderdash">-</span><span class="slidertext val_high">'.$sel[1].'</span>':'')
                    .'</div>'
                    .'<div class="slider"></div>'
                    .(strpos($this->validate, 'required')!==false?'<span class="reqstar">*</span>':'')
                    .($this->description?'<span class="description">'.$this->description.'</span>':'')
                    .'</div></span>';
    }
}
?>
