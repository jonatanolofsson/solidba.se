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
    /**
     * Create a company
     * @param number $id Id of the company
     * @return void
     */
    function __construct($id=false) {
        parent::__construct($id);
        Base::registerMetadata(array('Name', 'logo', 'URL', 'weight', 'type', 'redirect'));
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
/*
    function run(){
        if($this->redirect) redirect($this->URL);
        parent::run();
    }
*/

}
?>
