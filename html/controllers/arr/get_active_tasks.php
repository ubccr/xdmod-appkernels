<?php
/**
 * Return ARR active task data.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 */

use CCR\DB;

try {

    $db = DB::factory('akrr-db');

    $sql = '
        SELECT task_id, next_check_time, status, statusinfo,
            statusupdatetime, datetimestamp, time_activated,
            time_submitted_to_queue, task_lock, time_to_start, repeat_in,
            resource, app, resource_param, app_param, task_param, group_id,
            FatalErrorsCount, FailsToSubmitToTheQueue
        FROM mod_akrr.ACTIVETASKS
        ORDER BY time_to_start DESC
    ';

    $returnData = array(
        'success'  => true,
        'response' => $db->query($sql),
    );
    $returnData['count'] = count($returnData['response']);

} catch (Exception $e) {
    $returnData = array(
        'success' => false,
        'message' => $e->getMessage(),
    );
}

echo json_encode($returnData);

