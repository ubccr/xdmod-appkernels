<?php

namespace AppKernel;

use xd_utilities;
use DateTime;
use DateInterval;
use ZendMailWrapper;

/**
 * class for App kernel report generator
 *
 * Below is example how to use it:
 *   $report=new Report(array());
 *   $report->send_report_to_email($recipient);
 *
 *
 * Constructor takes associative array as configuration,possible keys:
 * @param array          $options options for constructor
 *
 * @param \DateTime      $options['start_date']    starting day of report
 * @param \DateTime      $options['end_date']      inclusive last day of report
 * @param string         $options['report_type']   Type of the report, possible types:
 *                                                 ['for_specified_period'|'daily_report'|'weekly_report'|'monthly_report']
 * @param string[]|null  $options['resource']
 * @param string[]|null  $options['appKer']
 * @param int[]|null     $options['problemSize']
 * @param float          $options['controlThreshold']
 *
 */
class Report
{
    /**
     * @param array $report_params Report parameters
     *
     * $report_params initiated from $default_report_params['default'] then from $default_report_params[$report_type]
     *
     * Possible keys:
     * @param string         $report_params['report_type']        Type of the report ['for_specified_period'|'daily_report'|'weekly_report'|'monthly_report']
     * @param \DateTime      $report_params['start_date']         starting day of report
     * @param \DateTime      $report_params['end_date']           inclusive last day of report
     * @param \DateTime      $report_params['end_date_exclusive'] exclusive last day of report (calculate automatically from $report_params['end_date'])
     *
     * @param string[]|null  $report_params['resource']
     * @param string[]|null  $report_params['appKer']
     * @param int[]|null     $report_params['problemSize']
     * @param float          $report_params['controlThreshold']
     * @param float          $report_params['controlThresholdCoeff']
     * @param string         $report_params['send_on_event']       Possible values:'sendNever','sendAlways','sendOnAnyErrors','sendOnMajorErrors'
     *
     * @param string|null    $report_params['report_date_interval'] string in DateInterval format
     * @param string|null    $report_params['performance_map_date_interval'] string in DateInterval format
     * @param \DateTime      $report_params['performance_map_start_date']
     * @param \DateTime      $report_params['performance_map_end_date']
     * */
    private $report_params;

    /** @param array[] dafault parameters for reporter, first default is loaded then report specific */
    private $default_report_params=array(
        'default'=>array(
            'report_type'=>'daily_report',
            'resource'=>null,
            'appKer'=>null,
            'problemSize'=>null,
            'controlThreshold'=>-0.5,
            'controlThresholdCoeff'=>1.0,
            'send_on_event'=>'sendAlways',
            'start_date'=>null,
            'end_date'=>null,
            'end_date_exclusive'=>null
        ),
        'for_specified_period'=>array(
            'performance_map_date_interval'=>null,
            'report_date_interval'=>null
        ),
        'daily_report'=>array(
            'performance_map_date_interval'=>'P14D',
            'report_date_interval'=>'P1D'
        ),
        'weekly_report'=>array(
            'performance_map_date_interval'=>'P14D',
            'report_date_interval'=>'P7D'
        ),
        'monthly_report'=>array(
            'performance_map_date_interval'=>'P1M',
            'report_date_interval'=>'P1M'
        )
    );

    /**
     * @param string address for app kernel instance viewer.
     * eventually: xd_utilities\getConfigurationUrlBase('general', 'site_address').'internal_dashboard/index.php?op=ak_instance&instance_id=';
     * */
    private $ak_instance_view_web_address;


    public static $tdStyle_Day_Empty='style="background-color:white;"';
    public static $tdStyle_Day_Good='style="background-color:#B0FFC5;"';
    public static $tdStyle_Day_Warning='style="background-color:#FFFF99;"';
    public static $tdStyle_Day_Error='style="background-color:#FFB0C4;"';

    public static $tdStyle=array(
            ' '=>'style="background-color:white;"',//$tdStyle_Day_Empty
            'F'=>'style="background-color:#FFB0C4;"',//$tdStyle_Day_Error
            'U'=>'style="background-color:#FFFF99;"',//$tdStyle_Day_Warning
            'N'=>'style="background-color:#B0FFC5;"',//$tdStyle_Day_Good
            'R'=>'style="background-color:#FFFF99;"'//$tdStyle_Day_Warning
        );

