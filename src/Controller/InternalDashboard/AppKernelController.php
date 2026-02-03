<?php

namespace CCR\Controller\InternalDashboard;

use CCR\Controller\BaseController;
use CCR\Controller\DB;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 */
class AppKernelController extends BaseController
{
    #[Route('/internal_dashboard/controllers/app_kernel.php')]
    public function index(Request $request): Response
    {
        $user = $this->authorize($request);

        $operation = $this->getStringParam($request, 'operation', true);

        switch ($operation) {
            case 'get_ingestion_report':
                return $this->getIngestionReport($request, $user);
            case 'get_ingestion_summary':
                return $this->getIngestionSummary($request, $user);
        }

        return $this->json([
            'success' => false,
            'message' => 'Unknown Operation provided.'
        ]);
    }

    /**
     * Get the App Kernel Ingestion report.
     *
     * @return Response
     */
    private function getIngestionReport(): Response
    {
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

            $onlyMostRecent = $this->getBoolParam('only_most_recent');
            $startDate = $this->getStringParam('start_date');
            $endDate = $this->getStringParam('end_date');
            $onlyFailures = $this->getBoolParam(['only_failures']);

            if (isset($onlyMostRecent) && $onlyMostRecent) {
                $limit = true;
            } else {
                if (isset($startDate)) {
                    $clauses[] = 'last_update >= ?';
                    $params[] = $startDate . ' 00:00:00';
                }

                if (isset($endDate)) {
                    $clauses[] = 'last_update <= ?';
                    $params[] = $endDate . ' 23:59:59';
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

            if (isset($onlyFailures) && $onlyFailures) {
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

        } catch (\Exception $e) {
            $returnData = array(
                'success' => false,
                'message' => $e->getMessage(),
            );
        }
        return $this->json($returnData);
    }

    /**
     * Get the summary of AppKernel ingestion.
     *
     * @return Response
     */
    private function getIngestionSummary(): Response
    {
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

        } catch (\Exception $e) {
            $returnData = array(
                'success' => false,
                'message' => $e->getMessage(),
            );
        }

        return $this->json($returnData);
    }
}
