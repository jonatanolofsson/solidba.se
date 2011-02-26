<?php
class Flow {

    /**
     * Register a new item in the flow
     * @param integer $id The unique (spine) ID of the registered item
     * @param string $queue The queue to add the item to
     * @param integer $when Unix timestamp with the time to register
     * @return void
     */
    function register($id, $queue, $when = false) {
        global $DB;
        if(is_object($id)) $id = $id->ID;
        if($when === false) $when = time();
        $DB->flow->insert(array('id' => $id, 'queue' => $queue, 'created' => $when, 'modified' => $when), false, true);
    }

    /**
     * Update the last edited timestamp. If the item is not registered, register it
     * @param integer $id The unique (spine) ID of the registered item
     * @param string $queue The registered item's queue. If left out, all the ID's registered queues are updated, but no new entry is adeded
     * @param integer $when Unix timestamp with the time to register
     * @return void
     */
    function touch($id, $queue=false, $when = false) {
        global $DB;
        if($when === false) $when = time();
        $which = array('id' => $id);
        if($queue) $which['queue'] = $queue;
        if(!$DB->flow->update(array('when' => $when), $which)) {
            if($queue)self::register($id, $queue, $when);
        }
    }

    /**
     * Update the last edited timestamp, if the item is registered
     * @param integer $id The unique (spine) ID of the registered item
     * @param string $queue The queue to update. If left out, all the ID's registered queues are updated
     * @param integer $when Unix timestamp with the time to register
     * @return void
     */
    function notify($id, $queue=false, $when = false) {
        if($when === false) $when = time();
        $which = array('id' => $id);
        if($queue) $which['queue'] = $queue;
        $DB->flow->update(array('when' => $when), $queue);
    }


    /**
     * Delete the item from the flow
     * @param integer $id The unique (spine) ID of the registered item
     * @param string $queue The queue to remove the item from. If left out, all the ID's registered entries are removed
     * @return void
     */
    function unregister($id, $queue) {
        global $DB;
        $which = array('id' => $id);
        if($queue) $which['queue'] = $queue;
        $DB->flow->delete($which);
    }

    /**
     * Move item from one queue to another. If the source is not found, a new item is registered
     * in the target queue
     * @param integer $id The unique (spine) ID of the registered item
     * @param string $from The queue to unregister
     * @param string $to The queue to move the item to
     * @return void
     */
    function move($id, $from, $to) {
        global $DB;
        if(!$DB->flow->update(array('queue' => $to), array('id' => $id, 'queue' => $from))) {
            self::register($id, $to);
        }
    }

    /**
     * Fetch items from one or several queues. The amount of returned items may vary if not enough
     * items are available from the queues.
     * @param string|array $queues Which queues to fetch from
     * @param integer $amount How many items should (at most) be returned
     * @param integer $before Unix timestamp to limit the search
     * @param integer $after Unix timstamp that must have been passed
     * @param bool $last_edited A boolean flag to trigger search for last edited (true) or last created (false)
     * @param integer $offset An offset to the items that should be returned, i.e. start counting
     * @param const $aLEVEL The access-filter that should be used
     * @param User $u The user to try the permissions against
     * @return array Array of objects from the specified queues
     */
    function retrieve($queues, $amount, $before=false, $after=false, $last_edited=false, $offset=0, $pinned = 'any', $aLEVEL=ANYTHING, $u=false) {
        if(!$queues) return array();
        global $DB, $Controller;
        if(!$before) $before=time();
        if(!$after) $after = 0;
        if(!is_numeric($offset)) $offset = 0;
        if(is_string($queues)) $queues = explode(',', $queues);
        $queues = array_map('trim', $queues);

        $enough = false;
        $retrieved = 0;
        $dboffset=0;
        $new = 0;
        $items = array();
        $itemsIDS = array();
        do{
            $cond = array(  'flow.queue~' => $queues,
                            'flow.'.($last_edited?'modified':'created').'>=' => $after,
                            'flow.'.($last_edited?'modified':'created').'<=' => $before,
                            'metadata.field' => 'Activated',
                            'metadata.value' => '1');
            if($pinned != 'any') {
                $cond['metadata.field'] = 'LockedPos';
                $cond['metadata.value'] = (bool)$pinned;
            }
            if($itemsIDS) $cond['flow.id!'] = $itemsIDS;

            //FIXME: Fix database and remove GROUP BY
            $newIDS = $DB->{'flow,metadata'}->asList($cond, 'flow.id',
                            $dboffset.','.ceil(($amount * 1.5 + $offset)),
                            true, ($last_edited?'modified':'created').' DESC', 'flow.id,flow.queue');
            if(!$newIDS) break;
            $newItems = $Controller->get($newIDS, $aLEVEL, $u);
            $newItems = arrayKeySort($newItems, $newIDS);
            $newIDS = array_keys($newItems);
            $itemsIDS = array_merge($itemsIDS, $newIDS);

            $items = array_merge($items, $newItems);
            $retrieved = count($items);
            $new = $DB->numRows();
            $dboffset += $new;
        } while($newItems && $retrieved < $amount + $offset);
        return array_slice($items, $offset, $amount, true);
    }

    /**
     * Get a list of the different flows in the database
     * @return array
     */
    function flows() {
        global $Controller;
        return $Controller->getClass('FlowQueue');
    }
}
?>
