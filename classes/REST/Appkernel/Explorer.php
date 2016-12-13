<?php

namespace AppKernel;
use \Exception;

require_once("Log.php");

class Explorer extends \aRestAction
{
  protected $logger = NULL;

  // Tree node types, shown here in hierarchical order
  const TREENODE_APPKERNEL = "appkernel";
  const TREENODE_RESOURCE = "resource";
  const TREENODE_METRIC = "metric";
  const TREENODE_UNITS = "units";
  const TREENODE_INSTANCE = "instance";

  // --------------------------------------------------------------------------------
  // @see aRestAction::__call()
  // --------------------------------------------------------------------------------
  
  public function __call($target, $arguments)
  {
    // Verify that the target method exists and call it.
    
    $method = $target . ucfirst($this->_operation);
    
    if ( ! method_exists($this, $method) )
    {
      
      if ($this->_operation == 'Help')
      {
        // The help method for this action does not exist, so attempt to generate a response
        // using that action's Documentation() method.
        
        $documentationMethod = $target.'Documentation';
        
        if ( ! method_exists($this, $documentationMethod) )
        {
          throw new Exception("Help cannot be found for action '$target'");
        }
        
        return $this->$documentationMethod()->getRESTResponse();            
        
      }
      else
      {
        throw new Exception("Unknown action '$target' in category '" . strtolower(__CLASS__)."'");
      }
         
    }  // if ( ! method_exists($this, $method) )
         
    return $this->$method($arguments);
    
  } // __call()

  // --------------------------------------------------------------------------------

  public function __construct($request)
  {
    parent::__construct($request);

    // Initialize the logger

    $params = $this->_parseRestArguments("");
    $verbose = ( isset($params['debug']) && $params['debug'] );
    $maxLogLevel = ( $verbose ? PEAR_LOG_DEBUG : PEAR_LOG_INFO );
    $logConf = array('mode' => 0644);
    $akConfigSection = "appkernel-" . APPLICATION_ENV;
    $logfile = LOG_DIR . "/" . \xd_utilities\getConfiguration('general', 'rest_logfile');
    $this->logger = \Log::factory('file', $logfile, 'AppKernel', $logConf, $maxLogLevel);

  }  // __construct

  // --------------------------------------------------------------------------------
  // @see aRestAction::factory()
  // --------------------------------------------------------------------------------
  
  public static function factory($request)
  {
    return new Explorer($request);
  }
  
  // --------------------------------------------------------------------------------
  // Return the list of application kernel names currently in the database.
  //
  // @param filter A text string that must be found in name of the application
  //   kernel.
  // --------------------------------------------------------------------------------
  
  private function appkernelsAction()
  {
    $params = $this->_parseRestArguments();
    $results = array();

    $db = new AppKernelDb();
    $defs = $db->loadAppKernelDefinitions($params);

    foreach ( $defs as $def ) 
    {
      $results[$def->id] = $def->name;
    }

    return array('success' => true,
                 'results' => $results);
    
  }  // appkernelsAction()

  // --------------------------------------------------------------------------------

  private function appkernelsDocumentation()
  {
    $doc = new \RestDocumentation();
    $doc->setDescription("Retrieve the list of application kernels and ids.");
    $doc->setAuthenticationRequirement(false);
    $doc->addReturnElement("appkernels", "An associative array where the key is the app kernel id and the value is the app kernel name");

    
    return $doc;
    
  }  // appkernelsDocumentation()

  // --------------------------------------------------------------------------------
  // Return the list of visible resources.
  //
  // @param filter A text string that must be found in name of the resource
  // --------------------------------------------------------------------------------
  
  private function resourcesAction()
  {
    $params = $this->_parseRestArguments();
    $results = array();

    $db = new AppKernelDb();
    $resources = $db->loadResources($params);

    foreach ( $resources as $resource ) 
    {
      $results[$resource->id] = $resource->name;
    }

    return array('success' => true,
                 'results' => $results);
    
  }  // resourcesAction()

  // --------------------------------------------------------------------------------

  private function resourcesDocumentation()
  {
    $doc = new \RestDocumentation();
    
    $doc->setDescription("Retrieve the list of resources and ids.");
    $doc->setAuthenticationRequirement(false);
    $doc->addReturnElement("resources", "An associative array where the key is the resource id and the value is the resource name");
    return $doc;
    
  }  // resourcesDocumentation()

