<?php
/**
 * Return App Kernel ingestion report data.
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
    ';

    $limit = false;
    $clauses = array();
    $params = array();

    if (isset($_REQUEST['only_most_recent']) && $_REQUEST['only_most_recent']) {
        $limit = true;
    } else {
        if (isset($_REQUEST['start_date'])) {
            $clauses[] = 'last_update >= ?';
            $params[] = $_REQUEST['start_date'] . ' 00:00:00';
        }

        if (isset($_REQUEST['end_date'])) {
            $clauses[] = 'last_update <= ?';
            $params[] = $_REQUEST['end_date'] . ' 23:59:59';
        }
    }

    if (count($clauses) > 0) {
        $sql .= ' WHERE ' . implode(' AND ', $clauses);
    }

    $sql .= ' ORDER BY last_update DESC';

    if ($limit) {
        $sql .= ' LIMIT 1';
    }

    $rows = $pdo->query($sql, $params);

    $response = array();

    foreach ($rows as $row) {

        $report = unserialize($row['reportobj']);

        // Skip report data that isn't an array.
        if (!is_array($report)) {
            continue;
        }

        foreach ($report as $resource => $appKernels) {
            foreach ($appKernels as $ak => $results) {
                $akParts = explode('.', $ak);
                $cpuCount = array_pop($akParts);
                $akName = implode('.', $akParts);

                // Create unique ID
                $id = implode('-', array($resource, $akName, $cpuCount));

                if (isset($response[$id])) {
                    $currentResponse = $response[$id];
                    foreach ($results as $key => $value) {
                        $currentResponse[$key] += $value;
                    }
                } else {
                    $currentResponse = array_merge(
                        array(
                            'id'         => $id,
                            'resource'   => $resource,
                            'app_kernel' => $akName,
                            'ncpus'      => $cpuCount,
                        ),
                        $results
                    );
                }

                $response[$id] = $currentResponse;
            }
        }
    }

    if (isset($_REQUEST['only_failures']) && $_REQUEST['only_failures']) {
        foreach ($response as $id => $data) {
            if ($data['examined'] == $data['loaded']) {
                unset($response[$id]);
            }
        }
    }

    $returnData = array(
        'success'  => true,
        'response' => array_values($response),
        'count'    => count($response),
    );

} catch (Exception $e) {
    $returnData = array(
        'success' => false,
        'message' => $e->getMessage(),
    );
}

echo json_encode($returnData);

