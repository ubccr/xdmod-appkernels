<?php
	@session_start();

	@require_once dirname(__FILE__).'/../../../configuration/linker.php';
	
	$scale = isset($_GET['scale']) ? $_GET['scale'] : 1; 
	
	$logged_in_user = \xd_security\getLoggedInUser();
	
	$person_id = $logged_in_user->getPersonID();
	
	$data_only = (isset($_GET['data_only']) && $_GET['data_only'] == 'y')? true: false;
	
	$show_title = (isset($_GET['show_title']) && $_GET['show_title'] == 'y')? true: false;
	
	if(isset($_REQUEST['node']) && $_REQUEST['node'] == 'node_app_kernels')
	{
		$query = "select distinct(name) from mod_warehouse.app_kernel order by name";
		$kernels = DataWarehouse::connect()->query($query);
		
		$ret = array();	
		foreach ($kernels as $kernel)
		{
			$ret[] = array('text' => $kernel['name'],//.' ('."app_kernel_".$kernel['name'].')', 
			'id' => "app_kernel_".$kernel['name'], 'kernel_name' => $kernel['name'],  "iconCls" => 'kernel', 'filter' => false);
		}

		echo json_encode($ret);

	}
	else if(isset($_REQUEST['node']) && substr($_REQUEST['node'],0,11) == 'app_kernel_')
	{
		$app_kernel_name =  substr($_REQUEST['node'],11);
		$query = "select distinct resource_id, resource, nickname from mod_warehouse.resources order by nickname";
		$resources = DataWarehouse::connect()->query($query);
		$ret = array();	
		foreach ($resources as $resource)
		{
			$ret[] = array('text' => $resource['resource'],//.' ('.'app_kernelresource_'.$resource['resource_id'].'_'.$app_kernel_name.')', 
							'id' => 'app_kernelresource_'.$resource['resource_id'].'_'.$app_kernel_name, 
							'resource_id' => $resource['resource_id'],
							'kernel_name' => $app_kernel_name, 
							'iconCls' => 'kernel_resource', 
							'filter' => false);
		}

		echo json_encode($ret);
		
	}
	else if(isset($_REQUEST['node']) && substr($_REQUEST['node'],0,19) == 'app_kernelresource_')
	{
		$resource_id_and_kernel_name =  substr($_REQUEST['node'],19);
		
		$regex_resource_id_and_kernel_name = '/(?P<resource_id>[0-9]+)\_(?P<kernel_name>[a-zA-z0-9_-]+)/';
		if(preg_match($regex_resource_id_and_kernel_name,$resource_id_and_kernel_name,$matches) > 0)
		{
			$resource_id = intval($matches['resource_id']);
			$kernel_name = $matches['kernel_name'];
	
			$query = "select distinct m.name, m.metric_id
					  from mod_warehouse.metrics m, mod_warehouse.app_kernel_metrics akm, mod_warehouse.app_kernel ak
						where m.metric_id = akm.metric_id 
						  and ak.kernel_id = akm.kernel_id 
						  and ak.name=:kernel_name 
						  and m.short_name <> 'cores'
						  and exists  (select count(*) as c from mod_warehouse.app_kernel_data where kernel_id = ak.kernel_id and metric_id = m.metric_id and resource_id = :resource_id having c > 0)
						  "; 

			$metrics = DataWarehouse::connect()->query($query, array('kernel_name' => $kernel_name, 'resource_id' => $resource_id));
			$ret = array();	
			foreach ($metrics as $metric)
			{
				$ret[] = array('text' => $metric['name'],//.' ('.'app_kernelmetric_'.$metric['metric_id'].'_'.$resource_id.'_'.$kernel_name.')', 
								'id' => 'app_kernelmetric_'.$metric['metric_id'].'_'.$resource_id.'_'.$kernel_name,
								'metric_id' => $metric['metric_id'],
								'resource_id' => $resource_id,
								'kernel_name' => $kernel_name,
								"iconCls" => 'kernel_metric', 'leaf' => false, 'filter' => true);
			}
	
			echo json_encode($ret);
		}
	}
	else if(isset($_REQUEST['node']) && substr($_REQUEST['node'],0,17) == 'app_kernelmetric_')
	{
		$metric_id_resource_id_and_kernel_name =  substr($_REQUEST['node'],17);
		
		$regex_metric_id_resource_id_and_kernel_name = '/(?P<metric_id>[0-9]+)\_(?P<resource_id>[0-9]+)\_(?P<kernel_name>[a-zA-z0-9_-]+)/';
		if(preg_match($regex_metric_id_resource_id_and_kernel_name,$metric_id_resource_id_and_kernel_name,$matches) > 0)
		{
			$metric_id = intval($matches['metric_id']);
			$resource_id = intval($matches['resource_id']);
			$kernel_name = $matches['kernel_name'];
			
			$reporters = DataWarehouse::connect()->query("select distinct reporter_name, kernel_id, processor_unit from mod_warehouse.app_kernel where name = :kernel_name", array("kernel_name" => $kernel_name));
			
			$ret = array();
			foreach($reporters as $reporter)
			{
				
				$regex_reporter_name = '/\S*\.(?P<cores>[0-9]+)/';
				if(preg_match($regex_reporter_name,$reporter['reporter_name'],$matches) > 0)
				{
					$ret[] = array('text' => $matches['cores'].' '.$reporter['processor_unit'].(intval($matches['cores'])>1?'s':''), 
									'id' => 'app_kernelmetricreporter_'.$metric_id.'_'.$resource_id.'_'.$kernel_name.'_'.$reporter['reporter_name'],
									'metric_id' => $metric_id,
									'resource_id' => $resource_id,
									'kernel_name' => $kernel_name,
									'kernel_id' => $reporter['kernel_id'],
									"iconCls" => 'kernel_metric_cores', 'leaf' => true, 'filter' => true);
				}
			}
			
			echo json_encode($ret);
					
		}
	}
	else  if(isset($_REQUEST['kernel_id']) && isset($_REQUEST['metric_id']) && isset($_REQUEST['resource_id']) && isset($_REQUEST['kernel_name']) && isset($_REQUEST['date_range']))
	{

			$metric_id = $_REQUEST['metric_id'];
			$resource_id = $_REQUEST['resource_id'];
			$kernel_name = $_REQUEST['kernel_name'];
			$kernel_id = $_REQUEST['kernel_id'];
			$date_range_id = $_REQUEST['date_range'];
			
			$config = DataWarehouse::connect()->query("select * from moddb.ChartConfigs where description = 'App Kernel'");
			
			$width = isset($_GET['width']) ? $_GET['width'] : $config[0]['width'];
			$height= isset($_GET['height']) ? $_GET['height'] : $config[0]['height'];				
			
			$c = \DataWarehouse\Visualization::getAppKernelChart($width, $height, $config[0]['left'], $config[0]['top'], $config[0]['right'],$config[0]['bottom'],
												 $kernel_name, $resource_id, $metric_id, $date_range_id, $kernel_id, $scale, $show_title);
	
			if($data_only)
			{
				$format = isset($_GET['format']) ? $_GET['format']: 'csv';
				
				DataExporter::exportHeader($format, str_replace(' ','_',$c['title']).$c['start_date'].'to'.$c['end_date']);
				
				DataExporter::export($format, $c['title'], 'From: '.$c['start_date'].' To: '.$c['end_date'],  $c['chart_data'], $c['chart_png']);
			}
			else
			{
				echo  json_encode(array('totalCount' => 1, 'app_kernel_charts' => array($c)));	
			}
	}
	else if(isset($_REQUEST['metric_id']) && isset($_REQUEST['resource_id']) && isset($_REQUEST['kernel_name']) && isset($_REQUEST['date_range']))
	{

			$metric_id = $_REQUEST['metric_id'];
			$resource_id = $_REQUEST['resource_id'];
			$kernel_name = $_REQUEST['kernel_name'];
			$date_range_id = $_REQUEST['date_range'];
			
			$config = DataWarehouse::connect()->query("select * from moddb.ChartConfigs where description = 'App Kernel'");
			
			$width = isset($_GET['width']) ? $_GET['width'] : $config[0]['width'];
			$height= isset($_GET['height']) ? $_GET['height'] : $config[0]['height'];				
			
			$c = \DataWarehouse\Visualization::getAppKernelChart($width, $height, $config[0]['left'], $config[0]['top'], $config[0]['right'],$config[0]['bottom'],
												 $kernel_name, $resource_id, $metric_id, $date_range_id, -1, $scale, $show_title);
	
			if($data_only)
			{
				$format = isset($_GET['format']) ? $_GET['format']: 'csv';
				
				DataExporter::exportHeader($format, str_replace(' ','_',$c['title']).$c['start_date'].'to'.$c['end_date']);
				
				DataExporter::export($format, $c['title'], 'From: '.$c['start_date'].' To: '.$c['end_date'], $c['chart_data'], $c['chart_png']);
			}
			else
			{
				echo  json_encode(array('totalCount' => 1, 'app_kernel_charts' => array($c)));		
			}
	}
	else if(isset($_REQUEST['resource_id']) && isset($_REQUEST['kernel_name']) && isset($_REQUEST['date_range']))
	{

			$resource_id = $_REQUEST['resource_id'];
			$kernel_name = $_REQUEST['kernel_name'];
			$date_range_id = $_REQUEST['date_range'];

			$config = DataWarehouse::connect()->query("select * from moddb.ChartConfigs where description = 'App Kernel'");
			
			$width = isset($_GET['width']) ? $_GET['width'] : $config[0]['width'];
			$height= isset($_GET['height']) ? $_GET['height'] : $config[0]['height'];			
			
			$query = "select distinct m.metric_id
					  from mod_warehouse.metrics m, mod_warehouse.app_kernel_metrics akm, mod_warehouse.app_kernel ak
						where m.metric_id = akm.metric_id 
						  and ak.kernel_id = akm.kernel_id 
						  and ak.name=:kernel_name 
						  and m.short_name <> 'cores'
						  and exists  (select count(*) as c from mod_warehouse.app_kernel_data where kernel_id = ak.kernel_id and metric_id = m.metric_id and resource_id = :resource_id having c > 0)
						  "; 


			$metrics = DataWarehouse::connect()->query($query, array('kernel_name' => $kernel_name, 'resource_id' => $resource_id));
			$ret = array();	
			foreach ($metrics as $metric)
			{
				$c = \DataWarehouse\Visualization::getAppKernelChart($width, $height, $config[0]['left'], $config[0]['top'], $config[0]['right'],$config[0]['bottom'], 
													 $kernel_name, $resource_id, $metric['metric_id'], $date_range_id, -1, $scale,$show_title  );

				$ret[] = $c;
			}	
			
	
			echo  json_encode(array('totalCount' => 1, 'app_kernel_charts' => $ret));
	}
	else if(isset($_REQUEST['kernel_name']) && isset($_REQUEST['date_range']))
	{

			$kernel_name = $_REQUEST['kernel_name'];
			$date_range_id = $_REQUEST['date_range'];
			
			$config = DataWarehouse::connect()->query("select * from moddb.ChartConfigs where description = 'App Kernel'");
			
			$width = isset($_GET['width']) ? $_GET['width'] : $config[0]['width'];
			$height= isset($_GET['height']) ? $_GET['height'] : $config[0]['height'];		
			
			$ret = array();	
			
			$resources = DataWarehouse::connect()->query("select resource_id from mod_warehouse.resources order by nickname");
			
			foreach($resources as $resource)
			{
			
				$query = "select distinct m.metric_id
						  from mod_warehouse.metrics m, mod_warehouse.app_kernel_metrics akm, mod_warehouse.app_kernel ak
							where m.metric_id = akm.metric_id 
							  and ak.kernel_id = akm.kernel_id 
							  and ak.name=:kernel_name  
						      and m.short_name <> 'cores'
							  and exists  (select count(*) as c from mod_warehouse.app_kernel_data where kernel_id = ak.kernel_id and metric_id = m.metric_id and resource_id = :resource_id having c > 0)
							  "; 
	
				$metrics = DataWarehouse::connect()->query($query, array('kernel_name' => $kernel_name, 'resource_id' => $resource['resource_id']));
				
				foreach ($metrics as $metric)
				{
					$c = \DataWarehouse\Visualization::getAppKernelChart($width, $height, $config[0]['left'], $config[0]['top'], $config[0]['right'],$config[0]['bottom'],
														 $kernel_name, $resource['resource_id'], $metric['metric_id'], $date_range_id, -1, $scale,$show_title );
					$ret[] = $c; 
				}	
			}
	
			echo  json_encode(array('totalCount' => 1, 'app_kernel_charts' => $ret));
	}
?>