  // --------------------------------------------------------------------------------
  // Return the information needed to display the application kernels in a tree
  // hierarchy where application kernel is the top level followed by resources
  // where that kernel was executed, metrics available on those resources, the
  // number of processing units used, and finally the series of data points over
  // time:
  //
  // (app kernel -> resource -> metric -> proc units -> data series)
  //
  // Additionally, a time window may be provided to restrict the information
  // generated to only application kernels executed during that window.
  //
  // Only a single level of a tree branch is generated at a time with the branch
  // being determined by the values of the filters and the and depth determined
  // by the number the filters privided.  Specifying no filters will
  // generate the full list of application kernels (depth = 1), specifying an
  // application kernel will generate the list of resources where that kernel
  // was executed (depth = 2), adding a resource will generate list of metrics
  // available for that resource (depth = 3), adding a metric will generate the
  // list of processing units available for that metric (depth = 4), and adding
  // the number of procedding units will generate the data series.
  //
  // @param ak The application kernel id
  // @param resource The resource id
  // @param metric The metric id
  // @param num_proc_units The number of processing units
  // @param start_time Unix timestamp indicating the start of the time window
  // @param end_time Unix timestamp indicating the end of the time window
  //
  // --------------------------------------------------------------------------------
  /*
  private function treeAction()
  {
    $params = $this->_parseRestArguments();
    $results = array();
    $db = new AppKernelDb();

    // Extract the parameters that were sent

    $akId = ( isset($params['ak']) && is_numeric($params['ak'])
              ? $params['ak'] : NULL );
    $resourceId = ( isset($params['resource']) && is_numeric($params['resource'])
                    ? $params['resource'] : NULL );
    $metricId = ( isset($params['metric']) && is_numeric($params['metric'])
                  ? $params['metric'] : NULL );
    $numProcUnits = ( isset($params['num_proc_units']) && is_numeric($params['num_proc_units'])
                      ? $params['num_proc_units'] : NULL );
    $collected = ( isset($params['collected']) && is_numeric($params['collected'])
                  ? $params['collected'] : NULL );
    $debugMode = isset($params['debug']);
    
    $startTime = ( isset($params['start_time']) && is_numeric($params['start_time'])
                   ? $params['start_time'] : NULL );
    
    $endTime = ( isset($params['end_time']) && is_numeric($params['end_time'])
                 ? $params['end_time'] : NULL );

    $status = ( isset($params['status']) ? $params['status'] : NULL );

    $groupBy = ( NULL !== $numProcUnits ? NULL
                 : ( NULL !== $metricId ? "num_proc_units"
                     : ( NULL !== $resourceId ? "metric"
                         : ( NULL !== $akId ? "resource"
                             : "ak" ) ) ) );

    // Enforce the parameters in the hierarchy

    if ( ($resourceId && ! $akId) ||
         ($metricId && ! ($akId && $resourceId))  ||
         ($numProcUnits && ! ($akId && $resourceId && $metricId)) )
    {
      $msg = "Did not specify all levels of the hierarchy";
      throw new Exception($msg);
    }

    // Determine the node type

    $nodeType = self::TREENODE_APPKERNEL;
    if ( NULL !== $collected )
      $nodeType = NULL;
    else if ( NULL !== $metricId && NULL !== $resourceId && NULL !== $akId && NULL !== $numProcUnits)
      $nodeType = self::TREENODE_INSTANCE;
    else if ( NULL !== $metricId && NULL !== $resourceId && NULL !== $akId )
      $nodeType = self::TREENODE_UNITS;
    else if ( NULL !== $resourceId && NULL !== $akId )
      $nodeType = self::TREENODE_METRIC;
    else if ( NULL !== $akId )
      $nodeType = self::TREENODE_RESOURCE;

    // Load up the data

    if ( NULL !== $nodeType )
    {
      $restrictions = array('ak'        => $akId,
                            'resource'  => $resourceId,
                            'metric'    => $metricId,
                            'num_units' => $numProcUnits,
                            'start'     => $startTime,
                            'end'       => $endTime,
                            'group_by'  => $groupBy,
                            'debug'     => $debugMode,
                            'status'    => $status);
      
      $retval = $db->loadTreeLevel($restrictions);
      
      foreach ( $retval as $row ) 
      {
        $node = $this->createTreeNode($nodeType, $row);
        $node->leaf = ( ($debugMode && self::TREENODE_INSTANCE == $nodeType) ||
                        (! $debugMode && self::TREENODE_UNITS == $nodeType) );
        $results[] = $node;
      }  // foreach ( $retval as $row ) 
    }
    else
    {
      $ak = new InstanceData;
      $akOptions = array('ak_def_id'   => $akId,
                         'collected'   => $collected,
                         'resource_id' => $resourceId);
      $db->loadAppKernelInstanceInfo($akOptions, $ak, TRUE);
      $results[] = $ak->toHtml();
    }

    return array('success' => true,
                 'results' => $results);
    
  }  // treeAction()
  */

  // --------------------------------------------------------------------------------

  private function treeAction()
  {
    $params = $this->_parseRestArguments();
    $results = array();
    $db = new AppKernelDb();

    // Extract the parameters that were sent

    $debugMode = (isset($params['debug']) && $params['debug']);

    $akId = ( isset($params['ak']) && is_numeric($params['ak'])
              ? $params['ak'] : NULL );
    $resourceId = ( isset($params['resource']) && is_numeric($params['resource'])
                    ? $params['resource'] : NULL );
    $instanceId = ( isset($params['instance_id']) && is_numeric($params['instance_id'])
                    ? $params['instance_id'] : NULL );
    $metricId = ( isset($params['metric']) && is_numeric($params['metric'])
                  ? $params['metric'] : NULL );
    $numProcUnits = ( isset($params['num_proc_units']) && is_numeric($params['num_proc_units'])
                      ? $params['num_proc_units'] : NULL );
    $collected = ( isset($params['collected']) && is_numeric($params['collected'])
                  ? $params['collected'] : NULL );
    
    $startTime = ( isset($params['start_time']) && is_numeric($params['start_time'])
                   ? $this->checkDateParam($params['start_time']) : NULL );
    
    $endTime = ( isset($params['end_time']) && is_numeric($params['end_time'])
                   ? $this->checkDateParam($params['end_time'], $isEndDate=true) : NULL );

    // Default to showing only successful runs but in debug mode show everything

    $status = ( isset($params['status']) ? $params['status'] : ($debugMode ? NULL : 'success') );

    // Debug mode does not show metrics

    $groupBy = ( NULL !== $numProcUnits ? NULL
                 : ( NULL !== $metricId ? "num_proc_units"
                     : ( NULL !== $resourceId ? ( $debugMode ? "num_proc_units" : "metric" )
                         : ( NULL !== $akId ? "resource"
                             : "ak" ) ) ) );
    $resource_first=(isset($params['resource_first'])?$params['resource_first']:false);
    
    if($resource_first){
        $groupBy = ( NULL !== $numProcUnits ? NULL
                 : ( NULL !== $metricId ? "num_proc_units"
                     : ( NULL !== $akId ? ( $debugMode ? "num_proc_units" : "metric" )
                         : ( NULL !== $resourceId ? "ak"
                             : "resource" ) ) ) );
    }

    // Enforce the parameters in the hierarchy
    if($resource_first){
        if ( ($akId && ! $resourceId) ||
             ($metricId && ! ($akId && $resourceId))  ||
             ($numProcUnits && ! ($akId && $resourceId)) )
        {
          $msg = "Did not specify all levels of the hierarchy";
          throw new Exception($msg);
        }
    }
    else{
        if ( ($resourceId && ! $akId) ||
             ($metricId && ! ($akId && $resourceId))  ||
             ($numProcUnits && ! ($akId && $resourceId)) )
        {
          $msg = "Did not specify all levels of the hierarchy";
          throw new Exception($msg);
        }
    }

    // Determine the node type. Debug mode does not show metrics
    if($resource_first)
        $nodeType = self::TREENODE_RESOURCE;
    else
        $nodeType = self::TREENODE_APPKERNEL;
    
    if ( NULL !== $collected || NULL !== $instanceId )
      $nodeType = NULL;
    else if ( ($debugMode || NULL !== $metricId) &&
              NULL !== $resourceId && NULL !== $akId && NULL !== $numProcUnits)
      $nodeType = self::TREENODE_INSTANCE;
    else if ( NULL !== $metricId && NULL !== $resourceId && NULL !== $akId )
      $nodeType = self::TREENODE_UNITS;
    else if ( NULL !== $resourceId && NULL !== $akId )
      $nodeType = ( $debugMode ? self::TREENODE_UNITS : self::TREENODE_METRIC );
    else {
        if($resource_first){
            if ( NULL !== $resourceId )
                $nodeType = self::TREENODE_APPKERNEL;
            
        }
        else{
            if ( NULL !== $akId )
                $nodeType = self::TREENODE_RESOURCE;
        }
    }
        
      

    // Load up the data

    if ( NULL !== $nodeType )
    {
      $restrictions = array('ak'        => $akId,
                            'resource'  => $resourceId,
                            'metric'    => $metricId, //AG 9/6/12 added to fix expand bug
                            'num_units' => $numProcUnits,
                            'start'     => $startTime,
                            'end'       => $endTime,
                            'group_by'  => $groupBy,
                            'debug'     => $debugMode,
                            'status'    => $status);
      $retval = $db->loadTreeLevel($restrictions);
    
      foreach ( $retval as $row ) 
      {
        $node = $this->createTreeNode($nodeType, $row,$resource_first);
        $node->leaf = ( ($debugMode && self::TREENODE_INSTANCE == $nodeType) ||
                        (! $debugMode && self::TREENODE_UNITS == $nodeType) );
        $results[] = $node;
      }  // foreach ( $retval as $row ) 
    }
    else
    {
      $ak = new InstanceData;

      $akOptions = array('ak_def_id'   => $akId,
                         'collected'   => $collected,
                         'resource_id' => $resourceId,
                         'num_units'   => $numProcUnits,
                         'instance_id' => $instanceId);
      $db->loadAppKernelInstanceInfo($akOptions, $ak, TRUE);
      $results[] = $ak->toHtml();
    }

    return array('success' => true,
                 'results' => $results);
    
  }  // treeAction()

