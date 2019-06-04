<?php
namespace Rest\Controllers;

use DataWarehouse\Access\MetricExplorer;
use DateTime;
use Silex\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Response;

use Exception;
use CCR\DB;
use AppKernel\Report;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * Class AppKernelControllerProvider
 *
 * This class is responsible for maintaining routes for the REST stack that
 * handle app kernel-related functionality.
 */
class AppKernelControllerProvider extends BaseControllerProvider
{
    // Tree node types, shown here in hierarchical order
    //
    // Ported from: classes/REST/Appkernel/Explorer.php
    const TREENODE_APPKERNEL = "appkernel";
    const TREENODE_RESOURCE = "resource";
    const TREENODE_METRIC = "metric";
    const TREENODE_UNITS = "units";
    const TREENODE_INSTANCE = "instance";

    const DEFAULT_DELIM=',';

    /**
     * @see BaseControllerProvider::setupRoutes
     */
    public function setupRoutes(Application $app, \Silex\ControllerCollection $controller)
    {

        $root = $this->prefix;
        $class = get_class($this);

        $controller->get("$root/details", "$class::getDetails");
        $controller->get("$root/datasets", "$class::getDatasets");
        $controller->get("$root/plots", "$class::getPlots");
        $controller->get("$root/control_regions", "$class::getControlRegions");
        $controller->post("$root/control_regions", "$class::createOrUpdateControlRegions")
            ->value("update", false);
        $controller->put("$root/control_regions", "$class::createOrUpdateControlRegions")
            ->value("update", true);
        $controller->delete("$root/control_regions", "$class::deleteControlRegions");

        $controller->get("$root/notifications", "$class::getNotifications");
        $controller->put("$root/notifications", "$class::putNotifications");
        $controller->get("$root/notifications/default", "$class::getDefaultNotifications");
        $controller->get("$root/notifications/send", "$class::sendNotification");

        $controller->get("$root/resources", "$class::getResources");
        $controller->get("$root/app_kernels", "$class::getAppKernels");

        $controller->get("$root/performance_map", "$class::getPerformanceMap");
        $controller->get("$root/success_rate", "$class::getAppKernelSuccessRate");

        $controller->get("$root/performance_map/raw", "$class::getRawPerformanceMap");
    }

    /**
     * Retrieve information about various aspects of app kernels.
     *
     * This is built around creating tree nodes for the browser client.
     *
     * Ported from: classes/REST/Appkernel/Explorer.php
     *
     * @param  Request     $request The request used to make this call.
     * @param  Application $app     The router application.
     * @return Response Response data containing the following info:
     *                              success: A boolean indicating if the call was successful.
     * results: The requested information.
     * @throws Exception
     */
    public function getDetails(Request $request, Application $app)
    {
        $results = array();
        $db = new \AppKernel\AppKernelDb();

        // Extract the parameters that were sent

        $debugMode = $this->getBooleanParam($request, 'debug', false, false);

        $akId = $this->getIntParam($request, 'ak');
        $resourceId = $this->getIntParam($request, 'resource');
        $instanceId = $this->getIntParam($request, 'instance_id');
        $metricId = $this->getIntParam($request, 'metric');
        $numProcUnits = $this->getIntParam($request, 'num_proc_units');
        $collected = $this->getIntParam($request, 'collected');

        $startTime = $this->convertDateTime(
            $this->getDateTimeFromUnixParam($request, 'start_time')
        );
        $endTime = $this->convertDateTime(
            $this->getDateTimeFromUnixParam($request, 'end_time'),
            true
        );

        // Default to showing only successful runs but in debug mode show everything

        $status = $this->getStringParam(
            $request,
            'status',
            false,
            $debugMode ? null : 'success'
        );

        // Debug mode does not show metrics

        $groupBy = (null !== $numProcUnits ? null
            : (null !== $metricId ? "num_proc_units"
                : (null !== $resourceId ? ($debugMode ? "num_proc_units" : "metric")
                    : (null !== $akId ? "resource"
                        : "ak"))));
        $resource_first = $this->getBooleanParam($request, 'resource_first', false, false);

        if ($resource_first) {
            $groupBy = (null !== $numProcUnits ? null
                : (null !== $metricId ? "num_proc_units"
                    : (null !== $akId ? ($debugMode ? "num_proc_units" : "metric")
                        : (null !== $resourceId ? "ak"
                            : "resource"))));
        }

        // Enforce the parameters in the hierarchy
        if ($resource_first) {
            if (($akId && !$resourceId) ||
                ($metricId && !($akId && $resourceId)) ||
                ($numProcUnits && !($akId && $resourceId))
            ) {
                $msg = "Did not specify all levels of the hierarchy";
                throw new Exception($msg);
            }
        } else {
            if (($resourceId && !$akId) ||
                ($metricId && !($akId && $resourceId)) ||
                ($numProcUnits && !($akId && $resourceId))
            ) {
                $msg = "Did not specify all levels of the hierarchy";
                throw new Exception($msg);
            }
        }

        // Determine the node type. Debug mode does not show metrics
        if ($resource_first) {
            $nodeType = self::TREENODE_RESOURCE;
        } else {
            $nodeType = self::TREENODE_APPKERNEL;
        }

        if (null !== $collected || null !== $instanceId) {
            $nodeType = null;
        } elseif (($debugMode || null !== $metricId) &&
            null !== $resourceId && null !== $akId && null !== $numProcUnits
        ) {
            $nodeType = self::TREENODE_INSTANCE;
        } elseif (null !== $metricId && null !== $resourceId && null !== $akId) {
            $nodeType = self::TREENODE_UNITS;
        } elseif (null !== $resourceId && null !== $akId) {
            $nodeType = ($debugMode ? self::TREENODE_UNITS : self::TREENODE_METRIC);
        } else {
            if ($resource_first) {
                if (null !== $resourceId) {
                    $nodeType = self::TREENODE_APPKERNEL;
                }

            } else {
                if (null !== $akId) {
                    $nodeType = self::TREENODE_RESOURCE;
                }
            }
        }


        // Load up the data

        if (null !== $nodeType) {
            $restrictions = array('ak' => $akId,
                'resource' => $resourceId,
                'metric' => $metricId, //AG 9/6/12 added to fix expand bug
                'num_units' => $numProcUnits,
                'start' => $startTime,
                'end' => $endTime,
                'group_by' => $groupBy,
                'debug' => $debugMode,
                'status' => $status);
            $retval = $db->loadTreeLevel($restrictions);

            foreach ($retval as $row) {
                $node = $this->createTreeNode($nodeType, $row, $resource_first);
                $node->leaf = (($debugMode && self::TREENODE_INSTANCE == $nodeType) ||
                    (!$debugMode && self::TREENODE_UNITS == $nodeType));
                $results[] = $node;
            }  // foreach ( $retval as $row )
        } else {
            $ak = new \AppKernel\InstanceData;

            $akOptions = array('ak_def_id' => $akId,
                'collected' => $collected,
                'resource_id' => $resourceId,
                'num_units' => $numProcUnits,
                'instance_id' => $instanceId);
            $db->loadAppKernelInstanceInfo($akOptions, $ak, true);
            $results[] = $ak->toHtml();
        }

        // Return the found information.
        return $app->json(array(
            'success' => true,
            'results' => $results,
        ));
    }

