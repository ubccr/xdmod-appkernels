<?php

namespace AppKernel;

//require_once __DIR__.'/../../../configuration/linker.php';

use CCR\DB;
use Exception;
use Psr\Log\LoggerInterface;

//use XDUser;

require_once("AppKernelInstanceData_Arr.php");

class ArrExplorer implements iAppKernelExplorer
{
  // Optional PEAR::Log for logging messages
    /**
     * @var LoggerInterface
     */
  private $_logger = NULL;

  // Handle to the mod_appkernel database resource
  private $_db_appkernel = NULL;

  // Handle to the mod_akrr database resource
  private $_db_akrr = NULL;

  // Handle to the datawarehouse database resource
  private $_db_datawarehouse = NULL;

  // Start and end timestamps used for database query intervals
  private $_start = NULL;
  private $_end = NULL;

  // -------------------------------------------------------------------------
  // Instantiate an Explorer class.
  //
  // @see iAppKernelExplorer::factory()
  // -------------------------------------------------------------------------

  public static function factory(array $config, LoggerInterface $logger = NULL)
  {
    return new ArrExplorer($config, $logger);
  }  // factory()

  // -------------------------------------------------------------------------
  // @param $config
  //
  // @throws Exception if there was an error establishing the database connection.
  // -------------------------------------------------------------------------

  private function __construct(array $config, LoggerInterface $logger = NULL)
  {
    $this->add_supremm_metrix = ( isset($config['add_supremm_metrix'])
                 ? $config['add_supremm_metrix']
                 : FALSE );

    $appkernel_section
        = isset($config['config_appkernel'])
        ? $config['config_appkernel']
        : 'appkernel';

    $this->_db_appkernel = DB::factory($appkernel_section);

    $akrr_section
        = isset($config['config_akrr'])
        ? $config['config_akrr']
        : 'akrr-db';

    $this->_db_akrr = DB::factory($akrr_section);

    $this->_db_datawarehouse = DB::factory('datawarehouse', false);

    $this->_logger = $logger;

    //create correspondence table between appkernel resource_id and xdmod_resource_id and xdmod_cluster_id
    $sql="select resource_id,resource,nickname,xdmod_resource_id,xdmod_cluster_id from mod_appkernel.resource";
    $result = $this->_db_appkernel->query($sql);
    $this->resources=array();
    foreach ( $result as $row ){
        $this->resources[$row['nickname']]=$row;
    }

    if($this->add_supremm_metrix){
        //load appkernels id
        $sql="select ak_def_id,ak_base_name from mod_appkernel.app_kernel_def";
        $result = $this->_db_appkernel->query($sql);
        $this->ak_def_id=array();
        foreach ( $result as $row ){
            $this->ak_def_id[$row['ak_base_name']]=intval($row['ak_def_id']);
        }

        //load SUMPREMM metrics definition
        $sql="select id,name,formula,label,info,units from mod_appkernel.supremm_metrics";
        $result = $this->_db_appkernel->query($sql);
        $this->supremm_metrics=array();
        foreach ( $result as $row ){
            $supremm_metrics_id=intval($row['id']);
            $elm_str=str_replace(' ','',$row['formula']);
            $elm_str=str_replace(array('+','-','*','/','(',')'),' ',$elm_str);
            $elm_str=trim($elm_str);
            $elm=preg_split('/\s+/', $elm_str);

            $this->supremm_metrics[$row['name']]=$row;
            $this->supremm_metrics[$row['name']]['id']=$supremm_metrics_id;
            $this->supremm_metrics[$row['name']]['elements']=$elm;
            $this->supremm_metrics[$supremm_metrics_id]=$row;
            $this->supremm_metrics[$supremm_metrics_id]['id']=$supremm_metrics_id;
            $this->supremm_metrics[$supremm_metrics_id]['elements']=$elm;

        }

        //load app kernel specific SUPREMM metric usage
        $sql="select ak_def_id,supremm_metric_id from mod_appkernel.ak_supremm_metrics";
        $result = $this->_db_appkernel->query($sql);
        $this->ak_supremm_metrics=array();
        foreach ( $result as $row ){
            if(!array_key_exists($row['ak_def_id'],$this->ak_supremm_metrics))
                $this->ak_supremm_metrics[intval($row['ak_def_id'])]=array();
            $this->ak_supremm_metrics[intval($row['ak_def_id'])][]=intval($row['supremm_metric_id']);
        }
    }
  }  // __construct()