  // --------------------------------------------------------------------------------

  private function treeDocumentation()
  {
    $doc = new \RestDocumentation();
    $desc =
      "Return the information needed to display the application kernels in a tree
hierarchy where application kernel is the top level followed by resources
where that kernel was executed, metrics available on those resources, and the
number of processing units used:
<br><br>
(app kernel -> resource -> metric -> proc units -> data series)
<br><br>
Only a single level of a tree branch is generated at a time with the branch
being determined by the values of the filters and the and depth determined by
the number the filters privided.  Specifying no filters will generate the full
list of application kernels (depth = 1), specifying an application kernel id
(ak) will generate the list of resources where that kernel was executed (depth =
2), adding a resource id (resource) will generate list of metrics available for
that resource (depth = 3), adding a metric id (metric) will generate the list of
processing units available for that metric (depth = 4), and adding the number of
pprocessing units (num_proc_units) will generate the data series.  An error will
result if a level of the hierarchy is skipped (e.g., provididing an app kernel
and metric skips the resource and will result in an error).
<br><br>
Additionally, a time window may be provided to restrict the information
generated to only application kernels executed during that window.  If only the
start of the window is specified any results from that time to the present will be
returned; if only the end of the window is specified any results prior to that time
will be returned (inclusive).";
    $doc->setDescription($desc);
    $doc->setAuthenticationRequirement(false);

    $doc->addArgument("ak",
                      "The application kernel identifier taken from a returned tree node",
                      FALSE);
    $doc->addArgument("resource",
                      "The resource identifier taken from a returned tree node",
                      FALSE);
    $doc->addArgument("metric",
                      "The metric identifier taken from a returned tree node",
                      FALSE);
    $doc->addArgument("num_proc_units",
                      "Number of processing units taken from the returned tree node",
                      FALSE);
    $doc->addArgument("start_time",
                      "Unix timestamp specifying the start of the window for which data will be displayed (inclusive)",
                      FALSE);
    $doc->addArgument("end_time",
                      "Unix timestamp specifying the end of the window for which data will be displayed (inclusive)",
                      FALSE);

    $doc->setOutputFormatDescription("An array of records is returned where each record describes one node in the tree at this level.  Each each record contains the following data.");
    $doc->addReturnElement("id", "A unique identifier for the node created by concatenating the appkernel id, " .
                           "resource id, metric id, and units for this node");
    $doc->addReturnElement("type", "The type of tree node (appkernel, resource, metric, units, instance)");
    $doc->addReturnElement("text", "The of the node");
    $doc->addReturnElement("ak_id", "The app kernel id for this node");
    $doc->addReturnElement("resource_id", "The optional resource id for this node");
    $doc->addReturnElement("metric_id", "The optional metric id for this node");
    $doc->addReturnElement("num_proc_units", "The optional number of processing units for this node");
    $doc->addReturnElement("leaf", "1 or 0 indicating whether or not this tree node is a leaf");
    
    return $doc;
    
  }  // treeDocumentation()


  
  // --------------------------------------------------------------------------------
  // Return one or more datasets describing application kernels based on the
  // filters specified.  Each dataset contains:
  //
  // - Name of the application kernel
  // - Name of the resource that the application kernel was run on
  // - Name and unit of the metric
  // - Number of processing units (cores, nodes, etc.)
  // - A text/html description of the app kernel
  // - A text/html description of the resource
  // - A vector of data points
  // - A vector of unix timestamps for each data point collected<br>
  // - A vector containing a 0 or 1 for each data point indicating a version change
  //   from the previous data point.
  //
  // Filter parameters are used to restrict the list of datasets returned.  By
  // default, all datasets associated with an app kernel are returned.  Adding
  // additional filters will return only datasets matching the combination of
  // the supplied criteria.  For example, specifying an app kernel, resource,
  // and metric will generate a dataset for each distinct number of processing
  // units that combination of app kernel, resource, and metric was run on.
  //
  // Additionally, a time window may be provided to restrict the information
  // generated to only app kernels executed during that window.  If only the
  // start of the window is specified any results from that time to the present
  // will be returned; if only the end of the window is specified any results
  // prior to that time will be returned (inclusive).
  //
  // @param ak An app kernel identifier used to filter the results
  // @param resource A resource identifier used to filter the results
  // @param metric A metric identifier used to filter the results
  // @param num_proc_units The number of processing units used to filter the results
  // @param start_time A unix timestamp indicating the start window for the results
  // @param end_time A unix timestamp indicating the end window for the results
  // @param debug TRUE to enter debug mode
  //
  // @returns A list of Dataset objects containing the requested data
  // --------------------------------------------------------------------------------

