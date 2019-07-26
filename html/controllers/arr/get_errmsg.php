<?php
/**
 * Return ARR errmsg data.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 */

use CCR\DB;

try {

    $db = DB::factory('akrr-db');

    $sql = '
        SELECT appstdout, stderr, stdout, taskexeclog
        FROM akrr_errmsg
        WHERE task_id = ?
        LIMIT 1
    ';

    $returnData = array(
        'success'  => true,
        'response' => $db->query($sql, array($_REQUEST['task_id'])),
        'count'    => 1,
    );

} catch (Exception $e) {
    $returnData = array(
        'success' => false,
        'message' => $e->getMessage(),
    );
}

echo json_encode($returnData);

