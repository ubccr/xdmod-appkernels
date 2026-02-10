<?php

namespace AppKernel;

class RunsStats
{
    //probleSize/appKernel/resource
    public $level='probleSize';
    public $sum_map="";

    //Number of consecutively failed runs ending with last run
    public $FstreakRuns=0;
    //Number of consecutively over-performing runs ending with last run
    public $OstreakRuns=0;
    //Number of consecutively under-performing runs ending with last run
    public $UstreakRuns=0;

    //Number of consecutively failed days ending with last day and counted empty days
    public $FstreakDays=0;
    //Number of consecutively over-performing days ending with last day and counted empty days
    public $OstreakDays=0;
    //Number of consecutively under-performing days ending with last day and counted empty days
    public $UstreakDays=0;

    //Number of empty days ending with last day
    public $EstreakDays=0;

    //array with number of failed days in previous 1,2,3,4,...days
    public $F=array();
    //array with number of over-performing days in previous 1,2,3,4,...days
    public $O=array();
    //array with number of under-performing days in previous 1,2,3,4,...days
    public $U=array();
    //array with total number of days in previous 1,2,3,4,...days
    public $T=array();
    //array with total number of empty days in previous 1,2,3,4,...days
    public $E=array();
    //array with total number of not empty days in previous 1,2,3,4,...days
    public $NE=array();


    public $resource=NULL;
    public $appKer=NULL;
    public $problemSize=NULL;


