<?php

namespace CCR\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\SecurityBundle\Security;


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
     * Migrated from `html/controllers/data_explorer/get_ak_plot.php`
     *
     * @param Request $request
     * @param $user
     * @return Response
     */
    private function getAkPlot(Request $request, $user): Response
    {
        $m = new \DataWarehouse\Access\DataExplorer();

        $result = $m->get_ak_plot($user);
        $response = new Response($result['results']);
        foreach ($response['headers'] as $key => $value) {
            $response->headers->set($key, $value);
        }
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

            $node = $this->getStringParam('node');

            if (isset($node) && $node === 'resources') {
                $selectedResourceIds = $this->getArrayParam('selectedResourceIds');
                $selectedMetrics = $this->getArrayParam('selectedMetrics');
                $expandedAppKernels = $this->getArrayParam('expandedAppKernels');

                $startDate = $this->getStringParam('start_date');
                $endDate = $this->getStringParam('end_date');

                $selectedProcessingUnits = array();

                /*checkDateParameters();*/

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
                $selectedResourceIds = $this->getArrayParam('selectedResourceIds');
                $selectedMetrics = $this->getArrayParam('selectedMetrics');
                $selectedProcessingUnits = $this->getArrayParam('selectedPUCounts');
                $expandedAppKernels = $this->getArrayParam('expandedAppKernels');

                /*checkDateParameters();*/
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
                $selectedResourceIds = $this->getArrayParam('selectedResourceIds');
                $selectedMetrics = $this->getArrayParam('selectedMetrics');
                $expandedAppKernels = $this->getArrayParam('expandedAppKernels');
                /*checkDateParameters();*/

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

    private function getArrayParam($paramName, $delim = ','): array
    {
        $paramValue = $this->getStringParam($paramName, []);
        if (is_array($paramValue)) {
            return $paramValue;
        }
        return explode($delim, $paramValue);
    }


}
