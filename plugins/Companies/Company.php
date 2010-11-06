<?php
/**
 * @author Kalle Karlsson [kakar]
 * @version 1.0
 * @package Content
 */
/**
 * This class represent a company. Handles the information in the database
 * @package Content
 */

class Company extends Page {
    private $_Name = false;
    private $_logo = false;
    private $_URL = false;
    private $_weight = false;
    private $_type = false;
    private $_redirect = false;

    /**
     * Create a company
     * @param number $id Id of the company
     * @return void
     */
    function __construct($id=false) {
        global $DB;
        parent::__construct($id);
        $this->getMetadata(array('', $this->loadedLanguage), false, array('Name', 'logo', 'URL', 'weight', 'type', 'redirect'));
    }

    /**
     * Function for getting a company property
     * @param string $property Reqested property
     * @return string
     */
    function __get($property){
        if(in_array($property, array('Name', 'logo', 'URL', 'weight', 'type', 'redirect')))
            return $this->{'_'.$property};
        else return parent::__get($property);
    }

    /**
     * Function for setting a company property
     * @param string $property Property to be set
     * @param string|number $value The value of the property
     * @return void
     */
    function __set($property, $value){
        global $DB;
        if(in_array($property, array('Name', 'logo', 'URL', 'weight', 'type', 'redirect'))){
            $ipn = '_'.$property;
            if($this->$ipn !== false && $this->$ipn != $value && ($this->$ipn || $value)){
                Metadata::set($property, $value);
            }
            $this->$ipn = $value;
        } else parent::__set($property, $value);
    }

    /**
     * Delete the company
     * @return void
     */
    function delete(){
        global $DB, $Controller, $USER;
        if($Controller->alias('companyEditor')->may($USER, DELETE)){
            $DB->companies->delete(array('id' => $this->ID));
            parent::delete();
        }
    }

    /**
     * (non-PHPdoc)
     * @see lib/MenuItem#may($u, $lvl)
     */
    function may($u, $lvl) {
        if(is_bool($pr = parent::may($u, $lvl))) {
            return $pr;
        } elseif($lvl & READ) {
            return true;
        } else return $pr;
    }

    /**
     *
     */
    function updateWeight(){
        // add algorithm for weight
    }

    /**
     *
     *
     */
    function run(){
//		if($this->redirect) redirect($this->URL);
        parent::run();
    }

}
?>