    /**
     * Retrieve one or more datasets about application kernels.
     *
     * Ported from: classes/REST/Appkernel/Explorer.php
     *
     * @param Request     $request The request used to make this call.
     * @param Application $app     The router application.
     * @param boolean $returnRawData (Optional) If true, returns the data
     *                                    without converting it to a format.
     *                                    (Defaults to false.)
     * @return array                Response data containing the following info:
     *                              success: A boolean indicating if the call was successful.
     *                              results: The requested datasets.
     * @throws Exception
     */
    public function getDatasets(Request $request, Application $app, $returnRawData = false)
    {
        $results = array();
        $db = new \AppKernel\AppKernelDb();

        // Extract the parameters that were sent

        // The metric parameter may be either the ID of a metric or the name
        // of a metric. Check for the former first and the latter if that fails.
        $metricId = null;
        try {
            $metricId = $this->getIntParam($request, 'metric');
        } catch (Exception $e) {
            // This block will only be entered if the parameter was present
            // but its value could not be converted to an integer.
            $metricName = $this->getStringParam($request, 'metric');

            if ($metricName !== null) {
                $sql = "SELECT metric_id FROM metric WHERE LOWER(name)=LOWER(:name);";
                $result = $db->getDB()->query($sql, array(
                    ':name' => $metricName,
                ));
                if (count($result) === 1) {
                    $metricId = $result[0]['metric_id'];
                }
            }
        }

        $akId = $this->getIntParam($request, 'ak', true);
        $resourceId = $this->getIntParam($request, 'resource');
        $numProcUnits = $this->getIntParam($request, 'num_proc_units');
        $debugMode = $this->getBooleanParam($request, 'debug', false, false);

        $startTime = $this->convertDateTime(
            $this->getDateTimeFromUnixParam($request, 'start_time')
        );
        // Bug # 1342
        // bump the end time, if assigned, to just before midnight of the next day,
        // so all kernels from the current day display.
        $endTime = $this->convertDateTime(
            $this->getDateTimeFromUnixParam($request, 'end_time'),
            true
        );

        $metadataOnly = $this->getBooleanParam($request, 'metadata_only', false, false);
        $inline = $this->getBooleanParam($request, 'inline', false, true);

        // Load up the data
        $datasetList = $db->getDataset($akId, $resourceId, $metricId, $numProcUnits, $startTime, $endTime, $metadataOnly, $debugMode);

        $results['success'] = true;
        $results['results'] = $datasetList;

        if ($returnRawData) {
            return $results;
        }

        $formatParams = array(
            'format' => $this->getStringParam($request, 'format'),
        );
        $format = \DataWarehouse\ExportBuilder::getFormat(
            $formatParams,
            'json',
            array('json', 'xml', 'xls', 'csv')
        );//todo: perhaps add jsonstore support

        if ($format == 'json') //default format
        {
            return $app->json($results);
        } elseif ($format == 'jsonstore') //not supported yet
            {

        } elseif ($format == 'xls' || $format == 'csv' || $format == 'xml') {
            $exportedDatas = array();
            foreach ($datasetList as $result) {
                $exportedDatas[] = $result->export();
            }

            $content = \DataWarehouse\ExportBuilder::export($exportedDatas, $format, $inline);

            return new Response($content['results'], Response::HTTP_OK, $content['headers']);
        }
    }

