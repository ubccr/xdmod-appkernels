<?php

    require_once dirname(__FILE__).'/../../../configuration/linker.php';

    use CCR\DB;
    use Rest\Controllers;

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
    $user_id = $_SESSION['xdDashboardUser'];
    $pdo = DB::factory('database');
    $arr_db = DB::factory('akrr-db');
    $ak_db = new \AppKernel\AppKernelDb();
    switch($operation) {
        case 'get_resources_list' :
            try{
                /*$sql = 'SELECT resource_id,nickname as name FROM mod_appkernel.resource
                    WHERE enabled=1
                    ORDER BY name ASC';
                $sqlres=$pdo->query($sql);*/
                $allResources = $ak_db->getResources(date_format(date_sub(date_create(), new DateInterval('P90D')),'Y-m-d'),date_format(date_create(),'Y-m-d'));
                $returnData = array();
                foreach($allResources as $resource)
                {
                    if($resource->visible != 1) continue;
                    $returnData[] =
                    array(
                        'id' => $resource->id,
                        'fullname' => $resource->name,
                        'name' => $resource->nickname
                        /*'disabled' =>  !isset($resources[$resource->nickname]),
                        'checked' => in_array($resource->id,$selectedResourceIds)*/
                    );
                }
                $response['response']=$returnData;
                $response['success'] = true;
                $response['message'] = '';
            }
            catch (Exception $e) {
                $response['success'] = false;
                $response['message'] = 'Can not complete SQL query';
                $response['response'] = array();
            }
            echo json_encode($response);
            break;
        case 'get_appkernels_list' :
            try{
                /*$sql = 'SELECT ak_def_id,name,ak_base_name as fullname FROM mod_appkernel.app_kernel_def
                    WHERE enabled=1
                    ORDER BY name ASC';
                $sqlres=$pdo->query($sql);*/
                $start_ts=date_timestamp_get(date_sub(date_create(), new DateInterval('P90D')));
                $end_ts=date_timestamp_get(date_create());

                $all_app_kernels = $ak_db->getUniqueAppKernels();
                $returnData=array();
                foreach($all_app_kernels as $app_kernel)
                {
                    //print_r($app_kernel);
                    if($app_kernel->end_ts > $start_ts)
                        $returnData[] = array('name' => $app_kernel->name,
                            'id' => 'app_kernel_'.$app_kernel->id,
                            'end_ts' => $app_kernel->end_ts
                            );
                }
                $response['response'] = $returnData;
                $response['success'] = true;
                $response['message'] = '';
            }
            catch (Exception $e) {
                $response['success'] = false;
                $response['message'] = 'Can not complete SQL query';
                $response['response'] = array();
            }
            echo json_encode($response);
            break;
        case 'save_notification_settings' :
            try{
                $curent_tmp_settings=json_decode($_REQUEST['curent_tmp_settings'],true);
                AppKernelControllerProvider::formatNotificationSettingsFromClient($curent_tmp_settings);

                $send_report_daily=($curent_tmp_settings['daily_report']['send_on_event']==='sendNever')?(0):(1);
                $send_report_weekly=($curent_tmp_settings['weekly_report']['send_on_event']==='sendNever')?(-$curent_tmp_settings['weekly_report']['send_on']):($curent_tmp_settings['weekly_report']['send_on']);
                $send_report_monthly=($curent_tmp_settings['monthly_report']['send_on_event']==='sendNever')?(-$curent_tmp_settings['monthly_report']['send_on']):($curent_tmp_settings['monthly_report']['send_on']);

                $sqlres=$pdo->query('SELECT user_id,send_report_daily,send_report_weekly,send_report_monthly,settings
                                        FROM mod_appkernel.report
                                        WHERE user_id=:user_id',
                                    array(':user_id'=>$user_id));

                if(count($sqlres)==0){
                    $sqlres=$pdo->insert('INSERT INTO mod_appkernel.report (user_id,send_report_daily,send_report_weekly,send_report_monthly,settings)
                                            VALUES (:user_id,:send_report_daily,:send_report_weekly,:send_report_monthly,:settings)',
                                        array(
                                            ':user_id'=>$user_id,
                                            ':send_report_daily'=>$send_report_daily,
                                            ':send_report_weekly'=>$send_report_weekly,
                                            ':send_report_monthly'=>$send_report_monthly,
                                            ':settings'=>json_encode($curent_tmp_settings)//str_replace('"',"'",json_encode($curent_tmp_settings))
                    ));
                }
                else{
                    $sqlres=$pdo->execute('UPDATE mod_appkernel.report
                                            SET send_report_daily=:send_report_daily,send_report_weekly=:send_report_weekly,
                                                send_report_monthly=:send_report_monthly,settings=:settings
                                            WHERE user_id=:user_id',
                                        array(
                                            ':user_id'=>$user_id,
                                            ':send_report_daily'=>$send_report_daily,
                                            ':send_report_weekly'=>$send_report_weekly,
                                            ':send_report_monthly'=>$send_report_monthly,
                                            ':settings'=>json_encode($curent_tmp_settings)//str_replace('"',"'",json_encode($curent_tmp_settings))
                    ));
                }
                $response['data'] = array();
                $response['success'] = true;

            }
            catch (Exception $e) {
                $response['success'] = false;
                $response['errorMessage'] = 'Can not save notification_settings. '.$e->getMessage();
            }
            echo json_encode($response);
            break;
        case 'load_notification_settings' :
            try{
                if(isset($_REQUEST['curent_tmp_settings']))
                    $curent_tmp_settings=json_decode($_REQUEST['curent_tmp_settings'],true);
                else
                    throw new Exception('curent_tmp_settings is not set');

                AppKernelControllerProvider::formatNotificationSettingsFromClient($curent_tmp_settings,true);

                $sqlres=$pdo->query('SELECT user_id,send_report_daily,send_report_weekly,send_report_monthly,settings
                                        FROM mod_appkernel.report
                                        WHERE user_id=:user_id',
                                    array(':user_id'=>$user_id));

                if(count($sqlres)==1){
                    $sqlres=$sqlres[0];
                    $settings=json_decode($sqlres['settings'],true);
                    foreach ($settings as $key => $value) {
                        $curent_tmp_settings[$key]=$value;
                    }
                }
                else{
                    throw new Exception('settings is not set in db use default');
                }
                AppKernelControllerProvider::formatNotificationSettingsForClient($curent_tmp_settings);
                $response['data'] = $curent_tmp_settings;
                $response['success'] = true;
                echo json_encode($response);
                break;
            }
            catch (Exception $e) {
                //i.e. setting is not saved by user so send defaults(notice there is no break)
            }
        case 'load_default_notification_settings' :
            try{
                if(isset($_REQUEST['curent_tmp_settings']))
                    $curent_tmp_settings=json_decode($_REQUEST['curent_tmp_settings'],true);
                else
                    throw new Exception('curent_tmp_settings is not set in templates');

                AppKernelControllerProvider::formatNotificationSettingsFromClient($curent_tmp_settings,true);

                $curent_tmp_settings["controlThresholdCoeff"]='1.0';
                $curent_tmp_settings["resourcesList"]=array();//None means all
                $curent_tmp_settings["appkernelsList"]=array();//None means all
                AppKernelControllerProvider::formatNotificationSettingsForClient($curent_tmp_settings);
                $response['data'] = $curent_tmp_settings;
                $response['success'] = true;
            }
            catch (Exception $e) {
                $response['success'] = false;
                $response['errorMessage'] = 'Can not load load_default_notification_settings. '.$e->getMessage();
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
