<?php

namespace AppKernel\ProblemDetector;

class ResourceLevel extends AppKernelLevel
{
    public $runStatsResource=NULL;
    public $problemsScore=0.0;
    public function __construct($runStatsResource,$runStatsAppKernel)
    {
        AppKernelLevel::__construct($runStatsResource,$runStatsAppKernel,"RESOURCE");
        $this->runStatsResource=$runStatsResource;
        $this->runStatsAppKernel=$runStatsAppKernel;
        /*print "Resource: runStatsResource\n";
        print_r($runStatsResource);
        print "Resource runStatsResource END\n";
        print "Resource: runStatsAppKernel\n";
        print_r($runStatsAppKernel);
        print "Resource runStatsAppKernel END\n";*/

        /*$this->runStatsAppKernel=$runStatsAppKernel;
        $this->runStatsProblemSize=$runStatsProblemSize;
        $this->appKer=$runStatsAppKernel->appKer;
        $this->problemSizes=array_keys($runStatsAppKernel->sum_map);
        $this->appKerShortname=$appKerShortname;*/
    }
    public function detect($problemsAppKernelLevel){
        /*AppKernelLevel::detect();
        print "Resource:\n";
        print_r($this->problems);
        print "Resource END\n";*/

        //here we report problems by the ranking of their severity
        $this->problems=array();
        $scoreResource=0.0;

        //appKernelRanking rank by their importance
        $appKernelRanking=array();
        $appKernelPresent=array_keys($this->runStatsAppKernel);
        $appKernelRankingMain=array('xdmod.benchmark.mpi.imb','xdmod.benchmark.io.ior','xdmod.benchmark.hpcc');
        foreach ($appKernelRankingMain as $ak) {
            if(in_array($ak,$appKernelPresent)){
                $appKernelRanking[]=$ak;
            }
        }
        foreach ($appKernelPresent as $ak) {
            if(!in_array($ak,$appKernelRanking)){
                $appKernelRanking[]=$ak;
            }
        }
        //resource wide problems
        /*print_r(array_keys($problemsAppKernelLevel));
                print "\n";*/

        //specific appkernel proplems
        $scoresErrorTypes=array(
            'Failure'=>1.0,
            'UnderPerforming'=>0.75,
            'OverPerforming'=>0.5
        );
        $scoreErrorSubtypes=array(
            'total'=>1.0,
            'nodewisePartial'=>1.0,
            'percentage'=>1.0,
            'nodewisePercentagePartial'=>1.0
        );
        //loop over types and subtypels
        foreach ($this->errorTypes as $errorType) {
            foreach ($this->errorSubtypes as $errorSubtype) {
                $scores=array();
                $akproblems=array();
                foreach ($appKernelRanking as $ak) {
                    if(array_key_exists($ak,$problemsAppKernelLevel)){
                        foreach ($problemsAppKernelLevel[$ak]->problems as $problem){
                            if($problem['errorCode']===$errorSubtype.$errorType){
                                $score=$problem['fraction']*$problem['duration'];
                                $scoreResource+=$score*count($problem['problemSizes'])*$scoresErrorTypes[$errorType]*
                                    $scoreErrorSubtypes[$errorSubtype];
                                $scores[]=$score;
                                $akproblems[]=$problem;
                            }
                        }
                    }
                }
                array_multisort($scores,SORT_DESC,SORT_NUMERIC,$akproblems);
                $this->problems=array_merge($this->problems,$akproblems);
            }
        }
        $this->problemsScore=$scoreResource;
        return $this->problems;
    }
}
