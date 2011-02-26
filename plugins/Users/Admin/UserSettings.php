<?php
class UserSettings extends Page {
    function __construct($id) {
        parent::__construct($id);

        $this->suggestName('User settings', 'en');
        $this->suggestName('Användarinställningar', 'sv');
        $this->icon = 'small/user_edit';
    }

    static function installable() {return __CLASS__;}
    function install() {
        global $Controller;
        $Controller->newObj('UserSettings')->move('last', 'adminMenu');
    }

    function run() {
        global $SITE, $DB, $Templates;
        $_POST->setType('vis', 'any', true);
        $_POST->setType('def', 'string', true);
        $_REQUEST->setType('upd', 'any');

        $properties = $DB->setset->asArray(false, false, false, false, 'property');
        if($_POST['def']) {
            $vis = $_POST['vis'];
            $def = $_POST['def'];
            foreach($properties as $property) {
                $property = $property['property'];
                Settings::changeSetting($property, false, false, $vis[$property]);
                $SITE->settings[$property] = $def[$property];
            }
            redirect(url(array('upd' => 1), true));
        }
        if($_REQUEST['upd']) {
            Flash::create(__('Settings updated'), 'confirmation');
        }

        __autoload('Form');
        $TRs = array();

        $settings_types = array(
            __('Administrator-specified'),
            __('User level, pre-specified'),
            __('User level, self-specified'),
            __('User- or group level, pre-specified'),
            __('User- or group level , self-specified'),
            __('Group level, pre-specified'),
            __('Group level, self-specified')
        );

        foreach($properties as $property) {
            $TRs[] = new Tablerow(
                Settings::name($property['property']),
                new Select(false, 'vis['.$property['property'].']', $settings_types, $property['visible']),
                Settings::display($property['type'], false, 'def['.$property['property'].']', $SITE->settings[$property['property']], $property['description'], $property['set'])
            );
        }

        $form = new Form('sitesettings');
        $this->setContent('header', __('Default user settings'));
        $this->setContent('main',
            $form->collection(
                new Table(
                    new Tableheader(__('Property'), __('Type'), __('Property default')),
                    $TRs
                )
            )
        );

        $Templates->admin->render();
    }
}
