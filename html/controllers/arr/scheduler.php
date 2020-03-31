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
    $user_id = $_SESSION['xdDashboardUser'];
    $pdo = DB::factory('database');
    $arr_db = DB::factory('akrr-db');
    $ak_db = new \AppKernel\AppKernelDb();
    switch($operation) {
        case 'get_task_schedule' :
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
            catch (Exception $e) {
                $response['success'] = false;
                $response['message'] = 'Can not complete query';
                $response['response'] = array();
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
