<?php
class BookPage extends Page {
    static function installable() {return __CLASS__;}
    function install() {
        global $Controller, $DB;

        $DB->query("CREATE TABLE IF NOT EXISTS `booking_bookings` (
  `id` int(11) NOT NULL,
  `b_id` varchar(70) NOT NULL,
  `starttime` int(11) NOT NULL,
  `duration` int(11) NOT NULL,
  `booked_by` int(11) NOT NULL,
  `booked_for` int(100) NOT NULL,
  `comment` varchar(255) NOT NULL,
  `cleared_by` int(11) NOT NULL,
  KEY `starttime` (`starttime`),
  KEY `id` (`id`),
  KEY `b_id` (`b_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `booking_items` (
  `id` int(11) NOT NULL,
  `parent` int(11) unsigned NOT NULL,
  `place` int(11) unsigned NOT NULL,
  KEY `id` (`id`,`parent`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;");

        $Controller->newObj('BookPage')->move('last');
    }

    public $editable = array(
        'PageSettings' => EDIT,
        'PermissionEditor' => EDIT_PRIVILEGES,
        'MenuEditor' => EDIT,
        'Delete' => DELETE
    );

    function __construct($id, $language=false) {
        parent::__construct($id, $language);

        $this->suggestName('Book', 'en');
        $this->suggestName('Bokning', 'sv');

        $this->alias = 'book';
    }

    function run() {
        global $Templates, $DB, $Controller;
        Head::add('booking', 'css-lib');

        $Objects = $Controller->get($DB->booking_items->asList(array('parent' => array('0','')), 'id', false, false, 'place ASC'));
        $this->setContent('main', listify(array_map(create_function('$a', 'return $a->link();'), $Objects)));

        $Templates->render();
    }
}
?>
