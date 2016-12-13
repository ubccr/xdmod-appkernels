<?php
/**
 * Class that provides REST Endpoints for AKRR.
 *
 * @author Ryan Rathsam
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace AKRR;

require_once 'Log.php';

use Exception;
use xd_utilities;

class AKRR extends \aRestAction
{

    /**
     * Logger object.
     *
     * @var \Log
     */
    protected $logger;

    /**
     * AKRR Server host name.
     *
     * @var string
     */
    protected $host;

    /**
     * AKRR Server port number
     *
     * @var int
     */
    protected $port;

    /**
     * AKRR Server REST end point.
     *
     * @var string
     */
    protected $endPoint;

    /**
     * AKRR Server REST API URL.
     *
     * @var string
     */
    protected $url;

    /**
     * AKRR Server REST API username.
     *
     * @var string
     */
    protected $username;

    /**
     * AKRR Server REST API password.
     *
     * @var string
     */
    protected $password;

    /**
     * AKRR Server REST API token.
     *
     * @var string
     */
    protected $token;

    /**
     * Constructor.
     */
    public function __construct($request)
    {
        parent::__construct($request);

        $params = $this->_parseRestArguments("");
        $verbose = (isset($params['debug']) && $params['debug']);
        $maxLogLevel = ($verbose ? PEAR_LOG_DEBUG : PEAR_LOG_INFO);
        $logConf = array('mode' => 0644);
        $logfile = LOG_DIR . "/" . \xd_utilities\getConfiguration('general', 'rest_general_logfile');
        $this->logger = \Log::factory('file', $logfile, 'AppKernel', $logConf, $maxLogLevel);

        $this->host = xd_utilities\getConfiguration('akrr', 'host');
        $this->port = xd_utilities\getConfiguration('akrr', 'port');
        $this->endPoint = xd_utilities\getConfiguration('akrr', 'end_point');
        $this->username = xd_utilities\getConfiguration('akrr', 'username');
        $this->password = xd_utilities\getConfiguration('akrr', 'password');

        $this->url = 'https://' . $this->host . ':' . $this->port . '/' . $this->endPoint;

        $this->updateToken();
    }

    /**
     * @see aRestAction::factory()
     */
    public function factory($request)
    {
        return new AKRR($request);
    }

    /**
     * @see aRestAction::__call()
     */
    public function __call($target, $arguments)
    {
        $method = $target . ucfirst($this->_operation);

        if (!method_exists($this, $method)) {

            if ($this->_operation == 'Help') {

                $documentationMethod = $target . 'Documentation';
                if (!method_exists($this, $documentationMethod)) {

                    throw new Exception("Help cannot be found for action '$target'");
                }

                return $this->$documentationMethod()->getRESTResponse();
            } else {
                throw new Exception("Unknown action '$target' in category '" . strtolower(__CLASS__) . "'");
            } // if ( ! method_exists($this, $method) )
        }

        return $this->$method($arguments);
    } // __call($target, $arguments)

    /**
     * Get a list of resources.
     */
    public function resourcesAction()
    {
        $this->_authenticateUser(ROLE_ID_MANAGER);
        header('Content-Type: application/json');
        return $this->callAPI('/resources', 'GET');
    }

    /**
     * Get resources documentation.
     */
    public function resourcesDocumentation()
    {
        $doc = new \RestDocumentation();
        $doc->setDescription('Retrieve the resource list from the AKRR REST API.');
        $doc->setAuthenticationRequirement(true);
        return $doc;
    }

    /**
     * Get a list of app kernels.
     */
    public function kernelsAction()
    {
        $this->_authenticateUser(ROLE_ID_MANAGER);
        $params = $this->_parseRestArguments('', 'disabled');

        $data = false;

        if (isset($params['disabled'])) {
            $data = array('disabled' => $params['disabled']);
        }

        header('Content-Type: application/json');
        return $this->callAPI('/kernels', 'GET', $data);
    }

    /**
     * Get kernels documentation.
     */
    public function kernelsDocumentation()
    {
        $doc = new \RestDocumentation();
        $doc->setDescription('Retrieve the app kernel list from the AKRR REST API.');
        $doc->addArgument('disabled', 'True if disabled app kernels should be included.', false);
        $doc->setAuthenticationRequirement(true);
        return $doc;
    }

    /**
     * Create an AKRR scheduled task.
     */
    public function createTaskAction()
    {
        $this->_authenticateUser(ROLE_ID_MANAGER);

        $requiredParams = array(
            'resource',
            'app',
            'resource_param',
        );
        $optionalParams = array(
            'time_to_start',
            'repeat_in',
            'app_param',
            'task_param',
            'group_id',
        );
        $params = $this->_parseRestArguments(
            implode('/', $requiredParams),
            implode('/', $optionalParams)
        );

        // Remove XDMOD REST token.
        unset($params['token']);

        // Remove any parameters with empty string values.
        $params = array_filter($params, function ($v) {
            return $v !== '';
        });

        $response = $this->callAPI('/scheduled_tasks', 'POST', $params);

        header('Content-Type: application/json');
        return $response;
    }

    /**
     * Get "createTask" documentation.
     */
    public function createTaskDocumentation()
    {
        $doc = new \RestDocumentation();
        $doc->setDescription('Create a scheduled task using the AKRR REST API.');
        $doc->addArgument('task_id', 'The id of the task to update', true);
        $doc->addArgument('resource', 'The task resource', true);
        $doc->addArgument('app', 'The task application', true);
        $doc->addArgument('resource_param', 'The resource parameters', true);
        $doc->addArgument('time_to_start', 'Time to start the task');
        $doc->addArgument('repeat_in', 'Repeat frequency');
        $doc->addArgument('app_param', 'Application parameters');
        $doc->addArgument('task_param', 'Task parameters');
        $doc->addArgument('group_id', 'Group id');
        $doc->setAuthenticationRequirement(true);
        return $doc;
    }

    /**
     * Update an AKRR scheduled task.
     */
    public function updateTaskAction()
    {
        $this->_authenticateUser(ROLE_ID_MANAGER);

        $requiredParams = array(
            'task_id',
        );
        $optionalParams = array(
            'time_to_start',
            'repeat_in',
        );
        $params = $this->_parseRestArguments(
            implode('/', $requiredParams),
            implode('/', $optionalParams)
        );

        $id = $params['task_id'];
        unset($params['task_id']);

        // Remove XDMOD REST token.
        unset($params['token']);

        // Remove any parameters with empty string values.
        $params = array_filter($params, function ($v) {
            return $v !== '';
        });

        $response = $this->callAPI('/scheduled_tasks/' . $id, 'POST', $params);

        header('Content-Type: application/json');
        return $response;
    }

    /**
     * Get "updateTask" documentation.
     */
    public function updateTaskDocumentation()
    {
        $doc = new \RestDocumentation();
        $doc->setDescription('Update a scheduled task using the AKRR REST API.');
        $doc->addArgument('task_id', 'The id of the task to update', true);
        $doc->addArgument('time_to_start', 'Time to start the task');
        $doc->addArgument('repeat_in', 'Repeat frequency');
        $doc->setAuthenticationRequirement(true);
        return $doc;
    }

    /**
     * Delete an AKRR scheduled task.
     */
    public function deleteTaskAction()
    {
        $this->_authenticateUser(ROLE_ID_MANAGER);

        $params = $this->_parseRestArguments('task_id');

        $url = '/scheduled_tasks/' . $params['task_id'];
        $method = 'DELETE';

        $response = $this->callAPI(
            $url,
            $method
        );

        header('Content-Type: application/json');
        return $response;
    }

    /**
     * Get "deleteTask" documentation.
     */
    public function deleteTaskDocumentation()
    {
        $doc = new \RestDocumentation();
        $doc->setDescription('Delete a scheduled task using the AKRR REST API.');
        $doc->addArgument('task_id', 'The id of the task to delete', true);
        $doc->setAuthenticationRequirement(true);
        return $doc;
    }

    /**
     * Retrieve the walltime information from the App Kernel REST API.
     *
     * @return array
     * @throws Exception
     */
    public function walltime()
    {
        $this->_authenticateUser(ROLE_ID_MANAGER);

        header('Content-Type: application/json');
        return $this->callAPI('/walltime', 'GET');
    }

    public function walltimeDocumentation()
    {
        $doc = new \RestDocumentation();
        $doc->setDescription('Retrieve the walltime information from the App Kernel REST API.');
        $doc->setAuthenticationRequirement(true);
        return $doc;
    }

    public function createWalltime()
    {
        $this->_authenticateUser(ROLE_ID_MANAGER);

        $requiredParams = array(
            'resource',
            'app',
            'walltime',
            'resource_param',
            'app_param'
        );
        $params = $this->_parseRestArguments(implode('/', $requiredParams));

        $url = '/walltime/' . $params['resource'] . '/' . $params['app'];
        $method = 'POST';

        if (isset($params['token'])) {
            unset($params['token']);
        }
        header('Content-Type: application/json', true);
        return $this->callAPI($url, $method, $params);
    }

    public function createWalltimeDocumentation()
    {
        $doc = new \RestDocumentation();
        $doc->setDescription('Create a new default walltime entry for the provided parameters.');
        $doc->addArgument('resource', 'The resource for which the new default walltime will apply.', true);
        $doc->addArgument('app', 'The application kernel for which the new default walltime will apply.', true);
        $doc->addArgument('walltime', 'The amount of time in minutes.', true);
        $doc->addArgument('resource_param', 'The json-encoded number of nodes to which this walltime will apply.', true);
        $doc->addArgument('app_param', 'An empty string for now, saved for further use.', true);
        $doc->setAuthenticationRequirement(true);
        return $doc;
    }

    public function deleteWalltime()
    {
        $this->_authenticateUser(ROLE_ID_MANAGER);

        $requiredParams = array(
            'id'
        );
        $params = $this->_parseRestArguments(implode('/', $requiredParams));
        $id = $params['id'];
        if (!isset($id) || $id === '') {
            header('Content-Type: application/json', true, 400);
            $result = array(
                "message" => "Must provide a value for the id parameter.",
                "success" => false,
                "action" => "deleteWalltime"
            );
            return json_encode($result);
        }
        $url = '/walltime/' . $id;
        $method = 'DELETE';

        header('Content-Type: application/json', true);
        return $this->callAPI($url, $method);
    }

    public function deleteWalltimeDocumentation()
    {
        $doc = new \RestDocumentation();
        $doc->setDescription('Create a new walltime entry for the ');
        $doc->setAuthenticationRequirement(true);
        return $doc;
    }

    public function activeTasks()
    {
        $this->_authenticateUser(ROLE_ID_MANAGER);

        header('Content-Type: application/json', true);
        return $this->callAPI('/active_tasks', 'GET');
    }

    public function activeTasksDocumentation()
    {
        $doc = new \RestDocumentation();
        $doc->setDescription('Retrieve the current active Application Kernel tasks.');
        $doc->setAuthenticationRequirement(true);
        return $doc;
    }

    public function updateActiveTask()
    {
        $this->_authenticateUser(ROLE_ID_MANAGER);
        $requiredParams = array(
            'task_id',
            'next_check_time'
        );
        $params = $this->_parseRestArguments(implode('/', $requiredParams));

        $id = $params['task_id'];
        if (!isset($id) || $id === '') {
            header('Content-Type: application/json', true, 400);
            $result = array(
                "message" => "Must provide a value for the id parameter.",
                "success" => false,
                "action" => "updateActiveTask"
            );
            return json_encode($result);
        }
        $this->logger->info('Updating Active Task: ' + $id);

        return $this->callAPI('/active_tasks/' . $id, 'PUT', $params);
    }

    public function updateActiveTaskDocumentation()
    {
        $doc = new \RestDocumentation();
        $doc->setDescription('Update the Active Task identified by the provided task_id with the given Next Check Time.');
        $doc->addArgument('task_id', 'The unique identifier for the active task in question.', true);
        $doc->addArgument('next_check_time', 'The next time at which the active task will be checked.', true);
        $doc->setAuthenticationRequirement(true);
        return $doc;
    }

    /**
     * Attempt to retrieve a token from the AKRR REST API.
     * - Is the user authorized to access the AKRR REST API?
     * - If so then at what level are they allowed access ( rw, ro )
     * - Retrieve appropriate creds from configuration.
     * - submit token request to appropriate AKRR REST API url.
     * - pass along any error code that arrises other than 200.
     * - return the token.
     */
    private function updateToken()
    {
        $response = $this->callAPI('/token', 'GET', false, false);

        if (isset($response['success']) && !$response['success']) {
            throw new Exception('Failed to get token: ' . $response['error']);
        }

        if (!isset($response['data']) || !isset($response['data']['token'])) {
            throw new Exception('No token found in response.');
        }

        $this->token = $response['data']['token'];
    }

    /**
     * Provides a wrapper around the PHP curl library for making calls
     * to the AKRR REST API.
     *
     * @param string $path REST API path.
     * @param string $method HTTP verb (GET, POST, PUT, DELETE).
     * @param bool|array $data Data to send in request or false to
     *     indicate no data should be sent.
     * @param bool $useToken True if the token should be used for
     *     authentication.
     *
     * @return array
     */
    private function callAPI(
        $path,
        $method = 'GET',
        $data = false,
        $useToken = true
    )
    {
        $url = $this->url . $path;

        $this->logger->info("$method $url");

        if ($data !== false) {
            $this->logger->info('... with data: ' . json_encode($data));
        }

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

        if (isset($this->token) && $useToken) {
            curl_setopt($curl, CURLOPT_USERPWD, sprintf("%s:", $this->token));
        } else {
            curl_setopt($curl, CURLOPT_USERPWD, sprintf("%s:%s", $this->username, $this->password));
        }

        curl_setopt($curl, CURLOPT_UNRESTRICTED_AUTH, TRUE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HEADER, 0);

        $result = curl_exec($curl);

        $errno = curl_errno($curl);

        if ($errno == 0) {
            $this->logger->debug('No curl errors');
            $result = json_decode($result, true);
        } else {
            $error = curl_error($curl);
            $info = curl_getinfo($curl);

            $this->logger->err("Found curl error: $error");
            $this->logger->err('Curl info: ' . json_encode($info));

            $result = array(
                'success' => false,
                'errno' => $errno,
                'error' => $error,
                'info' => $info,
            );
        }

        curl_close($curl);

        header('Content-Type: application/json', true);
        return $result;
    }
}
