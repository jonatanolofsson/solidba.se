<?PHP

class Matrikel extends Page {
    public $editable = array(
        'MatrikelSettings' => EDIT,
        'PermissionEditor' => EDIT_PRIVILEGES
    );
    function __construct($id) {
        parent::__construct($id);
        $this->suggestName('Matrikel', 'sv');
        $this->suggestName('Cadastral', 'en');
    }
    function run() {
        global $Templates;

        $this->setContent('main',
            Form::quick(false, __('Search'),
                $this->searchForm()
            )
            .$this->result()
        );


        $Templates->render();
    }

    function result() {
        if($_REQUEST->valid('query')) {
            $match = $this->search($_REQUEST['query'], $_REQUEST['searchfields'], $_REQUEST['groups']);
            switch($_REQUEST['resulttype']) {
                case 'tsv': return $this->csv($match, $_REQUEST['resultfields']);
                default: return $this->tabularize($match, ($_REQUEST['resultfields']?$_REQUEST['resultfields']:array('attendance')));
            }
        }
    }

    function tsv($ids, $fields) {
        global $Controller;
        $r = '';
        foreach($fields as $field) {
            if($field != 'attendance') {
                $r .= $field."\t";
            }
        }
        $r .= "\n";
        foreach($ids as $id) {
            foreach($fields as $field) {
                switch($field) {
                    case 'attendance':
                    break;
                    case 'group':
                        $s = (array)$this->interesting_groups;
                        $ig = array();
                        foreach($s as $g) {
                            $g = $Controller->get($g, OVERRIDE, false, true, 'Group');
                            if($g && $g->isMember($id)) {
                                $ig[] = $g;
                            }
                        }
                        $r .= implode(',', $ig);
                        break;
                    default:
                        $r .= $Controller->get($id)->userinfo[$field];
                        break;
                }
                $r .= "\t";
            }
            $r .= "\n";
        }
    }

    function tabularize($ids, $fields) {
        global $Controller;
        __autoload('Table');
        $r = array(new Tableheader($fields));
        foreach($ids as $id) {
            $row = array();
            foreach($fields as $field) {
                switch($field) {
                    case 'attendance':
                        $row[] = new Table(new Tablerow(null,null,null,null,null,null,null,null,null,null,null,null,null,null,null));
                    break;
                    case 'group':
                        $s = (array)@$this->interesting_groups;
                        $ig = array();
                        foreach($s as $g) {
                            $g = $Controller->get($g, OVERRIDE, false, true, 'Group');
                            if($g && $g->isMember($id)) {
                                $ig[] = $g;
                            }
                        }
                        $row[] = implode(',', $ig);
                        break;
                    default:
                        $row[] = $Controller->get($id)->userinfo[$field];
                        break;
                }
            }
            $r[] = new Tablerow($row);
        }
        return new Table($r);
    }

    function searchForm() {

        $_REQUEST->setType('query', 'string');
        $_REQUEST->setType('searchfields', 'string', true);
        $_REQUEST->setType('groups', 'numeric', true);
        $_REQUEST->setType('resultfields', 'string', true);
        $_REQUEST->setType('format', 'string');

        return array(
            new Formsection(__('Search'),
                new Input(__('Search text'), 'query', $_REQUEST['query'], false, __('Use _ (single character) and % (multiple characters) for wildcards')),
                new Select(__('Fields'), 'searchfields', $this->searchFields(), ($_REQUEST['searchfields']?$_REQUEST['searchfields']:true), true),
                new Select(__('Group'), 'groups', $this->groups(), ($_REQUEST['groups']?$_REQUEST['groups']:MEMBER_GROUP), true)
            ),
            new Formsection(__('Result'),
                new Select(__('Display'), 'resultfields', $this->resultFields(), $_REQUEST['resultfields'], true),
                new Select(__('Format'), 'format', array(
                    'table' => __('Tabularized'),
                    'tsv' => __('Tab separated data')
                ), $_REQUEST['format'])
            )
        );
    }

    function groups() {
        global $Controller;
        return $Controller->getClass('Group');
    }

    function searchFields() {
        global $CONFIG;
        $fields = array();
        $uifields = @$CONFIG->userinfo->Fields;
        foreach($uifields as $name => $field) {
            if($field['type'] == 'string') {
                $fields[$name] = $field['label'];
            }
        }

        return $fields;
    }

    function resultFields() {
        global $CONFIG;
        $fields = array(
            'attendance' => __('Attendance'),
            'group' => __('Group')
        );
        $uifields = @$CONFIG->userinfo->Fields;
        foreach($uifields as $name => $field) {
            if(in_array($field['type'], array('string', 'image'))) {
                $fields[$name] = $field['label'];
            }
        }
        asort($fields);
        return $fields;
    }

    function search($query, $fields, $groups) {
        global $DB, $Controller;
        $match = $DB->userinfo->asList(array(
            'prop' => $fields,
            'val~' => '%'.$query.'%'
        ), 'id', false, false, 'id');

        $keep = array();
        foreach($match as $user) {
            foreach($groups as $g) {
                $g = $Controller->get($g, OVERRIDE, false, true, 'Group');
                if($g && $g->memberOf($g)) {
                    $keep[] = $user;
                    break;
                }
            }
        }

        return $keep;
    }
}

?>
