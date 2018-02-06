<?php
require_once 'common.php';
$returnData = array();

try
{
    
    $user = \xd_security\getLoggedInUser();
   
    $ak_db = new \AppKernel\AppKernelDb();
     
    if(isset($_REQUEST['node']) && $_REQUEST['node'] === 'resources') {
        $selectedResourceIds = getSelectedResourceIds();
        $selectedProcessingUnits = array();
        $selectedMetrics = getSelectedMetrics();
        $expandedAppKernels = getExpandedAppKernels();

        checkDateParameters();
        
        $resources = $ak_db->getResources(
            $_REQUEST['start_date'],
            $_REQUEST['end_date'],
            $selectedProcessingUnits,
            $selectedMetrics
        );
        $allResources = $ak_db->getResources();

        
        foreach($allResources as $resource)
        {
            if($resource->visible != 1) {
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
                'disabled' =>  !isset($resources[$resource->nickname]),
                'checked' => in_array($resource->id, $selectedResourceIds)
            );
        }
        $returnData = array('totalCount'=> 1, 'data' => array(array('nodes' => json_encode($returnData))));
    }
    elseif(isset($_REQUEST['node']) && $_REQUEST['node'] === 'pus') {
        $selectedResourceIds = getSelectedResourceIds();
        $selectedProcessingUnits = getSelectedPUCounts();
        $selectedMetrics = getSelectedMetrics();
        $expandedAppKernels = getExpandedAppKernels();
        
        checkDateParameters();
        $selectedProcessingUnitsCount = count($selectedProcessingUnits);
        
        $processing_units = $ak_db->getProcessingUnits(
            '2010-01-01',
            date("Y-m-d"),
            $selectedResourceIds,
            $selectedMetrics
        );

        $all_processing_units = $ak_db->getProcessingUnits();
        
        $pus = array();
        foreach($all_processing_units as $processing_unit)
        {
            $disabled = true;
            
            foreach($processing_units as $pu)
            {
                if($processing_unit->count === $pu->count && $processing_unit->unit === $pu->unit) {
                    $disabled = false;
                }
            }
            $pus[$processing_unit->count] =
            array(
                'text' => $processing_unit->count,
                'id' => $processing_unit->count,
                'qtip' => $processing_unit->count.' Node(s)/Core(s)',
                'type' => 'node',
                'iconCls' => 'node',
                'leaf' => true,
                'checked' => $selectedProcessingUnitsCount === 0 || in_array($processing_unit->count, $selectedProcessingUnits)
            );
        }
        $returnData = array('totalCount'=> 1, 'data' => array(array('nodes' => json_encode(array_values($pus)))));

    }
    elseif(isset($_REQUEST['node']) && $_REQUEST['node'] === 'app_kernels') {
        $selectedResourceIds = getSelectedResourceIds();
        $selectedMetrics = getSelectedMetrics();
        $expandedAppKernels = getExpandedAppKernels();
        checkDateParameters();
            
        $all_app_kernels = $ak_db->getUniqueAppKernels();
        foreach($all_app_kernels as $app_kernel)
        {
            $metrics = $ak_db->getMetrics(
                $app_kernel->id,
                '2010-01-01',
                date("Y-m-d")
            );
            $all_metrics = $ak_db->getMetrics($app_kernel->id);
            
            $children = array();
            foreach($all_metrics as $metric)
            {
                $metric_disabled = true;
                foreach($metrics as $m)
                {
                    if($metric->id === $m->id) {
                        $metric_disabled = false;
                    }
                }
                
                $c_id = 'ak_'.$app_kernel->id.'_metric_'.$metric->id;
                $pu_children = array();
                
                $pus = $ak_db->getProcessingUnits('2010-01-01', date("Y-m-d"), array(), array($c_id));
                
                foreach($pus as $pu)
                {
                    $pu_children[] = array('text' => $pu->count.' '.$pu->unit,
                    'id' => $c_id.'_'.$pu->count,
                    'qtip' => $metric->name,
                    'start_ts' => $app_kernel->start_ts,
                    'end_ts' => $app_kernel->end_ts,
                    'ak_def_id' => $app_kernel->id,
                    'type' => 'pu',
                    'iconCls' => 'node',
                    'leaf' => true,
                    'uiProvider' => 'Ext.tree.TriStateNodeUI',
                    'checked' =>  in_array($c_id.'_'.$pu->count, $selectedMetrics) || in_array($c_id, $selectedMetrics)
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
                'id' => 'app_kernel_'.$app_kernel->id,
                'qtip' => $app_kernel->description,
                'start_ts' => $app_kernel->start_ts,
                'end_ts' => $app_kernel->end_ts,
                'type' => 'app_kernel',
                'iconCls' => 'appkernel',
                'leaf' => false,
                'uiProvider' => 'Ext.tree.TriStateNodeUI',
                'expanded' => in_array('app_kernel_'.$app_kernel->id, $expandedAppKernels),
                'children' => $children
                );
        }
        $returnData = array('totalCount'=> 1, 'data' => array(array('nodes' => json_encode($returnData))));
    }
}
catch(SessionExpiredException $see) {
    // TODO: Refactor generic catch block below to handle specific exceptions,
    //       which would allow this block to be removed.
    throw $see;
}
catch(Exception $ex)
{
    print_r($ex);
    $returnData = array();
}

xd_controller\returnJSON($returnData);