    /**
     * Retrieve one or more plots about application kernels.
     *
     * Ported from: classes/REST/Appkernel/Explorer.php
     *
     * @param Request     $request The request used to make this call.
     * @param Application $app     The router application.
     *
     * @return Response response data containing the following info:
     *                              success: A boolean indicating if the call was successful.
     *                              results: The requested plots.
     * @throws Exception
     */
    public function getPlots(Request $request, Application $app)
    {
        $show_title = $this->getBooleanParam($request, 'show_title', false, false);
        $width = $this->getFloatParam($request, 'width', false, 740);
        $height = $this->getFloatParam($request, 'height', false, 345);
        $scale = $this->getFloatParam($request, 'scale', false, 1.0);

        $start_date = $this->convertDateTime(
            $this->getDateTimeFromUnixParam($request, 'start_time'),
            false,
            true
        );

        $end_date = $this->convertDateTime(
            $this->getDateTimeFromUnixParam($request, 'end_time'),
            true,
            true
        );

        $swap_xy = $this->getBooleanParam($request, 'swap_xy', false, false);

        $limit = $this->getIntParam($request, 'limit', false, 20);
        $offset = $this->getIntParam($request, 'offset', false, 0);

        $legend_location = $this->getStringParam($request, 'legend_type');
        if ($legend_location === null || $legend_location == '') {
            $legend_location = 'bottom_center';
        }

        $font_size = $this->getStringParam($request, 'font_size');
        if ($font_size === null || $font_size == '') {
            $font_size = 'default';
        }

        $inline = $this->getBooleanParam($request, 'inline', false, true);
        $show_change_indicator = $this->getBooleanParam($request, 'show_change_indicator', false, false);
        $show_control_plot = $this->getBooleanParam($request, 'show_control_plot', false, false);
        $show_control_zones = $this->getBooleanParam($request, 'show_control_zones', false, false);
        $discrete_controls = $this->getBooleanParam($request, 'discrete_controls', false, false);
        $show_running_averages = $this->getBooleanParam($request, 'show_running_averages', false, false);
        $show_control_interval = $this->getBooleanParam($request, 'show_control_interval', false, false);

        $show_num_proc_units_separately = $this->getBooleanParam($request, 'show_num_proc_units_separately', false, false);

        $single_metric = $this->getIntParam($request, 'num_proc_units') !== null;
        if ($show_num_proc_units_separately) {
            $single_metric = true;
        }

        $contextMenuOnClick = $this->getStringParam($request, 'contextMenuOnClick');

        $formatParams = array(
            'format' => $this->getStringParam($request, 'format'),
        );
        $format = \DataWarehouse\ExportBuilder::getFormat(
            $formatParams,
            'session_variable',
            array('session_variable', 'png_inline', 'img_tag', 'png', 'svg', 'pdf')
        );

        $dataset = $this->getDatasets($request, $app, true);
        if (!$dataset['success']) {
            throw new Exception('Dataset is empty');
        }
        $results = $dataset['results'];

        $returnValue = array();

        $user = $this->getUserFromRequest($request);
        $userIsPublic = $user->isPublicUser();
        if (!$userIsPublic) {
            $chartPool = new \XDChartPool($user);
        }

        $lastResult = new \AppKernel\Dataset('Empty App Kernel Dataset', -1, "", -1, "", -1, "", "", "", "");
        $hc = new \DataWarehouse\Visualization\HighChartAppKernel($start_date, $end_date, $scale, $width, $height, $user, $swap_xy);
        $hc->setTitle($show_title ? 'Empty App Kernel Dataset' : null, $font_size);
        $hc->setLegend($legend_location, $font_size);

        $datasets = array();
        $hc->configure(
            $datasets,
            $font_size,
            $limit,
            $offset,
            $format === 'svg',
            true,
            true,
            false,
            $show_change_indicator,
            $single_metric && $show_control_plot,
            $single_metric && $discrete_controls,
            $single_metric && $show_control_zones,
            $single_metric && $show_running_averages,
            $single_metric && $show_control_interval,
            $contextMenuOnClick
        );

        srand(\DataWarehouse\VisualizationBuilder::make_seed());

        $paramBag = new ParameterBag();
        $paramBag->add($request->query->all());
        $paramBag->add($request->request->all());
        $params = $paramBag->all();

        foreach ($results as $result) {
            $num_proc_units_changed = false;
            if ($show_num_proc_units_separately && $result->rawNumProcUnits != $lastResult->rawNumProcUnits) {
                $num_proc_units_changed = true;
            }

            if ($result->akName != $lastResult->akName
                || $result->resourceName != $lastResult->resourceName
                || $result->metric != $lastResult->metric
                || $num_proc_units_changed
            ) {
                if ($lastResult->akName != "Empty App Kernel Dataset") {
                    $requestDescripter = new \User\Elements\RequestDescripter($params);
                    $chartIdentifier = $requestDescripter->__toString();

                    if ($format == 'session_variable') {
                        $vis = array(
                            'random_id' => 'chart_' . rand(),
                            'title' => $hc->getTitle(),
                            'short_title' => $lastResult->metric,
                            'comments' => $lastResult->description,
                            'ak_name' => $lastResult->akName,
                            'resource_name' => $lastResult->resourceName,
                            'resource_description' => $lastResult->resourceDescription,
                            'chart_args' => $chartIdentifier,
                            'included_in_report' => !$userIsPublic ? ($chartPool->chartExistsInQueue($chartIdentifier) ? 'y' : 'n') : 'NA - auth required',
                            'textual_legend' => '',
                            'start_date' => $start_date,
                            'end_date' => $end_date,

                            'ak_id' => $lastResult->akId,
                            'resource_id' => $lastResult->resourceId,
                            'metric_id' => $lastResult->metricId
                        );
                        $json = $hc->exportJsonStore();
                        $vis['hc_jsonstore'] = $json['data'][0];
                    } else {
                        $vis = $hc->getRawImage($format, $params);
                    }
                    $returnValue[] = $vis;

                }
                if ($format != 'params') {
                    $hc = new \DataWarehouse\Visualization\HighChartAppKernel($start_date, $end_date, $scale, $width, $height, $user, $swap_xy);
                    $hc->setTitle($show_title ? $result->metric : null, $font_size);
                    $hc->setSubtitle($show_title ? $result->resourceName : null, $font_size);
                }
            }

            if ($format != 'params') {
                $datasets = array($result);

                $hc->configure(
                    $datasets,
                    $font_size,
                    $limit,
                    $offset,
                    $format === 'svg',
                    true,
                    true,
                    false,
                    $show_change_indicator,
                    $single_metric && $show_control_plot,
                    $single_metric && $discrete_controls,
                    $single_metric && $show_control_zones,
                    $single_metric && $show_running_averages,
                    $single_metric && $show_control_interval,
                    $contextMenuOnClick
                );
            }
            $lastResult = $result;
        }
        $requestDescripter = new \User\Elements\RequestDescripter($params);
        $chartIdentifier = $requestDescripter->__toString();

        if ($format == 'session_variable') {
            $vis = array(
                'random_id' => 'chart_' . rand(),
                'title' => $hc->getTitle(),
                'short_title' => $lastResult->metric,
                'comments' => $lastResult->description,
                'ak_name' => $lastResult->akName,
                'resource_name' => $lastResult->resourceName,
                'resource_description' => $lastResult->resourceDescription,
                'chart_args' => $chartIdentifier,
                'included_in_report' => !$userIsPublic ? ($chartPool->chartExistsInQueue($chartIdentifier) ? 'y' : 'n') : 'NA - auth required',
                'textual_legend' => '',
                'start_date' => $start_date,
                'end_date' => $end_date,

                'ak_id' => $lastResult->akId,
                'resource_id' => $lastResult->resourceId,
                'metric_id' => $lastResult->metricId
            );

            $json = $hc->exportJsonStore();
            $vis['hc_jsonstore'] = $json['data'][0];
        } else {
            $vis = $hc->getRawImage($format, $params);
        }

        $returnValue[] = $vis;

        if ($format == 'session_variable') {
            return new Response(
                json_encode(array(
                    'success' => true,
                    'results' => $returnValue,
                )),
                Response::HTTP_OK,
                array(
                    'Content-Type' => 'application/javascript',
                )
            );
        } elseif ($format == 'img_tag') {
            foreach ($returnValue as $vis) {
                return new Response(
                    $vis,
                    Response::HTTP_OK,
                    \DataWarehouse\ExportBuilder::getHeader($format)
                );
            }
        } elseif ($format == 'png' || $format == 'svg' || $format == 'pdf' || $format == 'png_inline') {
            foreach ($returnValue as $vis) {
                return new Response(
                    $vis,
                    Response::HTTP_OK,
                    \DataWarehouse\ExportBuilder::getHeader(
                        $format,
                        $inline,
                        'ak_usage_' . $start_date . '_to_' . $end_date . '_' . $lastResult->resourceName . '_' . $lastResult->akName . '_' . $lastResult->metric
                    )
                );
            }

        }
    }

    /**
     * Retrieve one or more control regions.
     *
     * Ported from: classes/REST/Appkernel/Explorer.php
     *
     * @param  Request     $request The request used to make this call.
     * @param  Application $app     The router application.
     * @return JsonResponse Response data containing the following info:
     *                              success: A boolean indicating if the call was successful.
     *                              results: The requested control regions.
     *                              count: The number of control regions.
     */
    public function getControlRegions(Request $request, Application $app)
    {
        $resource_id = $this->getIntParam($request, 'resource_id', true);
        $ak_def_id = $this->getIntParam($request, 'ak_def_id', true);

        $db = new \AppKernel\AppKernelDb($app['logger.db']);
        $results = $db->getControlRegions($resource_id, $ak_def_id);

        return $app->json(array(
            'success' => true,
            'results' => $results,
            'count' => count($results),
        ));
    }

    /**
     * Create one or more control regions.
     *
     * Ported from: classes/REST/Appkernel/Explorer.php
     *
     * @param  Request     $request The request used to make this call.
     * @param  Application $app     The router application.
     * @param  boolean $update True if updating control regions. False if
     *                              creating control regions.
     * @return JsonResponse Response data containing the following info:
     *                              success: A boolean indicating if the call was successful.
     *                              message: A human-readable message about what occurred.
     */
    public function createOrUpdateControlRegions(Request $request, Application $app, $update)
    {
        // Ensure that the user is a manager.
        $this->authorize($request, array(ROLE_ID_MANAGER));

        // Get an app kernel database connection.
        $db = new \AppKernel\AppKernelDb($app['logger.db']);

        // Load the application kernel definitions for the description
        $akList = $this->getAppKernelMapping($db);

        // Load the resource definitions for the description
        $resourceList = $this->getResourceMapping($db);

        // Get mandatory parameters.
        $resource_id = $this->getIntParam($request, 'resource_id', true);
        $ak_def_id = $this->getIntParam($request, 'ak_def_id', true);
        $control_region_type = $this->getStringParam($request, 'control_region_time_interval_type', true);
        $startDateTime = $this->getStringParam($request, 'startDateTime', true);

        // Get optional parameters.
        $n_points = $this->getIntParam($request, 'n_points');
        $endDateTime = $this->getStringParam($request, 'endDateTime');
        $comment = $this->getStringParam($request, 'comment');

        $control_region_def_id = $this->getStringParam($request, 'control_region_def_id');

        // Create or update the control regions.
        $msg = $db->newControlRegions(
            $resource_id,
            $ak_def_id,
            $control_region_type,
            $startDateTime,
            $endDateTime,
            $n_points,
            $comment,
            $update,
            $control_region_def_id
        );

        // If successful, calculate controls.
        if ($msg['success']) {
            $ak = $akList[$ak_def_id];
            $res = $resourceList[$resource_id];
            $db->calculateControls(false, false, 20, 5, $res->nickname, $ak->basename);
        }

        // Return the message.
        return $app->json($msg);
    }

