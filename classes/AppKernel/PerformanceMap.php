<?php

namespace AppKernel;

use CCR\DB;
use xd_utilities;
use DateTime;
use DateInterval;

class PerformanceMap
{
    public $start_date; /** @param \DateTime */
    public $end_date; /** @param \DateTime */
    private $end_date_exclusive; /** @param \DateTime */


    public $controlThreshold;/** @param float */
    public $controlThresholdCoeff;/** @param float */
    public $resource; /** @param string[]|null*/
    public $appKer; /** @param string[]|null*/
    public $problemSize; /** @param string[]|null*/

    /**
     * The organization id of the user who is requesting data from this object.
     *
     * @var int
     */
    public $userOrganization;

    public $perfMap;

    public $ak_shortnames;
    public $ak_fullnames;
    public $ak_def_ids;
    public $resource_ids;
    public $walltime_metric_id;


    private static $default_options=array(
        'resource'=>null,
        'appKer'=>null,
        'problemSize'=>null,
        'controlThreshold'=>-0.5,
        'controlThresholdCoeff'=>1.0
    );

    /**
     * @param string address for app kernel instance viewer.
     * eventually: xd_utilities\getConfigurationUrlBase('general', 'site_address').'internal_dashboard/index.php?op=ak_instance&instance_id=';
     * */
    private $ak_instance_view_web_address;
    private $ak_plot_viewer_web_address;
    private $ak_control_region_web_address;

