<?php
/**
 * @author Kalle Karlsson (kakar)
 * @version 1.0
 * @package Content
 */
/**
 * Interface for creating and editing companies
 * @package Content
 */

class CompanyEditor extends Page {
    public $privilegeGroup = 'Administrationpages';
    static public function installable() {return __CLASS__;}
    //static public function uninstallable() {return __CLASS__;}

    function install() {
        global $Controller;
        $Controller->newObj('CompanyEditor')->move('last', 'adminMenu');
    }

    function __construct($id=false){
        parent::__construct($id);
        $this->suggestName('Companies', 'en');
        $this->suggestName('Företag', 'sv');
        $this->alias = 'companyEditor';

        $this->icon = 'small/vcard';
    }

    function run(){
        global $Templates, $USER, $Controller, $DB, $CONFIG;
        if(!$this->may($USER, ANYTHING)) errorPage('401');

        /**
         * Company input types
         */
        $_REQUEST->setType('edit', array('numeric','#^new$#'));
        $_REQUEST->setType('newCompanySubm', 'any');
        $_REQUEST->setType('updCompanySubm', 'any');
        $_REQUEST->setType('delCompany', 'numeric');
        $_REQUEST->setType('compid', 'numeric');
        $_REQUEST->setType('name', 'string');
        $_REQUEST->setType('logo', 'string');
        $_REQUEST->setType('url', 'string');
        $_REQUEST->setType('redirect', 'any');
        $_REQUEST->setType('weight', 'numeric');
        $_REQUEST->setType('type','#^(main|sub)$#');
        $_REQUEST->setType('madd', 'numeric');

        /**
         * Add a new company
         */
        if($this->may($USER, EDIT) && $_REQUEST['newCompanySubm']){
            if(!$DB->companies->exists(array('name' => $_REQUEST['name'])) && $_REQUEST->nonempty('name')){
                $comp = $Controller->newObj('Company');
                $DB->companies->insert(array('id'=>$comp->ID));
                $comp->Name = $_REQUEST['name'];
                $comp->logo = $_REQUEST['logo'];
                $comp->URL = $_REQUEST['url'];
                if(isset($_REQUEST['redirect']))$comp->redirect = 1;
                else $comp->redirect = 0;
                $comp->weight = $_REQUEST['weight'];
                $comp->type = $_REQUEST['type'];
                Flash::create(__('New company was registered'), 'confirmation');
/* 				Log::write('New company created'); */
            }else Flash::create(__('A Company with that name already exists'), 'warning');
        }

        /**
         * Edit a company
         */
        elseif($this->may($USER, EDIT) && $_REQUEST['updCompanySubm'] && $Controller->{$_REQUEST['compid']}('Company') !== false){
            $comp = $Controller->{$_REQUEST['compid']}(OVERRIDE);
            if($_REQUEST->valid('name')){
                $comp->Name = $_REQUEST['name'];
                if($_REQUEST->valid('logo')){
                    $comp->logo = $_REQUEST['logo'];
                    if($_REQUEST->valid('url')){
                        $comp->URL = $_REQUEST['url'];
                        if($_REQUEST->valid('weight')){
                            $comp->weight = $_REQUEST['weight'];
                            if($_REQUEST->valid('type')){
                                $comp->type = $_REQUEST['type'];
                                if(isset($_REQUEST['redirect'])) $comp->redirect = 1;
                                else $comp->redirect = 0;
/* 								Log::write('Company('.$comp->name.') was updated'); */
                                Flash::create(__('Company was updated'), 'confirmation');
                            }else Flash::create(__('Company type invalid'), 'warning');
                        }else Flash::create(__('Company weight must not be empty'), 'warning');
                    }else Flash::create(__('Company URL must not be empty'), 'warning');
                }else Flash::create(__('Company logo must not be empty'), 'warning');
            }else Flash::create(__('Company name must not be empty'), 'warning');
        }

        /**
         * Add a page to the menu
         */
        elseif($_REQUEST['madd']) {
            if($Controller->menuEditor->mayI(EDIT) && $obj = $Controller->{$_REQUEST['madd']}('Company')) {
                $obj->move('last');
                redirect(url(array('id' => 'menuEditor', 'status' => 'ok'), false, false));
            }
        }

        /**
         * Delete company
         */
        elseif($_REQUEST->numeric('delCompany')){
            if($Controller->{$_REQUEST['delCompany']}(DELETE) && $Controller->{$_REQUEST['delCompany']}->delete()) {
    /* 			Log::write('Company was deleted'); */
                Flash::create(__('Company was deleted'));
            }
        }

        /**
         * Display page
         */
        if($_REQUEST->valid('edit')) {
            $this->content = array('header' => ($_REQUEST['edit'] == 'new'?__('New company'):__('Edit company')), 'main' => $this->companyForm($_REQUEST['edit']));
        }else{
            $this->content = array('header' => $this->Name, 'main' => $this->displayCompanies());
        }

        $Templates->admin->render();
    }


