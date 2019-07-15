<?php

function implode_smart($a){
    $s="";
    for($i=0;$i<count($a);$i++) {
        $s.=$a[$i];
        if($i<count($a)-1){
            if($i==count($a)-2){
                $s.=' and ';
            }
            else{
                $s.=', ';
            }
        }
    }
    return $s;
}

/**
 * Retrieve the value for $k in $a, if $a has a key $k. Else, $d is returned by default.
 * @param mixed      $k the key to be retrieved.
 * @param array      $a the array to retrieve from.
 * @param null|mixed $d the default value to be returned if $k is not found.
 *
 * @return mixed value for $k if $k is present in $a else $d.
 */
function arrayValue($k,$a,$d=null)
{
    if(array_key_exists($k,$a))
        return $a[$k];
    else
        return $d;
}
/**
 * format NotificationSettings from client form submittion
 */
function formatNotificationSettingsFromClient( &$s,$preserveCheckBoxes=false) {
    $daysOfTheWeek=array('Sunday'=>1,'Monday'=>2,'Tuesday'=>3,'Wednesday'=>4, 'Thursday'=>5,'Friday'=>6,'Saturday'=>7);
    //set report periodisity
    $groupCombine=array('daily_report','weekly_report','monthly_report');
    
    foreach ($groupCombine as $g) {
        $s[$g]=array();
        foreach ($s as $key => $value) {
            if(strpos($key, $g.'_') === 0){
                $s[$g][str_replace($g.'_','',$key)]=$value;
                unset($s[$key]);
            }
        }
    }
    /*(array_key_exists('send_report_daily',$s) && ($s['send_report_daily']=='on'))?($s['send_report_daily']=1):($s['send_report_daily']=0);
    (array_key_exists('send_report_weekly',$s) && ($s['send_report_weekly']=='on'))?($s['send_report_weekly']=$daysOfTheWeek[$s['send_report_weekly_on_day']]):($s['send_report_weekly']=-$daysOfTheWeek[$s['send_report_weekly_on_day']]);
    unset($s['send_report_weekly_on_day']);
    (array_key_exists('send_report_monthly',$s) && ($s['send_report_monthly']=='on'))?($s['send_report_monthly']=intval($s['send_report_monthly_on_day'])):($s['send_report_monthly']=-intval($s['send_report_monthly_on_day']));
    unset($s['send_report_monthly_on_day']);*/
    //make list of resources and appkernels
    $s['resource']=array();
    $s['appKer']=array();
    foreach ($s as $key => $value) {
        if(strpos($key, 'resourcesList_') === 0){
            $s['resource'][]=str_replace('resourcesList_','',$key);
            if($preserveCheckBoxes) $s[$key]='';
            else unset($s[$key]);
        }
        if(strpos($key, 'appkernelsList_') === 0){
            $s['appKer'][]=str_replace('appkernelsList_','',$key);
            if($preserveCheckBoxes) $s[$key]='';
            else unset($s[$key]);
        }
    }
    
    if(count($s['resource'])==1 && $s['resource'][0]=='all')
        $s["resource"]=array();//None means all
    if(count($s['appKer'])==1 && $s['appKer'][0]=='all')
        $s["appKer"]=array();//None means all
}
/**
 * format NotificationSettings for client
 */
function formatNotificationSettingsForClient( &$s) {
    $daysOfTheWeek=array(1=>'Sunday',2=>'Monday',3=>'Tuesday',4=>'Wednesday', 5=>'Thursday',6=>'Friday',7=>'Saturday');
    //set report periodisity
    //"send_report_daily":"on","send_report_monthly":"","send_report_weekly":"","send_report_weekly_on_day":"Monday","send_report_monthly_on_day":"4"
    /*($s['send_report_daily']>0)?($s['send_report_daily']='on'):($s['send_report_daily']='');
    if($s['send_report_weekly']>0){
        $s['send_report_weekly_on_day']=$daysOfTheWeek[$s['send_report_weekly']];
        $s['send_report_weekly']='on';
    }
    else{
        $s['send_report_weekly_on_day']=$daysOfTheWeek[-$s['send_report_weekly']];
        $s['send_report_weekly']='';
    }
    if($s['send_report_monthly']>0){
        $s['send_report_monthly_on_day']=$s['send_report_monthly'];
        $s['send_report_monthly']='on';
    }
    else{
        $s['send_report_monthly_on_day']=-$s['send_report_monthly'];
        $s['send_report_monthly']='';
    }*/
    //make list of resources and appkernels
    if(count($s['resource'])==0)
        $s["resource"]=array('all');//None means all
    if(count($s['appKer'])==0)
        $s["appKer"]=array('all');//None means all
    foreach ($s['resource'] as $value) {
        $s['resourcesList_'.$value]='on';
    }
    foreach ($s['appKer'] as $value) {
        $s['appkernelsList_'.$value]='on';
    }
    
    unset($s['resource']);
    unset($s['appKer']);

    $groupCombine=array('daily_report','weekly_report','monthly_report');
    
    foreach ($groupCombine as $g) {
        foreach ($s[$g] as $key => $value) {
            $s[$g.'_'.$key]=$value;
        }
        unset($s[$g]);
    }
}