  // -------------------------------------------------------------------------
  // @see iAppKernelExplorer::setQueryInterval()
  // -------------------------------------------------------------------------

  public function setQueryInterval($start, $end)
  {
    $this->_start = $start;
    $this->_end = $end;
  }  // setQueryInterval()

  // -------------------------------------------------------------------------
  // This is currently not supported because the list of configured resources is
  // not stored in the database.  - SMG 2012-12-04
  //
  // @see iAppKernelExplorer::getAvailableResources()
  // -------------------------------------------------------------------------

  public function getAvailableResources($summary = FALSE)
  {
    $results = array();
    $sql = "";

    if ( $summary )
    {
      $sql = "select resource, reporternickname, count(collected) as num, " .
        "min(collected) as first, max(collected) as last from akrr_xdmod_instanceinfo";
    }
    else
    {
      $sql = "select distinct resource from akrr_xdmod_instanceinfo";
    }

    $timeWindowSql = $this->generateSqlTimeWindow();
    if ( "" == $timeWindowSql ) $sql .= "where $timeWindowSql";

    if ( $summary ) $sql .= " group by resource, reporternickname";
    $sql .= " order by resource" . ( $summary ? ", reporternickname" : "" );

    $this->_logger->debug(__FUNCTION__ . "() Query database (" . date("Y-m-d H:i:s", $this->_start) .
                        " - " . date("Y-m-d H:i:s", $this->_end) . ")");

    $result = $this->_db_akrr->query($sql);

    if ( $summary )
    {
      $resourceNickname = NULL;
      foreach ( $result as $row )
      {
        if ( $row['resource'] != $resourceNickname )
        {
          $resourceNickname = $row['resource'];
          $results[$resourceNickname] = array();
        }
        $results[$resourceNickname][] = array('reporter'       => $row['reporternickname'],
                                              'first_instance' => $row['first'],
                                              'last_instance'  =>$row['last'],
                                              'num_instances'  =>$row['num']);
      }  // foreach ( $result as $row )
    }  // if ( $summary )
    else
    {
      foreach ( $result as $row ) $results[] = $row['resource'];
    }

    return $results;

  }  // getAvailableResources()

  // -------------------------------------------------------------------------
  // @see iAppKernelExplorer::getAvailableAppKernels()
  // -------------------------------------------------------------------------

  public function getAvailableAppKernels(array $options)
  {

    $reporterList = array();
    $criteriaList = array();

    // --------------------------------------------------------------------------------
    // Process options

    // If we only want the base name remove any processing unit information from
    // the reporter name.  E.g., xdmod.appker.densela.blas.16 =>
    // xdmod.appker.densela.blas

    $baseNameOnly = ( isset($options['base_name_only']) && $options['base_name_only'] );

    // We can select individual app kernels using the filter

    $filter = ( isset($options['name']) && ! empty($options['name'])
                ? $options['name'] : NULL );

    // Additional resources to filter.

    $resourceList = ( isset($options['resources']) &&
                      is_array($options['resources']) &&
                      0 != count($options['resources'])
                      ? $options['resources']
                      : NULL );

    // --------------------------------------------------------------------------------
    // Query

    $sql = "select resource, " . ( $baseNameOnly ? "reporter" : "reporternickname" ) .
      " as appkernel from akrr_xdmod_instanceinfo";

    if ( NULL !== $resourceList )
      $criteriaList[] = "resource in ('" . implode("','", $resourceList) . "')";

    $timeWindowSql = $this->generateSqlTimeWindow();
    if ( NULL !== $timeWindowSql ) $criteriaList[] = $timeWindowSql;

    if ( NULL !== $filter ) $criteriaList[] = "reporternickname like '%{$options['filter']}%'";

    if ( 0 != count($criteriaList) ) $sql .= " where " . implode(" and ", $criteriaList);

    $sql .= " group by " . ( $baseNameOnly ? "reporter" : "reporternickname" );

    $this->_logger->debug(__FUNCTION__ . "() Query database (" . date("Y-m-d H:i:s", $this->_start) .
                        " - " . date("Y-m-d H:i:s", $this->_end) . ")");

    $result = $this->_db_akrr->query($sql);

    // Query the database

    foreach ( $result as $row )
    {
      $reporterList[ $row['resource'] ][] = $row['appkernel'];
    }  // foreach ( $result as $row )

    return $reporterList;

  }  // getAvailableAppKernels()