  private function datasetAction()
  {
    $params = $this->_parseRestArguments();

    $results = array();
    $db = new AppKernelDb();

    // Extract the parameters that were sent
    if(isset($params['metric'])&& (!is_numeric($params['metric']))){
        $sql = "SELECT * FROM metric WHERE LOWER(name)=LOWER('{$params['metric']}');";
        $result = $db->getDB()->query($sql);
        if(count($result)==1){
            $params['metric']=$result[0]['metric_id'];
        }
    }

    $akId = ( isset($params['ak']) && is_numeric($params['ak'])
              ? $params['ak'] : NULL );
    $resourceId = ( isset($params['resource']) && is_numeric($params['resource'])
                    ? $params['resource'] : NULL );
    $metricId = ( isset($params['metric']) && is_numeric($params['metric'])
                  ? $params['metric'] : NULL );
    $numProcUnits = ( isset($params['num_proc_units']) && is_numeric($params['num_proc_units'])
                      ? $params['num_proc_units'] : NULL );
    $debugMode = isset($params['debug']);
    
    $startTime = ( isset($params['start_time']) && is_numeric($params['start_time'])
                   ? $this->checkDateParam($params['start_time']) : NULL );

    // Bug # 1342
    // bump the end time, if assigned, to midnight, so all kernels from that day display.
    $endTime = ( isset($params['end_time']) && is_numeric($params['end_time'])
                 ? $this->checkDateParam($params['end_time'], $isEndDate=true) : NULL );

    $metadataOnly = ( isset($params['metadata_only']) &&
                      ( 1 == $params['metadata_only'] || "y" == $params['metadata_only'] ) );

	$inline = true;
	if(isset($params['inline']))
	{
		$inline = $params['inline'] == 'true' || $params['inline'] === 'y';
	}

    if ( NULL === $akId )
    {
      throw new Exception("Application kernel id must be specified");
    }
	
	$format = \DataWarehouse\ExportBuilder::getFormat($params, 'json', array('json', 'xml', 'xls', 'csv'));//todo: perhaps add jsonstore support

	// Load up the data
    $datasetList =  $db->getDataset($akId, $resourceId, $metricId, $numProcUnits, $startTime,$endTime, $metadataOnly, $debugMode);

	if($format == 'json') //default format
	{
		 return array('success' => true,
             'results' => $datasetList
         );
	}
	else
	if($format == 'jsonstore') //not supported yet
	{
		
	}
	else
	if($format == 'xls' || $format == 'csv' || $format == 'xml')
	{
		$title = 'data';
		$exportedDatas = array();
		foreach($datasetList as $result)
		{
			$exportedDatas[] = $result->export();
			$title = $result->akName.': '.$result->resourceName.': '.$result->metric;
		}
						
		return \DataWarehouse\ExportBuilder::export($exportedDatas,$format,$inline, $title);
	}
  }  // datasetAction()

  // --------------------------------------------------------------------------------

  private function datasetDocumentation()
  {
    $doc = new \RestDocumentation();
    $desc =
      "Return one or more datasets describing application kernels based on the
filters specified.  Each dataset contains:<br>
- Name of the application kernel<br>
- Name of the resource that the application kernel was run on<br>
- Name and unit of the metric<br>
- Number of processing units (cores, nodes, etc.)<br>
- A vector of data points<br>
- A vector of unix timestamps for each data point collected<br>
- A vector containing a 0 or 1 for each data point indicating a version change
  from the previous data point.
<br><br>
Filter parameters are used to restrict the list of datasets returned.  By
default, all datasets associated with an app kernel are returned.  Adding
additional filters will return only datasets matching the combination of the
supplied criteria.  For example, specifying an app kernel, resource, and metric
will generate a dataset for each distinct number of processing units that
combination of app kernel, resource, and metric was run on.
<br><br>
Additionally, a time window may be provided to restrict the information
generated to only app kernels executed during that window.  If only the start of
the window is specified any results from that time to the present will be
returned; if only the end of the window is specified any results prior to that
time will be returned (inclusive).";

    $doc->setDescription($desc);
    $doc->setAuthenticationRequirement(false);

    $doc->addArgument("ak",
                      "The application kernel identifier taken from a returned tree node");
    $doc->addArgument("resource",
                      "The resource identifier taken from a returned tree node",
                      FALSE);
    $doc->addArgument("metric",
                      "The metric identifier taken from a returned tree node",
                      FALSE);
    $doc->addArgument("num_proc_units",
                      "Number of processing units taken from the returned tree node",
                      FALSE);
    $doc->addArgument("start_time",
                      "Unix timestamp specifying the start of the window for which data will be displayed (inclusive)",
                      FALSE);
    $doc->addArgument("end_time",
                      "Unix timestamp specifying the end of the window for which data will be displayed (inclusive)",
                      FALSE);
    $doc->addArgument("metadata_only",
                      "TRUE to return metadata (AK name, resource name, metric, description, etc.) only - no data points",
                      FALSE);

    $doc->setOutputFormatDescription("An array of records is returned where each record represents a dataset that matches the specified filter parameters.  Each each record containings the following data.");
    $doc->addReturnElement("akName", "The name of the app kernel");
    $doc->addReturnElement("resourceName", "The name of the resource the app kernel was run on");
    $doc->addReturnElement("metric", "The name of the metric measured");
    $doc->addReturnElement("metricUnit", "The metric unit");
    $doc->addReturnElement("numProcUnits", "The number of processing units used");
    $doc->addReturnElement("description", "A text or HTML description of the app kernel");
    $doc->addReturnElement("valueVector", "A vector of the data points (values) to plot");
    $doc->addReturnElement("timeVector", "A vector of the times associated with each data point");
    $doc->addReturnElement("versionVector", "A vector of boolean (0 or 1) values indicating whether or not the app kernel version changed at this point in time");
    return $doc;
    
  }  // datasetDocumentation()

