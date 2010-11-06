<?php

/**
 * @author Jonatan Olofsson [joolo]
 * @version 1.0
 * @license http://creativecommons.org/licenses/by-nc/3.0/ Creative Commons Attribution-Noncommercial 3.0 Unported License
 * @package GUI
 */
/**
 * The Tabber class provides a simple interface for creating a tabular view, using jquery
 * @package GUI
 */
class Tabber{
    private $tabs;
    private $uniqid;

    /**
     * Initiates the tab and filling it with contents
     * Example of usage:
     * <code>
     * $Form->collection(
    *				new Tabber('ps',
    *					new Tab(	__('Page settings'),
    *						new Input(__('Title'), 'title', @$page->Name, 'requried'),
    *						new Input(__('Alias'), 'alias', @$page->alias),
    *						new TextArea(__('Description'), 'desc', @$page->description, false)
    *					),
    *					new EmptyTab(	__('Template'),
    *								new Radioset(__('Choose template'), 'template', array_merge(array('default' => __('Default')), $Templates->ThumbnailImgList()), (empty($tpl)?'default':$tpl), 3)),
    *					new EmptyTab(	__('Content'),
    *								'<h3>'.__('Modify section content').'</h3>',
    *								new Tabber($contentArray))
    *				)
    *			);
    *</code>
    * @param string $uniqid ID to reference tabs by
    * @param mixed $a List of arguments with the contained tabs
    * @return void
    */
    function __construct(){
        $a = func_get_args();
        $this->uniqid = array_shift($a);
        $this->tabs = $this->__filterTabs($a);
    }

    /**
     * Simplifies the ways of outputting the tabs
     * @return unknown_type
     */
    function __toString() {
        return $this->render();
    }

    /**
     * Renders the HTML and loads the libraries needed to view the tabs
     * @return void
     */
    public function render() {
        global $SITE, $CONFIG;
        static $tabnames = array();
        JS::loadjQuery();
        JS::raw('$(function() {$(".ui-tabs-dohide").addClass("ui-tabs-hide");$(".ui-tabs").tabs({show:function(event,ui){
        id = $(ui.tab.hash).attr("id");
        obj = $(ui.tab.hash).attr("id", ""); //So the browser doesnt jump to the object
        window.location.hash=ui.tab.hash;
        obj.attr("id", id);

    }});
    //if(window.location.hash) {
        //$(window.location.hash).parent(".ui-tabs").tabs("select", document.location.hash);
    //}
    });');
        Head::add($CONFIG->UI->jQuery_theme.'/jquery-ui-*', 'css-lib');

        $r  = '<div class="ui-tabs"><ul class="ui-tabs-nav">';
        foreach($this->tabs as $id => $tab) {
            $a = $tab->id;
            $i=0;
            while(in_array($a, $tabnames)) { $i++; $a = $tab->id . $i; }
            $tabnames[] = $tab->id = $a;

            $classes = implode(' ', array_filter(array(($tab->selected?'ui-tabs-selected':false), ($tab->disabled?'ui-tabs-disabled':false))));
            $r .= '<li class="'.$classes.'">'
                    .'<a href="#tab'.$this->uniqid.(!is_numeric($id)?$id:($tab->id?$tab->id:$id)).'"><span>'.$tab->name.'</span></a></li>';
        }
        $r .= '</ul>';
        foreach($this->tabs as $id => $tab) {
            $id =  $this->uniqid.(!is_numeric($id)?$id:($tab->id?$tab->id:$id));
            $r .= '<div id="tab'.$id.'" title="tab'.$id.'" class="ui-tabs-panel'.(!$tab->selected?' ui-tabs-dohide':'').'">'.str_replace('#::tab-id::#', 'tab'.$id, $tab->render()).'</div>';
        }
        return $r.'</div>';
    }

    /**
     * Arrange and flatten the incoming arguments
     * @param array $args The incoming tabs
     * @return array
     */
    private function __filterTabs($args) {
        $args = array_values($args);
        $res = array();
        $count = count($args);
        for($i=0;$i<$count;$i++) {
            if(is_array($args[$i])) $res = array_merge($res, $this->__filterTabs($args[$i]));
            elseif(is_a($args[$i], 'EmptyTab')) $res[] = $args[$i];
            elseif(is_string($args[$i]) && @is_string($args[$i+1])) {
                $res[] = new EmptyTab($args[$i], $args[++$i]);
            }
        }
        return $res;
    }
}
global $SITE;
__autoload('Form');
/**
 * Basic, empty tab
 * @author Jonatan Olofsson [joolo]
 * @package GUI
 */
class EmptyTab {
    public $id;
    public $selected;
    public $disabled;
    public $name;
    public $deselectable;
    private $content;

    /**
     * Fills the tab with name and content
     * @param string $name The name of the tab
     * @param mixed $content The rest of the arguments are loaded as content to the tab
     * @return unknown_type
     */
    function __construct() {
        $a = func_get_args();
        $this->name = array_shift($a);
        $this->content = (array)$a;
    }

    /**
     * Render the tab with contents
     * @return string
     */
    function render(){
        $hidden = '';
        $r='';
        foreach($this->content as $i => $c) {
            if(is_object($c) && method_exists($c, 'render')) {
                if(is_a($c, 'Hidden')) $hidden .= $c->render();
                else
                    $r .= $c->render();
            } elseif(is_string($c)) $r .= $c;
        }
        return $r.$hidden;
    }

    /**
     * Simplifes output
     * @return string
     */
    function __toString() {
        return $this->render();
    }
}

/**
 * Tab with a set wrapping contents
 * @author Jonatan Olofsson [joolo]
 * @package GUI
 */
class Tab extends EmptyTab {
    protected $set=false;

    /**
     * Fills the tab with name and content, wrapped in a Set
     * @param string $name The name of the tab
     * @param mixed $content The rest of the arguments are loaded as content to the tab
     * @return unknown_type
     */
    function __construct(){
        $a = func_get_args();
        $this->name = array_shift($a);
        if($a) {
            $this->set = new Set($a);
        }
    }

    /**
     * Deliver from set
     * @see solidbase/lib/EmptyTab#render()
     */
    function render(){
        return $this->set->render();
    }
}

/**
 * Tab with a fieldset wrapping contents
 * @author Jonatan Olofsson [joolo]
 * @package GUI
 */
class FieldsetTab extends Tab{

    /**
     * Fills the tab with name and content, wrapped in a Fieldset
     * @param string $name The name of the tab
     * @param mixed $content The rest of the arguments are loaded as content to the tab
     * @return unknown_type
     */
    function __construct(){
        $a = func_get_args();
        $this->name = $a[0];
        $this->set = new Fieldset($a);
    }
}
?>
