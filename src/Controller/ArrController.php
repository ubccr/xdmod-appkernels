<?php

namespace CCR\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\SecurityBundle\Security;
use Appkernel\Report;
use Rest\Controllers\AppKernelController;

use CCR\DB;

class ArrController extends BaseController
{

    #[Route('/controllers/arr.php', methods: ["GET", "POST"])]
    public function index(Request $request): Response
    {
        $this->authorize($request);

        $operation = $this->getStringParam($request, 'operation', true);

        switch ($operation) {
            case 'get_active_tasks':
                return $this->getActiveTasks($request);
            case 'get_errmsg':
                return $this->getErrMsg($request);
            case 'get_summary':
                return $this->getSummary($request);
        }

        return $this->json([
            'success' => false,
            'message' => 'Unknown Operation provided.'
        ]);
    }

    private function getActiveTasks(Request $request): Response
    {
        try {

            $db = DB::factory('akrr-db');

            $sql = '
        SELECT task_id, next_check_time, status, status_info,
            status_update_time, datetime_stamp, time_activated,
            time_submitted_to_queue, task_lock, time_to_start, repeat_in,
            resource, app, resource_param, app_param, task_param, group_id,
            fatal_errors_count, fails_to_submit_to_the_queue
        FROM active_tasks
        ORDER BY time_to_start DESC
    ';

            $returnData = array(
                'success'  => true,
                'response' => $db->query($sql),
            );
            $returnData['count'] = count($returnData['response']);

        } catch (\Exception $e) {
            $returnData = array(
                'success' => false,
                'message' => $e->getMessage(),
            );
        }

        return $this->json($returnData);
    }

    private function getErrMsg(Request $request): Response
    {
        try {

            $db = DB::factory('akrr-db');

            $sql = '
        SELECT appstdout, stderr, stdout, taskexeclog
        FROM akrr_errmsg
        WHERE task_id = ?
        LIMIT 1
    ';

            $returnData = array(
                'success'  => true,
                'response' => $db->query($sql, array($_REQUEST['task_id'])),
                'count'    => 1,
            );

        } catch (\Exception $e) {
            $returnData = array(
                'success' => false,
                'message' => $e->getMessage(),
            );
        }

        return $this->json($returnData);
    }

    private function getSummary(Request $request): Response
    {
        try {

            $db = DB::factory('akrr-db');

            $sql = 'SELECT COUNT(*) AS count FROM active_tasks';
            list($countRow) = $db->query($sql);
            $activeCount = $countRow['count'];

            // TODO: Refactor these queries.
            $sql = 'SELECT COUNT(*) AS count FROM active_tasks WHERE DATEDIFF(NOW(), time_submitted_to_queue) >= 1';
            list($countRow) = $db->query($sql);
            $queued1DayCount = $countRow['count'];

            $sql = 'SELECT COUNT(*) AS count FROM active_tasks WHERE DATEDIFF(NOW(), time_submitted_to_queue) >= 2';
            list($countRow) = $db->query($sql);
            $queued2DaysCount = $countRow['count'];

            $sql = 'SELECT COUNT(*) AS count FROM active_tasks WHERE DATEDIFF(NOW(), time_submitted_to_queue) >= 3';
            list($countRow) = $db->query($sql);
            $queued3DaysCount = $countRow['count'];

            $sql = 'SELECT COUNT(*) AS count FROM active_tasks WHERE DATEDIFF(NOW(), time_submitted_to_queue) >= 4';
            list($countRow) = $db->query($sql);
            $queued4DaysCount = $countRow['count'];

            $sql = 'SELECT COUNT(*) AS count FROM active_tasks WHERE DATEDIFF(NOW(), time_submitted_to_queue) >= 5';
            list($countRow) = $db->query($sql);
            $queued5DaysCount = $countRow['count'];

            $returnData = array(
                'success' => true,
                'response' => array(
                    array(
                        'active_count'        => $activeCount,
                        'queued_1_day_count'  => $queued1DayCount,
                        'queued_2_days_count' => $queued2DaysCount,
                        'queued_3_days_count' => $queued3DaysCount,
                        'queued_4_days_count' => $queued4DaysCount,
                        'queued_5_days_count' => $queued5DaysCount,
                    )
                ),
                'count' => 1,
            );

        } catch (\Exception $e) {
            $returnData = array(
                'success' => false,
                'message' => $e->getMessage(),
            );
        }
        return $this->json($returnData);
    }

