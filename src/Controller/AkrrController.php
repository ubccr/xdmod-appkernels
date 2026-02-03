<?php

namespace CCR\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;


/**
 * Class AkrrControllerProvider
 *
 * This class is responsible for maintaining the routes that pertain to
 * Application Kernels.
 *
 * @author Ryan Rathsam <ryanrath@buffalo.edu>
 */
#[Route('/akrr')]
class AkrrController extends BaseController
{
    private $token = null;

    /**
     * Retrieve an authentication / authorization token from the AKRR REST API.
     *
     * @param Request $request that will be used to collect the required information.
     * @return Response in the form (JSON):
     * {
     *   "message": <string>,
     *   "data": {
     *     "token": <string>
     *   },
     *   "success": <boolean>
     * }
     * @throws HttpInvalidParamException if the path or method
     */
    #[Route('/token', methods: ["GET"])]
    public function getToken(Request $request): Response
    {
        return $this
            ->json(
                $this->call($request, '/token', 'GET', false, false),
                200
            )
            ->setTtl(60);
    }

    /**
     * Retrieves a listing of the currently active resources from the AKRR REST API.
     *
     * @param Request $request that will be used to collect the required information.
     * @return Response in the form (JSON):
     * {
     *   "message": <string>,
     *   "data": [
     *     {
     *       "name": <string>,
     *       "id":   <int>
     *     }
     *   ],
     *   "success": <boolean>
     * }
     * @throws HttpInvalidParamException if the path cannot be found.
     */
    #[Route('/resources', methods: ["GET"])]
    public function getResources(Request $request): Response
    {
        return $this->json(
            $this->call($request, '/resources'),
            200
        );
    }

    /**
     * Retrieves a listing of the currently active Application Kernels from the AKRR REST API.
     *
     * @param Request $request that will be used to collect the required information.
     * @return Response in the form (JSON):
     * {
     *   "message": <string>,
     *   "data": [
     *     {
     *       "nodes_list": <string:csv>,
     *       "enabled": <boolean>,
     *       "id":   <int>
     *     }
     *   ],
     *   "success": <boolean>
     * }
     * @throws HttpInvalidParamException if the path cannot be found.
     */
    #[Route('/kernels', methods: ["GET"])]
    public function getKernels(Request $request)
    {
        return $this->json(
            $this->call($request, '/kernels'),
            200
        );
    }

    /**
     * Retrieves a listing of the contents of the scheduled_tasks table from the
     * AKRR REST API.
     *
     * @param Request $request that will be used to collect the required information.
     * @return Response in the form (JSON):
     * {
     *   "message": <string>,
     *   "data": [
     *     {
     *       "time_to_start": <timestamp:YYYY-MM-DD HH24:MI:SS>,
     *       "resource": <boolean>,
     *       "task_id": <int>,
     *       "app": <string>,
     *       "repeat_in": <timestamp: Y-MM-DDD HH24:MI:SS>,
     *       "task_param": <json object>,
     *       "resource_param": <json object>,
     *       "app_param": <json object>,
     *       "parent_task_id": <int>,
     *       "group_id": <string>,
     *     }
     *   ],
     *   "success": <boolean>
     * }
     * @throws HttpInvalidParamException if the path cannot be found.
     */
    #[Route('/tasks/scheduled', methods: ["GET"])]
    public function getTasks(Request $request)
    {
        return $this->json(
            $this->call($request, '/scheduled_tasks'),
            200
        );
    }

    /**
     * Attempts to create a new Scheduled Task from the parameters provided.
     *
     * @param Request $request that will be used to collect the required information.
     * @return Response in the form (JSON):
     * {
     *   "message": <string>,
     *   "success": <boolean>
     * }
     * @throws HttpInvalidParamException if the path cannot be found.
     */
    #[Route('/tasks/scheduled', methods: ["POST"])]
    public function createTask(Request $request): Response
    {
        $data = $this->_parseRestArguments($request, array(
            'repeat_in',
            'time_to_start',
            'resource',
            'app_kernel',
            'resource_param',
            'app_param',
            'task_param',
            'group_id'
        ));

        $data = $this->cleanUpData($data);

        $appKernel = $data['app_kernel'];
        unset($data['app_kernel']);
        $data['app'] = $appKernel;

        return $this->json(
            $this->call($request, '/scheduled_tasks', 'POST', $data),
            200
        );
    }