    /**
     * Delete one or more control regions.
     *
     * Ported from: classes/REST/Appkernel/Explorer.php
     *
     * @param  Request     $request The request used to make this call.
     * @param  Application $app     The router application.
     * @return JsonResponse Response data containing the following info:
     *                              success: A boolean indicating if the call was successful.
     *                              message: A human-readable message about what occurred.
     */
    public function deleteControlRegions(Request $request, Application $app)
    {
        // Ensure that the user is a manager.
        $this->authorize($request, array(ROLE_ID_MANAGER));

        // Get an app kernel database connection.
        $db = new \AppKernel\AppKernelDb($app['logger.db']);

        // Load the application kernel definitions for the description
        $akList = $this->getAppKernelMapping($db);

        // Load the resource definitions for the description
        $resourceList = $this->getResourceMapping($db);

        // Get mandatory parameters.
        $resource_id = $this->getIntParam($request, 'resource_id', true);
        $ak_def_id = $this->getIntParam($request, 'ak_def_id', true);

        $controlRegiondIDs = explode(",", $this->getStringParam($request, 'controlRegiondIDs', true));

        // Attempt to delete each specified control region.
        foreach ($controlRegiondIDs as $control_region_def_id) {
            $msg = $db->deleteControlRegion(intval($control_region_def_id));
            if (!$msg['success']) {
                return $app->json($msg);
            }
        }

        // If successful, calculate controls and return a success message.
        $ak = $akList[$ak_def_id];
        $res = $resourceList[$resource_id];

        $db->calculateControls(false, false, 20, 5, $res->nickname, $ak->basename);

        return $app->json(array(
            'success' => true,
            'message' => "deleted control region time intervals",
        ));
    }

    /**
     * Retrieve information about e-mail notifications
     *
     * @return JsonResponse Response data containing the following info:
     *                              success: A boolean indicating if the call was successful.
     *                              results: The requested information.
     */
    public function getNotifications(Request $request, Application $app)
    {
        try {
            $response = array();
            $pdo = \CCR\DB::factory('database');

            $curent_tmp_settings = $this->getStringParam($request, 'curent_tmp_settings', true);
            $curent_tmp_settings = json_decode($curent_tmp_settings, true);

            $user_id = $this->getUserFromRequest($request)->getUserID();

            formatNotificationSettingsFromClient($curent_tmp_settings, true);

            $sqlres = $pdo->query(
                'SELECT user_id,send_report_daily,send_report_weekly,send_report_monthly,settings
                                    FROM mod_appkernel.report
                                    WHERE user_id=:user_id',
                array(':user_id' => $user_id)
            );

            if (count($sqlres) == 1) {
                $sqlres = $sqlres[0];
                $settings = json_decode($sqlres['settings'], true);
                foreach ($settings as $key => $value) {
                    $curent_tmp_settings[$key] = $value;
                }
            } else {
                throw new Exception('settings is not set in db use default');
            }
            formatNotificationSettingsForClient($curent_tmp_settings);
            $response['data'] = $curent_tmp_settings;
            $response['success'] = true;
            return $app->json($response);
        } catch (Exception $e) {
            //i.e. setting is not saved by user so send defaults
            return $this->getDefaultNotifications($request, $app);
        }
    }

    /**
     * Save information about e-mail notifications
     *
     * @return JsonResponse Response data containing the following info:
     *                              success: A boolean indicating if the call was successful.
     */
    public function putNotifications(Request $request, Application $app)
    {
        try {
            $pdo = \CCR\DB::factory('database');

            $curent_tmp_settings = $this->getStringParam($request, 'curent_tmp_settings', true);
            $curent_tmp_settings = json_decode($curent_tmp_settings, true);

            $user_id = $this->getUserFromRequest($request)->getUserID();

            formatNotificationSettingsFromClient($curent_tmp_settings);

            $send_report_daily = ($curent_tmp_settings['daily_report']['send_on_event'] === 'sendNever') ? (0) : (1);
            $send_report_weekly = ($curent_tmp_settings['weekly_report']['send_on_event'] === 'sendNever') ? (-$curent_tmp_settings['weekly_report']['send_on']) : ($curent_tmp_settings['weekly_report']['send_on']);
            $send_report_monthly = ($curent_tmp_settings['monthly_report']['send_on_event'] === 'sendNever') ? (-$curent_tmp_settings['monthly_report']['send_on']) : ($curent_tmp_settings['monthly_report']['send_on']);

            $sqlres = $pdo->query(
                'SELECT user_id,send_report_daily,send_report_weekly,send_report_monthly,settings
                                    FROM mod_appkernel.report
                                    WHERE user_id=:user_id',
                array(':user_id' => $user_id)
            );

            if (count($sqlres) == 0) {
                $pdo->insert(
                    'INSERT INTO mod_appkernel.report (user_id,send_report_daily,send_report_weekly,send_report_monthly,settings)
                                        VALUES (:user_id,:send_report_daily,:send_report_weekly,:send_report_monthly,:settings)',
                    array(
                        ':user_id' => $user_id,
                        ':send_report_daily' => $send_report_daily,
                        ':send_report_weekly' => $send_report_weekly,
                        ':send_report_monthly' => $send_report_monthly,
                        ':settings' => json_encode($curent_tmp_settings)//str_replace('"',"'",json_encode($curent_tmp_settings))
                    )
                );
            } else {
                $pdo->execute(
                    'UPDATE mod_appkernel.report
                                        SET send_report_daily=:send_report_daily,send_report_weekly=:send_report_weekly,
                                            send_report_monthly=:send_report_monthly,settings=:settings
                                        WHERE user_id=:user_id',
                    array(
                        ':user_id' => $user_id,
                        ':send_report_daily' => $send_report_daily,
                        ':send_report_weekly' => $send_report_weekly,
                        ':send_report_monthly' => $send_report_monthly,
                        ':settings' => json_encode($curent_tmp_settings)//str_replace('"',"'",json_encode($curent_tmp_settings))
                    )
                );
            }
            $response['data'] = array();
            $response['success'] = true;

        } catch (Exception $e) {
            $response['success'] = false;
            $response['errorMessage'] = 'Can not save notification_settings. ' . $e->getMessage();
        }
        return $app->json($response);
    }