    #[Route('/controllers/arr/scheduler.php')]
    public function getScheduler(Request $request, Security $security): Response
    {
        $this->authorize($request, array(ROLE_ID_MANAGER));

        $operation = $this->getStringParam($request, 'operation');

        $response = array();
        switch($operation) {
            case 'logout':
                $security->logout(false);

                $splashRedirect = $request->get('splash_redirect');
                if (isset($splashRedirect)) {
                    return $this->redirectToRoute('xdmod_home');
                }
                return $this->json(array(
                    'success' => true
                ));
            case 'get_task_schedule':
                return $this->getTaskSchedule($request);
            default:
                $response['success'] = false;
                $response['message'] = 'operation not recognized';
                $response['response'] = array();
        }
        return $this->json($response);
    }

    #[Route('/controllers/arr/send_report.php', methods: ["GET", "POST"])]
    private function sendReport(Request $request, Security $security): Response
    {
        $user = $this->authorize($request, array(ROLE_ID_MANAGER));

        $operation = $this->getStringParam($request, 'operation');

        switch($operation) {
            case 'logout':
                $security->logout(false);

                $splashRedirect = $request->get('splash_redirect');
                if (isset($splashRedirect)) {
                    return $this->redirectToRoute('xdmod_home');
                }
                return $this->json(array(
                    'success' => true
                ));
            case 'send_report':
                return $this->_sendReport($request, $user);
            default :
                return $this->json(array(
                    'success' => false,
                    'operation' => 'operation not recognized',
                    'response' => array()
                ));
        }

    }

    #[Route('/controllers/arr/settings.php', methods: ["GET", "POST"])]
    private function getSettings(Request $request, Security $security): Response
    {
        $user = $this->authorize($request, array(ROLE_ID_MANAGER));

        $operation = $this->getStringParam($request, 'operation');

        switch($operation) {
            case 'logout':
                $security->logout(false);

                $splashRedirect = $request->get('splash_redirect');
                if (isset($splashRedirect)) {
                    return $this->redirectToRoute('xdmod_home');
                }
                return $this->json(array(
                    'success' => true
                ));
            case 'get_resources_list':
                return $this->getResourcesList();
            case 'get_appkernels_list':
                return $this->getAppkernelsList();
            case 'save_notifications_settings':
                return $this->saveNotificationsSettings($user->getUserID());
            case 'load_notification_settings':
                return $this->loadNotificationSettings($user->getUserID());
            case 'load_default_notification_settings':
                return $this->loadDefaultNotificationSettings();
            default :
                return $this->json(array(
                    'success' => false,
                    'operation' => 'operation not recognized',
                    'response' => array()
                ));
        }
    }

    private function getTaskSchedule(Request $request)
    {
        $response['success'] = false;
        $response['message'] = "";
        $response['response'] = array();

        try{
            $arr_db = DB::factory('akrr-db');

            $scheduled_tasks=$arr_db->query('SELECT task_id,
                                            time_to_start,
                                            repeat_in,
                                            resource,
                                            app,
                                            resource_param,
                                            app_param,
                                            task_param,
                                            group_id,
                                            parent_task_id
                                    FROM scheduled_tasks'
            );
            $bundle=array();
            foreach ($scheduled_tasks as &$task) {
                $resource_param = json_decode(str_replace("'","\"",$task['resource_param']),true);
                $task['nnodes']=$resource_param['nnodes'];
                $task['appExt']=$task['app'];//for bundles

            }
            //handle bundles
            foreach ($scheduled_tasks as $key => $task) {
                if($task['app']==='xdmod.bundle'){
                    $bundle[$task['resource']][$task['app']][$task['task_param']][]=$key;
                }
            }
            foreach ($bundle as $l1) {
                foreach ($l1 as $l2) {
                    $i=1;
                    foreach ($l2 as $l3) {

                        foreach ($l3 as $key) {
                            $scheduled_tasks[$key]['appExt'].=' '.$i;
                        }
                        $i++;
                    }
                }
            }

            //sort
            $resource=array();
            $app=array();
            $nodes=array();
            foreach ($scheduled_tasks as $key => $task) {
                $resource[$key]=$task['resource'];
                $app[$key]=$task['appExt'];
                $nodes[$key]=$task['nnodes'];
            }
            array_multisort($resource, SORT_ASC,$app, SORT_ASC, $nodes, SORT_ASC, $scheduled_tasks);//, SORT_ASC,  $nodes)

            $response['response']=$scheduled_tasks;
            $response['count']=count($scheduled_tasks);
            $response['success'] = true;
            $response['message'] = '';
        }
        catch (\Exception $e) {
            $response['success'] = false;
            $response['message'] = 'Can not complete query';
            $response['response'] = array();
        }
        return $this->json($response);
    }

