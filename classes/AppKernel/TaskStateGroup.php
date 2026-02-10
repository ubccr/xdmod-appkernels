<?php

namespace AppKernel;

/**
 * Class for group of similar TaskState for a single day
 */
class TaskStateGroup
{
    public $controlThreshold=-0.5;/** @param float */
    public $controlThresholdCoeff=1.0;/** @param float */

    public $tasks=array();/** @param \TaskState */

    public $inControlRuns=array();
    public $underPerformingRuns=array();
    public $overPerformingRuns=array();
    public $noControlInfoRuns=array();
    public $controlIntervalRuns=array();
    public $failedRuns=array();

    public $summary=null;/** @param string actually single character*/
    public $summaryMethod='byLast';/** @param string byLast/best*/

    public function __construct($options)
    {
        if(array_key_exists('controlThreshold',$options))
            $this->controlThreshold=$options['controlThreshold'];
        if(array_key_exists('controlThresholdCoeff',$options))
            $this->controlThresholdCoeff=$options['controlThresholdCoeff'];
        if(array_key_exists('summaryMethod',$options))
            $this->summaryMethod=$options['summaryMethod'];
    }
    public function add_task($task)
    {
        $this->tasks[$task->id]=$task;
    }
    /**
     * determine failed/underperformint/good tasks
     */
    public function process()
    {
        //$controlThreshold=$this->controlThreshold*$this->controlThresholdCoeff;
        $this->inControlRuns=array();
        $this->underPerformingRuns=array();
        $this->overPerformingRuns=array();
        $this->noControlInfoRuns=array();
        $this->controlIntervalRuns=array();
        $this->failedRuns=array();

        foreach ($this->tasks as $task_id=>&$task)
            $task->process();

        foreach ($this->tasks as $task_id=>&$task)
        {
            switch ($task->summary){
                case "R":
                    $this->noControlInfoRuns[]=intval($task->id);
                    break;
                case "C":
                    $this->controlIntervalRuns[]=intval($task->id);
                    break;
                case "N":
                    $this->inControlRuns[]=intval($task->id);
                    break;
                case "U":
                    $this->underPerformingRuns[]=intval($task->id);
                    break;
                case "O":
                    $this->overPerformingRuns[]=intval($task->id);
                    break;
                case "F":
                    $this->failedRuns[]=intval($task->id);
                    break;
                default:
                    break;
            }
            $last_task_id=intval($task->id);
        }

        if($this->summaryMethod=='byLast')
        {
            if(count($this->tasks)>0)
                $this->summary=$this->tasks[$last_task_id]->summary;
            else
                $this->summary=' ';
        }
        else//i.e. best
        {
            if(count($this->inControlRuns)>0)
                $this->summary='N';
            else if(count($this->underPerformingRuns)>0)
                $this->summary='U';
            else if(count($this->noControlInfoRuns)>0)
                $this->summary='R';
            else if(count($this->failedRuns)>0)
                $this->summary='F';
            else
                $this->summary=' ';
        }

    }
    public function sort_by_collection_time()
    {
        uasort($this->tasks,array('\AppKernel\TaskState','cmp_by_collection_time'));
    }
}