    /**
     * Constructor for Report.
     * @param array $options associative array of options see class description for possible options
     */
    public function __construct($options = array())
    {
        //set default values
        $siteAddress = xd_utilities\getConfigurationUrlBase('general', 'site_address');

        $this->ak_instance_view_web_address = $siteAddress.'internal_dashboard/index.php?op=ak_instance&instance_id=';

        //set report_params first default then report specific then options
        $this->report_params=array_merge(array(), $this->default_report_params['default']);
        if (array_key_exists('report_type', $options)) {
            if ((!in_array($options['report_type'], array_keys($this->default_report_params))) || ($options['report_type']==='default')) {
                throw new Exception('Unknown type of report: '.$options['report_type']);
            }
            $this->report_params=array_merge($this->report_params, $this->default_report_params[$options['report_type']]);
        } else {
            $this->report_params=array_merge($this->report_params, $this->default_report_params[$this->report_params['report_type']]);
        }
        $this->report_params=array_merge($this->report_params, $options);
        unset($this->report_params['report_params']);

        //now augment with options['report_params']
        if (array_key_exists('report_params', $options) && array_key_exists($this->report_params['report_type'], $options['report_params'])) {
            $this->report_params=array_merge($this->report_params, $options['report_params'][$this->report_params['report_type']]);
        }

        foreach ($options['report_params'] as $key => $value) {
            if (in_array($key, array('for_specified_period','daily_report','weekly_report','monthly_report'))) {
                continue;
            }
            $this->report_params[$key]=$value;
        }

        //clone some variables to avoid data curraption
        if ($this->report_params['start_date']!==null) {
            $this->report_params['start_date']=clone  $this->report_params['start_date'];
        }
        if ($this->report_params['end_date']!==null) {
            $this->report_params['end_date']=clone  $this->report_params['end_date'];
        }

        //if end_date is not set then set it to yesterday
        if ($this->report_params['end_date']===null) {
            $this->report_params['end_date']=new DateTime(date('Y-m-d'));
            $this->report_params['end_date']->sub(new DateInterval('P1D'));
        }
        $this->report_params['end_date_exclusive']=clone $this->report_params['end_date'];
        $this->report_params['end_date_exclusive']->add(new DateInterval('P1D'));

        //
        if (array_key_exists('user', $options) && $options['user']!==null) {
            $ak_db = new \AppKernel\AppKernelDb();
            $allResources = $ak_db->getResources(date_format(date_sub(date_create(), date_interval_create_from_date_string("90 days")), 'Y-m-d'),
                date_format(date_create(), 'Y-m-d'),
                array(), array(), $options['user']);
            $all_resource_names=array();
            foreach ($allResources as $r) {
                $all_resource_names[]=$r->nickname;
            }

            if ($this->report_params['resource']!==null && count($this->report_params['resource'])==0) {
                $this->report_params['resource']=null;
            }
            if ($this->report_params['resource']!==null && in_array('all', $this->report_params['resource'])) {
                $this->report_params['resource']=null;
            }

            if ($this->report_params['resource']===null) {
                $this->report_params['resource']=$all_resource_names;
            } else {
                $allowed_resources=array();
                foreach ($this->report_params['resource'] as $r) {
                    if (in_array($r, $all_resource_names)) {
                        $allowed_resources[]=$r;
                    }
                }
                $this->report_params['resource']=$allowed_resources;
            }
        } else {
            if ($this->report_params['resource']!=null && in_array('all', $this->report_params['resource'])) {
                $this->report_params['resource']=null;
            }
        }
        if ($this->report_params['appKer']!=null && in_array('all', $this->report_params['appKer'])) {
            $this->report_params['appKer']=null;
        }

        //initiate dependent variables for periodic reports
        if ($this->report_params['report_type']==='for_specified_period') {
            $this->report_params['performance_map_end_date']=clone $options['end_date'];
            $this->report_params['performance_map_start_date']=clone $options['start_date'];
        } else {
            //$this->report_params['end_date']->sub(new DateInterval('P1D'));//periodic reports report for period ending at previous day
            $this->report_params['start_date']=clone $this->report_params['end_date'];
            $this->report_params['start_date']->sub(new DateInterval($this->report_params['report_date_interval']));
            $this->report_params['start_date']->add(new DateInterval('P1D'));
            $this->report_params['performance_map_end_date']=clone $this->report_params['end_date'];
            $this->report_params['performance_map_start_date']=clone $this->report_params['performance_map_end_date'];
            $this->report_params['performance_map_start_date']->sub(new DateInterval($this->report_params['performance_map_date_interval']));
            $this->report_params['performance_map_start_date']->add(new DateInterval('P1D'));
        }
        if ($options['report_type']==='daily_report') {
        }
        $this->report_params['days']=array();
        $run_date = clone $this->report_params['start_date'];
        $day_interval = new DateInterval('P1D');

        while ($run_date<$this->report_params['end_date_exclusive']) {
            $this->report_params['days'][]=$run_date->format('Y/m/d');
            $run_date->add($day_interval);
        }
        //print_r($options);print_r($this->report_params);
        //exit();
        //throw new Exception('<pre>'.print_r($options,true).print_r($this->report_params,true).'</pre>');
    }

