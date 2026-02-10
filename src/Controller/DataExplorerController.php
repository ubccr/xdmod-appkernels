<?php

namespace CCR\Controller;

use SessionExpiredException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;


class DataExplorerController extends BaseController
{
    #[Route('/controllers/data_explorer.php', methods: ["GET", "POST"])]
    public function index(Request $request): Response
    {
        $user = $this->authorize($request);

        $operation = $this->getStringParam($request, 'operation', true);

        switch ($operation) {
            case 'get_ak_plot':
                return $this->getAkPlot($request, $user);
            case 'get_tree':
                return $this->getTree($request, $user);
        }

        return $this->json([
            'success' => false,
            'message' => 'Unknown Operation provided.'
        ]);
    }

    /**
     *
     *
     * @param Request $request
     * @param $user
     * @return Response
     */
    private function getAkPlot(Request $request, $user): Response
    {
        $m = new \DataWarehouse\Access\DataExplorer($request);

        $result = $m->get_ak_plot($user);
        $response = new Response($result['results'], 200, $result['headers']);
        return $response;
    }

    /**
     * Migrated from `html/controllers/data_explorer/get_tree.php`
     *
     * @param Request $request
     * @return Response
     */
    private function getTree(Request $request, $user): Response
    {
        try {
            $ak_db = new \AppKernel\AppKernelDb();

            $node = $this->getStringParam($request, 'node');
            $returnData = [];
            if (isset($node) && $node === 'resources') {
                $selectedResourceIds = $this->getArrayParam($request, 'selectedResourceIds');
                $selectedMetrics = $this->getArrayParam($request, 'selectedMetrics');
                $expandedAppKernels = $this->getArrayParam($request, 'expandedAppKernels');

                $startDate = $this->getStringParam($request, 'start_date');
                $endDate = $this->getStringParam($request, 'end_date');

                $selectedProcessingUnits = array();

                $this->checkDateParameters($request);

                $resources = $ak_db->getResources(
                    $startDate,
                    $endDate,
                    $selectedProcessingUnits,
                    $selectedMetrics
                );
                $allResources = $ak_db->getResources();


                foreach ($allResources as $resource) {
                    if ($resource->visible != 1) {
                        continue;
                    }
                    $returnData[] =
                        array(
                            'text' => $resource->name,
                            'id' => $resource->id,
                            'nick' => $resource->nickname,
                            'qtip' => $resource->description,
                            'type' => 'resource',
                            'iconCls' => 'resource',
                            'leaf' => true,
                            'disabled' => !isset($resources[$resource->nickname]),
                            'checked' => in_array($resource->id, $selectedResourceIds)
                        );
                }
                $returnData = array('totalCount' => 1, 'data' => array(array('nodes' => json_encode($returnData))));
            } elseif (isset($node) && $node === 'pus') {
                $selectedResourceIds = $this->getArrayParam($request, 'selectedResourceIds');
                $selectedMetrics = $this->getArrayParam($request, 'selectedMetrics');
                $selectedProcessingUnits = $this->getArrayParam($request, 'selectedPUCounts');
                $expandedAppKernels = $this->getArrayParam($request, 'expandedAppKernels');

                $this->checkDateParameters($request);
                $selectedProcessingUnitsCount = count($selectedProcessingUnits);

                $processing_units = $ak_db->getProcessingUnits(
                    '2010-01-01',
                    date("Y-m-d"),
                    $selectedResourceIds,
                    $selectedMetrics
                );

                $all_processing_units = $ak_db->getProcessingUnits();

                $pus = array();
                foreach ($all_processing_units as $processing_unit) {
                    $disabled = true;

                    foreach ($processing_units as $pu) {
                        if ($processing_unit->count === $pu->count && $processing_unit->unit === $pu->unit) {
                            $disabled = false;
                        }
                    }
                    $pus[$processing_unit->count] =
                        array(
                            'text' => $processing_unit->count,
                            'id' => $processing_unit->count,
                            'qtip' => $processing_unit->count . ' Node(s)/Core(s)',
                            'type' => 'node',
                            'iconCls' => 'node',
                            'leaf' => true,
                            'checked' => $selectedProcessingUnitsCount === 0 || in_array($processing_unit->count, $selectedProcessingUnits)
                        );
                }
                $returnData = array('totalCount' => 1, 'data' => array(array('nodes' => json_encode(array_values($pus)))));

            } elseif (isset($node) && $node === 'app_kernels') {
                $selectedResourceIds = $this->getArrayParam($request, 'selectedResourceIds');
                $selectedMetrics = $this->getArrayParam($request, 'selectedMetrics');
                $expandedAppKernels = $this->getArrayParam($request, 'expandedAppKernels');
                $this->checkDateParameters($request);

                $all_app_kernels = $ak_db->getUniqueAppKernels();
                foreach ($all_app_kernels as $app_kernel) {
                    $metrics = $ak_db->getMetrics(
                        $app_kernel->id,
                        '2010-01-01',
                        date("Y-m-d")
                    );
                    $all_metrics = $ak_db->getMetrics($app_kernel->id);

                    $children = array();
                    foreach ($all_metrics as $metric) {
                        $metric_disabled = true;
                        foreach ($metrics as $m) {
                            if ($metric->id === $m->id) {
                                $metric_disabled = false;
                            }
                        }

                        $c_id = 'ak_' . $app_kernel->id . '_metric_' . $metric->id;
                        $pu_children = array();

                        $pus = $ak_db->getProcessingUnits('2010-01-01', date("Y-m-d"), array(), array($c_id));

                        foreach ($pus as $pu) {
                            $pu_children[] = array('text' => $pu->count . ' ' . $pu->unit,
                                'id' => $c_id . '_' . $pu->count,
                                'qtip' => $metric->name,
                                'start_ts' => $app_kernel->start_ts,
                                'end_ts' => $app_kernel->end_ts,
                                'ak_def_id' => $app_kernel->id,
                                'type' => 'pu',
                                'iconCls' => 'node',
                                'leaf' => true,
                                'uiProvider' => 'Ext.tree.TriStateNodeUI',
                                'checked' => in_array($c_id . '_' . $pu->count, $selectedMetrics) || in_array($c_id, $selectedMetrics)
                            );
                        }

                        $children[] = array('text' => $metric->name,
                            'id' => $c_id,
                            'qtip' => $metric->name,
                            'start_ts' => $app_kernel->start_ts,
                            'end_ts' => $app_kernel->end_ts,
                            'ak_def_id' => $app_kernel->id,
                            'type' => 'metric',
                            'iconCls' => 'metric',
                            'leaf' => false,
                            'expanded' => in_array($c_id, $expandedAppKernels),
                            'uiProvider' => 'Ext.tree.TriStateNodeUI',
                            'checked' => in_array($c_id, $selectedMetrics),
                            'children' => $pu_children
                        );
                    }
                    $returnData[] = array('text' => $app_kernel->name,
                        'id' => 'app_kernel_' . $app_kernel->id,
                        'qtip' => $app_kernel->description,
                        'start_ts' => $app_kernel->start_ts,
                        'end_ts' => $app_kernel->end_ts,
                        'type' => 'app_kernel',
                        'iconCls' => 'appkernel',
                        'leaf' => false,
                        'uiProvider' => 'Ext.tree.TriStateNodeUI',
                        'expanded' => in_array('app_kernel_' . $app_kernel->id, $expandedAppKernels),
                        'children' => $children
                    );
                }
                $returnData = array('totalCount' => 1, 'data' => array(array('nodes' => json_encode($returnData))));
            }
        } catch (SessionExpiredException $see) {
            // TODO: Refactor generic catch block below to handle specific exceptions,
            //       which would allow this block to be removed.
            throw $see;
        } catch (\Exception $ex) {
            /*print_r($ex);*/
            $returnData = array();
        }

        return $this->json($returnData);
    }

