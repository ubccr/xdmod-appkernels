<?php
/**
 * Returns app kernel ingestion summary data.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 */

use CCR\DB;

try {

    $pdo = DB::factory('database');

    $sql = '
        SELECT source, last_update, start_time, end_time, success,
            UNCOMPRESS(reportobj) AS reportobj
        FROM mod_appkernel.ingester_log
        ORDER BY last_update DESC
        LIMIT 1
    ';
    list($row) = $pdo->query($sql);

    $report = unserialize($row['reportobj']);

    $summary = array(
        'update_time'        => $row['last_update'],
        'start_time'         => $row['start_time'],
        'end_time'           => $row['end_time'],
        'success'            => $row['success'],
        'examined_count'     => 0,
        'loaded_count'       => 0,
        'incomplete_count'   => 0,
        'parse_error_count'  => 0,
        'queued_count'       => 0,
        'error_count'        => 0,
        'sql_error_count'    => 0,
        'unknown_type_count' => 0,
        'duplicate_count'    => 0,
        'exception_count'    => 0,
    );

    foreach ($report as $resource => $appKernels) {
        foreach ($appKernels as $ak => $results) {
            foreach ($results as $key => $value) {
                $summary[$key . '_count'] += $value;
            }
        }
    }

    $returnData = array(
        'success'  => true,
        'response' => array($summary),
        'count'    => 1,
    );

} catch (Exception $e) {
    $returnData = array(
        'success' => false,
        'message' => $e->getMessage(),
    );
}

echo json_encode($returnData);

