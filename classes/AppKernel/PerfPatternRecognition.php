<?php

namespace AppKernel;

use CCR\DB;
use AppKernel\ProblemDetector\AppKernelLevel;
use AppKernel\ProblemDetector\ResourceLevel;

/**
 * Class for recognition error patterns
 *
 */
class PerfPatternRecognition
{
    private $perfMap;
    private $pmap;
    public $recpatterns;

    private $patterns=array(
        /*Resource wide*/
        array(
            'name'=>'Whole resource is down',
            'where'=>'resource',
            'pattern'=>'#F *$#',
            'pattern_weak'=>'# *$#',
            'strong_portion'=>0.75,
            'message'=>'All app kernels on resource Failed to run',
            'code'=>'red'
        ),
        array(
            'name'=>'Whole resource is underperforming',
            'where'=>'resource',
            'pattern'=>'#U *$#',
            'pattern_weak'=>'# *$#',
            'strong_portion'=>0.75,
            'message'=>'All app kernels on resource were underperforming',
            'code'=>'yellow'
        ),
        /*Resource->app kernel wide*/
        array(
            'name'=>'Failed to run for all node counts',
            'where'=>'appKer',
            'pattern'=>'#F *$#',
            'pattern_weak'=>'# *$#',
            'strong_portion'=>0.75,
            'message'=>'Failed to run for all node counts',
            'code'=>'red'
        ),
        array(
            'name'=>'Underperforming on all node counts',
            'where'=>'appKer',
            'pattern'=>'#U *$#',
            'pattern_weak'=>'# *$#',
            'strong_portion'=>0.75,
            'message'=>'Underperforming on all node counts',
            'code'=>'yellow'
        ),
        array(
            'name'=>'Overperforming on all node counts',
            'where'=>'appKer',
            'pattern'=>'#O *$#',
            'pattern_weak'=>'# *$#',
            'strong_portion'=>0.75,
            'message'=>'Overperforming on all node counts',
            'code'=>'orange'
        ),
        /*Resource->app kernel->problem size*/
        array(
            'name'=>'app kernel is not working',
            'where'=>'problemSize',
            'pattern'=>'#F *F *F *$#',
            'pattern_weak'=>null,
            'strong_portion'=>null,
            'message'=>'Failed to run at least 3 times consecutively',
            'code'=>'red'
        ),
        array(
            'name'=>'app kernel is not Underperforming',
            'where'=>'problemSize',
            'pattern'=>'#U *U *U *$#',
            'pattern_weak'=>null,
            'strong_portion'=>null,
            'message'=>'Underperforming at least 3 times consecutively',
            'code'=>'yellow'
        ),
        array(
            'name'=>'Overperforming at least 3 times consecutively',
            'where'=>'problemSize',
            'pattern'=>'#O *O *O *$#',
            'pattern_weak'=>null,
            'strong_portion'=>null,
            'message'=>'Overperforming at least 3 times consecutively',
            'code'=>'orange'
        )
    );
    private $code_colors=array(
        'red' => 'style="background-color:#FFB0C4;"',
        'yellow' => 'style="background-color:#FFFF99;"',
        'orange' => 'style="background-color:#FE9A2E;"'
    );
    private $code_colors_by_error_code=array();
    public $ak_shortnames;
    public $ak_fullnames;

    private $runStatsProblemSize=NULL;
    private $runStatsAppKernel=NULL;
    private $runStatsResource=NULL;