    /**
     * Get DefaultNotifications settings
     *
     * @param Request     $request
     * @param Application $app
     *
     * @return JsonResponse
     */
    public function getDefaultNotifications(Request $request, Application $app)
    {
        $response = array();
        try {
            $curent_tmp_settings = $this->getStringParam($request, 'curent_tmp_settings', true);
            $curent_tmp_settings = json_decode($curent_tmp_settings, true);

            formatNotificationSettingsFromClient($curent_tmp_settings, true);

            $curent_tmp_settings["controlThresholdCoeff"] = '1.0';
            $curent_tmp_settings["resource"] = array();//None means all
            $curent_tmp_settings["appKer"] = array();//None means all

            formatNotificationSettingsForClient($curent_tmp_settings);

            $response['data'] = $curent_tmp_settings;
            $response['success'] = true;
        } catch (Exception $e) {
            $response['success'] = false;
            $response['errorMessage'] = 'Can not load load_default_notification_settings. ' . $e->getMessage();
        }
        return $app->json($response);
    }

    /**
     * Send e-mail report
     *
     * @param Request     $request
     * @param Application $app
     * @return JsonResponse|Response Response data containing the following info:
     *                              success: A boolean indicating if the call was successful.
     *                              results: The requested information.
     */
    public function getPerformanceMap(Request $request, Application $app)
    {
        $response = array();
        try {

            $start_date = $this->getStringParam($request, 'start_date', false, null);
            if ($start_date !== null) {
                $start_date = new \DateTime($start_date);
            }

            $end_date = $this->getStringParam($request, 'end_date', false, null);
            if ($end_date !== null) {
                $end_date = new \DateTime($end_date);
            }

            $format = $this->getStringParam($request, 'format', true);

            $resources = null;
            $appKers = null;
            $problemSizes = null;

            if (count($resources) === 0) {
                $resources = null;
            }
            if (count($appKers) === 0) {
                $appKers = null;
            }
            if (count($problemSizes) === 0) {
                $problemSizes = null;
            }


            //PerformanceMap
            $perfMap = new \AppKernel\PerformanceMap(array(
                'start_date' => $start_date,
                'end_date' => $end_date,
                'resource' => $resources,
                'appKer' => $appKers,
                'problemSize' => $problemSizes
            ));
            $response = $perfMap->getMapForWeb();

            if ($format === 'csv' || $format === 'xml') {

                $controlThreshold = -0.5;
                $day_interval = new DateInterval('P1D');

                $rec_dates = array();
                while ($start_date <= $end_date) {
                    $rec_dates[] = $start_date->format('Y/m/d');
                    $start_date->add($day_interval);
                }
                // ADD: one more day for ranges.
                $rec_dates[] = $start_date->format('Y/m/d');

                $filename = 'data.' . $format;
                $inline = false;

                $exportData = array();
                $exportData['title'] = array('title' => 'App Kernels Performance Map (control threshold = ' . $controlThreshold . ' )');
                $exportData['duration'] = array('from:' => $start_date, 'to' => $end_date);
                $exportData['headers'] = array('resource', 'appKer', 'problemSize');
                foreach ($rec_dates as $rec_date) {
                    $exportData['headers'][] = $rec_date;
                }

                $exportData['rows'] = array();
                foreach ($response['response'] as $result) {
                    $expRes = array(
                        'resource' => $result['resource'],
                        'appKer' => $result['appKer'],
                        'problemSize' => $result['problemSize']
                    );
                    foreach ($rec_dates as $rec_date) {
                        $expRes[$rec_date] = $result[$rec_date];
                    }
                    $exportData['rows'][] = $expRes;
                }

                $content = \DataWarehouse\ExportBuilder::export(array($exportData), $format, $inline, $filename);

                return new Response($content['results'], Response::HTTP_OK, $content['headers']);
            } else {
                return $app->json($response);
            }

        } catch (Exception $e) {
            $response['success'] = false;
            $response['message'] = 'Can not send performance map. ' . $e->getFile() . ':' . $e->getLine() . ' ' . $e->getMessage();

        }
        return $app->json($response);
    }

    /**
     * Send e-mail report
     *
     * @param Request     $request
     * @param Application $app
     * @return JsonResponse Response data containing the following info:
     *                              success: A boolean indicating if the call was successful.
     *                              results: The requested information.
     */
    public function sendNotification(Request $request, Application $app)
    {
        $response = array();
        try {
            $user = $this->getUserFromRequest($request);
            $recipient = $user->getEmailAddress();
            $internal_dashboard_user = $user->isDeveloper() || $user->isDeveloper();

            $report_type = $this->getStringParam($request, 'report_type', true);

            $start_date = $this->getStringParam($request, 'start_date', false, null);
            if ($start_date !== null) {
                $start_date = new \DateTime($start_date);
            }

            $end_date = $this->getStringParam($request, 'end_date', false, null);
            if ($end_date !== null) {
                $end_date = new \DateTime($end_date);
            }

            $report_param = $this->getStringParam($request, 'report_param', true);
            $report_param = json_decode($report_param, true);

            formatNotificationSettingsFromClient($report_param);

            $report = new Report(array(
                'start_date' => $start_date,
                'end_date' => $end_date,
                'report_type' => $report_type,
                'report_params' => $report_param,
                'user' => $user
            ));

            try {
                $report->send_report_to_email($recipient, $internal_dashboard_user);
            } catch (Exception $e) {
                $response['success'] = false;
                $response['message'] = $e->getMessage();
                return $app->json($response);
            }
            $response['success'] = true;
            $response['message'] = 'Report has been sent to ' . $recipient;
        } catch (Exception $e) {
            $response['success'] = false;
            $response['message'] = 'Can not send report. ' . $e->getFile() . ':' . $e->getLine() . ' ' . $e->getMessage();

        }
        return $app->json($response);
    }

    /**
     * Get list of resources active in last 90 days
     * @param Request     $request
     * @param Application $app
     * @return JsonResponse
     */
    public function getResources(Request $request, Application $app)
    {
        $response = array();
        try {
            $ak_db = new \AppKernel\AppKernelDb();

            $user = $this->getUserFromRequest($request);


            $allResources = $ak_db->getResources(
                date_format(date_sub(date_create(), date_interval_create_from_date_string("90 days")), 'Y-m-d'),
                date_format(date_create(), 'Y-m-d'),
                array(),
                array(),
                $user
            );


            $returnData = array();
            foreach ($allResources as $resource) {
                if ($resource->visible != 1) {
                    continue;
                }

                $returnData[] = array(
                    'id' => $resource->id,
                    'fullname' => $resource->name,
                    'name' => $resource->nickname
                );
            }
            $response['response'] = $returnData;
            $response['success'] = true;
            $response['message'] = '';
        } catch (Exception $e) {
            $response['success'] = false;
            $response['message'] = 'Can not complete SQL query';
            $response['response'] = array();
        }
        return $app->json($response);
    }

    /**
     * Get list of app kernels active in last 90 days
     * @param Request     $request
     * @param Application $app
     * @return JsonResponse
     */
    public function getAppKernels(Request $request, Application $app)
    {
        $response = array();
        try {
            $ak_db = new \AppKernel\AppKernelDb();
            $start_ts = date_timestamp_get(date_sub(date_create(), date_interval_create_from_date_string("90 days")));

            $all_app_kernels = $ak_db->getUniqueAppKernels();
            $returnData = array();
            foreach ($all_app_kernels as $app_kernel) {
                //print_r($app_kernel);
                if ($app_kernel->end_ts > $start_ts) {
                    $returnData[] = array('name' => $app_kernel->name,
                        'id' => 'app_kernel_' . $app_kernel->id,
                        'end_ts' => $app_kernel->end_ts
                    );
                }
            }
            $response['response'] = $returnData;
            $response['success'] = true;
            $response['message'] = '';
        } catch (Exception $e) {
            $response['success'] = false;
            $response['message'] = 'Can not complete SQL query';
            $response['response'] = array();
        }
        return $app->json($response);
    }