    /**
     * Attempts to update the Scheduled Task identified by the id route param with
     * the provided properties.
     *
     * @param Request $request that will be used to collect the required information.
     * @param int $id the id of the scheduled task that is to be updated.
     * @return Response in the form (JSON):
     * {
     *   "message": <string>,
     *   "success": <boolean>
     * }
     * @throws HttpInvalidParamException if the path cannot be found.
     */
    #[Route('/tasks/scheduled/{id}', requirements: ['id' => '\d+'], methods: ["PUT"])]
    public function updateTask(Request $request, int $id): Response
    {

        $data = $this->_parseRestArguments($request, array(
            'time_to_start',
            'repeat_in'
        ));

        $data = $this->cleanUpData($data);

        return $this->json(
            $this->call($request, "/scheduled_tasks/$id", 'POST', $data),
            200
        );
    }

    /**
     * Attempts to delete the Scheduled Task identified by the 'id' route param.
     *
     * @param Request $request that will be used to collect the required information.
     * @return Response in the form (JSON):
     * {
     *   "message": <string>,
     *   "success": <boolean>
     * }
     * @throws HttpInvalidParamException if the path cannot be found.
     */
    #[Route('/tasks/scheduled/{id}', requirements: ['id' => '\d+'], methods: ["DELETE"])]
    public function deleteTask(Request $request, int $id): Response
    {

        return $this->json(
            $this->call($request, "/scheduled_tasks/$id", 'DELETE'),
            200
        );
    }

    /**
     * Retrieves a listing of the current default wall limit records.
     *
     * @param Request $request that will be used to collect the required information.
     * @return Response in the form (JSON):
     * {
     *   "message": <string>,
     *   "data": [
     *     {
     *       "walllimit": <int>,
     *       "app": <string>,
     *       "comments": <string>,
     *       "last_update": <timestamp: YYYY-MM-DD HH24:MI:SS>,
     *       "resource": <string>,
     *       "id": <int>,
     *       "resource_param": <json object>,
     *       "app_param": <json object>
     *     }
     *   ],
     *   "success": <boolean>
     * }
     * @throws HttpInvalidParamException if the path cannot be found.
     */
    #[Route('/walltime', methods: ["GET"])]
    public function getWalltime(Request $request): Response
    {

        return $this->json(
            $this->call($request, '/walltime'),
            200
        );
    }

    /**
     * Attempts to create a new default wall time entry from the provided parameters.
     *
     * @param Request $request that will be used to collect the required information.
     * @return Response in the form (JSON):
     * {
     *   "message": <string>,
     *   "success": <boolean>
     * }
     * @throws HttpInvalidParamException if the path cannot be found.
     */
    #[Route('/walltime', methods: ["POST"])]
    public function createWalltime(Request $request)
    {
        $data = $this->_parseRestArguments($request, array(
            'resource',
            'app_kernel',
            'walltime',
            'resource_param',
            'app_param',
            'comments'
        ));

        $data = $this->cleanUpData($data);

        $resource = $data['resource'];
        $appKernel = $data['app_kernel'];

        unset($data['app_kernel']);

        return $this->json(
            $this->call($request, "/walltime/$resource/$appKernel", 'POST', $data),
            200
        );
    }

    /**
     * @param Request $request
     * @return Response
     * @throws HttpInvalidParamException
     */
    #[Route('/walltime', methods: ["PUT"])]
    public function updateWalltime(Request $request): Response
    {
        $data = $this->_parseRestArguments($request, array(
            'resource',
            'app_kernel',
            'walltime',
            'resource_param',
            'app_param',
            'comments'
        ));

        $data = $this->cleanUpData($data);

        $resource = $data['resource'];
        $appKernel = $data['app_kernel'];

        unset($data['app_kernel']);

        return $this->json(
            $this->call($request, "/walltime/$resource/$appKernel", 'POST', $data),
            200
        );
    }

