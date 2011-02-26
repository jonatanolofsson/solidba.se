<?PHP
class FlowstreamEditor extends Page {
    private $that;
    public $edit_icon = 'small/arrow_join';
    public $edit_text = 'Edit stream';

    function canEdit($obj) {
        return is_a($obj, 'Flowstream');
    }

    function __construct($obj) {
        parent::__construct($obj->ID);
        $this->that = $obj;
    }

    function run() {
        if($this->saveChanges()) redirect(-2);
        global $Template;
        $this->setContent('main', $this->edit());
        $Template->render();
    }

    function edit() {
        return Form::quick(false, false,
            new Select(__('Flows'), 'flows', Flow::flows(), $this->that->flows, true),
            new Input(__('Items per page', 'items', $this->that->flows_per_page, 'numeric'))
        );
    }

    function saveChanges() {
        $_REQUEST->setType('flows', 'numeric', true);
        $_REQUEST->setType('items', 'numeric');
        if($_REQUEST['items'] && $_REQUEST['flows']) {
            $this->that->flows = $_REQUEST['flows'];
            $this->that->items_per_page = $_REQUEST['items'];
            Flash::queue(__('Your changes were saved'), 'confirmation');
            return true;
        }
        return false;
    }
}
?>