    /**
     * Convert a DateTime to a date representation usable by app kernel functions.
     *
     * Ported from: classes/REST/Appkernel/Explorer.php
     *
     * @param  DateTime $dateVal The DateTime to convert. If a DateTime is
     *                               not given, null will be returned.
     * @param  boolean $isEndDate (Optional) If true, the time will be set
     *                               to 23:59:59 of the date given. Otherwise,
     *                               the time will be set to midnight.
     *                               (Defaults to false.)
     * @param  boolean $isYMDFormat (Optional) If true, the DateTime will be
     *                               converted to an ISO 8601 date string.
     *                               Otherwise, it will be converted to a Unix
     *                               timestamp. (Defaults to false.)
     * @return mixed                 If a DateTime was given, either an ISO
     *                               8601 date string or a Unix timestamp,
     *                               depending on the value of isYMDFormat.
     *                               Otherwise, null.
     */
    private function convertDateTime($dateVal, $isEndDate = false, $isYMDFormat = false)
    {
        // If a DateTime was not given, return null.
        if (!($dateVal instanceof \DateTime)) {
            return null;
        }

        // If this is for an end date, set the time to 23:59:59.
        // Otherwise, set the time to midnight.
        $modifiedDateVal = clone $dateVal;
        if ($isEndDate) {
            $modifiedDateVal->setTime(23, 59, 59);
        } else {
            $modifiedDateVal->setTime(0, 0, 0);
        }

        // If Y-m-d format was requested, return that string for the DateTime.
        // Otherwise, return the Unix timestamp for the DateTime.
        if ($isYMDFormat) {
            $retDate = $modifiedDateVal->format('Y-m-d');
        } else {
            $retDate = $modifiedDateVal->getTimestamp();
        }

        return $retDate;
    }

    /**
     * Helper function for creating an ExtJs tree node based on the type and a row
     * from the v_tree view.
     *
     * Ported from: classes/REST/Appkernel/Explorer.php
     *
     * @param String  $type           Tree node type
     * @param array   $record Record  returned from the database
     * @param boolean $resource_first (Optional) (Defaults to false.)
     *
     * @return Object An object representation of the tree node
     */
    private function createTreeNode($type, $record, $resource_first = false)
    {
        $node = array(
            'id' => $this->nodeId($type, $record),
            'type' => $type,
        );
        switch ($type) {
            case self::TREENODE_INSTANCE:
                $node['status'] = $record['status'];
                $node['text'] = date("Y-m-d H:i:s", $record['collected']);
                $node['collected'] = $record['collected'];
                $node['num_proc_units'] = $record['num_units'];
                $node['metric_id'] = (isset($record['metric_id']) ? $record['metric_id'] : null);
                $node['resource_id'] = $record['resource_id'];
                $node['ak_id'] = $record['ak_def_id'];
                $node['instance_id'] = $record['instance_id'];
                break;
            case self::TREENODE_UNITS:
                $text = $record['num_units'] . " " . $record['processor_unit'] . ($record['num_units'] > 1 ? "s" : "");
                $node['text'] = $text;
                $node['num_proc_units'] = $record['num_units'];
                $node['metric_id'] = (isset($record['metric_id']) ? $record['metric_id'] : null);
                $node['resource_id'] = $record['resource_id'];
                $node['ak_id'] = $record['ak_def_id'];
                break;
            case self::TREENODE_METRIC:
                $node['text'] = $record['metric'];
                $node['metric_id'] = (isset($record['metric_id']) ? $record['metric_id'] : null);
                $node['resource_id'] = $record['resource_id'];
                $node['ak_id'] = $record['ak_def_id'];
                break;
            case self::TREENODE_RESOURCE:
                $node['text'] = $record['resource'];
                $node['resource_id'] = $record['resource_id'];
                if (!$resource_first) {
                    $node['ak_id'] = $record['ak_def_id'];
                }
                break;
            case self::TREENODE_APPKERNEL:
                $node['text'] = $record['ak_name'];/*.' '.date('Y-m-d',$record['start_ts']).' '.date('Y-m-d', $record['end_ts'])*/
                $node['ak_id'] = $record['ak_def_id'];
                if ($resource_first) {
                    $node['resource_id'] = $record['resource_id'];
                }
                break;
            default:
                break;
        }
        if (isset($record['start_ts'])) {
            $node['start_ts'] = $record['start_ts'];
        }
        if (isset($record['end_ts'])) {
            $node['end_ts'] = $record['end_ts'];
        }
        return (object)$node;
    }

    /**
     * Generate the ID for a tree node.
     *
     * Ported from: classes/REST/Appkernel/Explorer.php
     *
     * @param  string $type The type of the tree node.
     * @param  array $record The database record corresponding to the node.
     * @return string         The ID for the tree node.
     */
    private function nodeId($type, $record)
    {
        $id = array();
        switch ($type) {
            // comment describing why there is no break.
            case self::TREENODE_UNITS:
                array_unshift($id, $record['num_units']);
            // comment describing why there is no break.
            case self::TREENODE_METRIC:
                if (isset($record['metric_id'])) {
                    array_unshift($id, $record['metric_id']);
                }
            // comment describing why there is no break.
            case self::TREENODE_RESOURCE:
                array_unshift($id, $record['resource_id']);
            // comment describing why there is no break.
            case self::TREENODE_APPKERNEL:
                array_unshift($id, $record['ak_def_id']);
                break;
            default:
                break;
        }
        return implode("_", $id);
    }

    /**
     * Get a mapping of app kernel IDs to app kernels from the database.
     *
     * @param  \AppKernel\AppKernelDb $db The app kernel database.
     * @return array                      An associative array of app kernel
     *                                    IDs to app kernels.
     */
    private function getAppKernelMapping(\AppKernel\AppKernelDb $db)
    {
        $appKernelDefs = $db->loadAppKernelDefinitions();
        $akList = array();
        foreach ($appKernelDefs as $ak) {
            $akList[$ak->id] = $ak;
        }
        return $akList;
    }

    /**
     * Get a mapping of resource IDs to resources from the database.
     *
     * @param  \AppKernel\AppKernelDb $db The app kernel database.
     * @return array                      An associative array of resource
     *                                    IDs to resources.
     */
    private function getResourceMapping(\AppKernel\AppKernelDb $db)
    {
        $resourceDefs = $db->loadResources();
        $resourceList = array();
        foreach ($resourceDefs as $res) {
            $resourceList[$res->id] = $res;
        }
        return $resourceList;
    }