    /**
     * Attempts to delete the default walltime limit record identified by the 'id' route param.
     *
     * @param Request $request that will be used to collect the required information.
     * @param int $id of the walltime to be deleted.
     * @return Response in the form (JSON):
     * {
     *   "message": <string>,
     *   "success": <boolean>
     * }
     * @throws HttpInvalidParamException if the path cannot be found.
     */
    #[Route('/walltime/{id}', requirements: ['id' => '\d+'], methods: ["DELETE"])]
    public function deleteWalltime(Request $request, int $id): Response
    {
        return $this->json(
            $this->call($request, "/walltime/$id", 'DELETE'),
            200
        );
    }

    /**
     * Retrieves a listing of the active_tasks table. These tasks should be currently running on a resource.
     *
     * @param Request $request that will be used to collect the required information.
     * @return Response in the form (JSON):
     * {
     *   "message": <string>,
     *   "data": [
     *     {
     *       "status_update_time": <timestamp: YYYY-MM-DD HH24:MI:SS>,
     *       "master_task_id": <int>,
     *       "app": <string>,
     *       "resource_param": <json object>,
     *       "task_lock": <int>,
     *       "datetime_stamp": <timestamp: YYYY.MM.DD.HH24.MI.SS.FF>,
     *       "time_submitted_to_queue": <timestamp: YYYY-MM-DD HH24:MI:SS>,
     *       "fatal_errors_count": <int>,
     *       "fails_to_submit_to_the_queue": <int>,
     *       "status": <string>,
     *       "next_time_check": <timestamp: YYYY-MM-DD HH24:MI:SS>,
     *       "time_to_start": <timestamp: YYYY-MM-DD HH24:MI:SS>,
     *       "status_info": <string>,
     *       "resource": <string>,
     *       "task_id": <int>,
     *       "time_activated": <timestamp: YYYY-MM-DD HH24:MI:SS>,
     *       "repeat_in": <timestamp: Y-MM-DDD HH24:MI:SS>,
     *       "group_id": <string>,
     *       "taskexeclog": <string?null>,
     *       "task_param": <json object>,
     *       "app_para": <json object
     *     }
     *   ],
     *   "success": <boolean>
     * }
     * @throws HttpInvalidParamException if the path cannot be found.
     */
    #[Route('/tasks/active', methods: ["GET"])]
    public function getActiveTasks(Request $request): Response
    {

        return $this->json(
            $this->call($request, '/active_tasks'),
            200
        );
    }

    /**
     * Attempts to update the Active Task identified by the 'id' route param.
     *
     * @param Request $request that will be used to collect the required information.
     * @param int $id the id of the Active Task to be updated.
     * @return Response in the form (JSON):
     * {
     *   "message": <string>,
     *   "success": <boolean>
     * }
     * @throws HttpInvalidParamException if the path cannot be found.
     */
    #[Route('/tasks/active/{id}', requirements: ['id' => '\d+'], methods: ["PUT"])]
    public function updateActiveTask(Request $request, int $id): Response
    {

        $data = $this->_parseRestArguments($request, array(
            'next_check_time'
        ));

        $data = $this->cleanUpData($data);

        return $this->json(
            $this->call($request, "/active_tasks/$id", 'PUT', $data),
            200
        );
    }

    /**
     * Attempt to delete the Active Task identified by the 'id' route param.
     *
     * @param Request $request that will be used to collect the required information.
     * @param int $id of the Active Task that is to be deleted.
     *
     * @return Response in the form (JSON):
     * {
     *   "message": <string>,
     *   "success": <boolean>
     * }
     */
    #[Route('/tasks/active/{id}', requirements: ['id' => '\d+'], methods: ["DELETE"])]
    public function deleteActiveTask(Request $request, int $id)
    {
        return $this->json(
            $this->call($request, "/active_tasks/$id", 'DELETE'),
            200
        );
    }

    /**
     * A helper method that strips the 'token' entry from the 'data' entry as
     * well as any entry that has an 'empty' value.
     *
     * @param array $data
     * @return array that was passed in, only "clean".
     */
    private function cleanUpData(array $data)
    {
        if (isset($data)) {
            unset($data['token']);
        }
        return array_filter($data, function ($value) {
            return $value !== '';
        });
    }

