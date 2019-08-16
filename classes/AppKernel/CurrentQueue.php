<?php

namespace AppKernel;

use CCR\DB;

/**
 * Class for handling report for app kernel in current queue (i.e. currently executing)
 *
 * @param array $options options send through constructor
 * Possible keys:
 * @param string[]|null  $options['resource']
 * @param string[]|null  $options['appKer']
 * @param int[]|null     $options['problemSize']
 */
class CurrentQueue
{
    /**
     * @param array $report_params Report parameters
     *
     * Possible keys:
     * @param string[]|null  $report_params['resource']
     * @param string[]|null  $report_params['appKer']
     * @param int[]|null     $report_params['problemSize']
     * */
    private $report_params;
    private $default_options=array(
        'resource'=>null,
        'appKer'=>null,
        'problemSize'=>null
    );
    private $full_table_fields=array(
        'task_id'=>'task id',
        'resource'=>'resource',
        'app'=>'app kernel',
        'status'=>'status'
    );

    public function __construct($options)
    {
        $this->report_params=array_merge(array(),$this->default_options);
        $this->report_params=array_merge($this->report_params,$options);
    }
    /**
     * Generate html-report
     *
     * @return string $message html report
     */
    public function make_report($shortReport=true)
    {
        $active_tasks=$this->get_active_tasks();
        if($shortReport===true)
        {
            $message='Number of application kernels in queue on resource as of '.date_format(date_create(),'Y-m-d G:i') . ': <b>'.count($active_tasks)."</b><br/>";
        }
        else
        {
           $message='';
           $message.='<h2>Active Tasks as of '.date_format(date_create(),'Y-m-d G:ia') .'</h2>';

           if(count($active_tasks)>0)
           {
               $message.=$this->print_full_table($active_tasks);
           }
           else
           {
               $message.='<p>Currently, there is no tasks in active queue.</p>';
           }
        }
        return $message;
    }
    private function get_highlighting_for_task($task)
    {
        $tdStyle_Resource='style=""';
        $tdStyle_AppKer='style="""';
        $tdStyle_ProblemSize='style=""';
        $tdStyle_Day='style="" align="center"';

        $tdStyle_Empty='style="background-color:white;"';
        $tdStyle_Good='style="background-color:#B0FFC5;"';
        $tdStyle_Warning='style="background-color:#FFFF99;"';
        $tdStyle_Error='style="background-color:#FFB0C4;"';

        if(stristr($task['status'],'error')===false)
            return $tdStyle_Empty;
        else
            return $tdStyle_Error;
    }
    private function print_full_table_print_cell($task,$value)
    {
        return '<td '.$this->get_highlighting_for_task($task).'>'.$value.'</td>';
    }
    private function print_full_table($active_tasks)
    {
        $message='';

        //print table



        //'<div class="x-grid3-cell-inner" style="background-color:#FFB0C4;"><span style="color:red;">' + value + '</span></div>';
        //'<div class="x-grid3-cell-inner" style="background-color:#FFFF99;"><span style="color:brown;">' + value + '</span></div>';
        //'<div class="x-grid3-cell-inner" style="background-color:#B0FFC5;"><span style="color:green;">' + value + '</span></div>';

        $message.='<table border="1" cellspacing="0" style="">';
        //table header
        $message.='<tr>';
        foreach ($this->full_table_fields as $key => $value) {
             $message.='<td>'.$value.'</td>';
        }
        $message.='</tr>';
        foreach($active_tasks as $task){
            $message.='<tr>';
            foreach ($this->full_table_fields as $key => $value) {
                 $message.=$this->print_full_table_print_cell($task,$task[$key]);
            }
            $message.='</tr>';
        }
        $message.='</table>';
        return $message;
    }
    private function get_active_tasks()
    {
        $arr_db = DB::factory('akrr-db');
        $active_tasks=array();

        $sqlres=$arr_db->query('SELECT task_id,
                                next_check_time,
                                status,
                                status_info,
                                status_update_time,
                                datetime_stamp,
                                time_activated,
                                time_submitted_to_queue,
                                task_lock,
                                time_to_start,
                                repeat_in,
                                resource,
                                app,
                                resource_param,
                                app_param,
                                task_param,
                                group_id,
                                fatal_errors_count,
                                fails_to_submit_to_the_queue,
                                taskexeclog,
                                master_task_id
                            FROM active_tasks
                            ORDER BY resource,app'
        );
        foreach ($sqlres as $res) {
            //$res['time_activated']
            if($this->report_params['resource']===null || empty($this->report_params['resource']) ||
                in_array($res['resource'], $this->report_params['resource']))
                $active_tasks[]=$res;
        }
        return $active_tasks;
    }
}