  // -------------------------------------------------------------------------
  // @see iAppKernelExplorer::getAvalableInstanceIds()
  // -------------------------------------------------------------------------

  public function getAvailableInstanceIds(array $options)
  {
    $instanceList = array();
    $criteriaList = array();

    if ( ! is_array($options) ) throw new Exception("Options not provided");

    // The resoure is required

    if ( ! isset($options['resource']) || empty($options['resource']) )
    {
      throw new Exception("Resource not provided");
    }
    $criteriaList[] = "resource = '{$options['resource']}'";

    if ( isset($options['app_kernel']) && ! empty($options['app_kernel']) )
      $criteriaList[] = "reporternickname like '%{$options['app_kernel']}%'";

    $timeWindowSql = $this->generateSqlTimeWindow();
    if ( NULL !== $timeWindowSql ) $criteriaList[] = $timeWindowSql;

    $sql = "select instance_id from akrr_xdmod_instanceinfo";
    if ( 0 != count($criteriaList) ) $sql .= " where " . implode(" and ", $criteriaList);
    $sql .= " order by collected";

    $this->_logger->debug(__FUNCTION__ . "() Query database (" . date("Y-m-d H:i:s", $this->_start) .
                        " - " . date("Y-m-d H:i:s", $this->_end) . "), options: " .
                        $this->optionsToString($options));
    $this->_logger->debug($sql);

    $result = $this->_db_akrr->query($sql);

    foreach ( $result as $row ) $instanceList[] = $row['instance_id'];

    return $instanceList;

  }  // getAvailableInstanceIds()

  // -------------------------------------------------------------------------
  // @see iAppKernelExplorer::getAvalableInstances()
  // -------------------------------------------------------------------------

  public function getAvailableInstances(array $options,$groupByAKs=false)
  {
    $instanceList = array();
    $criteriaList = array();

    if ( ! is_array($options) ) throw new Exception("Options not provided");

    if ( isset($options['instance_id']) && (!empty($options['instance_id'])) )
    {
        $criteriaList[] = "instance_id = '{$options['instance_id']}'";
    } else {
        if ( ! isset($options['resource']) || empty($options['resource']) )
        {
          throw new Exception("Resource not provided");
        }
        $criteriaList[] = "resource = '{$options['resource']}'";
    }
    if ( isset($options['app_kernel']) && ! empty($options['app_kernel']) )
      $criteriaList[] = "reporternickname like '%{$options['app_kernel']}%'";

    if ( ! isset($options['instance_id']) || empty($options['instance_id']) ){
        $timeWindowSql = $this->generateSqlTimeWindow();
        if ( NULL !== $timeWindowSql ) $criteriaList[] = $timeWindowSql;
    }
    $sql = "select * from akrr_xdmod_instanceinfo";

    if ( 0 != count($criteriaList) ) $sql .= " where " . implode(" and ", $criteriaList);
    $sql .= " order by reporternickname, collected";

    $this->_logger->debug(__FUNCTION__ . "() Query database (" . date("Y-m-d H:i:s", $this->_start) .
                        " - " . date("Y-m-d H:i:s", $this->_end) . "), options: " .
                        $this->optionsToString($options));
    $this->_logger->debug($sql);

    $result = $this->_db_akrr->query($sql);

    if($groupByAKs){
        foreach ( $result as $row )
        {
            $ak_base_name=$row['reporter'];
            $num_units=intval(substr($row['reporternickname'],strlen($ak_base_name)+1));

            if(!array_key_exists($ak_base_name,$instanceList))
                $instanceList[$ak_base_name]=array();
            if(!array_key_exists($num_units,$instanceList[$ak_base_name]))
                $instanceList[$ak_base_name][$num_units]=array();

            $instanceList[$ak_base_name][$num_units][$row['instance_id']] = $this->createInstanceData($row);
        }
    }
    else
    {
        foreach ( $result as $row ) $instanceList[$row['instance_id']] = $this->createInstanceData($row);
    }


    return $instanceList;

  }  // getAvailableInstances()