    public function __construct($options)
    {
        //set default values
        $siteAddress = xd_utilities\getConfigurationUrlBase('general', 'site_address');

        $this->ak_instance_view_web_address = $siteAddress.'#main_tab_panel:app_kernels:app_kernel_viewer?ak_instance=';
        $this->ak_plot_viewer_web_address = $siteAddress.'#main_tab_panel:app_kernels:app_kernel_viewer?';
        $this->ak_control_region_web_address = $siteAddress.'internal_dashboard/index.php#appkernels:ak_control_regions?';

        if(!array_key_exists('end_date', $options)) {
            $options['end_date'] = new DateTime(date('Y-m-d'));
        }
        if(!array_key_exists('start_date', $options)) {
            $options['start_date']=clone $options['end_date'];
            $options['start_date']->sub(new DateInterval('P7D'));
        }

        $options=array_merge(self::$default_options, $options);

        //initiate interlal variables
        $this->start_date=$options['start_date'];
        $this->end_date=$options['end_date'];
        $this->end_date_exclusive=clone $this->end_date;
        $this->end_date_exclusive->add(new DateInterval('P1D'));

        $this->resource=$options['resource'];
        $this->appKer=$options['appKer'];
        $this->problemSize=$options['problemSize'];

        $this->controlThreshold=$options['controlThreshold'];
        $this->controlThresholdCoeff=$options['controlThresholdCoeff'];

        //get AK short names
        $pdo = DB::factory('appkernel');
        $ak_shortnames=array();
        $ak_fullnames=array();
        $ak_def_ids=array();
        $sql = "SELECT ak_def_id,ak_base_name,name
                FROM app_kernel_def;";
        $sqlres_tasdb=$pdo->query($sql);
        foreach($sqlres_tasdb as $row)
        {
            $ak_shortnames[$row['ak_base_name']]=$row['name'];
            $ak_fullnames[$row['name']]=$row['ak_base_name'];
            $ak_def_ids[$row['ak_base_name']]=$row['ak_def_id'];
        }
        if(!in_array('xdmod.bundle', $ak_shortnames)) {
            $ak_shortnames['xdmod.bundle']='Bundle';
            $ak_fullnames['Bundle']='xdmod.bundle';
        }
        $this->ak_shortnames=$ak_shortnames;
        $this->ak_fullnames=$ak_fullnames;
        $this->ak_def_ids=$ak_def_ids;

        //get resource_ids
        $sql = 'SELECT akr.resource_id, akr.resource, akr.nickname FROM mod_appkernel.resource akr';
        $params = array();

        if(isset($this->resource)) {
            if (array_key_exists('data', $this->resource)) {
                // limit resources by xdmod_resource_id
                $quotedResourceIds = array_reduce(
                    $this->resource['data'],
                    function ($carry, $item) use ($pdo) {
                        $carry[] = $pdo->quote($item['id']);
                        return $carry;
                    },
                    array()
                );
                $sql = "SELECT akr.resource_id, akr.resource, akr.nickname FROM mod_appkernel.resource akr " .
                    "WHERE akr.xdmod_resource_id IN (" . implode(', ', $quotedResourceIds) . ')';
            } elseif (!empty($this->resource)) {
                // limit resources by AKRR resource name
                $sql = "SELECT akr.resource_id, akr.resource, akr.nickname FROM mod_appkernel.resource akr " .
                    "WHERE akr.nickname IN ('" . implode("', '", $this->resource) . "')";
            }
        }

        $sqlres_tasdb=$pdo->query($sql, $params);

        $resource_ids=array();
        foreach($sqlres_tasdb as $row)
        {
            $resource_ids[$row['nickname']]=$row['resource_id'];
        }
        $this->resource_ids=$resource_ids;

        //get $walltime_metric_id
        $sql = "SELECT metric_id,name
                FROM metric WHERE name = 'Wall Clock Time';";
        $sqlres_tasdb=$pdo->query($sql);
        if(count($sqlres_tasdb)>0){
            $this->walltime_metric_id=$sqlres_tasdb[0]['metric_id'];
        }else{
            $this->walltime_metric_id=null;
        }

        $this->perfMap=$this->getMap();
    }
    /**
     * Generate html-report with performance map table
     *
     * @param \DateTime      $start_date    starting day of report
     * @param \DateTime      $end_date      inclusive last day of report
     * @param array|null     $perfMap       if $perfMap is set it uses it for table ($start_date and $end_date can be set to null)
     * otherwise it create a new using $start_date and $end_date
     *
     * @return html with performance map table
     */
    public function makeReport($internal_dashboard_user = false)
    {
        //PerformanceMap
        $web_address = $this->ak_instance_view_web_address;
        $ak_plot_viewer_web_address=$this->ak_plot_viewer_web_address;
        $ak_control_region_web_address=$this->ak_control_region_web_address;

        $datesStr="start={$this->start_date->format('Y-m-d')}&end={$this->end_date->format('Y-m-d')}";

        $runsStatus=$this->perfMap['runsStatus'];
        $rec_dates=$this->perfMap['rec_dates'];

        //print table
        $tdStyle_Resource='style=""';
        $tdStyle_ProblemSize='style=""';
        $tdStyle_Day='style="" align="center"';

        $tdStyle=array(
            ' '=>'style="background-color:white;"',
            'F'=>'style="background-color:#FFB0C4;"',
            'U'=>'style="background-color:#FFB336;"',
            'O'=>'style="background-color:#81BEF7;"',
            'C'=>'style="background-color:#FFF8DC;"',
            'N'=>'style="background-color:#B0FFC5;"',
            'R'=>'style="background-color:#DCDCDC;"'
        );

        $message='';
        $message.='<h3>Table 3. Performance Heat Map of All App Kernels on Each System</h3>' . "\n";

        $message.='<b>KEY</b>: Each day is summarized in a table cell as pair of a symbol and a number. The symbol represents the status of last application kernel'
                . ' execution on that day and the number shows the total number of runs. Each cell is colored according to the status of last application kernel run.'
                . ' The description of the codes are: <br/><br/>' . "\n"
                . '<table border="1" cellspacing="0" style="">' . "\n"
                . '<tr>'
                . '<td>Code</td>'
                . '<td>Description</td>'
                . '</tr>' . "\n";


        foreach(array('N','U','O','F','C','R', ' ') as $c){
            $message.="<tr>";
            $message.="<td {$tdStyle[$c]}>{$c}</td>\n";
            $message.="<td>".TaskState::$summaryCodes[$c]."</td>";
            $message.="</tr>\n";
        }

        $message.="</table>\n";
        $message.='The status code is linked to full report of the last run.<br/><br/>';


        $totalColumns=1+count($rec_dates)+1;

        //body
        foreach ($runsStatus as $resource => $val0)
        {
            //table header
            $message.='<table border="1" cellspacing="0" style="">';
            $message.='<tr>';
            $message.='<td colspan=1>&nbsp;</td>';
            $span=0;
            $prev_monthyear='//';
            for($i=0; $i<count($rec_dates); $i++) {
                $rd=explode('/', $rec_dates[$i]);
                $monthyear=date("F", mktime(0, 0, 0, $rd[1], 10)).', '.$rd[0];
                $span++;
                if(($i==count($rec_dates)-1)||($i>0 && $monthyear!=$prev_monthyear)) {
                    if($i==count($rec_dates)-1) {
                        $span++;
                    }
                    if($i==0) {
                        $prev_monthyear = $rd[0] . '/' . $rd[1];
                    }
                    $message.='<td colspan='.($span-1).' '.$tdStyle_Day.'>'.$prev_monthyear.'</td>';
                    $span=1;
                }
                $prev_monthyear=$monthyear;
            }
            $message.='<td colspan=1>&nbsp;</td>';
            $message.='</tr>';

            $message.='<tr>';
            $message.='<td '.$tdStyle_ProblemSize.'>Nodes</td>';

            foreach ($rec_dates as $rec_date)
            {
                $rd=explode('/', $rec_date);
                $message.='<td '.$tdStyle_Day.'>&nbsp;'.$rd[2].'&nbsp;</td>';
            }
            $message.='<td colspan=1>Plot</td>';
            $message.='</tr>';
            foreach ($val0 as $appKer => $val1)
            {
                $message.='<tr>';
                $message.='<td colspan='.$totalColumns.' '.$tdStyle_Resource.'>Resource: <b>'.$resource.'</b>    Application Kernel: <b>'.$this->ak_shortnames[$appKer].'</b>';
                if(array_key_exists($appKer, $this->ak_def_ids) && array_key_exists($resource, $this->resource_ids) && $this->walltime_metric_id!==null) {
                    $kernel_id="{$this->resource_ids[$resource]}_{$this->ak_def_ids[$appKer]}";
                    if($internal_dashboard_user){
                        $message.=", <a href=\"{$ak_control_region_web_address}kernel={$kernel_id}&{$datesStr}\">control region panel</a></td>";
                    }

                }
                $message.="</td>";
                $message.='</tr>';
                foreach ($val1 as $problemSize => $val2)
                {
                    $message.='<tr>';
                    $tag='perfMap_'.$resource.'_'.$appKer.'_'.$problemSize;
                    $message.='<td '.$tdStyle_ProblemSize.'><a id="'.$tag.'">'.$problemSize.'</a></td>';
                    foreach ($val2 as $rec_date => $taskStateGroup)
                    {
                        $totalRuns=count($taskStateGroup->tasks);

                        if($totalRuns>0)
                        {
                            $last_task=end($taskStateGroup->tasks);
                            $message.="<td {$tdStyle[$last_task->summary]}>";
                            $message.="<a href=\"{$web_address}{$last_task->id}\">";
                            $message.="{$last_task->summary}";
                            $message.="</a>";
                            $message.="/{$totalRuns}";
                            $message.="</td>";
                        }
                        else
                        {
                            $message.="<td {$tdStyle[' ']}>&nbsp;</td>";
                        }

                    }
                    if(array_key_exists($appKer, $this->ak_def_ids) && array_key_exists($resource, $this->resource_ids) && $this->walltime_metric_id!==null) {
                        $kernel_id="{$this->ak_def_ids[$appKer]}_{$this->resource_ids[$resource]}_{$this->walltime_metric_id}_$problemSize";
                        $message.="<td><a href=\"{$ak_plot_viewer_web_address}kernel={$kernel_id}&{$datesStr}\">Plot</a></td>";
                    }
                    else {
                        $message.="<td>&nbsp;</td>";
                    }
                    $message.='</tr>';
                }
            }
            $message.='</table><br/>';
        }
        return $message;
    }
    public function getSummaryForDays($days)
    {
        $runsStatus=$this->perfMap['runsStatus'];

        //table by resources
        $messageTable='';
        $messageTable.='<h3>Table 1. Summary Table of App Kernel Results for Each Resource</h3><br/>';
        $messageTable.='<table border="1" cellspacing="0" style="">';

        //table header
        $messageTable.='<tr>';
        $messageTable.='<td>Resource</td>';
        $messageTable.='<td>In Control Runs</td>';
        $messageTable.='<td>Out Of Control Runs</td>';
        $messageTable.='<td>No Control Information Runs</td>';
        $messageTable.='<td>Failed Runs</td>';
        $messageTable.='<td>Total Runs</td>';
        $messageTable.='</tr>';

        $tdStyle=Report::$tdStyle;
        foreach ($tdStyle as $k => $v)
        {
            $tdStyle[$k]=substr($v, 0, -1);
            $tdStyle[$k].='"';
            $tdStyle[$k].='align="right"';
        }
        foreach ($runsStatus as $resource => $val0)
        {
            $inControlRuns=0;
            $outOfControlRuns=0;
            $noControlInfoRuns=0;
            $failedRuns=0;
            $totalRuns=0;
            foreach ($val0 as $appKer => $val1)
            {
                foreach ($val1 as $problemSize => $taskStateGroups)
                {
                    foreach ($days as $rec_date)
                    {
                        $totalRuns2=count($taskStateGroups[$rec_date]->tasks);
                        $totalRuns+=$totalRuns2;
                        if($totalRuns2>0)
                        {
                            $inControlRuns+=count($taskStateGroups[$rec_date]->inControlRuns);
                            $outOfControlRuns+=count($taskStateGroups[$rec_date]->underPerformingRuns);
                            $noControlInfoRuns+=count($taskStateGroups[$rec_date]->noControlInfoRuns);
                            $failedRuns+=count($taskStateGroups[$rec_date]->failedRuns);
                        }
                    }
                }
            }
            $messageTable.='<tr>';
            if($totalRuns>0)
            {
                $messageTable.='<td>'.$resource.'</td>';
                if($inControlRuns>0) {
                    $messageTable.='<td '.$tdStyle['N'].'>';
                } else {
                    $messageTable.='<td '.$tdStyle[' '].'>';
                }
                $messageTable.=$inControlRuns.sprintf(' (%\'@5.1f%%)', 100.0*$inControlRuns/$totalRuns).'</td>';

                if($outOfControlRuns>0) {
                    $messageTable.='<td '.$tdStyle['U'].'>';
                } else {
                    $messageTable.='<td '.$tdStyle[' '].'>';
                }
                $messageTable.=$outOfControlRuns.sprintf(' (%\'@5.1f%%)', 100.0*$outOfControlRuns/$totalRuns).'</td>';

                if($noControlInfoRuns>0) {
                    $messageTable.='<td '.$tdStyle['R'].'>';
                } else {
                    $messageTable.='<td '.$tdStyle[' '].'>';
                }
                $messageTable.=$noControlInfoRuns.sprintf(' (%\'@5.1f%%)', 100.0*$noControlInfoRuns/$totalRuns).'</td>';

                if($failedRuns>0) {
                    $messageTable.='<td '.$tdStyle['F'].'>';
                } else {
                    $messageTable.='<td '.$tdStyle[' '].'>';
                }
                $messageTable.=$failedRuns.sprintf(' (%\'@5.1f%%)', 100.0*$failedRuns/$totalRuns).'</td>';

                if($totalRuns>0) {
                    $messageTable.='<td '.$tdStyle[' '].'>';
                } else {
                    $messageTable.='<td '.$tdStyle[' '].'>';
                }
                $messageTable.=$totalRuns.'</td>';
            }
            else
            {
                $messageTable.='<td align="right">'.$resource.'</td>';
                $messageTable.='<td align="right">'.$inControlRuns.'</td>';
                $messageTable.='<td align="right">'.$outOfControlRuns.'</td>';
                $messageTable.='<td align="right">'.$noControlInfoRuns.'</td>';
                $messageTable.='<td align="right">'.$failedRuns.'</td>';
                $messageTable.='<td align="right">'.$totalRuns.'</td>';
            }
            $messageTable.='</tr>';
        }

        //overall
        $inControlRuns=0;
        $outOfControlRuns=0;
        $noControlInfoRuns=0;
        $failedRuns=0;
        $totalRuns=0;

        foreach ($runsStatus as $resource => $val0) {
            foreach ($val0 as $appKer => $val1) {
                foreach ($val1 as $problemSize => $taskStateGroups) {
                    foreach ($days as $rec_date) {
                        $totalRuns2 = count($taskStateGroups[$rec_date]->tasks);
                        $totalRuns += $totalRuns2;
                        if ($totalRuns2 > 0) {
                            $inControlRuns += count($taskStateGroups[$rec_date]->inControlRuns);
                            $outOfControlRuns += count($taskStateGroups[$rec_date]->underPerformingRuns);
                            $noControlInfoRuns += count($taskStateGroups[$rec_date]->noControlInfoRuns);
                            $failedRuns += count($taskStateGroups[$rec_date]->failedRuns);
                        }
                    }
                }
            }
        }
        $messageTable.='<tr>';
        if($totalRuns>0)
        {
            $messageTable.='<td align="right">Total</td>';
            if($inControlRuns>0) {
                $messageTable.='<td '.$tdStyle['N'].'>';
            } else {
                $messageTable.='<td '.$tdStyle[' '].'>';
            }
            $messageTable.=$inControlRuns.sprintf(' (%\'@5.1f%%)', 100.0*$inControlRuns/$totalRuns).'</td>';

            if($outOfControlRuns>0) {
                $messageTable.='<td '.$tdStyle['U'].'>';
            } else {
                $messageTable.='<td '.$tdStyle[' '].'>';
            }
            $messageTable.=$outOfControlRuns.sprintf(' (%\'@5.1f%%)', 100.0*$outOfControlRuns/$totalRuns).'</td>';

            if($noControlInfoRuns>0) {
                $messageTable.='<td '.$tdStyle['R'].'>';
            } else {
                $messageTable.='<td '.$tdStyle[' '].'>';
            }
            $messageTable.=$noControlInfoRuns.sprintf(' (%\'@5.1f%%)', 100.0*$noControlInfoRuns/$totalRuns).'</td>';

            if($failedRuns>0) {
                $messageTable.='<td '.$tdStyle['F'].'>';
            } else {
                $messageTable.='<td '.$tdStyle[' '].'>';
            }
            $messageTable.=$failedRuns.sprintf(' (%\'@5.1f%%)', 100.0*$failedRuns/$totalRuns).'</td>';

            if($totalRuns>0) {
                $messageTable.='<td '.$tdStyle[' '].'>';
            } else {
                $messageTable.='<td '.$tdStyle[' '].'>';
            }
            $messageTable.=$totalRuns.'</td>';
        }
        else
        {
            $messageTable.='<td align="right">Total</td>';
            $messageTable.='<td align="right">'.$inControlRuns.'</td>';
            $messageTable.='<td align="right">'.$outOfControlRuns.'</td>';
            $messageTable.='<td align="right">'.$noControlInfoRuns.'</td>';
            $messageTable.='<td align="right">'.$failedRuns.'</td>';
            $messageTable.='<td align="right">'.$totalRuns.'</td>';
        }
        $messageTable.='</tr>';
        $messageTable.='</table>';
        $messageTable=str_replace('@', '&nbsp;', $messageTable);


        $messageHeader='';
        if(count($days)==1) {
            $messageHeader .= 'Summary for app kernels executed on ' . $days[0] . '</br>';
        } else {
            $messageHeader .= 'Summary for app kernels executed from ' . $days[0] . ' to ' . end($days) . '</br>';
        }

        $message='';
        $message.='Total number of runs: <b>'. $totalRuns.'</b><br/>';
        $message.='Number of failed runs: <b>'. $failedRuns.'</b><br/>';
        $message.='Number of runs without control information: <b>'. $noControlInfoRuns.'</b><br/>';
        $message.='Number of out of control runs: <b>'. $outOfControlRuns.'</b><br/>';
        $message.='Number of runs within threshold: <b>'. $inControlRuns.'</b><br/>';

        $result=array(
            'message'=>$message,
            'messageTable'=>$messageTable,
            'messageHeader'=>$messageHeader,
            'inControlRuns'=>$inControlRuns,
            'outOfControlRuns'=>$outOfControlRuns,
            'noControlInfoRuns'=>$noControlInfoRuns,
            'failedRuns'=>$failedRuns,
            'totalRuns'=>$totalRuns
        );
        return $result;
    }
    private function getMap()
    {
        $pdo = DB::factory('appkernel');
        $arr_db = DB::factory('akrr-db');
        //set dates
        $rec_dates = array();

        $start_date = clone $this->start_date;
        $end_date_exclusive = clone $this->end_date_exclusive;

        $run_date = clone $this->start_date;
        $day_interval = new DateInterval('P1D');

        while ($run_date < $end_date_exclusive) {
            $rec_dates[] = $run_date->format('Y/m/d');
            $run_date->add($day_interval);
        }
        //prep arrays for table
        $runsStatus = array();

        //add one more day for ranges
        $rec_dates[] = $run_date->format('Y/m/d');

        //get appKer instances
        $sql = "SELECT ak_id,num_units,ak_def_id,name FROM app_kernel ORDER BY ak_id ASC;";
        $sqlres_tasdb = $pdo->query($sql);

        $ak_ids = array();
        foreach ($sqlres_tasdb as $row) {
            $ak_ids[$row['name']][$row['num_units']] = $row['ak_id'];
        }

        // Retrieve the control state
        $sql = <<<SQL
        SELECT aki.ak_id,
               aki.collected,
               aki.resource_id,
               aki.instance_id,
               aki.status,
               aki.controlStatus
        FROM mod_appkernel.ak_instance aki
        WHERE aki.collected      >= :start_date AND
              aki.collected      <  :end_date
        ORDER BY aki.collected ASC;
SQL;
        $params = array(
            ':start_date' => $start_date->format('Y/m/d'),
            ':end_date' => $end_date_exclusive->format('Y/m/d')
        );

        // If a userOrganization has been provided then filter the results on them.
        if (isset($this->userOrganization)) {
            $sql = <<<SQL
        SELECT aki.ak_id,
               aki.collected,
               aki.resource_id,
               aki.instance_id,
               aki.status,
               aki.controlStatus
        FROM mod_appkernel.ak_instance aki
        JOIN mod_appkernel.resource r ON aki.resource_id = r.resource_id
        JOIN modw.resourcefact rf ON r.xdmod_resource_id = rf.id
        WHERE aki.collected      >= :start_date AND
              aki.collected      <  :end_date   AND
              rf.organization_id =  :organization_id
        ORDER BY aki.collected ASC;

SQL;
            $params[':organization_id'] = $this->userOrganization;
        } elseif (isset($this->resource_ids) && (!(empty($this->resource_ids)))) {
            $resourceIds = implode(', ', $this->resource_ids);
            $sql = <<<SQL
        SELECT aki.ak_id,
               aki.collected,
               aki.resource_id,
               aki.instance_id,
               aki.status,
               aki.controlStatus
        FROM mod_appkernel.ak_instance aki
        WHERE aki.collected      >= :start_date AND
              aki.collected      <  :end_date   AND
              aki.resource_id IN ($resourceIds)
        ORDER BY aki.collected ASC;
SQL;

        }

        $rows = $pdo->query($sql, $params);

        $controlState = array();
        foreach ($rows as $row) {
            $controlState[$row['resource_id']][$row['ak_id']][$row['collected']] = $row['controlStatus'];
        }

        // Retrieve the main set of data used to create the map.
        $sql = <<<SQL
            SELECT
              axi.instance_id,
              axi.collected,
              axi.resource,
              axi.reporter,
              axi.reporternickname,
              axi.status
            FROM akrr_xdmod_instanceinfo AS axi
            WHERE axi.collected >=  :start_date
              AND axi.collected <   :end_date
            ORDER BY axi.collected ASC;
SQL;
        $params = array(
            ':start_date' => $start_date->format('Y/m/d'),
            ':end_date' => $end_date_exclusive->format('Y/m/d')
        );

        // If we have resource id's then we should additionally filter on them.
        if (count($this->resource_ids) > 0) {

            $quotedResources = implode(
                ",",
                array_map(
                    function ($value) use ($arr_db) {
                        return $arr_db->quote($value);
                    },
                    array_keys($this->resource_ids)
                )
            );

            $sql = <<<SQL
            SELECT
              axi.instance_id,
              axi.collected,
              axi.resource,
              axi.reporter,
              axi.reporternickname,
              axi.status
            FROM akrr_xdmod_instanceinfo AS axi
            WHERE axi.collected >=  :start_date
              AND axi.collected <   :end_date
              AND axi.resource IN ( $quotedResources )
            ORDER BY axi.collected ASC;
SQL;
        }

        //get information from appkernel-db
        $sqlres = $arr_db->query($sql, $params);

        $taskStateGroupOptions = array(
            'controlThreshold' => $this->controlThreshold,
            'controlThresholdCoeff' => $this->controlThresholdCoeff,
        );

        foreach ($sqlres as $row) {
            $rec_date = date_format(date_create($row['collected']), 'Y/m/d');
            $resource = $row['resource'];
            $appKer = $row['reporter'];
            $reporterNickName = explode('.', $row['reporternickname']);
            $problemSize = end($reporterNickName);
            $collected = $row['collected'];

            $resource_id = array_key_exists($resource, $this->resource_ids) ? $this->resource_ids[$resource] : null;
            $ak_id = null;
            if (array_key_exists($appKer, $ak_ids) && array_key_exists($problemSize, $ak_ids[$appKer])) {
                $ak_id = $ak_ids[$appKer][$problemSize];
            }

            // Proceed with filtering the values...
            if ((!isset($ak_id)) ||
                (isset($this->resource_ids) && !array_key_exists($resource, $this->resource_ids)) ||
                (isset($this->appKer) && (!(empty($this->appKer) || in_array($this->ak_shortnames[$appKer], $this->appKer)))) ||
                (isset($this->problemSize) && !in_array($problemSize, $this->problemSize))
            ) {
                continue;
            }

            $task = new TaskState($taskStateGroupOptions);
            $task->id = $row['instance_id'];
            $task->collected = DateTime::createFromFormat('Y-m-d H:i:s', $row['collected']);
            $task->status = intval($row['status']);
            $task->controlStatus = 'undefined';

            if (isset($resource_id)) {
                if (isset($ak_id) &&
                    array_key_exists($resource_id, $controlState) &&
                    array_key_exists($ak_id, $controlState[$resource_id]) &&
                    array_key_exists($collected, $controlState[$resource_id][$ak_id])) {
                    $task->controlStatus = $controlState[$resource_id][$ak_id][$collected];
                }

                //init entries in $runsStatus if needed
                if (!array_key_exists($resource, $runsStatus)) {
                    $runsStatus[$resource] = array();
                }

                if (!array_key_exists($appKer, $runsStatus[$resource])) {
                    $runsStatus[$resource][$appKer] = array();
                }

                if (!array_key_exists($problemSize, $runsStatus[$resource][$appKer])) {
                    $runsStatus[$resource][$appKer][$problemSize] = array();
                    for ($i = 0; $i < count($rec_dates) - 1; $i++) {
                        $runsStatus[$resource][$appKer][$problemSize][$rec_dates[$i]] = new TaskStateGroup($taskStateGroupOptions);
                    }
                }

                $runsStatus[$resource][$appKer][$problemSize][$rec_date]->add_task($task);
            }
        }
        //sort
        ksort($runsStatus);
        foreach ($runsStatus as $resource => $val1) {

            ksort($runsStatus[$resource]);
            foreach ($runsStatus[$resource] as $appKer => $val2) {

                ksort($runsStatus[$resource][$appKer]);
                foreach ($runsStatus[$resource][$appKer] as $problemSize => $val3) {

                    ksort($runsStatus[$resource][$appKer][$problemSize]);
                    foreach ($runsStatus[$resource][$appKer][$problemSize] as $d => $val4) {

                        $runsStatus[$resource][$appKer][$problemSize][$d]->sort_by_collection_time();
                        $runsStatus[$resource][$appKer][$problemSize][$d]->process();
                    }
                }
            }
        }

        //remove last date
        array_pop($rec_dates);

        return array(
            'rec_dates' => $rec_dates,
            'runsStatus' => $runsStatus
        );
    }