    private function _sendReport(Request $request, $user)
    {
        try{
            $recipient=$user->getEmailAddress();

            $report_type = $request->get('report_type');
            $startDateParam = $request->get('start_date');
            $endDateParam = $request->get('end_date');

            $start_date = isset($startDateParam) ?? new DateTime($startDateParam);
            $end_date = isset($startDateParam) ?? new DateTime($request->get('end_date'));

            $reportParam = $this->getStringparam('report_param');
            $report_param=json_decode($reportParam,true);

            AppKernelController::formatNotificationSettingsFromClient($report_param);


            $report=new Report(array(
                'start_date'=>$start_date,
                'end_date'=>$end_date,
                'report_type'=>$report_type,
                'report_params'=>$report_param
            ));

            try {
                $report->sendReportToEmail($recipient);
            }
            catch (\Exception $e) {
                $response['success'] = false;
                $response['message'] = $e->getMessage();
                echo json_encode($response);
                exit;
            }
            $response['success'] = true;
            $response['message'] = 'Report is send to '.$recipient;
        }
        catch (\Exception $e) {
            $response['success'] = false;
            $response['message'] = 'Can not send report. '.$e->getFile().':'.$e->getLine().' '.$e->getMessage();

        }
        return $this->json($response);
    }

    private function getResourcesList()
    {
        $ak_db = new \AppKernel\AppKernelDb();
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
        return $this->json(array(
            'response'=> $returnData,
            'success' => true,
            'message' => ''
        ));
    }

    private function getAppkernelsList()
    {
        try{
            $ak_db = new \AppKernel\AppKernelDb();
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
        catch (\Exception $e) {
            $response['success'] = false;
            $response['message'] = 'Can not complete SQL query';
            $response['response'] = array();
        }
        return $this->json($response);
    }

    private function saveNotificationsSettings($user_id)
    {
        try{
            $pdo = DB::factory('database');
            $curent_tmp_settings=json_decode($_REQUEST['curent_tmp_settings'],true);
            AppKernelController::formatNotificationSettingsFromClient($curent_tmp_settings);

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
        catch (\Exception $e) {
            $response['success'] = false;
            $response['errorMessage'] = 'Can not save notification_settings. '.$e->getMessage();
        }
        return $this->json($response);

    }

    private function loadNotificationSettings($user_id)
    {
        $pdo = DB::factory('database');
        if(isset($_REQUEST['curent_tmp_settings']))
            $curent_tmp_settings=json_decode($_REQUEST['curent_tmp_settings'],true);
        else
            throw new \Exception('curent_tmp_settings is not set');

        AppKernelController::formatNotificationSettingsFromClient($curent_tmp_settings,true);

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
            throw new \Exception('settings is not set in db use default');
        }
        AppKernelController::formatNotificationSettingsForClient($curent_tmp_settings);
        $response['data'] = $curent_tmp_settings;
        $response['success'] = true;
        return $this->json($response);
    }

    private function loadDefaultNotificationSettings()
    {
        try{
            $current_tmp_settings = $this->getStringParam('curent_tmp_settings', true)

            if(isset($current_tmp_settings))
                $curent_tmp_settings=json_decode($current_tmp_settings,true);
            else
                throw new \Exception('curent_tmp_settings is not set in templates');

            AppKernelController::formatNotificationSettingsFromClient($curent_tmp_settings,true);

            $curent_tmp_settings["controlThresholdCoeff"]='1.0';
            $curent_tmp_settings["resourcesList"]=array();//None means all
            $curent_tmp_settings["appkernelsList"]=array();//None means all
            AppKernelController::formatNotificationSettingsForClient($curent_tmp_settings);
            $response['data'] = $curent_tmp_settings;
            $response['success'] = true;
        }
        catch (\Exception $e) {
            $response['success'] = false;
            $response['errorMessage'] = 'Can not load load_default_notification_settings. '.$e->getMessage();
        }
        return $this->json($response);
    }


}