  // -------------------------------------------------------------------------
  // Query the deployment manager for the data associated with a reported
  // execution instance.
  //
  // @param $instanceInfo An instance information data structure as returned by
  //   getAvailableInstances()
  //
  // @returns A list of the reporters run on the resoure in the specified time
  //   period.
  // -------------------------------------------------------------------------

  public function getInstanceData($instance)
  {
    $instanceData = NULL;

    if ( empty($instance) )
      throw new Exception("Empty instance identifier");
    else if ( ! is_numeric($instance) )
      throw new Exception("Malformed instance identifier");

    $sql = "select * from akrr_xdmod_instanceinfo where instance_id = $instance";
    $result = $this->_db_akrr->query($sql);

    if ( 0 == count($result) ) return FALSE;

    $row = array_shift($result);
    return $this->createInstanceData($row);

  }  // getInstanceData()

  // --------------------------------------------------------------------------------
  // Create an instance of an AppKernelInstanceData object from a database query
  // v_xdmod_instanceinfo result and return it.
  //
  // @param $row An array containing the results of the query
  // --------------------------------------------------------------------------------

  private function createInstanceData(array $row)
  {
    $instance = new AppKernelInstanceData_Arr;
    $instance->data = "<body>" . $row['body'] . "</body>";
    $instance->instance_id = $row['instance_id'];
    $instance->job_id = $row['job_id'];
    $instance->hostname = $row['resource'];

    // In some cases the reporter base name is "xdmod.batch.wrapper" and the
    // nickname is the actual appkernel name.  In this case, replace the base
    // name with the nickname sans the number of processing elements.

    $instance->akName = ( "xdmod.batch.wrapper" == $row['reporter']
                          ? preg_replace('/\.[0-9]+]$/', "", $row['reporter'])
                          : $row['reporter'] );

    $instance->akName = $row['reporter'];
    $instance->akNickname = $row['reporternickname'];
    $instance->execution_hostname = $row['executionhost'];
    $instance->time = $row['collected'];
    $instance->completed = ( 1 == $row['status'] );
    $instance->message = $row['message'];
    $instance->walltime = $row['walltime'];
    $instance->memory = $row['memory'];
    $instance->cputime = $row['cputime'];
    $instance->stderr = $row['stderr'];
    $instance->supremm = NULL;
    //Try to load SUPREMM
    if($this->add_supremm_metrix){
        if(array_key_exists($instance->akName, $this->ak_def_id) && $instance->job_id!==NULL){
            $m_ak_def_id=$this->ak_def_id[$instance->akName];
            if(array_key_exists($m_ak_def_id,$this->ak_supremm_metrics) &&
              ($this->resources[$instance->hostname]['xdmod_resource_id']!==NULL) && ($this->resources[$instance->hostname]['xdmod_cluster_id']!==NULL)){
                $time_start = microtime(TRUE);

                $sql="SELECT * FROM  modw_supremm.job ";
                $sql.=" WHERE  local_job_id ={$instance->job_id}";
                $sql.=" AND  resource_id ={$this->resources[$instance->hostname]['xdmod_resource_id']} ";
                $sql.=" AND  cluster_id ={$this->resources[$instance->hostname]['xdmod_cluster_id']} ";
                $result = $this->_db_datawarehouse->query($sql);
                $time_end = microtime(TRUE);
                $time = $time_end - $time_start;
                //print "SUPREMM query took $time seconds\n";

                if(count($result)===1){
                    $instance->supremm=array();
                    $sm=$result[0];
                    foreach ($this->ak_supremm_metrics[$m_ak_def_id] as $supremm_metrics_id) {
                        $m_supremm_metrics=$this->supremm_metrics[$supremm_metrics_id];
                        try {
                            //check that values is not null
                            foreach($m_supremm_metrics['elements'] as $elm){
                                $elm_val=NULL;
                                eval('$elm_val = '.$elm.';');
                                if($elm_val===NULL)throw new Exception($elm.' is NULL.');;
                            }
                            $val=NULL;
                            eval('$val = '.$m_supremm_metrics['formula'].';');

                            $instance->supremm[$m_supremm_metrics['name']]=array(
                                'name'=>$m_supremm_metrics['label'],
                                'value'=>$val,
                                'unit'=>$m_supremm_metrics['units'],
                            );
                        } catch (Exception $e) {
                            $this->_logger->warning($instance->hostname.'::'.$instance->akName." instance=".$instance->instance_id.' : Can not evaluate SUPREMM expression: '.$e->getMessage());
                        }
                    }
                    if(count($instance->supremm)==0)
                        $instance->supremm=NULL;
                }
                elseif (count($result)>1) {
                    throw new Exception("Non unique job id on resource");
                }
                else{
                    $this->_logger->debug($instance->hostname.'::'.$instance->akName." instance=".$instance->instance_id.' '.$instance->time.' : There is no SUPREMM data in db.');
                }
            }
        }
    }
    return $instance;
  }  // createInstanceData()

