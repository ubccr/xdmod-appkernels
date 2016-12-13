<?php

namespace AppKernel\ProblemDetector;

class AppKernelLevel
{
    public $resource=NULL;
    public $appKer=NULL;
    public $appKerShortname=NULL;
    public $problemSizes=NULL;
    public $runStatsAppKernel=NULL;
    public $runStatsProblemSize=NULL;
    public $problems=array();
    public $errorTypes=array('Failure','UnderPerforming','OverPerforming');
    /* total - all nodes counts have same problem
     * nodewisePartial - some of node counts have same problem
     * percentage - certain persentage of all runs have same problem
     * nodewisePercentagePartial - some of node counts have certain persentage of all runs with same problem
     */
    public $errorSubtypes=array('total','nodewisePartial','percentage','nodewisePercentagePartial');

    public function __construct($runStatsAppKernel,$runStatsProblemSize,$appKerShortname)
    {
        $this->runStatsAppKernel=$runStatsAppKernel;
        $this->runStatsProblemSize=$runStatsProblemSize;
        $this->resource=$runStatsAppKernel->resource;
        $this->appKer=$runStatsAppKernel->appKer;
        $this->problemSizes=array_keys($runStatsAppKernel->sum_map);
        $this->appKerShortname=$appKerShortname;
    }

