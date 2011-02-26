<?PHP
class Flowstream extends Page {
    public $editable = array('FlowstreamEditor' => EDIT);

    function __construct($id) {
        parent::__construct($id);
        $this->registerMetadata('flows', array());
        $this->registerMetadata('items_per_page', 5);
    }

    function run() {
        global $Templates;
        $_REQUEST->setType('flow_offset', 'numeric');
        $this->setContent('main' ,$this->list($_REQUEST['flow_offset']));
        $Templates->render();
    }

    function list_all($offset) {
        return listify(
            Flow::retrieve(
                $this->flows,
                $this->items_per_page,
                false, false, false, $offset
            )
        );
    }
}
?>
