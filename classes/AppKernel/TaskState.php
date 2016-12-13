<?php

namespace AppKernel;

/**
 * Class for state of particulat AK execution
 */
class TaskState
{
    public $id=null;/** @param int */
    public $collected=null;/** @param \DataTime */
    public $status=null;/** @param int */
    //public $control=null;/** @param float */
    public $controlStatus=null;/** @param string */
    public $summary=null;/** @param string actually single character*/

    public $controlThreshold=-0.5;/** @param float */
    public $controlThresholdCoeff=1.0;/** @param float */

    public static $summaryCodes=array(
        ' '=>'There was no application kernel runs',
        'F'=>'Application kernel failed to run',
        'U'=>'Application kernel was under-performing',
        'N'=>'Application kernel was executed within control interval',
        'O'=>'Application kernel was over-performing',
        'C'=>'This run was used to calculate control region',
        'R'=>'Application kernel ran, but control information is not available'
    );

    public function __construct($options)
    {
        if(array_key_exists('controlThreshold',$options))
            $this->controlThreshold=$options['controlThreshold'];
        if(array_key_exists('controlThresholdCoeff',$options))
            $this->controlThresholdCoeff=$options['controlThresholdCoeff'];
    }
    /**
     * determine is it failed/underperformint/good
     */
    public function process()
    {
        $controlThreshold=$this->controlThreshold*$this->controlThresholdCoeff;
        if($this->status==1)
        {
            if($this->controlStatus!==null){
                switch ($this->controlStatus){
                    case "undefined":
                        $this->summary='R';
                        break;
                    case "control_region_time_interval":
                        $this->summary='C';
                        break;
                    case "in_contol":
                        $this->summary='N';
                        break;
                    case "under_performing":
                        $this->summary='U';
                        break;
                    case "over_performing":
                        $this->summary='O';
                        break;
                    case "failed":
                        $this->summary='F';
                        break;
                    default:
                        $this->summary='R';
                        break;
                }
            }
        }
        else
        {
            $this->summary='F';
        }
    }
    public static function cmp_by_collection_time($a,$b)
    {
        return ($a->collected > $b->collected) ? +1 : -1;
    }
}