    public function getAppKernelSuccessRate(Request $req, Application $app)
    {
        $response = null;

        $format = $this->getStringParam($req, 'format', false);

        //get request
        $start_date = $this->getStringParam($req, 'start_date', true);
        $end_date = $this->getStringParam($req, 'end_date', true);

        $raw_resources = $this->getStringParam($req, 'resources');
        $resources = explode(';', strtolower($raw_resources));

        $resourceSelected = '';
        if (count($resources) == 1) {
            $resourceSelected = "AND resource='$resources[0]'";
        }

        $raw_appKers = $this->getStringParam($req, 'appKers');
        $appKers = explode(';', strtolower($raw_appKers));

        $raw_problemSizes = $this->getStringParam($req, 'problemSizes');
        $problemSizes = explode(';', strtolower($raw_problemSizes));
        foreach ($problemSizes as $key => $var) {
            $problemSizes[$key] = (int)$var;
        }

        $showAppKer = $this->getBooleanParam($req, 'showAppKer', false, false);
        $showAppKerTotal = $this->getBooleanParam($req, 'showAppKerTotal', false, false);
        $showResourceTotal = $this->getBooleanParam($req, 'showResourceTotal', false, false);
        $showUnsuccessfulTasksDetails = $this->getBooleanParam($req, 'showUnsuccessfulTasksDetails', false, false);
        $showSuccessfulTasksDetails = $this->getBooleanParam($req, 'showSuccessfulTasksDetails', false, false);
        $internalFailureTasksFilter = $this->getBooleanParam($req, 'showInternalFailureTasks', false, false);

        $internalFailureTasksFilter = $internalFailureTasksFilter ? '' : 'AND internal_failure=0';

        try {
            $node = $this->getStringParam($req, 'node');
            $nodeSelected = isset($node) ? "AND nodes LIKE '%;$node;%'" : '';

            $arr_db = DB::factory('akrr-db');

            $results = array();
            foreach ($resources as $resource) {
                $results[$resource] = array();
                foreach ($appKers as $appKer) {
                    $results[$resource][$appKer] = array();
                }
            }

            $extraFilters = "$resourceSelected $internalFailureTasksFilter $nodeSelected";

            $sql = "
                SELECT resource,reporter,reporternickname,COUNT(*) as total_tasks,AVG(status) as success_rate
                FROM mod_akrr.akrr_xdmod_instanceinfo
                WHERE '$start_date' <=collected AND  collected < '$end_date' AND status=1
                $extraFilters
                GROUP BY resource,reporternickname ORDER BY resource,reporternickname ASC;";

            $sqlres = $arr_db->query($sql);

            foreach ($sqlres as $rownum => $row) {
                $resource = $row['resource'];
                $appKer = $row['reporter'];

                $problemSize = explode('.', $row['reporternickname']);
                $problemSize = (int)$problemSize[count($problemSize) - 1];

                if (!array_key_exists($resource, $results)) {
                    continue;
                }
                if (!array_key_exists($appKer, $results[$resource])) {
                    continue;
                }

                if (!array_key_exists($problemSize, $results[$resource][$appKer])) {
                    $results[$resource][$appKer][$problemSize] = array(
                        "succ" => 0,
                        "unsucc" => 0,
                    );
                }
                $results[$resource][$appKer][$problemSize]["succ"] = (int)$row['total_tasks'];
            }
            //Count unsuccessfull Tasks
            $sql = "
                SELECT resource,reporter,reporternickname,COUNT(*) as total_tasks,AVG(status) as success_rate
                FROM mod_akrr.akrr_xdmod_instanceinfo
                WHERE '$start_date' <=collected AND  collected < '$end_date' AND status=0
                $extraFilters
                GROUP BY resource,reporternickname ORDER BY resource,reporternickname ASC;";
            $sqlres = $arr_db->query($sql);

            foreach ($sqlres as $rownum => $row) {
                $resource = $row['resource'];
                $appKer = $row['reporter'];

                $problemSize = explode('.', $row['reporternickname']);
                $problemSize = (int)$problemSize[count($problemSize) - 1];

                if (!array_key_exists($resource, $results)) {
                    continue;
                }
                if (!array_key_exists($appKer, $results[$resource])) {
                    continue;
                }

                if (!array_key_exists($problemSize, $results[$resource][$appKer])) {
                    $results[$resource][$appKer][$problemSize] = array(
                        "succ" => 0,
                        "unsucc" => 0,
                    );
                }
                $results[$resource][$appKer][$problemSize]["unsucc"] = (int)$row['total_tasks'];
            }
            //Merge results to respond
            $results2 = array();
            foreach ($results as $resource => $row1) {
                $unsuccRes = 0;
                $succRes = 0;
                foreach ($row1 as $appKer => $row2) {
                    $unsucc = 0;
                    $succ = 0;
                    $resultsTMP = array();
                    foreach ($row2 as $problemSize => $row) {
                        if (!in_array($problemSize, $problemSizes)) {
                            continue;
                        }

                        if ($showAppKer) {

                            $unsuccessfull_tasks = '';
                            if (!($showUnsuccessfulTasksDetails || $showSuccessfulTasksDetails)) {
                                $unsuccessfull_tasks = 'Select "Show Details of Unsuccessful Tasks"
or "Show Details of Successful Tasks" options to see details on tasks';
                            }

                            if ($showUnsuccessfulTasksDetails) {
                                if ((int)$row["unsucc"] > 0) {
                                    $sql = "
                                        SELECT instance_id
                                        FROM mod_akrr.akrr_xdmod_instanceinfo
                                        WHERE '$start_date' <=collected AND  collected < '$end_date'
                                        AND status=0 AND resource='$resource'
                                        AND reporternickname='$appKer.$problemSize' $extraFilters
                                        ORDER BY collected DESC;";
                                    $sqlres = $arr_db->query($sql);
                                    $unsuccessfull_tasks = $unsuccessfull_tasks . 'Tasks finished unsuccessfully:<br/>';
                                    $icount = 1;
                                    foreach ($sqlres as $row2) {
                                        $task_id = $row2['instance_id'];
                                        $unsuccessfull_tasks = $unsuccessfull_tasks .
                                            "<a href=\"#\" onclick=\"javascript:new XDMoD.AppKernel.InstanceWindow({instanceId:$task_id});\">#$task_id</a> ";
                                        if ($icount % 10 == 0) {
                                            $unsuccessfull_tasks = $unsuccessfull_tasks . '<br/>';
                                        }
                                        $icount += 1;
                                    }
                                    $unsuccessfull_tasks = $unsuccessfull_tasks . '<br/>';
                                } else {
                                    $unsuccessfull_tasks = $unsuccessfull_tasks . 'There is no unsuccessful runs.<br/>';
                                }
                            }
                            if ($showSuccessfulTasksDetails) {
                                if ((int)$row["succ"] > 0) {
                                    $sql = "
                                        SELECT instance_id
                                        FROM mod_akrr.akrr_xdmod_instanceinfo
                                        WHERE '$start_date' <=collected AND  collected < '$end_date'
                                        AND status=1 AND resource='$resource'
                                        AND reporternickname='$appKer.$problemSize' $extraFilters
                                        ORDER BY collected DESC;";
                                    $sqlres = $arr_db->query($sql);
                                    $unsuccessfull_tasks = $unsuccessfull_tasks . 'Tasks finished successfully:<br/>';
                                    $icount = 1;
                                    foreach ($sqlres as $row2) {
                                        $task_id = $row2['instance_id'];
                                        $unsuccessfull_tasks = $unsuccessfull_tasks .
                                            "<a href=\"#\" onclick=\"javascript:new XDMoD.AppKernel.InstanceWindow({instanceId:$task_id});\">#$task_id</a> ";
                                        if ($icount % 10 == 0) {
                                            $unsuccessfull_tasks = $unsuccessfull_tasks . '<br/>';
                                        }
                                        $icount += 1;
                                    }
                                } else {
                                    $unsuccessfull_tasks = $unsuccessfull_tasks . 'There is no successful runs.<br/>';
                                }
                            }
                            $resultsTMP[$problemSize] = array(
                                "resource" => $resource,
                                "appKer" => $appKer,
                                "problemSize" => (string)$problemSize,
                                "successfull" => (int)$row["succ"],
                                "unsuccessfull" => (int)$row["unsucc"],
                                "total" => (int)$row["succ"] + (int)$row["unsucc"],
                                "successfull_percent" => 100.0 * (float)$row["succ"] / (float)($row["succ"] + $row["unsucc"]),
                                "unsuccessfull_tasks" => $unsuccessfull_tasks
                            );
                        }
                        $unsucc += $row["unsucc"];
                        $succ += $row["succ"];
                    }

                    foreach ($problemSizes as $problemSize) {
                        if (array_key_exists($problemSize, $resultsTMP)) {
                            $results2[] = $resultsTMP[$problemSize];
                        }
                    }

                    if ($succ + $unsucc > 0) {
                        if ($showAppKerTotal) {
                            $successfull_percent = 100.0 * (float)$succ / (float)($succ + $unsucc);
                            $unsuccessfull_tasks = 'Tasks details are showed only for individual problem sizes';
                            $results2[] = array(
                                "resource" => $resource,
                                "appKer" => $appKer,
                                "problemSize" => "Total",
                                "successfull" => $succ,
                                "unsuccessfull" => $unsucc,
                                "total" => $succ + $unsucc,
                                "successfull_percent" => $successfull_percent,
                                "unsuccessfull_tasks" => $unsuccessfull_tasks
                            );
                        }
                    }
                    $unsuccRes += $unsucc;
                    $succRes += $succ;
                }
                if ($succRes + $unsuccRes > 0) {
                    if ($showResourceTotal) {
                        $successfull_percent = 100.0 * (float)$succRes / (float)($succRes + $unsuccRes);
                        $unsuccessfull_tasks = 'Tasks details are showed only for individual problem sizes';
                        $results2[] = array(
                            "resource" => $resource,
                            "appKer" => "Total",
                            "problemSize" => "Total",
                            "successfull" => $succRes,
                            "unsuccessfull" => $unsuccRes,
                            "total" => $succRes + $unsuccRes,
                            "successfull_percent" => $successfull_percent,
                            "unsuccessfull_tasks" => $unsuccessfull_tasks
                        );
                    }
                }
            }

            $response =  $app->json(
                array(
                    'success' => true,
                    'response' => $results2,
                    'count' => count($results2)
                )
            );
        } catch (Exception $e) {
            $response = $app->json(
                array(
                    'success' => false,
                    'message' => "There was an exception while trying to process the requested operation. Message: " . $e->getMessage()
                ),
                500
            );
        }

        if ($format == 'csv') {
            $filename = 'data.csv';
            $inline = false;
            $format = 'csv';

            $exportData = array();
            $exportData['title'] = array('title' => 'App Kernels Success Rates');
            $exportData['duration'] = array('from:' => $start_date, 'to' => $end_date);
            $exportData['headers'] = array_keys($results2[0]);

            $exportData['rows'] = $results2;

            $content = \DataWarehouse\ExportBuilder::export(array($exportData), $format, $inline, $filename);

            return new Response($content['results'], Response::HTTP_OK, $content['headers']);
        } else {
            if (isset($response)) {
                return $response;
            } else {
                return $app->json(
                    array(
                        'success' => false,
                        'message' => 'An undefined error has occurred while attempting to process the requested operation.'
                    ),
                    500
                );
            }
        }
    }

