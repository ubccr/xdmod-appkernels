<?php

    require_once dirname(__FILE__).'/../common_params.php';
    
function getSelectedResourceIds()
{
    return isset($_REQUEST['selectedResourceIds']) && $_REQUEST['selectedResourceIds'] != '' ? explode(',', $_REQUEST['selectedResourceIds']):array();
}

function getSelectedNodeCounts()
{
    return isset($_REQUEST['selectedNodeCounts']) && $_REQUEST['selectedNodeCounts'] != '' ? explode(',', $_REQUEST['selectedNodeCounts']):array();
}

function getSelectedCoreCounts()
{
    return isset($_REQUEST['selectedCoreCounts'])  && $_REQUEST['selectedCoreCounts'] != '' ? explode(',', $_REQUEST['selectedCoreCounts']):array();
}

function getSelectedPUCounts()
{
    return isset($_REQUEST['selectedPUCounts'])  && $_REQUEST['selectedPUCounts'] != '' ? explode(',', $_REQUEST['selectedPUCounts']):array();
}
    
function getSelectedMetrics()
{
    return isset($_REQUEST['selectedMetrics'])  && $_REQUEST['selectedMetrics'] != '' ? explode(',', $_REQUEST['selectedMetrics']):array();
}
    
function getExpandedAppKernels()
{
    return isset($_REQUEST['expandedAppKernels'])  && $_REQUEST['expandedAppKernels'] != '' ? explode(',', $_REQUEST['expandedAppKernels']):array();
}
    
function getShowChangeIndicator()
{
    return  ( isset($_REQUEST['show_change_indicator'])
              ? $_REQUEST['show_change_indicator'] === 'y' : false );
}