    public function detect(){
        $max_days=max(array_keys($this->runStatsAppKernel->NE));
        $all_problems=array();
        $init_max_days=10;
        //check if it was failing for x days
        foreach ($this->errorTypes as $errorType) {
            $streakDays='FstreakDays';
            $stat='F';
            $verb='failed';

            if($errorType==='UnderPerforming'){
                $streakDays='UstreakDays';
                $stat='U';
                $verb='under-performed';
            }
            if($errorType==='OverPerforming'){
                $streakDays='OstreakDays';
                $stat='O';
                $verb='over-performed';
            }
            //total failure on all nodes counts
            if($this->runStatsAppKernel->$streakDays >= 3 &&
                    $this->runStatsAppKernel->{$stat}[$this->runStatsAppKernel->$streakDays]>8){
                $msg="{$this->appKerShortname} $verb for {$this->runStatsAppKernel->$streakDays} days";
                if($errorType==='UnderPerforming'){
                    if($this->runStatsAppKernel->{$stat}>0 || $this->runStatsAppKernel->F[$this->runStatsAppKernel->$streakDays]>0){
                        $msg.=" (totally ";

                        if($this->runStatsAppKernel->{$stat}>0)
                            $msg.="{$this->runStatsAppKernel->{$stat}[$this->runStatsAppKernel->$streakDays]} runs $verb";

                        if($this->runStatsAppKernel->{$stat}>0 && $this->runStatsAppKernel->F[$this->runStatsAppKernel->$streakDays]>0)
                            $msg.=" and ";

                        if($this->runStatsAppKernel->{$stat}>0 || $this->runStatsAppKernel->F[$this->runStatsAppKernel->$streakDays]>0)
                            $msg.="{$this->runStatsAppKernel->F[$this->runStatsAppKernel->$streakDays]} runs failed";

                        $msg.=")";

                    }

                }
                else{
                    $msg.=" ({$this->runStatsAppKernel->{$stat}[$this->runStatsAppKernel->$streakDays]} runs $verb)";
                }
                $msg.=" on ".implode(",", $this->problemSizes)." nodes";


                $all_problems[] = array(
                    'errorCode'=>'total'.$errorType,
                    'duration'=>$this->runStatsAppKernel->$streakDays,
                    'fraction'=>1.0,
                    'appKernel'=>$this->appKer,
                    'appKernelShort'=>$this->appKerShortname,
                    'resource'=>$this->resource,
                    'problemSizes'=>$this->problemSizes,
                    'msg'=>$msg
                    );
                continue;
            }
            //total failure on several nodes counts
            $msg="";
            $failedTimes=array();
            $problemSizes=array();
            $days=0;
            $count=0;
            foreach ($this->runStatsProblemSize as $problemSize => $runStats) {
                if($runStats->$streakDays >= 3 &&
                        $runStats->{$stat}[$runStats->$streakDays]>3){
                    $count++;
                    $days+=$runStats->$streakDays;
                    $failedTimes[]=$runStats->{$stat}[$runStats->$streakDays];
                    $problemSizes[]=$problemSize;
                }
            }
            if($count>0){
                $days=$days/$count;
                $msg.="{$this->appKerShortname} was $verb ".implode_smart($failedTimes)." times";
                $msg.=" on ".implode_smart($problemSizes)." nodes";
                if($count>1)$msg.=" respectively";
                $msg.=" during last ".number_format($days,0)." days";

                $all_problems[] = array(
                    'errorCode'=>'nodewisePartial'.$errorType,
                    'duration'=>$days,
                    'fraction'=>count($problemSizes)/count($this->problemSizes),
                    'appKernel'=>$this->appKer,
                    'appKernelShort'=>$this->appKerShortname,
                    'resource'=>$this->resource,
                    'problemSizes'=>$problemSizes,
                    'msg'=>$msg
                    );
                continue;
            }
            //unstable runs
            for($days=3;$days<$init_max_days;$days++){
                if($this->runStatsAppKernel->NE > 12){
                    break;
                }
            }
            if($days<7 && $this->runStatsAppKernel->NE[$days] > 12 &&
                    $this->runStatsAppKernel->{$stat}[$days]/$this->runStatsAppKernel->NE[$days] > 0.5){
                $fmax=$this->runStatsAppKernel->{$stat}[$days]/$this->runStatsAppKernel->NE[$days];
                $daysmax=$days;
                for(;$days<$max_days;$days++){
                    $f=$this->runStatsAppKernel->{$stat}[$days]/$this->runStatsAppKernel->NE[$days];
                    if($f>=.97*$fmax){
                        $fmax=$f;
                        $daysmax=$days;
                    }
                }
                $f=number_format(100.0*$fmax,1);
                $msg="{$this->appKerShortname} was $verb {$f}% of {$this->runStatsAppKernel->NE[$daysmax]} runs";
                $msg.=" during last $daysmax days";
                $all_problems[] = array(
                    'errorCode'=>'percentage'.$errorType,
                    'duration'=>$daysmax,
                    'fraction'=>$fmax,
                    'appKernel'=>$this->appKer,
                    'appKernelShort'=>$this->appKerShortname,
                    'resource'=>$this->resource,
                    'problemSizes'=>$this->problemSizes,
                    'msg'=>$msg
                    );
                continue;
            }
            //individual node counts unstable run
            $msg="";
            $failedPercentage=array();
            $problemSizes=array();
            $daysR=0;
            $count=0;
            foreach ($this->runStatsProblemSize as $problemSize => $runStats) {
                for($days=4;$days<$init_max_days;$days++){
                    if($runStats->NE > 6){
                        break;
                    }
                }
                if($days<7 && $runStats->NE[$days] > 6 &&
                    $runStats->{$stat}[$days]/$runStats->NE[$days] > 0.5){
                    $fmax=$runStats->{$stat}[$days]/$runStats->NE[$days];
                    $daysmax=$days;
                    for(;$days<$max_days;$days++){
                        $f=$runStats->{$stat}[$days]/$runStats->NE[$days];
                        if($f>=.97*$fmax){
                            $fmax=$f;
                            $daysmax=$days;
                        }
                    }
                    $failedPercentage[]=number_format(100.0*$fmax,1).'%';
                    $daysR+=$daysmax;
                    $count++;
                }
                $problemSizes[]=$problemSize;
            }
            if($count>0){
                $daysR=$daysR/$count;

                $msg.="{$this->appKerShortname} was $verb ".implode_smart($failedPercentage)."%";
                $msg.=" on ".implode_smart($problemSizes)." nodes";
                $msg.=" during last ".number_format($daysR,0)." days";
                if($count>1)$msg.=" respectively";

                $all_problems[] = array(
                    'errorCode'=>'nodewisePercentagePartial'.$errorType,
                    'duration'=>$daysR,
                    'fraction'=>array_sum($failedPercentage)/count($this->problemSizes)/100.0,
                    'appKernel'=>$this->appKer,
                    'appKernelShort'=>$this->appKerShortname,
                    'resource'=>$this->resource,
                    'problemSizes'=>$problemSizes,
                    'msg'=>$msg
                    );
                continue;
            }
        }
        $this->problems=$all_problems;
        return count($all_problems);
    }


}