  // --------------------------------------------------------------------------------

  private function plotAction()
  {
    $params = $this->_parseRestArguments(); 
    
    $thumbnail = ( isset($params['thumbnail']) 
        ? $params['thumbnail'] === 'y' : false );	
    $show_title = ( isset($params['show_title']) 
              ? $params['show_title'] === 'y' : false );
    $width = ( isset($params['width']) && is_numeric($params['width'])
              ? $params['width'] : 740 );
    $height = ( isset($params['height']) && is_numeric($params['height'])
                    ? $params['height'] : 345 );
    $scale = ( isset($params['scale']) && is_numeric($params['scale'])
                  ? $params['scale'] : 1.0 );

    $start_date =  (isset($params['start_time']) && is_numeric($params['start_time'])
                   ? $this->checkDateParam($params['start_time'], $isEndDate=false, $isYMDFormat=true) : NULL );

    $end_date = ( isset($params['end_time']) && is_numeric($params['end_time'])
                   ? $this->checkDateParam($params['end_time'], $isEndDate=true, $isYMDFormat=true) : NULL );

    $swap_xy = isset($params['swap_xy'])?$params['swap_xy'] == 'true' || $params['swap_xy'] === 'y': false;
	
    $limit = !isset($params['limit']) || empty($params['limit']) ? 20 : $params['limit'];
    $offset = !isset($params['offset']) || empty($params['offset']) ? 0 : $params['offset'];
	
    $legend_location = isset($params['legend_type']) && $params['legend_type'] != '' ? $params['legend_type']: 'bottom_center';
    $font_size = isset($params['font_size']) && $params['font_size'] != '' ? $params['font_size']: 'default'	;	  
    $show_guide_lines = true;
    if(isset($params['show_guide_lines']))
    {
        $show_guide_lines = $params['show_guide_lines'] == 'true' || $params['show_guide_lines'] === 'y';
    }	
	$inline = true;
	if(isset($params['inline']))
	{
		$inline = $params['inline'] == 'true' || $params['inline'] === 'y';
	}	
	$show_change_indicator =  ( isset($params['show_change_indicator']) 
        	      ? $params['show_change_indicator'] === 'y' : false );
	$show_control_plot =  ( isset($params['show_control_plot']) 
        	      ? $params['show_control_plot'] === 'y' : false );	
	$show_control_zones =  ( isset($params['show_control_zones']) 
        	      ? $params['show_control_zones'] === 'y' : false );	
	$discrete_controls =  ( isset($params['discrete_controls']) 
        	      ? $params['discrete_controls'] === 'y' : false );		
	$show_running_averages =  ( isset($params['show_running_averages']) 
        	      ? $params['show_running_averages'] === 'y' : false );			  
	$show_control_interval =  ( isset($params['show_control_interval']) 
        	      ? $params['show_control_interval'] === 'y' : false );
        
        $show_num_proc_units_separately =  ( isset($params['show_num_proc_units_separately']) 
        	      ? $params['show_num_proc_units_separately'] === 'y' : false );
				  	  		  			  	  
	$single_metric =  isset($params['num_proc_units']) && is_numeric($params['num_proc_units']);
        if($show_num_proc_units_separately)
            $single_metric = true;
        
        $contextMenuOnClick=( isset($params['contextMenuOnClick'])
        	      ? $params['contextMenuOnClick'] : NULL );
				  
	$format = \DataWarehouse\ExportBuilder::getFormat($params, 'session_variable', array('session_variable', 'png_inline', 'img_tag', 'png', 'svg'));

	$dataset = $this->datasetAction();
                
	if($dataset['success'] !== true)
	{
		throw new \Exception('Dataset is empty');
	}
	$results = $dataset['results'];

	$returnValue = array();

	if($this->_request->getToken() != '')
	{
		$chartPool = new \XDChartPool($this->_authenticateUser());
	}
	
	$resourceDescription = '';
	$lastResult = new \AppKernel\Dataset('Empty App Kernel Dataset',-1,"",-1,"",-1,"","","","");
	$hc = new \DataWarehouse\Visualization\HighChartAppKernel($start_date, $end_date, $scale, $width, $height, $swap_xy);
	$hc->setTitle($show_title?'Empty App Kernel Dataset':NULL, $font_size);
	$hc->setLegend($legend_location, $font_size);

	$datasets = array();
	$hc->configure($datasets,
					$font_size,
					$limit,
					$offset,
					$format === 'svg',
					true,
					true,
					false,
					$show_change_indicator,
					$single_metric && $show_control_plot, 
					$single_metric && $discrete_controls, 
					$single_metric && $show_control_zones, 
					$single_metric && $show_running_averages, 
					$single_metric && $show_control_interval,
                                        $contextMenuOnClick
					);
	
	srand(\DataWarehouse\VisualizationBuilder::make_seed());
	foreach($results as $result)
	{
                $num_proc_units_changed=false;
                if($show_num_proc_units_separately && $result->rawNumProcUnits != $lastResult->rawNumProcUnits)
                    $num_proc_units_changed=true;
                    
		if($result->akName != $lastResult->akName 
		   || $result->resourceName != $lastResult->resourceName
		   || $result->metric != $lastResult->metric
                   || $num_proc_units_changed)
		{
		   if($lastResult->akName != "Empty App Kernel Dataset")
		   {
				$requestDescripter = new \User\Elements\RequestDescripter($params);
				$chartIdentifier = $requestDescripter->__toString();
				
				if($format == 'session_variable')
				{
					$vis = array(
						'random_id' => 'chart_'.rand(),
						'title' => $hc->getTitle(),
						'short_title' => $lastResult->metric,
						'comments' => $lastResult->description,
						'ak_name' => $lastResult->akName,
						'resource_name' => $lastResult->resourceName,
						'resource_description' => $lastResult->resourceDescription,
						'chart_args' => $chartIdentifier,
						'included_in_report' => $this->_request->getToken() != ''?($chartPool->chartExistsInQueue($chartIdentifier) ? 'y' : 'n'): 'NA - auth required', 
						'textual_legend' => '',				
						'start_date' => $start_date,
						'end_date' => $end_date,

						'ak_id' => $lastResult->akId,
						'resource_id' => $lastResult->resourceId,
						'metric_id' => $lastResult->metricId
					);
					$json  = $hc->exportJsonStore();
					$vis['hc_jsonstore'] = $json['data'][0];				   
				}
				else
				{
					
					$vis = $hc->getRawImage($format, $params);
					
				}
				$returnValue[] = $vis; 
				
		   }
		   if($format != 'params')
		   {
			    $hc = new \DataWarehouse\Visualization\HighChartAppKernel($start_date, $end_date, $scale, $width, $height, $swap_xy);
			    $hc->setTitle($show_title?$result->metric:NULL, $font_size);
				$hc->setSubtitle($show_title?$result->resourceName:NULL, $font_size);
			    $hc->setLegend($legend_location, $font_size);
	
		   }
	   }
		
		$resourceDescription = $result->resourceDescription;
		if($format != 'params')
		{
			$datasets = array($result);
			
			$hc->configure($datasets,
					$font_size,
					$limit,
					$offset,
					$format === 'svg',
					true,
					true,
					false,
					$show_change_indicator,
					$single_metric && $show_control_plot,
					$single_metric && $discrete_controls, 
					$single_metric && $show_control_zones, 
					$single_metric && $show_running_averages, 
					$single_metric && $show_control_interval,
                                        $contextMenuOnClick
					);	
		}
		$lastResult = $result;
	}	
	$requestDescripter = new \User\Elements\RequestDescripter($params);
	$chartIdentifier = $requestDescripter->__toString();
	
	if($format == 'session_variable')
	{
		$vis = array(
			'random_id' => 'chart_'.rand(),
			'title' => $hc->getTitle(),
			'short_title' => $lastResult->metric,
			'comments' => $lastResult->description,
			'ak_name' => $lastResult->akName,
			'resource_name' => $lastResult->resourceName,
			'resource_description' => $lastResult->resourceDescription,
			'chart_args' => $chartIdentifier,
			'included_in_report' => $this->_request->getToken() != ''?($chartPool->chartExistsInQueue($chartIdentifier) ? 'y' : 'n'): 'NA - auth required', 
			'textual_legend' => '',
			'start_date' => $start_date,
			'end_date' => $end_date,

			'ak_id' => $lastResult->akId,
			'resource_id' => $lastResult->resourceId,
			'metric_id' => $lastResult->metricId
		);
					    
		$json  = $hc->exportJsonStore();
		$vis['hc_jsonstore'] = $json['data'][0];
	}else 
	{
		$vis = $hc->getRawImage($format, $params);
	}
	
	$returnValue[] = $vis;

	if($format == 'session_variable' )
	{
		return array('success' => true, 'results' => $returnValue);
	}else
	if($format == 'img_tag')
	{
		foreach($returnValue as $vis)
		{
			return array('headers' =>\DataWarehouse\ExportBuilder::getHeader($format),
						 'results' => $vis);
		}
	}else
	if($format == 'png' || $format == 'svg' || $format == 'png_inline')
	{
		foreach($returnValue as $vis)
		{
			return array('headers' => \DataWarehouse\ExportBuilder::getHeader($format, $inline, 'ak_usage_'.$start_date.'_to_'.$end_date.'_'.$lastResult->resourceName.'_'.$lastResult->akName.'_'.$lastResult->metric ),
						 'results' => $vis);
		}
	
	}

  }  // plotAction()

