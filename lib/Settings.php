<?php
class Settings extends ArrayObject {
    private $_TYPE = array();

    private $ID;
    function __construct($id) {
        global $USER, $CONFIG, $SITE;
        $this->ID = $id;
        $_POST->setType('user_settings::language', 'string');
        if($_POST['user_settings::language'] && in_array($_POST['user_settings::language'], $CONFIG->Site->languages)){
            $domain = array_reverse(explode('.', $_SERVER['HTTP_HOST']));
            $domain = $domain[1].'.'.$domain[0];
            if ($USER && ($USER->ID != NOBODY)) $USER->settings->language = $_POST['user_settings::language'];
            else {
                setcookie('user_settings::language', $_POST['user_settings::language'], 0, '/', $domain);
                $_COOKIE['user_settings::language'] = $_POST['user_settings::language'];
            }
            $_POST->clear('user_settings::language');
        }
    }
    /**
     * Loads settings
     * @param $force
     * @return unknown_type
     */
    function ld($force=false) {
        if($this->loaded && !$force) return;
        global $DB;
        $where = array('`setset`.`property` = `settings`.`property`', 'settings.id' => $this->ID);
        if($this->ID > 0) {
            global $Controller;
            $myself = $Controller->{(string)$this->ID}(OVERRIDE);
            if(is_a($myself, 'Group')) {
                $where['setset.visible'] = array(3,4,5,6);
            } elseif(is_a($myself, 'User')) {
                $where['setset.visible'] = array(1,2,3,4);
            } else return false;
        }
        $load = $DB->{'setset,settings'}->asArray($where, 'settings.property,settings.value,setset.type', false, true);
        if(!is_array($load)) $load = array();
        $this->_TYPE = array();
        $new_data = array();
        foreach($load as $property => $data) {
            $new_data[$property] = $data['value'];
            $this->_TYPE[$property] = $data['type'];
        }
        parent::exchangeArray($new_data);
        $this->loaded = true;
    }
    private $loaded = false;


    function getFormElements($fieldname) {
        global $DB, $Controller;
        $self = $Controller->{(string)$this->ID}(OVERRIDE);
        if(is_a($self, 'User')) {
            if($Controller->userEditor(EDIT)) {
                $visibility = array(1,2,3,4);
            } else {
                $visibility = array(1,2,4);
            }
        } elseif(is_a($self, 'Group')) {
            if($Controller->adminGroups(EDIT)) {
                $visibility = array(3,4,5,6);
            } else {
                $visibility = array(4,6);
            }
        } else {
            return false;
        }
        $properties = $DB->setset->asArray(array('visible' => $visibility), false, false, false, 'property');

        $_POST->setType($fieldname, 'string', true);
        if($_POST[$fieldname]) {
            $val = $_POST[$fieldname];
            foreach($properties as $property) {
                if($property['type'] == 'check') $val[$property['property']] = isset($val[$property['property']]);
                $self->settings[$property['property']] = $val[$property['property']];
                parent::offsetset($property['property'], $val[$property['property']]); // Shouldn't be nescessary, but is
            }
        }
        $rows = array();
        __autoload('Form');
        foreach($properties as $row) {
            $rows[] = Settings::display($row['type'], $row['property'], $fieldname.'['.$row['property'].']', $this->{$row['property']}, $row['description'], $row['set']);
        }
        return $rows;
    }

    /**
     * retrieve settings. If the setting is not set, $SITE is asked for default values.
     * @param $property
     * @return unknown_type
     */
    function __get($property) {
        global $SITE, $USER, $CONFIG;
        if($property == 'ID') return $this->ID;
        $this->ld();
        if($property == 'language' && isset($_COOKIE['user_settings::language']) && in_array($_COOKIE['user_settings::language'], $CONFIG->Site->languages)){
            return $_COOKIE['user_settings::language'];
        }
        if($this->offsetExists($property)) {
            $val = parent::offsetGet($property);
            switch(@$this->_TYPE[$property]) {
                case 'CSV':
                    if(is_string($val)) {
                        $val = array_map('trim', explode(',', $val));
                    }
                    break;
            }
            return $val;
        }
        elseif($this->ID>0) return $SITE->settings[$property];
        else return null;
    }