    /**
     * Get the Performance Map data, formatted for display via the `Performance Map` tab web
     * element.
     *
     * @return array formatted as:
     * {
     *     "success": boolean, ### always true.
     *     "response": {
     *         "metaData": {
     *           ### Data meant for the ExtJS component that is responsible for displaying this
     *           ### information.
     *         },
     *       "results": [
     *           {
     *               "resource":    string, ### The name of the resource the app kernel was run on.
     *               "appKer":      string, ### The human friendly name of the app kernel run.
     *               "problemSize": number, ### Number of nodes used.
     *               "<date>":      string, ### Date this app kernel was run.
     *               "<date>-IDs-F": [
     *                   ### List of Failed Run Ids
     *               ],
     *               "<date>-IDs-U": [
     *                   ### List of Under Performing Run Ids
     *               ],
     *               "<date>-IDs-N": [
     *                   ### List of In Control Run Ids
     *               ],
     *               "<date>-IDs-O": [
     *                   ### List of Over Performing Run Ids
     *               ],
     *               "<date>-IDs-C": [
     *                   ### List of Control Interval Run Ids
     *               ],
     *               "<date>-IDs-R": [
     *                   ### List of Run Ids with No Control Info
     *               ]
     *           }
     *       ]
     *     },
     *     "count": number      ### Count of 'response'
     * }
     */
    public function getMapForWeb()
    {
        $response = array();

        // Initialize the metaData to be returned.
        $response['metaData'] = array(
            'root' => 'response',
            'successProperty' => 'success',
            'totalProperty' => 'count',
            'fields' => array(
                array('name' => 'resource', 'type' => 'string'),
                array('name' => 'appKer', 'type' => 'string'),
                array('name' => 'problemSize', 'type' => 'string'),
            )
        );

        // Add additional metadata fields for each recorded date.
        foreach ($this->perfMap['rec_dates'] as $rec_date) {
            $response['metaData']['fields'][] = array('name' => $rec_date, 'type' => 'string');
            $response['metaData']['fields'][] = array('name' => $rec_date . '-IDs-F', 'type' => 'string');
            $response['metaData']['fields'][] = array('name' => $rec_date . '-IDs-U', 'type' => 'string');
            $response['metaData']['fields'][] = array('name' => $rec_date . '-IDs-N', 'type' => 'string');
            $response['metaData']['fields'][] = array('name' => $rec_date . '-IDs-O', 'type' => 'string');
            $response['metaData']['fields'][] = array('name' => $rec_date . '-IDs-C', 'type' => 'string');
            $response['metaData']['fields'][] = array('name' => $rec_date . '-IDs-R', 'type' => 'string');
        }

        // Begin packing the results
        $results = array();

        $runsStatus=$this->perfMap['runsStatus'];
        foreach($runsStatus as $resource => $runData) {
            foreach($runData as $appKernel => $nodeCountData) {
                // We default the value displayed for the user to the full style name.
                // Ex. xdmod.benchmark.io.ior
                // But we'd prefer to show them the `shortName`.
                // Ex. IOR
                $appKernelDisplay = $appKernel;
                if (array_key_exists($appKernel, $this->ak_shortnames)) {
                    $appKernelDisplay = $this->ak_shortnames[$appKernel];
                }

                foreach($nodeCountData as $problemSize => $problemSizeData) {
                    $result = array(
                        'resource' => $resource,
                        'appKer' => $appKernelDisplay,
                        'problemSize' => $problemSize
                    );

                    foreach($problemSizeData as $rec_date => $taskGroup) {
                        $runs = count($taskGroup->tasks);
                        if ($runs === 0) {
                            $result[$rec_date] = ' ';
                            continue;
                        }

                        $result[$rec_date] = $taskGroup->summary . '/' . $runs;
                        $result[$rec_date . '-IDs-F'] = $taskGroup->failedRuns;
                        $result[$rec_date . '-IDs-U'] = $taskGroup->underPerformingRuns;
                        $result[$rec_date . '-IDs-N'] = $taskGroup->inControlRuns;
                        $result[$rec_date . '-IDs-O'] = $taskGroup->overPerformingRuns;
                        $result[$rec_date . '-IDs-C'] = $taskGroup->controlIntervalRuns;
                        $result[$rec_date . '-IDs-R'] = $taskGroup->noControlInfoRuns;
                    }

                    $results[] = $result;
                }
            }
        }

        $response['success'] = true;
        $response['response'] = $results;
        $response['count'] = count($response['response']);

        return $response;
    }
}