    /**
     * Generate and send report to e-mail
     *
     * @param string $send_to e-mail address for report
     *
     * @return void
     * @throws Exception on failure
     *
     */
    public function send_report_to_email($send_to, $internal_dashboard_user = false)
    {
        //get report
        try {
            $message=$this->make_report($internal_dashboard_user);
            if ($message===null) { //i.e. do not send report (e.g. user asked to send report only on errors)
                return;
            }
        } catch (Exception $e) {
            throw new Exception('Can not prepare report. '.$e->getMessage());
        }
        //send report
        try {
            $mailer_sender = xd_utilities\getConfiguration('mailer', 'sender_email');
            $subject = '[XDMoD] ';
            if ($this->report_params['report_type']==='for_specified_period') {
            } elseif ($this->report_params['report_type']==='daily_report') {
                $subject .= 'Daily ';
            } elseif ($this->report_params['report_type']==='weekly_report') {
                $subject .= 'Weekly ';
            } elseif ($this->report_params['report_type']==='monthly_report') {
                $subject .= 'Monthly ';
            }
            $subject .= 'App Kernel Execution Report';
            if ($this->report_params['start_date']->format('Y-m-d')===$this->report_params['end_date']->format('Y-m-d')) {
                $subject .= ' for '.$this->report_params['start_date']->format('Y-m-d');
            } else {
                $subject .= ', '.$this->report_params['start_date']->format('Y-m-d').' to '.$this->report_params['end_date']->format('Y-m-d');
            }

            $mail = ZendMailWrapper::init();
            $mail->setSubject($subject);
            $mail->addTo($send_to);
            $mail->setFrom($mailer_sender, 'XDMoD');
            $mail->setBodyHtml($message);
            $mail->send();
        } catch (Exception $e) {
            throw new Exception('Failed to send e-mail. '.$e->getMessage());
        }
    }

    /**
     * Generate html-report
     *
     * @return string html report
     */
    public function make_report($internal_dashboard_user = false)
    {
        //header
        $message = '';
        if ($this->report_params['start_date']->format('Y-m-d')===$this->report_params['end_date']->format('Y-m-d')) {
            $message .= '<h1>Report Period: '.$this->report_params['start_date']->format('Y-m-d').'</h1>';
        } else {
            $message .= '<h1>Report Period: '.$this->report_params['start_date']->format('Y-m-d').' to '.$this->report_params['end_date']->format('Y-m-d').'</h1>';
        }

        //$message .= 'start_date: '.$this->start_date->format('Y-m-d H:i:s').'<br/>';
        //$message .= 'end_date: '.$this->end_date->format('Y-m-d H:i:s').'<br/>';
        //$message .= 'end_date_exclusive: '.$this->end_date_exclusive->format('Y-m-d H:i:s').'<br/>';

        //PerformanceMap
        $perfMap=new PerformanceMap(array(
            'start_date'=>$this->report_params['performance_map_start_date'],
            'end_date'=>$this->report_params['performance_map_end_date'],
            'resource'=>$this->report_params['resource'],
            'appKer'=>$this->report_params['appKer'],
            'problemSize'=>$this->report_params['problemSize'],
            'controlThreshold'=>$this->report_params['controlThreshold'],
            'controlThresholdCoeff'=>$this->report_params['controlThresholdCoeff']
        ));
        $messagePerfMap=$perfMap->make_report($internal_dashboard_user);

        //Error Patterns
        $pprecog=new PerfPatternRecognition(array(
            'perfMap'=>$perfMap->perfMap
        ));
        $messageErrorPaterns=$pprecog->make_report();

        //CurrentQueue
        $cur_queue=new CurrentQueue(array(
                'resource'=>$this->report_params['resource'],
                'appKer'=>$this->report_params['appKer'],
                'problemSize'=>$this->report_params['problemSize']
        ));
        $messageCurrentQueue=$cur_queue->make_report(true);

        //get summary

        $sum=$perfMap->get_summary_for_days($this->report_params['days']);
        $message.=$sum['messageHeader'];

        $message.='<br/>';
        $message.='Number of repeatedly failed runs : <b>'. $pprecog->get_num_of_code_red().'</b><br/>';
        $message.='Number of repeatedly underperforming runs : <b>'. $pprecog->get_num_of_code_yellow().'</b><br/>';

        $message.='<br/>';
        $message.=$sum['message'];
        $message.=$messageCurrentQueue;

        $message.='<br/>';
        $message.=$sum['messageTable'];
        $message.='<br/>';
        //add other sections
        $message.=$messageErrorPaterns;
        $message.='<br/>';
        $message.=$messagePerfMap;

        //check does report needed to be send
        if ($this->report_params['send_on_event']==='sendAlways') {
            return $message;
        }
        if ($this->report_params['send_on_event']==='sendNever') {
            return null;
        }
        if ($this->report_params['send_on_event']==='sendOnAnyErrors') {
            if ($sum['outOfControlRuns']+$sum['failedRuns']>0) {
                return $message;
            } else {
                return null;
            }
        }
        if ($this->report_params['send_on_event']==='sendOnFailedRuns') {
            if ($sum['failedRuns']>0) {
                return $message;
            } else {
                return null;
            }
        }
        if ($this->report_params['send_on_event']==='sendOnPatternRecAnyErrors') {
            if ($pprecog->get_num_of_code_red()+$pprecog->get_num_of_code_yellow()>0) {
                return $message;
            } else {
                return null;
            }
        }
        if ($this->report_params['send_on_event']==='sendOnPatternRecFailedRuns') {
            if ($pprecog->get_num_of_code_red()>0) {
                return $message;
            } else {
                return null;
            }
        }
        /*$message.="\n<br/>\n<pre>\n"
            .print_r($perfMap,true).'<br/>'
            ."\n</pre>";*/
        return $message;
    }
}
