<?PHP
// Konfiguration:
error_reporting(E_ALL);$i=0;
chdir('..');
//FIXME: Korrigera sökväg efter flytt
include './lib/init.php';

$events = $DB->events->asList('id',
    array(
        'soft_deadline<=' time()+86400*2,
        'soft_deadline>=' time(),
        'reminder_sent' => 0
    )
);
foreach($events as $event) {
    $event = $Controller->get($event, OVERRIDE);
    $groups = @$Controller->get($event->attending_groups, OVERRIDE);
    foreach($groups as $group) {
        $group->mail(__('Reminder').': '.$event->Name,
            'This is a reminder'
        );
    }
    $event->reminder_sent = true;
}
?>