    public function __construct($resource=NULL,$appKer=NULL,$problemSize=NULL,$sum_map=NULL)
    {
        $this->resource=$resource;
        $this->appKer=$appKer;
        $this->problemSize=$problemSize;

        if($sum_map===NULL)
            return;

        $this->sum_map=(string)$sum_map;

        $days=1;
        //calculate statistics
        for($i=strlen($sum_map)-1;$i>=0;$i--){

            $this->F[$days]=0;
            $this->O[$days]=0;
            $this->U[$days]=0;
            $this->T[$days]=0;
            $this->E[$days]=0;
            $this->NE[$days]=0;

            if($sum_map[$i] ==' '){
                $this->E[$days]++;
            }
            else{
                $this->NE[$days]++;
                if($sum_map[$i] =='F')$this->F[$days]++;
                if($sum_map[$i] =='O')$this->O[$days]++;
                if($sum_map[$i] =='U')$this->U[$days]++;
            }
            $this->T[$days]++;

            if($days>1){
                $this->F[$days]+=$this->F[$days-1];
                $this->O[$days]+=$this->O[$days-1];
                $this->U[$days]+=$this->U[$days-1];
                $this->T[$days]+=$this->T[$days-1];
                $this->E[$days]+=$this->E[$days-1];
                $this->NE[$days]+=$this->NE[$days-1];
            }

            $days++;
        }

        //calculate streaks
        //TotalFaulure
        $days=1;
        for($i=strlen($sum_map)-1;$i>=0;$i--){
            if($this->NE[$days]!==$this->F[$days]){
                break;
            }
            if($i!==0)$days++;
        }
        if($this->F[$days]!==0){
            $this->FstreakRuns=$this->F[$days];
            $this->FstreakDays=$days;
        }
        //Under-performance can be mixed with failure
        for($i=0;$i<strlen($sum_map);$i++){
            $days=strlen($sum_map)-$i;
            if($sum_map[$i] ==='U' && $this->NE[$days]===$this->F[$days]+$this->U[$days]){
                $this->UstreakRuns=$this->F[$days]+$this->U[$days];
                $this->UstreakDays=$days;
                break;
            }
        }
        //Over
        $days=1;
        for($i=strlen($sum_map)-1;$i>=0;$i--){
            if($this->NE[$days]!==$this->O[$days]){
                break;
            }
            if($i!==0)$days++;
        }
        if($this->O[$days]!==0){
            $this->OstreakRuns=$this->O[$days];
            $this->OstreakDays=$days;
        }
        /*
        $firstNonEmptyRun=NULL;

        $consecutiveRuns=0;
        $consecutiveDays=0;
        $consecutive=True;
        for($i=strlen($sum_map)-1;$i>=0;$i--){
            if($consecutive===True){
                if($firstNonEmptyRun===NULL){
                    if($sum_map{$i}!==' '){
                        $firstNonEmptyRun=$sum_map{$i};
                        $consecutiveRuns++;
                        $consecutiveDays++;
                    }
                    else{
                        $consecutiveDays++;
                    }
                }
                else{
                    if ($firstNonEmptyRun===$sum_map{$i}) {
                        $consecutiveRuns++;
                        $consecutiveDays++;
                    }
                    elseif($sum_map{$i}===' '){
                        $consecutiveDays++;
                    }
                    else{
                        $consecutive=False;
                    }
                }
            }
            $days++;
        }
        if($firstNonEmptyRun=='F'){
            $this->FstreakRuns=$consecutiveRuns;
            $this->FstreakDays=$consecutiveDays;
        }
        if($firstNonEmptyRun=='O'){
            $this->OstreakRuns=$consecutiveRuns;
            $this->OstreakDays=$consecutiveDays;
        }
        if($firstNonEmptyRun=='U'){
            $this->UstreakRuns=$consecutiveRuns;
            $this->UstreakDays=$consecutiveDays;
        }*/
    }
    public static function agregateOnResourceLevel($AKRR_RunsStats_AppkernelLevel){
        if(count($AKRR_RunsStats_AppkernelLevel)==0)
            return NULL;

        $resource=NULL;
        $sum_map=array();
        foreach($AKRR_RunsStats_AppkernelLevel as $s){
            if($resource===NULL)$resource=$s->resource;

            if($resource!==$s->resource)return NULL;

            foreach ($s->sum_map as $key => $value) {
                $sum_map[$s->appKer.'.'.$key]=$value;
            }
        }

        return RunsStats::agregate($AKRR_RunsStats_AppkernelLevel, $resource, NULL,$sum_map);
    }
    public static function agregateOnAppkernelLevel($AKRR_RunsStats_ProbleSizeLevel){
        if(count($AKRR_RunsStats_ProbleSizeLevel)==0)
            return NULL;


        $sum_map=array();
        $resource=NULL;
        $appKer=NULL;
        foreach($AKRR_RunsStats_ProbleSizeLevel as $s){
            if($resource===NULL)$resource=$s->resource;
            if($appKer===NULL)$appKer=$s->appKer;

            if($resource!==$s->resource)return NULL;
            if($appKer!==$s->appKer)return NULL;
            $sum_map[$s->problemSize]=$s->sum_map;
        }

        return RunsStats::agregate($AKRR_RunsStats_ProbleSizeLevel, $resource, $appKer,$sum_map);
    }
    private static function agregate($AKRR_RunsStats, $resource, $appKer=NULL,$sum_map=NULL){
        $runStats=new RunsStats($resource, $appKer);
        $runStats->sum_map=$sum_map;
        if($appKer===NULL){
            $runStats->level='resource';
        }
        else{
            $runStats->level='appKernel';
        }
        if($sum_map!==NULL){
            //reduce summary maps to single string line
            $reducedMap='';
            $Ndays=strlen(reset($sum_map));
            for($i=$Ndays-1;$i>=0;$i--){
                $commondNonEmptyRun=NULL;
                foreach($sum_map as $s) {
                    if($s[$i] !=' '){
                        if($commondNonEmptyRun===NULL){
                            $commondNonEmptyRun=$s[$i];
                        }
                        else{
                            if($commondNonEmptyRun!==$s[$i]){
                                $commondNonEmptyRun='Z';
                            }
                        }
                    }
                }
                if($commondNonEmptyRun===NULL)$commondNonEmptyRun=' ';
                $reducedMap=$commondNonEmptyRun.$reducedMap;
            }
            //calc steaks
            $runStats->FstreakRuns=reset($AKRR_RunsStats)->FstreakRuns;
            $runStats->FstreakDays=reset($AKRR_RunsStats)->FstreakDays;
            $runStats->OstreakRuns=reset($AKRR_RunsStats)->OstreakRuns;
            $runStats->OstreakDays=reset($AKRR_RunsStats)->OstreakDays;
            $runStats->UstreakRuns=reset($AKRR_RunsStats)->UstreakRuns;
            $runStats->UstreakDays=reset($AKRR_RunsStats)->UstreakDays;


            foreach ($AKRR_RunsStats as $runStatsProbSize) {
                $runStats->FstreakRuns=min($runStats->FstreakRuns,$runStatsProbSize->FstreakRuns);
                $runStats->FstreakDays=min($runStats->FstreakDays,$runStatsProbSize->FstreakDays);
                $runStats->OstreakRuns=min($runStats->OstreakRuns,$runStatsProbSize->OstreakRuns);
                $runStats->OstreakDays=min($runStats->OstreakDays,$runStatsProbSize->OstreakDays);
                $runStats->UstreakRuns=min($runStats->UstreakRuns,$runStatsProbSize->UstreakRuns);
                $runStats->UstreakDays=min($runStats->UstreakDays,$runStatsProbSize->UstreakDays);
            }
            /*$days=1;
            $firstNonEmptyRun=NULL;
            $consecutiveRuns=0;
            $consecutiveDays=0;
            $consecutive=True;

            for($i=strlen($reducedMap)-1;$i>=0;$i--){
                if($firstNonEmptyRun===NULL){
                    if($reducedMap{$i}!==' '){
                        $firstNonEmptyRun=$reducedMap{$i};
                        $consecutiveRuns++;
                        $consecutiveDays++;
                    }
                    else{
                        $consecutiveDays++;
                    }
                }
                else{
                    if ($firstNonEmptyRun===$reducedMap{$i}) {
                        $consecutiveRuns++;
                        $consecutiveDays++;
                    }
                    elseif($reducedMap{$i}===' '){
                        $consecutiveDays++;
                    }
                    else{
                        break;
                    }
                }
                $days++;
            }
            if($firstNonEmptyRun=='F'){
                $runStats->FstreakRuns=$consecutiveRuns;
                $runStats->FstreakDays=$consecutiveDays;
            }
            if($firstNonEmptyRun=='O'){
                $runStats->OstreakRuns=$consecutiveRuns;
                $runStats->OstreakDays=$consecutiveDays;
            }
            if($firstNonEmptyRun=='U'){
                $runStats->UstreakRuns=$consecutiveRuns;
                $runStats->UstreakDays=$consecutiveDays;
            }*/
        }
        //Calculate stats
        $s=reset($AKRR_RunsStats);
        $days_common_max=count($s->T);
        foreach($AKRR_RunsStats as $s){
            if($days_common_max < count($s->T))
                $days_common_max=count($s->T);
        }
        $vars=array('F','O','U','T','E','NE');
        foreach($vars as $var){
            for($days=1;$days<=$days_common_max;$days++){
                $runStats->{$var}[$days]=0;
                foreach($AKRR_RunsStats as $s){
                    $runStats->{$var}[$days]+=$s->{$var}[$days];
                }
            }
        }
        return $runStats;
    }
}