  // --------------------------------------------------------------------------------

  private function plotDocumentation()
  {
    $doc = new \RestDocumentation();
    $desc =
      "Return one or more datasets describing application kernel plots based on the
filters specified.  Each plot contains:<br>
- Random unique identifer<br>
- Title of the plot<br>
- Short title of the plot<br>
- Comments<br>
- Description of the resource that hosted the application kernels<br>
- Start date<br>
- End date<br>
- A chart_url to be used as the suffix to /html/controllers/ui_data/getchart.php
	ie. /html/controllers/ui_data/getchart.php?chart_url
- A chart_map to be used as the html map definition fo the plot image.
	ie. <map> chart_map </map>
<br><br>
Filter parameters are used to restrict the dataset of the plots returned.  By
default, all plots associated with an app kernel are returned.  Adding
additional filters will return only plots matching the combination of the
supplied criteria.  For example, specifying an app kernel, resource, and metric
will generate a line plot containting a data series for each distinct number of 
processing units that combination of app kernel, resource, and metric was run on.
<br><br>
Additionally, a time window may be provided to restrict the information
generated to only app kernels executed during that window.  If only the start of
the window is specified any results from that time to the present will be
returned; if only the end of the window is specified any results prior to that
time will be returned (inclusive).
Furthermore, the following parameters can be used to tweak the plots: height, width, 
scale ratio and show (or hide) the title (and subtitle) on the plot image.";

    $doc->setDescription($desc);
    $doc->setAuthenticationRequirement(false);
    $doc->addArgument("ak",
                      "The application kernel identifier taken from a returned tree node");
    $doc->addArgument("resource",
                      "The resource identifier taken from a returned tree node",
                      FALSE);
    $doc->addArgument("metric",
                      "The metric identifier taken from a returned tree node",
                      FALSE);
    $doc->addArgument("num_proc_units",
                      "Number of processing units taken from the returned tree node",
                      FALSE);
    $doc->addArgument("start_time",
                      "Unix timestamp specifying the start of the window for which data will be displayed (inclusive)",
                      FALSE);
    $doc->addArgument("end_time",
                      "Unix timestamp specifying the end of the window for which data will be displayed (inclusive)",
                      FALSE);
	$doc->addArgument("width",
                      "Width of the plot in pixels. Defaults to 740",
                      FALSE);
	$doc->addArgument("height",
                      "Height of the plot in pixels. Defaults to 345",
                      FALSE);
	$doc->addArgument("scale",
                      "Value between 0 and 1. If 0 is passed, the default value of 1 is used.",
                      FALSE);
	$doc->addArgument("show_title",
                      "true or false. Whether to draw the title and subtitle texts on the plot. Defaults to false",
                      FALSE);
	$doc->addArgument("token",
                      "The auth token. Use if you need report builder capabilities, otherwise not needed.",
                      FALSE);	
	$doc->addArgument("format",
                      "default session_variable. Possible values: 'session_variable', 'params', 'png_inline', 'img_tag', 'png', 'svg'",
                      FALSE);				  
    $doc->addReturnElement("random_id", "Random unique identifer");
    $doc->addReturnElement("title", "Title of the plot");
    $doc->addReturnElement("short_title", "Short title of the plot");
    $doc->addReturnElement("comments", "Comments for the plot");
	$doc->addReturnElement("resource_description", "Description of the resource that hosted the application kernels");
    $doc->addReturnElement("start_date", "Start date of the data of the plot in Y-m-d format");
	$doc->addReturnElement("end_date", "End date of the data of the plot in Y-m-d format");
    $doc->addReturnElement("chart_url", "(if format=session_variable) A chart_url to be used as the suffix to /html/controllers/ui_data/getchart.php ie. /html/controllers/ui_data/getchart.php?chart_url");
    $doc->addReturnElement("chart_map", "(if format=session_variable)A chart_map to be used as the html map definition fo the plot image.
	ie. <map> chart_map </map>");

    return $doc;
    
  }  // plotDocumentation()
  
  // ================================================================================
  // Helper functions
  // ================================================================================

  // --------------------------------------------------------------------------------
  // Helper function for creating an ExtJs tree node based on the type and a row
  // from the v_tree view.
  //
  // @param $type Tree node type
  // @param $record Record returned from the database
  //
  // @returns An object representation of the tree node
  // --------------------------------------------------------------------------------

 private function createTreeNode($type, $record, $resource_first=false)
  {
    $node = array('id'    => $this->nodeId($type, $record),
                  'type'  => $type//,
				 // 'singleClickExpand' => true
				  );
    switch ($type)
    {
    case self::TREENODE_INSTANCE:
      $node['status'] = $record['status'];
      $node['text'] = date("Y-m-d H:i:s", $record['collected']);
      $node['collected'] = $record['collected'];
      $node['num_proc_units'] = $record['num_units'];
      $node['metric_id'] = ( isset($record['metric_id']) ? $record['metric_id'] : NULL );
      $node['resource_id'] = $record['resource_id'];
      $node['ak_id'] = $record['ak_def_id'];
      $node['instance_id'] = $record['instance_id'];
      break;
    case self::TREENODE_UNITS:
      $text = $record['num_units'] . " " . $record['processor_unit'] . ( $record['num_units'] > 1 ? "s" : "" );
      $node['text'] = $text;
      $node['num_proc_units'] = $record['num_units'];
      $node['metric_id'] = ( isset($record['metric_id']) ? $record['metric_id'] : NULL );
      $node['resource_id'] = $record['resource_id'];
      $node['ak_id'] = $record['ak_def_id'];
      break;
    case self::TREENODE_METRIC:
      $node['text'] = $record['metric'];
      $node['metric_id'] = ( isset($record['metric_id']) ? $record['metric_id'] : NULL );
      $node['resource_id'] = $record['resource_id'];
      $node['ak_id'] = $record['ak_def_id'];
      break;
    case self::TREENODE_RESOURCE:
      $node['text'] = $record['resource'];
      $node['resource_id'] = $record['resource_id'];
      if(!$resource_first)
        $node['ak_id'] = $record['ak_def_id'];
      break;
    case self::TREENODE_APPKERNEL:
      $node['text'] = $record['ak_name'];/*.' '.date('Y-m-d',$record['start_ts']).' '.date('Y-m-d', $record['end_ts'])*/
      $node['ak_id'] = $record['ak_def_id'];
      if($resource_first)
        $node['resource_id'] = $record['resource_id'];
      break;
    default:

      break;
    }
    if(isset($record['start_ts']))$node['start_ts'] = $record['start_ts'];
    if(isset($record['end_ts']))$node['end_ts'] = $record['end_ts'];
    return (object) $node;
  }  // createTreeNode()

  // --------------------------------------------------------------------------------
  
  private function nodeId($type, $record)
  {
    $id = array();
    switch ($type)
    {
    case self::TREENODE_UNITS:
      array_unshift($id, $record['num_units']);
    case self::TREENODE_METRIC:
      if ( isset($record['metric_id']) )
        array_unshift($id, $record['metric_id']);
    case self::TREENODE_RESOURCE:
      array_unshift($id, $record['resource_id']);
    case self::TREENODE_APPKERNEL:
      array_unshift($id, $record['ak_def_id']);
      break;
    default:
      break;
    }
    return implode("_", $id);
  }  // nodeId()

  // --------------------------------------------------------------------------------
  // checkDateParam()
  //
  // Helper function for providing begin or end date in indicated format, given timestamp
  // in seconds. Begin date should be midnight at beginning of the day (inclusive), or 
  // the date as supplied; end date should be the second before the next day begins 
  // (inclusive). 
  //    see classes/DataWarehouse/Query/Query.php setDuration()
  //
  // @param $dateVal (Unix timestamp in seconds)
  // @param $isEndDate (Should timestamp be interpreted as end date (true) or begin date?)
  // @param $isYMDFormat (Should output be provided in Y-m-d format (true) or as Unix timestamp?)
  //
  // @returns Date in Y-m-d format (string) or as Unix timestamp in seconds.
  // --------------------------------------------------------------------------------

  private function checkDateParam($dateVal, $isEndDate=false, $isYMDFormat=false){

    // Already know the date value is numeric, and not null.
    $retDate = $dateVal;

    // Cheesy. Convert from unixtime in seconds to date as associative array:
    $date_parsed = date_parse_from_format('Y-m-d', strftime('%Y-%m-%d', $dateVal));

    // Set the time for the end date to just before midnight so it includes the full day.
    if ($isEndDate == true) {
        $retDate = mktime(23,
                          59,
                          59,
                          $date_parsed['month'],
                          $date_parsed['day'],
                          $date_parsed['year']);
    } else {
        $retDate= mktime($date_parsed['hour'],
                         $date_parsed['minute'],
                         $date_parsed['second'],
                         $date_parsed['month'],
                         $date_parsed['day'],
                         $date_parsed['year']);
    }

    // If we need Y-m-d, convert it:
    $retDate = ( ($isYMDFormat==TRUE) ? strftime('%Y-%m-%d', $retDate): $retDate );

    return $retDate;
  } // checkDateParam()
  
  // --------------------------------------------------------------------------------
  // Return the list of application kernel names currently in the database.
  //
  // @param filter A text string that must be found in name of the application
  //   kernel.
  // --------------------------------------------------------------------------------
  
  private function control_regionsAction()
  {
    $params = $this->_parseRestArguments();
        
    $sub_action = ( isset($params['sub_action'])
              ? $params['sub_action'] : 'get_control_regions' );
    
    $db = new AppKernelDb($this->logger);
    if($sub_action==='get_control_regions')
    {
        if(key_exists('resource_id', $params) && key_exists('ak_def_id', $params))
        {
            $results=$db->getControlRegions(intval($params['resource_id']),intval($params['ak_def_id']));
        }
        else
        {
            $results=array();
        }
        return array('success' => true,
                     'results' => $results,
                     'count'   => count($results));
    }
    
    // Load the application kernel definitions for the description

    $appKernelDefs = $db->loadAppKernelDefinitions();
    $akList = array();
    foreach ( $appKernelDefs as $ak )
    {
        $akList[$ak->id] = $ak;
    }

    // Load the resource definitions for the description

    $resourceDefs = $db->loadResources();
    $resourceList = array();
    foreach ( $resourceDefs as $res )
    {
        $resourceList[$res->id] = $res;
    }
    
    if($sub_action==='delete_control_regions')
    {
        if(!( isset($params['resource_id']) && is_numeric($params['resource_id']))){
            return array('success' => false,
                'message' => "resource_id is not specified");            
        }
        if(!( isset($params['ak_def_id']) && is_numeric($params['ak_def_id']))){
            return array('success' => false,
                 'message' => "ak_def_id is not specified");
        }
        if(!( isset($params['controlRegiondIDs']))){
            return array('success' => false,
                 'message' => "controlRegiondIDs is not specified");
        }
        $resource_id = intval($params['resource_id']);
        $ak_def_id = intval($params['ak_def_id']);
        
        $controlRegiondIDs=explode(",", $params['controlRegiondIDs']);
        foreach($controlRegiondIDs as $control_region_def_id){
            $msg=$db->deleteControlRegion(intval($control_region_def_id));
            if($msg['success']==false){
                return $msg;
            }
        }
        
        $ak=$akList[$ak_def_id];
        $res=$resourceList[$resource_id];
        
        $db->calculateControls(false,false,20,5, $res->nickname,$ak->basename);
        
        return array('success' => true,
                 'message' => "deleted control region time intervals");
    }
    if($sub_action==='new_control_regions'||$sub_action==='change_control_regions')
    {
        if(!( isset($params['resource_id']) && is_numeric($params['resource_id']))){
            return array('success' => false,
                'message' => "resource_id is not specified");            
        }
        if(!( isset($params['ak_def_id']) && is_numeric($params['ak_def_id']))){
            return array('success' => false,
                 'message' => "ak_def_id is not specified");
        }
        
        $resource_id = intval($params['resource_id']);
        $ak_def_id = intval($params['ak_def_id']);
        $control_region_type=$params['control_region_time_interval_type'];
        $startDateTime = str_replace('%20',' ',$params['startDateTime']);
        $n_points = ( isset($params['n_points']) && is_numeric($params['n_points'])
                    ? intval($params['n_points']) : NULL );
        $endDateTime = ( isset($params['endDateTime']) 
                    ? str_replace('%20',' ',$params['endDateTime']) : NULL );
        $comment = ( isset($params['comment']) 
                    ? str_replace('%20',' ',$params['comment']) : NULL );
        
        $update=($sub_action==='change_control_regions');
        $control_region_def_id=( isset($params['control_region_def_id']) 
                    ? $params['control_region_def_id'] : NULL );
        
        $msg=$db->newControlRegions($resource_id,$ak_def_id,$control_region_type,
                $startDateTime,$endDateTime,$n_points,$comment,$update,$control_region_def_id);
        
        if($msg['success']==false){
            return $msg;
        }
        
        $ak=$akList[$ak_def_id];
        $res=$resourceList[$resource_id];
        $db->calculateControls(false,false,20,5, $res->nickname,$ak->basename);
        
        return $msg;
    }
    
    return array('success' => false,
                 'message' => "Unknown Sub-action");
    
  }  // appkernelsAction()

  // --------------------------------------------------------------------------------

  private function control_regionsDocumentation()
  {
    $doc = new \RestDocumentation();
    $doc->setDescription("Retrieve the list of application kernels and ids.");
    $doc->setAuthenticationRequirement(false);
    $doc->addReturnElement("appkernels", "An associative array where the key is the app kernel id and the value is the app kernel name");

    
    return $doc;
    
  }  // appkernelsDocumentation()
}  // class Explorer

?>