    public function __construct($options)
    {
        //get AK short names
        $pdo = DB::factory('appkernel');
        $ak_shortnames=array();
        $ak_fullnames=array();
        $sql = "SELECT ak_base_name,name
                FROM app_kernel_def;";
        $sqlres_tasdb=$pdo->query($sql);
        foreach($sqlres_tasdb as $row)
        {
            $ak_shortnames[$row['ak_base_name']]=$row['name'];
            $ak_fullnames[$row['name']]=$row['ak_base_name'];
        }
        if(!in_array('xdmod.bundle',$ak_shortnames)){
            $ak_shortnames['xdmod.bundle']='Bundle';
            $ak_fullnames['Bundle']='xdmod.bundle';
        }
        $this->ak_shortnames=$ak_shortnames;
        $this->ak_fullnames=$ak_fullnames;
        $this->ak_shortnames['*']='*';
        $this->ak_fullnames['*']='*';

        $errorTypes=array('Failure'=>'red','UnderPerforming'=>'yellow','OverPerforming'=>'orange');
        $errorSubtypes=array('total','nodewisePartial','percentage','nodewisePercentagePartial');
        foreach ($errorTypes as $errorType => $color) {
            foreach ($errorSubtypes as $errorSubtype){
                $this->code_colors_by_error_code[$errorSubtype.$errorType]=$color;
            }
        }

        //get list of resources with scheduled task
        $this->active_resources=array();
        $akrr_section = isset($config['config_akrr'])
	        ? $config['config_akrr']
	        : 'akrr-db';

        $db_akrr = DB::factory($akrr_section);
		$resourcesEnabled=array();
		$result = $db_akrr->query('select * from resources');
		foreach ($result as $r) {
			$resourcesEnabled[$r['name']]=$r['enabled'];
		}
		$result = $db_akrr->query('SELECT COUNT( * ) AS `Rows` , `resource` FROM `scheduled_tasks` GROUP BY `resource` ORDER BY `resource`');
		foreach ($result as $r) {
			if(! array_key_exists($r['resource'],$resourcesEnabled))
				$resourcesEnabled[$r['resource']]=1;
			if($resourcesEnabled[$r['resource']])
				$this->active_resources[]=$r['resource'];
		}

        $this->perfMap=$options['perfMap'];
        $this->build_pattern_map();
        $this->recpatterns=$this->recognize_patterns();
    }
    public function make_report()
    {
        $message='';
        $message.='<h3>Table 2. Summary of Underperforming (Out of Control) and Failed Runs</h3>';

        //$recpatterns=$this->recognize_patterns();
        //$active_tasks=$this->get_active_tasks();

        $message.='<table border="1" cellspacing="0" style="">';
        //table header
        $message.='<tr>';
        /*foreach (array('#','Resource','App. Kernel','Nodes','Message','Link to Details','Link to Performance Plot') as $key => $value) {
             $message.='<td>'.$value.'</td>';
        }*/
        foreach (array('#','App. Kernel','Nodes','Message') as $key => $value) {
             $message.='<td>'.$value.'</td>';
        }
        $message.='</tr>';
        $id=1;

        for($i=0;$i<count($this->recpatterns['resource']);$i++){
            $resource=$this->recpatterns['resource'][$i];
            $problems=$this->recpatterns['problems'][$i];

            $message.='<tr>';
            $message.="<td colspan=4>Resource: <b>$resource</b></td>";
            $message.='</tr>';
            foreach($problems as $rec){
                $message.='<tr>';
                $tag='perfMap_'.$resource.'_'.$rec['appKernel'].'_'.$rec['problemSizes'][0];
                $row=array(
                  $id,
                  $rec['appKernelShort'],
                  implode(',',$rec['problemSizes']),
                  $rec['msg'].". ".'<a href="#'.$tag.'">Details</a>'.". ".'Performance Plot.'
                  );
                foreach ($row as $value) {
                     $message.='<td '.$this->code_colors[$this->code_colors_by_error_code[$rec['errorCode']]].'>'.$value.'</td>';
                }
                $message.='</tr>';
                $id++;
            }
        }
        $message.='</table>';
        return $message;
    }
    public function get_num_of_code_red()
    {
        $count=0;
        for($i=0;$i<count($this->recpatterns['resource']);$i++){
            $resource=$this->recpatterns['resource'][$i];
            $problems=$this->recpatterns['problems'][$i];
            foreach($problems as $rec){
                if($this->code_colors_by_error_code[$rec['errorCode']]=='red')
                    $count++;
            }
        }
        return $count;
    }
    public function get_num_of_code_yellow()
    {
        $count=0;
        for($i=0;$i<count($this->recpatterns['resource']);$i++){
            $resource=$this->recpatterns['resource'][$i];
            $problems=$this->recpatterns['problems'][$i];
            foreach($problems as $rec){
                if($this->code_colors_by_error_code[$rec['errorCode']]=='yellow')
                    $count++;
            }
        }
        return $count;
    }
    private function recognize_patterns()
    {
        $problems=array(
            'resource'=>array(),
            'problemsScore'=>array(),
            'problems'=>array());

        foreach ($this->runStatsResource as $resource=>$runStatsResource) {

        	if(!in_array($resource, $this->active_resources))continue;


            $problems[$resource]=array();

            //detect appkernel level problems
            $problemsAppKernelLevel=array();
            foreach ($this->runStatsAppKernel[$resource] as $appKer=>$runStatsAppKernel) {
                $pd=new AppKernelLevel($runStatsAppKernel,
                    $this->runStatsProblemSize[$resource][$appKer],
                    $this->ak_shortnames[$appKer]);
                $problemsDetected=$pd->detect();

                if($problemsDetected>0){
                    $problemsAppKernelLevel[$appKer]=$pd;
                }
            }
            //detect resource level problems
            $pd=new ResourceLevel($runStatsResource,$this->runStatsAppKernel[$resource]);
            $problemsDetected=$pd->detect($problemsAppKernelLevel);
            if(count($problemsDetected)>0){
                $problems['resource'][]=$resource;
                $problems['problemsScore'][]=$pd->problemsScore;
                $problems['problems'][]=$problemsDetected;
            }
        }
        array_multisort($problems['problemsScore'],SORT_DESC,SORT_NUMERIC,$problems['resource'],$problems['problems']);

        return $problems;
    }
    private function build_pattern_map()
    {
        $this->pmap=array();
        $runStats=array();
        $runStatsAppKernel=array();
        $runStatsResource=array();
        foreach ($this->perfMap['runsStatus'] as $resource => $v0) {
            $this->pmap[$resource]=array();
            $runStats[$resource]=array();
            $runStatsAppKernel[$resource]=array();
            foreach ($v0 as $appKer => $v1) {
                $this->pmap[$resource][$appKer]=array();
                $runStats[$resource][$appKer]=array();
                foreach ($v1 as $problemSize => $v2) {
                    $s='';
                    foreach ($v2 as $day => $tasks) {
                        $s.=$tasks->summary;

                    }
                    $this->pmap[$resource][$appKer][$problemSize]=$s;
                    $runStats[$resource][$appKer][$problemSize]=new RunsStats($resource,$appKer,$problemSize,$s);
                }

                $runStatsAppKernel[$resource][$appKer]=RunsStats::agregateOnAppkernelLevel($runStats[$resource][$appKer]);
            }
            $runStatsResource[$resource]=RunsStats::agregateOnResourceLevel($runStatsAppKernel[$resource]);
        }
        $this->runStatsProblemSize=$runStats;
        $this->runStatsAppKernel=$runStatsAppKernel;
        $this->runStatsResource=$runStatsResource;
    }
    /**
     * Boil down to a status letter a set of $tasks for given $resource,$appKer and $problemSize
     * letters:
     * <space> - no tasks
     * F - failure
     * U - underperforming
     * N - normal
     * R - have run , but no control info
     */
    /*private function get_tasks_summary_status($resource,$appKer,$problemSize,$tasks)
    {
        $inControlRuns=0;
        $outOfControlRuns=0;
        $noControlInfoRuns=0;
        $failedRuns=0;
        $totalRuns=count($tasks);

        $inControlRunsID=0;
        $outOfControlRunsID=0;
        $noControlInfoRunsID=0;
        $failedRunsID=0;
        foreach ($tasks as $rec)
        {
            if($rec['status']==1)
            {
                if($rec['control']!=='null')
                {
                    if($rec['control']>=$this->controlThreshold)
                    {
                        $inControlRuns++;
                        $inControlRunsID=$rec['id'];
                    }
                    else
                    {
                        $outOfControlRuns++;
                        $outOfControlRunsID=$rec['id'];
                    }
                }
                else
                {
                    $noControlInfoRuns++;
                    $noControlInfoRunsID=$rec['id'];
                }
            }
            else
            {
                $failedRuns++;
                $failedRunsID=$rec['id'];
            }
        }
        if($totalRuns>0)
        {
            if($noControlInfoRuns==0)
            {
                if($inControlRuns>0)
                    return 'N';
                elseif($outOfControlRuns>0)
                    return 'U';
                else
                    return 'F';
            }
            else{
                return 'R';
            }
        }
        else {
            return ' ';
        }
    }*/
}