    /**
     * Display the current companies.
     * @return string
     */
    private function displayCompanies() {
        global $USER, $DB, $Controller;
        $req = $Controller->getClass('Company', OVERRIDE);
        if(!is_array($req)) $req = array();
        $i=array('main' => 0, 'sub' => 0);
        $companyList = array('main' => array(), 'sub' => array());
        foreach($req as $company) {
            $companyList[($company->type?$company->type:'sub')][] = '<span class="fixed-width">'.$company->Name.'</span><div class="tools">'
                .($this->mayI(EDIT)?icon('small/vcard_edit', 'Edit', url(array('edit' => $company->ID), 'id'))
                            .icon('small/pencil', __('Edit company page'), url(array('id' => 'pageEditor', 'edit' => $company->ID))):'')
                .($this->mayI(DELETE)?icon('small/delete', __('Delete'), url(array('delCompany' => $company->ID), array('id'))):'')
                .($Controller->menuEditor->mayI(EDIT)
                    ? icon('small/page_add', __('Add company page to menu'), url(array('madd' => $company->ID), 'id'))
                    : '')
                .'</div>';
        }
        if(!$companyList['main']) $companyList['main'][] = __('None');
        if(!$companyList['sub']) $companyList['sub'][] = __('None');
        $companyStr = '<ul class="flul">'
            .'<li class="fletter">'.__('Main sponsor')
                .listify($companyList['main'],'')
            .'</li>';
        $companyStr .= '<li class="fletter">'.__('Sub sponsor')
                .listify($companyList['sub'],'')
            .'</li>';
        $companyList .= '</ul>';

        return new Tabber('cc',
            new EmptyTab(__('Current Companies'),
                $companyStr
            ),(!$this->may($USER, EDIT)?null:
            new EmptyTab(__('New'),
                $this->companyForm()
            ))
        );
    }

    /**
     * Function for editing company proporties and adding a new one
     * @param $id Company ID to edit or void for new
     * @return string
     */
    function companyForm($company=false) {
        global $DB, $Controller;
        if($company && !is_object($company)) {
            if(!($company = $Controller->retrieve($company))) {
                return false;
            }
        }
        if(is_object($company)) {
            $edit = true;
            if(!$company->mayI(EDIT)) return false;
        }
        else $edit = false;
        $form = new Form(($edit?'updCompanySubm':'newCompanySubm'), url(null, 'id'));

        return '<div class="nav"><a href="'.url(array('id' => 'companyEditor')).'">'.icon('small/arrow_up').__('To company manager').'</a></div>'
        .$form->collection(
                        new Fieldset(($edit?__('Edit existing comapny'):__('Create a new company')),
                            new Hidden('compid',@$company->ID),
                            new Input(__('Name'), 'name', @$company->Name, 'nonempty', __('Name of the company')),
                            new ImagePicker(__('Logo'), 'logo', @$company->logo, 'nonempty', __('Company logo')),
                            new Input(__('URL'), 'url', @$company->URL, 'nonempty', __('URL to company page')),
                            new Checkbox(__('Automatic Redirect'), 'redirect', @$company->redirect, false, false, __('Redirect to Company URL instead of showing local page')),
                            new Input(__('Weight'), 'weight', @$company->weight, 'nonempty', __('Initial weight of the company')),
                            new Select(__('Type'), 'type', array('main' => __('Main sponsor'), 'sub' => __('Sub sponsor')), @$company->type)
                    )
                );
    }

}
?>