  // --------------------------------------------------------------------------------
  // Generate an sql fragment to select app kernels based on the time window specified.
  //
  // @returns An sql fragment for inclusion in a query
  // --------------------------------------------------------------------------------

  private function generateSqlTimeWindow(array $options = array())
  {
    $start=$this->_start;
    $end=$this->_end;
    $timeVar='collected';
    $convertToDateFromUNIXTIME=TRUE;
    if(array_key_exists('convertToDateFromUNIXTIME',$options))
        $convertToDateFromUNIXTIME=$options['convertToDateFromUNIXTIME'];

    //add padding
    if(array_key_exists('startPadding',$options))
        $start-=$options['startPadding'];

    if(array_key_exists('endPadding',$options))
        $end+=$options['endPadding'];

    if(array_key_exists('padding',$options)){
        $start-=$options['padding'];
        $end+=$options['padding'];
    }
    //convert to proper time format
    if ( $convertToDateFromUNIXTIME === TRUE )
    {
        if ( NULL !== $start )
            $start="FROM_UNIXTIME({$start})";
        if ( NULL !== $end )
            $end="FROM_UNIXTIME({$end})";
    }
    $sql = NULL;
    if ( NULL !== $start && NULL !== $end )
      $sql = "{$timeVar} between {$start} and {$end}";
    else if ( NULL !== $start )
      $sql = "{$timeVar} >= {$start}";
    else if ( NULL !== $end )
      $sql = "{$timeVar} <= {$end}";

    return $sql;
  }  // generateSqlTimeWindow()

  // --------------------------------------------------------------------------------
  // Convert an associative array of option key => value pairs into a string for
  // display.
  //
  // @param $options Associative array containing options
  //
  // @returns A string of option info for display
  // --------------------------------------------------------------------------------

  private function optionsToString(array $options)
  {
    $list = array();
    foreach ( $options as $k => $value )
    {
      if ( is_array($value) ) $list[] = $this->optionsToString($value);
      else $list[] = "$k = $value";
    }

    return "(" . implode(", ", $list) . ")";

  }  // optionsToString()

}  // class ArrExplorer