    function __set($property, $value) {
        $this->offsetset($property, $value);
    }

    function set($property, $value) {
        $this->offsetset($property, $value);
    }

    /**
     * Returns wether the the property is set
     * @param string $property Property name
     * @return bool
     */
    public function __isset($property) {
        return $this->offsetExists($property);
    }

    /**
     * Provides the unset() function with a handle so it works as usual
     * @param string $property Property to unset
     * @return void
     */
    public function __unset($property) {
        parent::offsetUnset($property);
    }

    /**
     * retrieves an element of the array
     * @param $offset
     * @return unknown_type
     */
    public function offsetGet($offset) {
        return $this->__get($offset);
    }

    /**
     * Sets an element of the array
     * @param $offset
     * @return unknown_type
     */
    public function offsetset($property, $value) {
        $this->ld();
        if(is_array($value) && @$this->_TYPE[$property] === 'CSV') $value = join(',',$value);
        if($this->offsetGet($property) != $value)
        {
            global $DB, $Controller;
            $myself = $Controller->{(string)$this->ID}(OVERRIDE);
            if($DB->settings->update(
                array('value' => $value),
                array('id' => $this->ID, 'property' => $property), true))
                {
                    parent::offsetset($property, $value);
                }
        }
    }

    /**
     * Register a new Setting
     * @param $property name of the setting
     * @param $type Any of 'text','CSV','select','set','check','password'
     * @param $set
     * @param int $visible Which level of visibility should be set by default:
     * 			0 => None,
     * 			1 => User specific,
     * 			2 => User specified,
     * 			3 => User or group specific,
     * 			4 => User or group specified,
     * 			5 => Group specific,
     * 			6 => Group specified
     * @return bool Success
     */
    function registerSetting($property, $type, $set = false, $visible=2) {
        global $DB;
        if(!$type) throw new Exception('Type must be set');
        return $DB->setset->insert(array('property' => $property,'type' => $type, 'set' => $set, 'visible' => $visible), false, true, true);
    }

    /**
     *
     * @param $property
     * @param $type
     * @param $set
     * @param $visible
     * @return unknown_type
     */
    function changeSetting($property, $type = false, $set = false, $visible = null) {
        if(!$property) return false;
        global $DB;
        $upd = array();
        if($type) {
            $upd['type'] = $type;
        }
        if($set) {
            $upd['set'] = $set;
        }
        if(!is_null($visible)) {
            $upd['visible'] = (int)$visible;
        }
        if(!$upd) return false;
        return $DB->setset->update($upd, array('property' => $property));
    }

    /**
     * Change the name of a setting
     * @param $old Old name
     * @param $new New name
     * @return bool Success
     */
    function changeSettingName($old, $new) {
        return (bool)$DB->setset->update(array('property' => $new),array('property' => $old));
    }

    function display($type, $property, $name, $value=false, $description=false, $set=false) {
        $mult = false;
        switch($type) {
            case 'CSV':
                $value = @join(',', $value);
            case 'text':
                    return new Input(ucwords(__(str_replace('_', ' ', $property))), $name, $value, null, __($description));
                break;
            case 'password':
                    return new password(ucwords(__(str_replace('_', ' ', $property))), 'usersettings['.$c['property'].']', '********', null, __($description));
                break;
            case 'set': $mult=true;
            case 'select':
                if(!is_array($set)) {
                    $set = $DB->setset->getCell(array('property' => $property), 'set');
                }
                if(is_array($set)) {
                    return new Select(ucwords(__(str_replace('_', ' ', $property))), $name, array_map('__', $set), $value, $mult, false, false, __($description));
                } else return false;
                break;
            case 'check':
                return new Checkbox(ucwords(__(str_replace('_', ' ', $property))), $name, $value, false, __($description));
                break;
        }
    }
}
?>
