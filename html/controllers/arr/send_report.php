<?php

    require_once dirname(__FILE__).'/../../../configuration/linker.php';

    use CCR\DB;

    @session_start();

    $response = array();

    $operation = isset($_REQUEST['operation']) ? $_REQUEST['operation'] : '';

    if ($operation == 'logout') {

      unset($_SESSION['xdDashboardUser']);
      $response['success'] = true;

      if (isset($_REQUEST['splash_redirect'])) {
         print "<html><head><script language='JavaScript'>top.location.href='../index.php';</script></head></html>";
          }
          else {
             echo json_encode($response);
          }

          exit;
    }
    xd_security\enforceUserRequirements(array(STATUS_LOGGED_IN, STATUS_MANAGER_ROLE), 'xdDashboardUser');

    // =====================================================
    $response['success'] = false;
    $response['message'] = "";
    $response['response'] = array();

    $user = XDUser::getUserByID($_SESSION['xdDashboardUser']);

    switch($operation) {
        case 'send_report' :
            /*=====================================================================send_report===================================*/
            try{
                $recipient=$user->getEmailAddress();

                $report_type = $_REQUEST['report_type'];

                if(isset($_REQUEST['start_date']))
                    $start_date = new DateTime($_REQUEST['start_date']);
                else
                    $start_date = null;

                $end_date = new DateTime($_REQUEST['end_date']);

                $report_param=json_decode($_REQUEST['report_param'],true);
                formatNotificationSettingsFromClient($report_param);
                //print_r($report_param);
                //throw new Exception(print_r($report_param,true));


                $report=new ARR_Report(array(
                    'start_date'=>$start_date,
                    'end_date'=>$end_date,
                    'report_type'=>$report_type,
                    'report_params'=>$report_param
                    //'resource'=>$report_param['resourcesList'],
                    //'appKer'=>$report_param['appkernelsList'],
                    //'controlThresholdCoeff'=>$report_param['controlThresholdCoeff']
                    //'report_param'=>$_REQUEST['report_param']
                ));

                try {
                    $report->send_report_to_email($recipient);
                }
                catch (Exception $e) {
                    $response['success'] = false;
                    $response['message'] = $e->getMessage();
                    echo json_encode($response);
                    exit;
                }
                $response['success'] = true;
                $response['message'] = 'Report is send to '.$recipient;
            }
            catch (Exception $e) {
                $response['success'] = false;
                $response['message'] = 'Can not send report. '.$e->getFile().':'.$e->getLine().' '.$e->getMessage();

            }
            echo json_encode($response);
            break;
        default :
            $response['success'] = false;
            $response['message'] = 'operation not recognized';
            $response['response'] = array();
            print json_encode($response);
            break;
    }//switch

    // =====================================================



?>