    private function getArrayParam($request, $paramName, $delim = ','): array
    {
        $paramValue = $this->getStringParam($request, $paramName, []);
        if (is_array($paramValue)) {
            return $paramValue;
        }
        return explode($delim, $paramValue);
    }

    protected function checkDateParameters(Request $request): array
    {
        $startDate = $request->get('start_date');
        if (!isset($startDate)) {
            throw new BadRequestHttpException(
                'missing required start_date parameter'
            );
        }

        $start_date_parsed = date_parse_from_format(
            'Y-m-d',
            $startDate
        );

        if ($start_date_parsed['error_count'] !== 0) {
            throw new BadRequestHttpException(
                'start_date param is not in the correct format of Y-m-d.'
            );
        }

        $endDate = $request->get('end_date');
        if (!isset($endDate)) {
            throw new BadRequestHttpException(
                'missing required end_date parameter'
            );
        }

        $end_date_parsed = date_parse_from_format('Y-m-d', $endDate);

        if ($end_date_parsed['error_count'] !== 0) {
            throw new BadRequestHttpException(
                'end_date param is not in the correct format of Y-m-d.'
            );
        }

        return array(
            $startDate,
            $endDate,
            mktime(
                $start_date_parsed['hour'],
                $start_date_parsed['minute'],
                $start_date_parsed['second'],
                $start_date_parsed['month'],
                $start_date_parsed['day'],
                $start_date_parsed['year']
            ),
            mktime(
                23,
                59,
                59,
                $end_date_parsed['month'],
                $end_date_parsed['day'],
                $end_date_parsed['year']
            )
        );
    }

}
