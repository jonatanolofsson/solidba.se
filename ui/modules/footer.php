<?php
/**
 * @author Jonatan Olofsson [joolo]
 * @version 1.0
 * @package Content
 */

/**
 * @author Jonatan Olofsson [joolo]
 * @package Content
 */
class footer {
    function __construct() {
        global $s, $DB, $Controller;
        if($_REQUEST->raw('force_dump') & 1)
            echo "<p>Execution time: " . (microtime(true)-$s) . ". Number of SQL queries: " . $DB->queries . ". SQL query time: " . $DB->queryTime  . ". ".$Controller->count()." objects loaded. Mem peak: ".round(memory_get_peak_usage(true)*9.53674316e-7, 4)." MB</p>";//Display execution time
        if($_REQUEST->raw('force_dump') & 2) {
            $q = $DB->rawQueries;
            sort($q);
            echo '<pre>';
            print_r($q);
            echo '</pre>';
        }
        if($_REQUEST->raw('force_dump') & 4) {
            echo '<pre>'.print_r($DB->rawQueries, true).'</pre>';
            echo '<pre>'.print_r(array_diff($DB->rawQueries, array_unique($DB->rawQueries)), true).'</pre>';
        }
    }
}
?>
