<?php
/**
 * Get ARR summary data.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 */

use CCR\DB;

try {

    $db = DB::factory('akrr-db');

    $sql = 'SELECT COUNT(*) AS count FROM active_tasks';
    list($countRow) = $db->query($sql);
    $activeCount = $countRow['count'];

    // TODO: Refactor these queries.
    $sql = 'SELECT COUNT(*) AS count FROM active_tasks WHERE DATEDIFF(NOW(), time_submitted_to_queue) >= 1';
    list($countRow) = $db->query($sql);
    $queued1DayCount = $countRow['count'];

    $sql = 'SELECT COUNT(*) AS count FROM active_tasks WHERE DATEDIFF(NOW(), time_submitted_to_queue) >= 2';
    list($countRow) = $db->query($sql);
    $queued2DaysCount = $countRow['count'];

    $sql = 'SELECT COUNT(*) AS count FROM active_tasks WHERE DATEDIFF(NOW(), time_submitted_to_queue) >= 3';
    list($countRow) = $db->query($sql);
    $queued3DaysCount = $countRow['count'];

    $sql = 'SELECT COUNT(*) AS count FROM active_tasks WHERE DATEDIFF(NOW(), time_submitted_to_queue) >= 4';
    list($countRow) = $db->query($sql);
    $queued4DaysCount = $countRow['count'];

    $sql = 'SELECT COUNT(*) AS count FROM active_tasks WHERE DATEDIFF(NOW(), time_submitted_to_queue) >= 5';
    list($countRow) = $db->query($sql);
    $queued5DaysCount = $countRow['count'];

    $returnData = array(
        'success' => true,
        'response' => array(
            array(
                'active_count'        => $activeCount,
                'queued_1_day_count'  => $queued1DayCount,
                'queued_2_days_count' => $queued2DaysCount,
                'queued_3_days_count' => $queued3DaysCount,
                'queued_4_days_count' => $queued4DaysCount,
                'queued_5_days_count' => $queued5DaysCount,
            )
        ),
        'count' => 1,
    );

} catch (Exception $e) {
    $returnData = array(
        'success' => false,
        'message' => $e->getMessage(),
    );
}

echo json_encode($returnData);