    /**
     * Retrieves the raw numeric values for the AppKernel Performance Map. This endpoint provides
     * the data for `CenterReportCardPortlet.js`
     *
     * **NOTE:** This function will throw an UnauthorizedException if the user making the request
     * does not have the Center Director or Center Staff acl.
     *
     * @param Request     $request
     * @param Application $app
     * @return JsonResponse
     * @throws Exception if there is a problem instantiating \DateTime objects.
     * @throws Exception if the user making the request is not a Center [Director|Staff]
     */
    public function getRawPerformanceMap(Request $request, Application $app)
    {
        $user = $this->authorize($request);

        // We need to ensure that only Center Director / Center Staff users are authorized to
        // utilize this endpoint. Note, we do not utilize the `requirements` parameter of the above
        // `authorize` call because it utilizes `XDUser::hasAcls` which only checks if the user has
        // *all* of the supplied acls, not any of the supplied acls.
        if (!$user->hasAcl(ROLE_ID_CENTER_DIRECTOR) ||
            !$user->hasAcl(ROLE_ID_CENTER_STAFF)) {
            throw  new UnauthorizedHttpException('xdmod', "Unable to complete action. User is not authorized.");
        }

        $startDate = $this->getStringParam($request, 'start_date', true);
        if ($startDate !== null) {
            $startDate = new \DateTime($startDate);
        }

        $endDate = $this->getStringParam($request, 'end_date', true);
        if ($endDate !== null) {
            $endDate = new \DateTime($endDate);
        }

        $appKernels = $this->getStringParam($request, 'app_kernels', false);
        if (strpos($appKernels, self::DEFAULT_DELIM) !== false) {
            $appKernels = explode(self::DEFAULT_DELIM, $appKernels);
        }

        $problemSizes = $this->getStringParam($request, 'problem_sizes', false);
        if (strpos($problemSizes, self::DEFAULT_DELIM) !== false) {
            $problemSizes = explode(self::DEFAULT_DELIM, $problemSizes);
        }

        $data = array();
        try {
            $perfMap = new \AppKernel\PerformanceMap(array(
                'start_date' => $startDate,
                'end_date' => $endDate,
                'resource' => $user->getResources(),
                'appKer' => $appKernels,
                'problemSize' => $problemSizes
            ));

            // The columns that we're going to be retrieving from the PerformanceMap and ultimately
            // returning to the requester.
            $valueCols = array(
                'failedRuns',
                'inControlRuns',
                'overPerformingRuns',
                'underPerformingRuns'
            );

            // Now that we have the app kernel data, iterate through and extract / sum data for presentation.
            foreach($perfMap->perfMap['runsStatus'] as $resource => $runData) {
                foreach($runData as $appKernel => $nodeCountData) {

                    // Values that we'll be collecting / summing by node count & date.
                    $values = array();
                    foreach($nodeCountData as $nodeCount => $byDateData) {
                        foreach($byDateData as $date => $runInfo) {

                            // Now that we've reached the data level, initialize or add in the data
                            // for the columns that we're interested in.
                            foreach($valueCols as $valueCol) {
                                if (!isset($values[$valueCol])) {
                                    $values[$valueCol] = 0;
                                }
                                $values[$valueCol] += count($runInfo->$valueCol);
                            }
                        }
                    }

                    $data[] = array_merge(
                        array(
                            'resource' => $resource,
                            'app_kernel' => $appKernel,
                        ),
                        $values
                    );
                }
            }

            $results = array(
                'success' => true,
                'results' => $data
            );
        } catch( Exception $e) {

            // make sure that we log the exception so that we dont lose sight of it.
            handle_uncaught_exception($e);

            $results = array(
                'success' => false,
                'message' => 'An unexpected error has occurred while retrieving the AppKernel Performance Map data.'
            );
        }

        return $app->json($results);
    }
}