    /**
     * A convenience method that reduces the amount of boilerplate required to wire up an end point in this controller.
     * It handles: token management and base url management ( aka. host, port and end point of the AKRR REST API )
     * before passing control on to the callAPI method.
     *
     * @param Request $request that will be used to gather the information required to complete the requested action.
     * @param string $path to the unique AKRR REST endpoint that is to be called.
     * @param string $method that is to be used when calling the AKRR REST endpoint.
     * @param mixed $data
     * @param bool $useToken
     * @return array
     * @throws Exception
     * @throws HttpInvalidParamException
     * @throws \Exception
     */
    private function call(Request $request, $path, $method = 'GET', $data = null, $useToken = true)
    {
        if (!isset($path)) {
            throw new HttpInvalidParamException('A path is required for the requested operation.');
        }
        if (!isset($method)) {
            throw new HttpInvalidParamException('A method is required for the requested operation.');
        }

        $baseUrl = $this->getUrl();
        $url = "$baseUrl$path";

        if ($useToken) {

            if (!isset($this->token)) {
                $results = $this->call($request, '/token', 'GET', false, false);
                if (isset($results) && isset($results['data']) && isset($results['data']['token'])) {
                    $this->token = $results['data']['token'];
                }
            }
            if (!isset($this->token)) {
                throw new HttpException('Unable to retrieve the required information. Unable to process request.');
            }

            $curlResult = $this->callAPI($url, $this->token, null, $method, $data);
        } else {

            $username = \xd_utilities\getConfiguration('akrr', 'username');
            $password = \xd_utilities\getConfiguration('akrr', 'password');

            $curlResult = $this->callAPI($url, $username, $password, $method, $data, $useToken);
        }


        return $curlResult;
    }

    /**
     * Helper method that retrieves the current AKRR REST API url.
     *
     * @return string in the format: https://host:port/endPoint
     * @throws Exception if the settings file is not readable.
     * @throws \Exception if there is a problem retrieving data from the settings file.
     */
    private function getUrl()
    {
        $host = \xd_utilities\getConfiguration('akrr', 'host');
        $port = \xd_utilities\getConfiguration('akrr', 'port');
        $endPoint = \xd_utilities\getConfiguration('akrr', 'end_point');

        return "https://$host:$port/$endPoint";
    }

    /**
     * Provides a wrapper around the PHP curl library for making calls
     * to the AKRR REST API.
     *
     * @param string $path REST API path.
     * @param string $username
     * @param string $password
     * @param string $method HTTP verb (GET, POST, PUT, DELETE).
     * @param bool|array $data Data to send in request or false to
     *     indicate no data should be sent.
     * @param bool $useToken True if the token should be used for
     *     authentication.
     * @return array
     */
    private function callAPI(
        $path,
        $username,
        $password,
        $method = 'GET',
        $data = false,
        $useToken = true
    ) {


        $url = $path;

        $curl = curl_init();

        switch ($method) {
            case 'POST':
                curl_setopt($curl, CURLOPT_POST, 1);
                if ($data) {
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                }
                break;
            case 'PUT':
                curl_setopt($curl, CURLOPT_PUT, 1);
                if ($data) {
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                }
                break;
            case 'DELETE':
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
                if ($data) {
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                }
                break;
            default:
                // GET
                if ($data) {
                    $url = sprintf("%s?%s", $url, http_build_query($data));
                }
        }

        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

        if ($useToken) {
            curl_setopt($curl, CURLOPT_USERPWD, sprintf("%s:", $username));
        } else {
            curl_setopt($curl, CURLOPT_USERPWD, sprintf("%s:%s", $username, $password));
        }

        curl_setopt($curl, CURLOPT_UNRESTRICTED_AUTH, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HEADER, 0);

        $result = curl_exec($curl);

        $errno = curl_errno($curl);

        if ($errno == 0) {
            $result = json_decode($result, true);
        } else {
            $error = curl_error($curl);
            $info = curl_getinfo($curl);

            $result = array(
                'success' => false,
                'errno' => $errno,
                'error' => $error,
                'info' => $info,
            );
        }

        curl_close($curl);

        return $result;
    }

    /**
     * @param Request $request
     * @param array $arguments
     * @return array
     */
    private function _parseRestArguments(Request $request, array $arguments): array
    {
        $results = array();
        foreach($arguments as $argument) {
            $results[$argument] = $request->get($argument, '');
        }
        return $results;
    }
}
