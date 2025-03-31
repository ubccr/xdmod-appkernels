<?php

namespace AppKernel;

use PDO;
use Psr\Log\LoggerInterface;
use xd_utilities;
use CCR\DB;
use Exception;

// ================================================================================
// Class for querying and manipulating the application kernel database.
// ================================================================================

// Include data class definitions

require_once "InstanceData.php";

if (!function_exists('stats_standard_deviation')) {
    /**
     * This user-land implementation follows the implementation quite strictly;
     * it does not attempt to improve the code or algorithm in any way. It will
     * raise a warning if you have fewer than 2 values in your array, just like
     * the extension does (although as an E_USER_WARNING, not E_WARNING).
     *
     * @param array $a
     * @param bool $sample [optional] Defaults to false
     * @return float|bool The standard deviation or false on error.
     */
    function stats_standard_deviation(array $a, $sample = false)
    {
        $n = count($a);
        if ($n === 0) {
            trigger_error("The array has zero elements", E_USER_WARNING);
            return false;
        }
        if ($sample && $n === 1) {
            trigger_error("The array has only 1 element", E_USER_WARNING);
            return false;
        }
        $mean = array_sum($a) / $n;
        $carry = 0.0;
        foreach ($a as $val) {
            $d = ((double)$val) - $mean;
            $carry += $d * $d;
        };
        if ($sample) {
            --$n;
        }
        return sqrt($carry / $n);
    }
}

class AppKernelDb
{
    // Handle to the database resource
    private $db = null;

    // Optional PEAR::Log for logging
    private $logger = null;

    // List or application kernel definitions
    private $appKernelDefinitions = null;

    // List of application kernel base names (with no processing units)
    private $akBaseNameList = null;

    // List of key/value pairs where the key is the resource nickname and the
    // value is the database resource id
    private $resourceList = null;

    // Multi-dimensional array of app kernels already in the database mapping to
    // the database id.  App kernels are uniquely identified by the name, number
    // of processing units, and version.
    private $akIdMap = null;

    // List of existing metric and their relationship to application kernels.
    //definitions.  Metrics are uniquely defined by the name,
    private $akMetrics = null;

    // List of metric guids mapped to metric ids
    private $akMetricGuids = null;

    // List of existing parameters
    private $akParameters = null;

    // List of parameter guids mapped to parameter ids
    private $akParameterGuids = null;

    //default control criteria in standard deviations
    private $control_criteria = 3.0;

    // --------------------------------------------------------------------------------
    // @param $logger A Pear::Log object (http://pear.php.net/package/Log/
    // @param $configSection The configuration section to be used to obtain
    //   database connection parameters.
    // --------------------------------------------------------------------------------

    public function __construct(LoggerInterface $logger = null, $configSection = null)
    {
        // If the configuration section is not explicitly specified, use the
        // APPLICATION_ENV constant to select the correct section

        $configSection = (null === $configSection
            //? "appkernel-" . APPLICATION_ENV
            ? "appkernel"
            : $configSection);

        $this->db = DB::factory($configSection);
        $this->modwDB = DB::factory('database');
        $this->logger = $logger !== null ? $logger : \CCR\Log::singleton('null');
    }

    // --------------------------------------------------------------------------------
    // @returns a ref to the db handle
    // --------------------------------------------------------------------------------

    public function getDB()
    {
        return $this->db;
    }
    // --------------------------------------------------------------------------------
    // Store metadata about an application kernel import/parsing session.
    //
    // @param $log Application kernel ingestion log entry that will be used to
    //   populate the database.
    //
    // @returns TRUE on success, FALSE otherwise.
    // --------------------------------------------------------------------------------

    public function storeIngestionLogEntry(IngestionLogEntry $log)
    {
        $sql =
            "INSERT INTO ingester_log
            (source, url, num, last_update, start_time, end_time, success, message, reportobj)
            VALUES (?, ?, ?, FROM_UNIXTIME(?), FROM_UNIXTIME(?), FROM_UNIXTIME(?), ?, ?, COMPRESS(?))";
        $params = array(
            $log->source,
            $log->url,
            $log->num,
            $log->last_update,
            $log->start_time,
            $log->end_time,
            $log->success,
            $log->message,
            $log->reportObj
        );
        $numRows = $this->db->execute($sql, $params);
        if (1 === $numRows) {
            $this->log("Added entry to ingestion log");
        }

        return (1 === $numRows);
    }

    // --------------------------------------------------------------------------------
    // Load metadata about the last successful import/parsing session.
    //
    // @param $log Reference to the application kernel ingestion log entry to be
    //   populated with the results.
    //
    // @returns TRUE on success, FALSE otherwise.
    // --------------------------------------------------------------------------------

    public function loadMostRecentIngestionLogEntry(IngestionLogEntry &$log)
    {
        $sql =
            "SELECT source, url, num, UNIX_TIMESTAMP(last_update) as last_update,
            UNIX_TIMESTAMP(start_time) as start_time, UNIX_TIMESTAMP(end_time) as end_time,
            success, message, UNCOMPRESS(reportobj) as reportobj FROM ingester_log
         WHERE success=1 ORDER BY last_update DESC LIMIT 1";
        $result = $this->db->query($sql);
        if (1 !== count($result)) {
            return false;
        }

        $row = array_shift($result);
        $log->source = $row['source'];
        $log->url = $row['url'];
        $log->num = $row['num'];
        $log->last_update = $row['last_update'];
        $log->start_time = $row['start_time'];
        $log->end_time = $row['end_time'];
        $log->success = $row['success'];
        $log->message = $row['message'];
        $log->reportObj = $row['reportobj'];

        return true;
    }

    // --------------------------------------------------------------------------------
    // Load application kernel definitions from the database.  By default only
    // those marked as enabled and visible will be loaded.  Supported criteria are:
    //   inc_disabled = {TRUE|FALSE} Load disabled kernels
    //   inc_hidden = {TRUE|FALSE} Load hidden kernels
    //   name = {string} The string must be contained in the kernel name
    //
    // @param $criteria An array of criteria for restricting the search.
    //
    // @returns An associative array where the key is the base app kernel name and
    //   the value is an AppKernelDefinition object
    // --------------------------------------------------------------------------------

    public function loadAppKernelDefinitions(array $criteria = null)
    {
        $this->akBaseNameList = array();
        $sqlParams = array();

        // Initialize default criteria to be overrided if necessary

        $sqlCriteria = array(
            'inc_disabled' => "enabled = 1",
            'inc_hidden' => "visible = 1"
        );

        if (null !== $criteria) {
            foreach ($criteria as $key => $value) {
                switch ($key) {
                    case 'inc_disabled':
                        $sqlCriteria[$key] = null;
                        break;
                    case 'inc_hidden':
                        $sqlCriteria[$key] = null;
                        break;
                    case 'filter':
                        $sqlCriteria[$key] = "name like ?";
                        $sqlParams[] = $this->convertWildcards($value);
                        break;
                    default:
                        break;
                }
            }
        }

        $sql = "SELECT ak_def_id, name, ak_base_name, description, enabled, visible,processor_unit FROM app_kernel_def";
        if (0 != count($sqlCriteria)) {
            $sql .= " WHERE " . implode(" AND ", $sqlCriteria);
        }

        $this->logger->debug(
            array(
                'message' => 'Executing query',
                'sql' => $sql,
                'params' => json_encode($sqlParams),
            )
        );
        $result = $this->db->query($sql, $sqlParams);

        while (false !== ($row = current($result))) {
            $basename = $row['ak_base_name'];
            if (empty($basename)) {
                $this->log("App kernel definition {$row['name']} has no base name, skipping.");
                continue;
            }

            $akDef = new AppKernelDefinition(
                $row['ak_def_id'],
                $row['name'],
                $row['ak_base_name'],
                $row['description'],
                $row['processor_unit'],
                $row['enabled'],
                $row['visible']
            );

            $this->appKernelDefinitions[$basename] = $akDef;
            $this->akBaseNameList[] = $basename;
            next($result);
        }

        return $this->appKernelDefinitions;
    }

    // --------------------------------------------------------------------------------
    // Given a value to be used in a WHERE clause, scan it for API wildcards and
    // convert them to SQL wildcards.  If wildcards were found then convert them
    // to SQL wildcards and return TRUE.
    //
    // @param $value Reference to the value that will be used in the WHERE clause
    //
    // @returns TRUE if wildcards were found
    // --------------------------------------------------------------------------------

    private function convertWildcards(&$value)
    {
        if ("*" == $value[0] || "*" == $value[strlen($value) - 1]) {
            $value = preg_replace('/^\*|\*$/', '%', $value);
        }
        return $value;
    }

    // --------------------------------------------------------------------------------
    // Extract optional operators from the start of an argument value and return
    // both the operator and argument value minus the operator.  Valid operators
    // are:
    //
    // Equal: = (default, used if no operator is present)
    // Not equal: !
    // Less Than: <
    // Greater Than: >
    //
    // @param $value Reference to the argument value to be searched, if an
    //   argument is found it will be removed
    // @param $op Reference to the operator found or "=" by default
    //
    // @returns TRUE if an operator was found, FALSE otherwise.
    // --------------------------------------------------------------------------------

    private function extractOperator(&$value, &$op)
    {
        $validOperators = array(
            '=' => '=',
            '!' => '!=',
            '>' => '>',
            '<' => '<'
        );
        $op = "=";
        $operatorFound = false;

        // Bools have no operator
        if (is_bool($value)) {
            return $operatorFound;
        }

        if (array_key_exists($value[0], $validOperators)) {
            $op = $validOperators[$value[0]];
            $value = substr($value, 1);
            $operatorFound = true;
        }

        return $operatorFound;
    }

    /**
     * Get conditions for sql where clause on processing units number
     *
     * @param array $pu_counts array with processing units
     * @return array array with conditions for sql where clause
     */
    private function getProcessorCountWheres(array $pu_counts = array())
    {
        $processorCountWheres = array();
        foreach ($pu_counts as $pu_count) {
            $pu_count = intval($pu_count);
            $processorCountWheres[] = "vt.num_units = $pu_count";
        }

        return $processorCountWheres;
    }

    /**
     * Get metrics wheres for SQL select
     *
     * @param array $metrics array of strings like ak_<ak_def_id>_metric_<metric_id>
     * @return array of string with SQL conditions for select
     */
    private function getMetricWheres(array $metrics = array())
    {
        $metricWheres = array();
        foreach ($metrics as $metric) {
            if (preg_match('/ak_(?P<ak>\d+)_metric_(?P<metric>\d+)_(?P<pu>\d+)/', $metric, $matches)) {
                $metricWheres[] = "(vt.ak_def_id = {$matches['ak']} and vt.metric_id = {$matches['metric']} and vt.num_units = {$matches['pu']})";
            } else {
                if (preg_match('/ak_(?P<ak>\d+)_metric_(?P<metric>\d+)/', $metric, $matches)) {
                    $metricWheres[] = "(vt.ak_def_id = {$matches['ak']} and vt.metric_id = {$matches['metric']})";
                }
            }
        }
        return $metricWheres;
    }

    // --------------------------------------------------------------------------------

    public function getResources(
        $start_date = null,
        $end_date = null,
        array $pu_counts = array(),
        array $metrics = array(),
        $user = null
    ) {
        $processorCountWheres = $this->getProcessorCountWheres($pu_counts);
        $metricWheres = $this->getMetricWheres($metrics);

        $sql = "SELECT distinct r.resource_id, r.resource, r.nickname, r.description , r.enabled, r.visible, r.xdmod_resource_id, r.xdmod_cluster_id
            FROM `a_tree` vt, resource r
        where vt.resource_id = r.resource_id " .
            ($start_date !== null ? " and '$start_date' <= end_time " : " ") .
            ($end_date !== null ? " and '$end_date' >= start_time " : " ") .
            (count($processorCountWheres) > 0 ? " and ( " . implode(' or ', $processorCountWheres) . " ) " : " ") .
            (count($metricWheres) > 0 ? " and ( " . implode(' or ', $metricWheres) . " ) " : " ") .
            " order by r.resource ";

        $results = $this->db->query($sql);

        $resources = array();
        foreach ($results as $row) {
            $resource = new AKResource;
            $resource->id = $row['resource_id'];
            $resource->nickname = $row['nickname'];
            $resource->name = $row['resource'];
            $resource->description = $row['description'];
            $resource->enabled = $row['enabled'];
            $resource->visible = $row['visible'];
            $resource->xdmod_resource_id = $row['xdmod_resource_id'];
            $resource->xdmod_cluster_id = $row['xdmod_cluster_id'];
            $resources[$row['nickname']] = $resource;
        }

        if ($user !== null) {
            $roles = $user->getRoles();

            if (in_array("mgr", $roles) || in_array("po", $roles) || in_array("dev", $roles)) {
                //$allResources
            } elseif (in_array("cd", $roles) || in_array("cs", $roles)) {
                //get all associated organization
                $organizations = array(
                    $user->getOrganizationID()
                );

                // get resource_id for all associated resources
                $rr = $this->getResourceListing(
                    date_format(
                        date_sub(
                            date_create(),
                            date_interval_create_from_date_string("90 days")
                        ),
                        'Y-m-d'
                    ),
                    date_format(date_create(), 'Y-m-d')
                );

                $organizations_resources_id = array();
                foreach ($rr as $r) {
                    if (in_array($r["organization_id"], $organizations)) {
                        $organizations_resources_id[] = $r["id"];
                    }
                }

                $allResources2 = array();
                foreach ($resources as $resource) {
                    if (in_array($resource->xdmod_resource_id, $organizations_resources_id)) {
                        $allResources2[] = $resource;
                    }
                }
                $resources = $allResources2;
            } else {
                $resources = array();
            }
        }

        return $resources;
    }

    /**
     * This function has been lifted from the XSEDE Modules Compliance class so that AppKernels can
     * be used by Open Source Users. Retrieves a list of resources filtered by the optionally provided
     * `$start_date` and `$end_date`
     *
     * @param string|null $start_date
     * @param string|null $end_date
     * @return mixed
     */
    public function getResourceListing($start_date = null, $end_date = null)
    {
        // Order by descending end_date and processors

        $ts = "";

        // Resources which have no end date are considered still active so use the max date
        // possible. Resources are sorted in order of decreasing end date so this will keep active
        // resources at the top of the list.

        $m = "CASE WHEN rf.end_date IS NULL THEN '9999-12-31' ELSE rf.end_date END";

        $query_params = array();

        if (isset($start_date) && isset($end_date)) {
            /*

            Account for ALL resources (in other words, an overlap between the supplied timeframe and the resource timeframe)

            S(t)                         E(t)
            |----------------------------|
            |------------------------|
            S(r)                     E(r)


            (t): Supplied timeframe
            (r): A resource

            Overlap exists when S(r) <= E(t) AND E(r) >= S(t)

            */

            $ts .= " AND rf.start_date <= :ts_start_date_lte AND ($m) >= :ts_end_date_gte";

            $query_params[':ts_start_date_lte'] = $end_date;
            $query_params[':ts_end_date_gte'] = $start_date;
        }

        $query = "
        SELECT
            rf.code,
            rf.organization_id,
            rf.id,
            rt.description,
            rt.abbrev,
            CASE
                WHEN rf.end_date IS NULL THEN 'N/A'
                ELSE DATE_FORMAT(rf.end_date, '%Y-%m-%d')
            END AS official_end_date,
            $m AS resource_end_date,
            DATE_FORMAT(rf.start_date, '%Y-%m-%d') AS resource_start_date,
            CASE
            WHEN rs.processors IS NULL THEN 0
            ELSE rs.processors
            END AS processors
        FROM
            modw.resourcefact AS rf,
            modw.resourcespecs rs,
            modw.resourcetype AS rt
        WHERE
            rf.id = rs.resource_id
        AND rf.resourcetype_id = rt.id
        AND rt.abbrev IN ('HPC', 'HTC', 'DIC', 'Vis', 'Disk', 'Cloud')
        AND rf.code NOT LIKE 'TG%'
        $ts
        AND UNIX_TIMESTAMP(:start_date_lte) >= rs.start_date_ts
        AND (
                   rs.end_date_ts IS NULL
                OR UNIX_TIMESTAMP(:end_date_gte) <= rs.end_date_ts
            )
        ORDER BY
            rs.processors DESC,
            resource_end_date DESC,
            rf.code DESC";

        $query_params[':start_date_lte'] = $end_date;
        $query_params[':end_date_gte'] = $end_date;

        return $this->modwDB->query($query, $query_params);
    }

    /**
     * Get Processing units for resources/appkernel/metrics between $start_date and $end_date.
     *
     * @param null $start_date Start Date
     * @param null $end_date End Date
     * @param array $resource_ids Resources IDs
     * @param array $metrics Metrics string IDs (ak_<ak_def_id>_metric_<metric_id>)
     * @return array of ProcessingUnit
     */
    public function getProcessingUnits(
        $start_date = null,
        $end_date = null,
        array $resource_ids = array(),
        array $metrics = array()
    ) {
        $processingUnitList = array();
        $metricWheres = $this->getMetricWheres($metrics);

        $sql = "SELECT distinct vt.num_units, vt.processor_unit FROM `a_tree` vt, app_kernel_def akd " .
            "WHERE vt.ak_def_id = akd.ak_def_id  ";
        $params = array();
        if ($start_date !== null) {
            $sql .= 'AND :start_date <= end_time ';
            $params[':start_date'] = $start_date;
        }
        if ($end_date !== null) {
            $sql .= 'AND :end_date >= start_time ';
            $params[':end_date'] = $end_date;
        }
        if (count($resource_ids) > 0) {
            $sql .= ' AND vt.resource_id in (';
            for ($i = 0; $i < count($resource_ids); $i++) {
                $sql .= ':resource_id_' . $i;
                if ($i < count($resource_ids) - 1) {
                    $sql .= ',';
                }
                $params[':resource_id_' . $i] = $resource_ids[$i];
            }
            $sql .= ')';
        }
        if (count($metricWheres) > 0) {
            $sql .= " and ( " . implode(' or ', $metricWheres) . " ) ";
        }

        $sql .= "ORDER BY vt.processor_unit, vt.num_units ";

        $results = $this->db->query($sql, $params);

        foreach ($results as $row) {
            $processing_unit = new ProcessingUnit;
            $processing_unit->unit = $row['processor_unit'];
            $processing_unit->count = $row['num_units'];
            $processingUnitList[] = $processing_unit;
        }
        return $processingUnitList;
    }

    /**
     * Get list of unique appkernels on $resource_ids. In case of multiple resources appkernel should be
     * at least at one.
     *
     * @param array $resource_ids
     * @param array $node_counts
     * @param array $core_counts
     * @return array
     */
    public function getUniqueAppKernels(
        array $resource_ids = array(),
        array $node_counts = array(),
        array $core_counts = array()
    ) {
        $processorCountWheres = $this->getProcessorCountWheres($node_counts, $core_counts);

        $sql = "SELECT distinct vt.ak_def_id, vt.ak_name, akd.description, akd.ak_base_name, akd.processor_unit, " .
            "unix_timestamp(min(start_time)) as start_ts, unix_timestamp(max(end_time)) as end_ts " .
            "FROM `a_tree` vt, app_kernel_def akd " .
            "WHERE vt.ak_def_id = akd.ak_def_id AND akd.enabled = 1 AND akd.visible = 1";
        $params = array();

        if (count($resource_ids) > 0) {
            $sql .= ' AND vt.resource_id in (';
            for ($i = 0; $i < count($resource_ids); $i++) {
                $sql .= ':resource_id_' . $i;
                if ($i < count($resource_ids) - 1) {
                    $sql .= ',';
                }
                $params[':resource_id_' . $i] = intval($resource_ids[$i]);
            }
            $sql .= ')';
        }
        if (count($processorCountWheres) > 0) {
            $sql .= " AND ( " . implode(' OR ', $processorCountWheres) . " ) ";
        }
        $sql .= " GROUP BY vt.ak_def_id  ORDER BY vt.ak_name ";

        $result = $this->db->query($sql, $params);

        $uniqueAppKernelList = array();

        foreach ($result as $row) {
            $ak_def = new AppKernelDefinition(
                $row['ak_def_id'],
                $row['ak_name'],
                $row['ak_base_name'],
                $row['description'],
                $row['processor_unit'],
                true,
                true,
                $row['start_ts'],
                $row['end_ts']
            );
            $uniqueAppKernelList[$row['ak_def_id']] = $ak_def;
        }
        return $uniqueAppKernelList;
    }

    /**
     * Get Metrics obtained between $start_date and $end_date for $ak_def_id on $resource_ids
     *
     * @param $ak_def_id
     * @param null $start_date
     * @param null $end_date
     * @param array $resource_ids
     * @param array $pu_counts
     * @param array $metric_names
     * @return array
     */
    public function getMetrics(
        $ak_def_id,
        $start_date = null,
        $end_date = null,
        array $resource_ids = array(),
        array $pu_counts = array(),
        array $metric_names = array()
    ) {
        $processorCountWheres = $this->getProcessorCountWheres($pu_counts);

        $sql = "SELECT distinct vt.metric_id, vt.metric, vt.unit, vt.processor_unit, vt.num_units " .
            "FROM `a_tree` vt, app_kernel_def akd " .
            "WHERE vt.ak_def_id = akd.ak_def_id and akd.enabled = 1 and akd.visible = 1 ";
        $params = array();

        if ($ak_def_id !== null) {
            $sql .= 'AND vt.ak_def_id = :ak_def_id ';
            $params[':ak_def_id'] = $ak_def_id;
        }
        if ($start_date !== null) {
            $sql .= 'AND :start_date <= end_time ';
            $params[':start_date'] = $start_date;
        }
        if ($end_date !== null) {
            $sql .= 'AND :end_date >= start_time ';
            $params[':end_date'] = $end_date;
        }
        if (count($resource_ids) > 0) {
            $sql .= ' AND vt.resource_id in (';
            for ($i = 0; $i < count($resource_ids); $i++) {
                $sql .= ':resource_id_' . $i;
                if ($i < count($resource_ids) - 1) {
                    $sql .= ',';
                }
                $params[':resource_id_' . $i] = intval($resource_ids[$i]);
            }
            $sql .= ')';
        }
        if (count($metric_names) > 0) {
            $sql .= ' AND vt.metric in (';
            for ($i = 0; $i < count($metric_names); $i++) {
                $sql .= ':metric_name_' . $i;
                if ($i < count($metric_names) - 1) {
                    $sql .= ',';
                }
                $params[':metric_name_' . $i] = $metric_names[$i];
            }
            $sql .= ')';
        }


        if (count($processorCountWheres) > 0) {
            $sql .= "AND ( " . implode(' OR ', $processorCountWheres) . " ) ";
        }

        $sql .= "ORDER BY vt.metric ";

        $results = $this->db->query($sql, $params);

        $metrics = array();
        foreach ($results as $row) {
            $metrics[$row['metric_id']] = new InstanceMetric($row['metric'], null, $row['unit'], $row['metric_id']);
        }
        return $metrics;
    }

    // --------------------------------------------------------------------------------

    public function getDataset(
        $akId,
        $resourceId,
        $metricId,
        $numProcUnits,
        $startTime,
        $endTime,
        $metadataOnly = false,
        $debugMode = false,
        $datesAsEpoch = true,
        $maximizeQueryCacheUsage = true
    ) {
        $restrictions = array(
            'ak' => $akId,
            'start' => $startTime,
            'end' => $endTime,
            'metadata_only' => $metadataOnly,
            'debug' => $debugMode,
            'dates_as_epoch' => $datesAsEpoch
        );

        if ($maximizeQueryCacheUsage === false) //this will make the queries as specific as possible
        {
            $restrictions['resource'] = $resourceId;
            $restrictions['metric'] = $metricId;
            $restrictions['num_units'] = $numProcUnits;
        }

        // Load the application kernel definitions for the description

        $appKernelDefs = $this->loadAppKernelDefinitions();
        $akList = array();
        foreach ($appKernelDefs as $ak) {
            $akList[$ak->id] = $ak;
        }

        // Load the resource definitions for the description

        $resourceDefs = $this->loadResources();
        $resourceList = array();
        foreach ($resourceDefs as $res) {
            $resourceList[$res->id] = $res;
        }

        list($query, $params) = $this->getDataPointsQuery($restrictions);

        $retStatement = $this->getDB()->query($query, $params, true);
        $retStatement->execute();

        $prevEnvVersion = null;
        $prev = new Tuple(4);
        $current = new Tuple(4);

        $datasetList = array();
        $dataset = null;

        while ($row = $retStatement->fetch(\PDO::FETCH_ASSOC, \PDO::FETCH_ORI_NEXT)) {
            // Amin needs the query to return all data for building thumbnails so skip
            // unwanted records here.

            if ($resourceId != null && $row['resource_id'] != $resourceId) {
                continue;
            }
            if ($metricId != null && $row['metric_id'] != $metricId) {
                continue;
            }
            if ($numProcUnits != null && $row['num_units'] != $numProcUnits) {
                continue;
            }

            $current->set($row['ak_name'], $row['resource'], $row['metric'], $row['num_units']);

            if ($current != $prev) {
                if (null !== $dataset) {
                    $datasetList[] = $dataset;
                }
                $dataset = new Dataset(
                    $row['ak_name'],
                    $row['ak_def_id'],
                    $row['resource'],
                    $row['resource_id'],
                    $row['metric'],
                    $row['metric_id'],
                    $row['unit'],
                    $row['num_units'] . " " . $row['processor_unit'] . ($row['num_units'] > 1 ? "s" : ""),
                    $akList[$row['ak_def_id']]->description,
                    $row['num_units'],
                    $resourceList[$row['resource_id']]->description
                );
                if (!$metadataOnly) {
                    $prevEnvVersion = $row['env_version'];
                }
            }

            if (!$metadataOnly) {
                $dataset->valueVector[] = $row['metric_value'];
                $dataset->timeVector[] = $row['collected'];
                $dataset->controlVector[] = $row['control'];
                $dataset->controlStartVector[] = $row['controlStart'];
                $dataset->controlEndVector[] = $row['controlEnd'];
                $dataset->controlMinVector[] = $row['controlMin'];
                $dataset->controlMaxVector[] = $row['controlMax'];
                $dataset->runningAverageVector[] = $row['running_average'];
                $dataset->versionVector[] = ($prevEnvVersion == $row['env_version'] ? 0 : 1);
                $dataset->controlStatus[] = $row['controlStatus'];
                $prevEnvVersion = $row['env_version'];
            }
            $prev->set($row['ak_name'], $row['resource'], $row['metric'], $row['num_units']);
        }

        if (null !== $dataset) {
            $datasetList[] = $dataset;
        }
        return $datasetList;
    }

    // --------------------------------------------------------------------------------
    // Load resources from the database.  By default only those marked as enabled
    // and visible will be loaded.  Supported criteria are:
    //   inc_disabled = {TRUE|FALSE} Load disabled kernels
    //   inc_hidden = {TRUE|FALSE} Load hidden kernels
    //   filter = {string} The string must be contained in the resource name
    //
    // @param $criteria An array of criteria for restricting the search.
    //
    // @returns An associative array of resources where the key is the resource
    //   nickname (short name) and the value is the database resource identifier.
    // --------------------------------------------------------------------------------

    public function loadResources(array $criteria = null)
    {
        $sqlParams = array();

        // Initialize default criteria to be overrided if necessary

        $sqlCriteria = array(
            'inc_disabled' => "enabled = 1",
            'inc_hidden' => "visible = 1"
        );

        if (null !== $criteria) {
            foreach ($criteria as $key => $value) {
                switch ($key) {
                    case 'inc_disabled':
                        $sqlCriteria[$key] = null;
                        break;
                    case 'inc_hidden':
                        if ($value) {
                            unset($sqlCriteria[$key]);
                        }
                        break;
                    case 'filter':
                        $sqlCriteria[$key] = "nickname like ?";
                        $sqlParams[] = $this->convertWildcards($value);
                        break;
                    default:
                        break;
                }
            }
        }

        $sql = "SELECT resource_id, resource, nickname, description , enabled, visible, xdmod_resource_id FROM resource";
        if (0 != count($sqlCriteria)) {
            $sql .= " WHERE " . implode(" AND ", $sqlCriteria);
        }

        // load xdmod resources from modw to ensure that xdmod_resource_id refer to existent resource
        $result = $this->modwDB->query("SELECT id, name FROM modw.resourcefact");
        $xdmod_resource = array();
        while (false !== ($row = current($result))) {
            $xdmod_resource[$row['id']] = $row['name'];
            next($result);
        }

        // load resources
        $result = $this->db->query($sql, $sqlParams);

        while (false !== ($row = current($result))) {
            $resource = new AKResource;
            $resource->id = $row['resource_id'];
            $resource->nickname = $row['nickname'];
            $resource->name = $row['resource'];
            $resource->description = $row['description'];
            $resource->enabled = $row['enabled'];
            $resource->visible = $row['visible'];
            $resource->xdmod_resource_id = $row['xdmod_resource_id'];
            if ( !is_null($resource->xdmod_resource_id) && !isset($xdmod_resource[$resource->xdmod_resource_id])) {
                $resource->xdmod_resource_id = null;
            }

            $this->resourceList[$row['nickname']] = $resource;
            next($result);
        }

        return $this->resourceList;
    }

    // --------------------------------------------------------------------------------
    // Load application kernel definitions from the database.  By default only
    // those marked as enabled and visible will be loaded.  The criteria are
    // cumulative and specified using an associative array where supported
    // criteria are:
    //   ak = app kernel definition id
    //   resource => resource id
    //   metric => metric id
    //   num_units => number of processing units
    //   group_by => criteria to group by (eliminates duplicates)
    //   start => timestamp marking the start of the search window
    //   end => timestamp marking the end of the search window
    //
    // @param $criteria An associative array of cumulative criteria for
    //   restricting the search.
    //
    // @returns An associative array where the key is the base app kernel name and
    //   the value is an AppKernelDefinition object
    // --------------------------------------------------------------------------------

    public function loadTreeLevel(array $criteria = null)
    {
        $sqlParams = array();
        $sqlCriteria = array();
        $groupBy = null;
        $orderBy = null;
        $debugMode = (array_key_exists('debug', $criteria) && $criteria['debug']);

        $sql = "SELECT * " . ($debugMode ? '' : ", unix_timestamp(min(start_time)) as start_ts, unix_timestamp(max(end_time)) as end_ts ") . "  FROM " . ($debugMode ? "v_tree_debug" : "a_tree");

        if (null !== $criteria && 0 !== count($criteria)) {
            foreach ($criteria as $key => $value) {
                if (null === $value) {
                    continue;
                }
                switch ($key) {
                    case 'ak':
                        $sqlCriteria[] = "ak_def_id = ?";
                        $sqlParams[] = $value;
                        break;
                    case 'resource':
                        $sqlCriteria[] = "resource_id = ?";
                        $sqlParams[] = $value;
                        break;
                    case 'metric':
                        $sqlCriteria[] = "metric_id = ?";
                        $sqlParams[] = $value;
                        break;
                    case 'num_units':
                        $sqlCriteria[] = "num_units = ?";
                        $sqlParams[] = $value;
                        break;
                    case 'status':
                        $sqlCriteria[] = "status = ?";
                        $sqlParams[] = $value;
                        break;
                    case 'start':
                        if ($debugMode) {
                            $sqlCriteria[] = "? <= collected";
                        } else {
                            $sqlCriteria[] = "FROM_UNIXTIME(?) <= end_time";
                        }
                        $sqlParams[] = $value;
                        break;
                    case 'end':
                        if ($debugMode) {
                            $sqlCriteria[] = "? >= collected";
                        } else {
                            $sqlCriteria[] = "FROM_UNIXTIME(?) >= start_time";
                        }
                        $sqlParams[] = $value;
                        break;
                    default:
                        break;
                }
            }
        }

        // Use the group by to return distinct values for the tree.

        if (array_key_exists('group_by', $criteria) && null !== $criteria['group_by']) {
            switch ($criteria['group_by']) {
                case 'ak':
                    $groupBy = 'ak_def_id';
                    $orderBy = 'ak_name';
                    break;
                case 'resource':
                    $groupBy = 'resource_id';
                    $orderBy = 'resource';
                    break;
                case 'metric':
                    $groupBy = 'metric_id';
                    $orderBy = 'metric';
                    break;
                case 'num_proc_units':
                    $groupBy = 'num_units';
                    $orderBy = 'num_units';
                    break;
                default:
                    break;
            }
        }

        if (0 !== count($sqlCriteria)) {
            $sql .= " WHERE " . implode(" AND ", $sqlCriteria);
        }
        if (null !== $groupBy) {
            $sql .= " GROUP BY $groupBy";
        }
        if (null !== $orderBy) {
            $sql .= " ORDER BY $orderBy";
        }

        $result = $this->db->query($sql, $sqlParams);
        $retval = array();

        while (false !== ($row = current($result))) {
            $retval[] = $row;
            next($result);
        }

        return $retval;
    }

    // --------------------------------------------------------------------------------
    // Load application kernel definitions from the database.  By default only
    // those marked as enabled and visible will be loaded.  The criteria are
    // cumulative and specified using an associative array where supported
    // criteria are:
    //   ak = app kernel definition id
    //   resource => resource id
    //   metric => metric id
    //   num_units => number of processing units
    //   group_by => criteria to group by (eliminates duplicates)
    //   start => timestamp marking the start of the search window
    //   end => timestamp marking the end of the search window
    //
    // @param $criteria An associative array of cumulative criteria for
    //   restricting the search.
    //
    // @returns An associative array where the key is the base app kernel name and
    //   the value is an AppKernelDefinition object
    // --------------------------------------------------------------------------------

    public function loadTreeLevelDebug(array $criteria = null)
    {
        $sqlParams = array();
        $sqlCriteria = array();
        $groupBy = null;
        $orderBy = null;
        $debugMode = (array_key_exists('debug', $criteria) && $criteria['debug']);

        $sql = "SELECT * FROM " . ($debugMode ? "v_tree_debug" : "a_tree");

        if (null !== $criteria && 0 !== count($criteria)) {
            foreach ($criteria as $key => $value) {
                if (null === $value) {
                    continue;
                }
                switch ($key) {
                    case 'ak':
                        $sqlCriteria[] = "ak_def_id = ?";
                        $sqlParams[] = $value;
                        break;
                    case 'resource':
                        $sqlCriteria[] = "resource_id = ?";
                        $sqlParams[] = $value;
                        break;
                    case 'metric':
                        $sqlCriteria[] = "metric_id = ?";
                        $sqlParams[] = $value;
                        break;
                    case 'num_units':
                        $sqlCriteria[] = "num_units = ?";
                        $sqlParams[] = $value;
                        break;
                    case 'status':
                        $sqlCriteria[] = "status = ?";
                        $sqlParams[] = $value;
                        break;
                    case 'start':
                        if ($debugMode) {
                            $sqlCriteria[] = "? <= collected";
                        } else {
                            $sqlCriteria[] = "FROM_UNIXTIME(?) <= end_time";
                        }
                        $sqlParams[] = $value;
                        break;
                    case 'end':
                        if ($debugMode) {
                            $sqlCriteria[] = "? >= collected";
                        } else {
                            $sqlCriteria[] = "FROM_UNIXTIME(?) >= start_time";
                        }
                        $sqlParams[] = $value;
                        break;
                    default:
                        break;
                }
            }
        }

        // Use the group by to return distinct values for the tree.

        if (array_key_exists('group_by', $criteria) && null !== $criteria['group_by']) {
            switch ($criteria['group_by']) {
                case 'ak':
                    $groupBy = 'ak_def_id';
                    $orderBy = 'ak_name';
                    break;
                case 'resource':
                    $groupBy = 'resource_id';
                    $orderBy = 'resource';
                    break;
                case 'metric':
                    $groupBy = 'metric_id';
                    $orderBy = 'metric';
                    break;
                case 'num_proc_units':
                    $groupBy = 'num_units';
                    $orderBy = 'num_units';
                    break;
                default:
                    break;
            }
        }

        if (0 !== count($sqlCriteria)) {
            $sql .= " WHERE " . implode(" AND ", $sqlCriteria);
        }
        if (null !== $groupBy) {
            $sql .= " GROUP BY $groupBy";
        }
        if (null !== $orderBy) {
            $sql .= " ORDER BY $orderBy";
        }

        $result = $this->db->query($sql, $sqlParams);
        $retval = array();
        while (false !== ($row = current($result))) {
            $retval[] = $row;
            next($result);
        }

        return $retval;
    }

    // --------------------------------------------------------------------------------
    // Get the query string to retrieve the  application kernel definitions from the
    // database. By default only those marked as enabled and visible will be loaded.
    // The criteria are cumulative and specified using an associative array where
    // supported criteria are:
    //   ak = app kernel definition id
    //   resource => resource id
    //   metric => metric id
    //   num_units => number of processing units
    //   group_by => criteria to group by (eliminates duplicates)
    //   start => timestamp marking the start of the search window
    //   end => timestamp marking the end of the search window
    //
    // @param $criteria An associative array of cumulative criteria for
    //   restricting the search.
    //
    // @returns A sql query string
    // --------------------------------------------------------------------------------

    public function getDataPointsQuery(array $criteria = null)
    {
        $sqlParams = array();
        $sqlCriteria = array();

        // If requesting only metadata use the a_tree view to reduce the amount of
        // data returned.

        $metadataOnly = (isset($criteria['metadata_only']) && $criteria['metadata_only']);
        $datesAsEpoch = (isset($criteria['dates_as_epoch']) && $criteria['dates_as_epoch']);

        $sql = "SELECT * FROM " . ($metadataOnly ? "a_tree2" : "a_data2");

        if (null !== $criteria && 0 !== count($criteria)) {
            foreach ($criteria as $key => $value) {
                if (null === $value) {
                    continue;
                }
                switch ($key) {
                    case 'ak':
                        $sqlCriteria[] = "ak_def_id = ?";
                        $sqlParams[] = $value;
                        break;
                    case 'resource':
                        $sqlCriteria[] = "resource_id = ?";
                        $sqlParams[] = $value;
                        break;
                    case 'metric':
                        $sqlCriteria[] = "metric_id = ?";
                        $sqlParams[] = $value;
                        break;
                    case 'num_units':
                        $sqlCriteria[] = "num_units = ?";
                        $sqlParams[] = $value;
                        break;
                    case 'start':
                        if ($metadataOnly) {
                            $sqlCriteria[] = "? <= UNIX_TIMESTAMP(end_time)";
                        } else {
                            if ($datesAsEpoch) {
                                $sqlCriteria[] = "? <= collected";
                            } else {
                                $sqlCriteria[] = "UNIX_TIMESTAMP(?) <= collected";
                            }
                        }
                        $sqlParams[] = $value;
                        break;
                    case 'end':
                        if ($metadataOnly) {
                            $sqlCriteria[] = "(UNIX_TIMESTAMP(?)+86399) >= UNIX_TIMESTAMP(start_time)";
                        } else {
                            if ($datesAsEpoch) {
                                $sqlCriteria[] = "? >= collected";
                            } else {
                                $sqlCriteria[] = "(UNIX_TIMESTAMP(?)+86399) >= collected";
                            }
                        }
                        $sqlParams[] = $value;
                        break;
                    default:
                        break;
                }
            }
        }
        $sqlCriteria[] = " status = 'success' ";
        if (0 != count($sqlCriteria)) {
            $sql .= " WHERE " . implode(" AND ", $sqlCriteria);
        }

        // the datasets need to guarantee order for the plotAction to work correctly - AG 4/12/11
        $sql .= ' ORDER BY ak_name, resource, metric, num_units, collected';

        return array($sql, $sqlParams);
    }

    // --------------------------------------------------------------------------------
    // Load application kernel definitions from the database.  By default only
    // those marked as enabled and visible will be loaded.  The criteria are
    // cumulative and specified using an associative array where supported
    // criteria are:
    //   ak = app kernel definition id
    //   resource => resource id
    //   metric => metric id
    //   num_units => number of processing units
    //   group_by => criteria to group by (eliminates duplicates)
    //   start => timestamp marking the start of the search window
    //   end => timestamp marking the end of the search window
    //
    // @param $criteria An associative array of cumulative criteria for
    //   restricting the search.
    //
    // @returns An associative array where the key is the base app kernel name and
    //   the value is an AppKernelDefinition object
    // --------------------------------------------------------------------------------

    public function loadDataPoints(array $criteria = null)
    {
        list($sql, $sqlParams) = $this->getDataPointsQuery($criteria);

        $result = $this->db->query($sql, $sqlParams);
        $retval = array();
        while (false !== ($row = current($result))) {
            $row['sql'] = $sql . " (" . implode(",", $sqlParams) . ")";
            $retval[] = $row;
            next($result);
        }

        return $retval;
    }

    // --------------------------------------------------------------------------------
    // Load application kernels from the database.  A single application kernel
    // definition may have multiple application kernels associated with it, each
    // with a different number of processing units.  By default, only those
    // associated with enabled application kernel definitions are loaded.
    //
    // @param $loadDisabled Load application kernels that are marked as disabled
    //   in the database.
    //
    // @returns A list of application kernel names
    // --------------------------------------------------------------------------------

    public function loadAppKernels($loadDisabled = false, $returnMap = false)
    {
        $sql =
            "SELECT ak_base_name as name, ak_id, num_units
            FROM app_kernel_def
            JOIN app_kernel USING(ak_def_id)" .
            ($loadDisabled ? "" : " WHERE enabled=1");
        $result = $this->db->query($sql);

        reset($result);
        $this->akIdMap = array();

        while (false !== ($row = current($result))) {
            $this->akIdMap[$row['name']][$row['num_units']] = $row['ak_id'];
            next($result);
        }

        if ($returnMap) {
            return $this->akIdMap;
        } else {
            return array_keys($this->akIdMap);
        }
    }

    // --------------------------------------------------------------------------------
    // Load application kernel execution instances from the database.  Only basic
    // information about the instance will be loaded, not acutal metrics or
    // paramters.
    //
    // @param $appKernelId Optional application kernel id for restricting instances
    // @param $resourceId Optional resource id for restricting instances
    //
    // @returns A list of application kernel instance records with fields ak_id,
    //   num_units, version, collected, and status.
    // --------------------------------------------------------------------------------

    public function loadAppKernelInstances($ak_def_id = null, $resource_id = null)
    {
        $criteriaList = array();
        $instanceList = array();

        $sql = "SELECT i.ak_id, ak.num_units, collected, status " .
            "FROM ak_instance i " .
            "JOIN app_kernel ak USING(ak_id)" .
            "JOIN resource r USING(resource_id)";
        $params = array();

        if ($ak_def_id !== null) {
            $criteriaList[] = "i.ak_def_id = :ak_def_id";
            $params[":ak_def_id"] = $ak_def_id;
        }
        if ($resource_id !== null) {
            $criteriaList[] = "resource_id = :resource_id";
            $params[":resource_id"] = $resource_id;
        }

        if (count($criteriaList) !== 0) {
            $sql .= " WHERE " . implode(" AND ", $criteriaList);
        }

        $sql .= " ORDER BY collected DESC";
        $result = $this->db->query($sql, $params);

        foreach ($result as $row) {
            $ak = new InstanceData;
            $ak->db_ak_id = intval($row['ak_id']);
            $ak->deployment_num_proc_units = intval($row['num_units']);
            $ak->deployment_time = strtotime($row['collected']);
            $ak->status = $row['status'];
            $instanceList[] = $ak;
        }

        return $instanceList;
    }

    // --------------------------------------------------------------------------------
    // Load detailed information about a specific application kernel execution
    // instance.  Instances are identified by an application kernel id, time
    // collected, and resource.
    //
    // @param $akId The application kernel id
    // @param $collected The time collected formatted as a unix timestamp
    // @param $resourceId The resource id of the target resource
    // @param $ak An instance of an AppKernelInstance object to be populated
    // @param $loadDebugInfo Flag indicating whether or not debug information
    //   should be loaded for the app kernel instance, if it is present.
    //
    // @returns TRUE on success, FALSE if there was an error
    // --------------------------------------------------------------------------------

    public function loadAppKernelInstanceInfo(array $options, InstanceData &$ak, $loadDebugInfo = false)
    {
        if (0 == count($options)
            || (!isset($options['ak_def_id']) || !isset($options['collected']) || !isset($options['resource_id']))
            && !isset($options['instance_id'])
        ) {
            $msg = "'ak_def_id', 'collected', and 'resource_id' options are required";
            throw new Exception($msg);
        }

        $criteriaList = array();
        $paramList = array();

        // We are now collecting the instance id so we can query using this only
        // for direct access to a single instance.

        if (isset($options['instance_id']) && null !== $options['instance_id']) {
            $criteriaList[] = "i.instance_id = ?";
            $paramList[] = $options['instance_id'];
        } else {
            if (null !== $options['ak_def_id']) {
                $criteriaList[] = "i.ak_def_id = ?";
                $paramList[] = $options['ak_def_id'];
            }
            if (null !== $options['resource_id']) {
                $criteriaList[] = "i.resource_id = ?";
                $paramList[] = $options['resource_id'];
            }
            if (null !== $options['collected']) {
                $criteriaList[] = "i.collected = FROM_UNIXTIME(?)";
                $paramList[] = $options['collected'];
            }

            if (isset($options['num_units']) && null !== $options['num_units']) {
                $criteriaList[] = "a.num_units = ?";
                $paramList[] = $options['num_units'];
            }

            if (isset($options['status']) && null !== $options['status']) {
                $criteriaList[] = "i.status = ?";
                $paramList[] = $options['status'];
            }
        }

        $sql =
            "SELECT i.ak_id, i.collected, i.status, i.env_version, i.instance_id, i.job_id,
            r.nickname, a.num_units,
            d.ak_base_name, d.name as ak_name, d.processor_unit
            FROM ak_instance i
            JOIN resource r USING(resource_id)
            JOIN app_kernel_def d USING(ak_def_id)
            JOIN app_kernel a USING (ak_id)";

        if (0 != count($criteriaList)) {
            $sql .= "\nWHERE " . implode(" AND ", $criteriaList);
        }

        $result = $this->db->query($sql, $paramList);

        if (0 == count($result)) {
            $this->log(
                "No matching app kernels found (ak_def_id = {$options['ak_def_id']}, " .
                "collected = {$options['collected']}, resource_id = {$options['resource_id']})",
                \CCR\Log::WARNING
            );
            return false;
        }

        $row = array_shift($result);

        $ak->reset();
        $ak->db_ak_def_id = intval($options['ak_def_id']);
        $ak->db_ak_def_name = $row['ak_name'];
        $ak->db_ak_id = intval($row['ak_id']);
        $ak->db_resource_id = $options['resource_id'];
        $ak->db_proc_unit_type = $row['processor_unit'];
        $ak->deployment_ak_name = $row['ak_base_name'] . "." . $row['num_units'];
        $ak->deployment_ak_base_name = $row['ak_base_name'];
        $ak->deployment_num_proc_units = intval($row['num_units']);
        $ak->deployment_hostname = $row['nickname'];
        $ak->deployment_time = strtotime($row['collected']);
        $ak->deployment_instance_id = $row['instance_id'];
        $ak->deployment_job_id = $row['job_id'];
        $ak->status = $row['status'];
        $ak->environmentVersion = $row['env_version'];

        $sql =
            "SELECT p.name, p.tag, p.unit, UNCOMPRESS(pd.value_string) as value
            FROM ak_instance i
         JOIN app_kernel a USING (ak_id)
            JOIN parameter_data pd USING(ak_id, collected, resource_id)
            JOIN parameter p USING(parameter_id)";
        if (0 != count($criteriaList)) {
            $sql .= " WHERE " . implode(" AND ", $criteriaList);
        }

        $result = $this->db->query($sql, $paramList);
        reset($result);

        while (false !== ($row = current($result))) {
            $ak->ak_parameters[] = new InstanceParameter(
                $row['name'],
                $row['value'],
                $row['unit'],
                $row['tag']
            );
            next($result);
        }

        $sql = "SELECT m.name, m.unit, md.value_string as value
            FROM ak_instance i
         JOIN app_kernel a USING (ak_id)
            JOIN metric_data md USING(ak_id, collected, resource_id)
            JOIN metric m USING(metric_id)";
        if (0 != count($criteriaList)) {
            $sql .= " WHERE " . implode(" AND ", $criteriaList);
        }
        $result = $this->db->query($sql, $paramList);
        reset($result);

        while (false !== ($row = current($result))) {
            $ak->ak_metrics[] = new InstanceMetric(
                $row['name'],
                $row['value'],
                $row['unit']
            );
            next($result);
        }

        if ($loadDebugInfo) {
            $sql = "SELECT UNCOMPRESS(message) as message, UNCOMPRESS(stderr) as stderr, walltime, cputime, memory,
                UNCOMPRESS(ak_error_cause) as ak_error_cause,
                UNCOMPRESS(ak_error_message) as ak_error_message,
                ak_queue_time
                FROM ak_instance_debug d
            JOIN ak_instance i USING(ak_id, collected, resource_id)
            JOIN app_kernel a USING (ak_id)";
            if (0 != count($criteriaList)) {
                $sql .= " WHERE " . implode(" AND ", $criteriaList);
            }
            $result = $this->db->query($sql, $paramList);
            if (count($result) > 0) {
                $row = array_shift($result);
                $ak->deployment_message = $row['message'];
                $ak->deployment_stderr = $row['stderr'];
                $ak->deployment_walltime = $row['walltime'];
                $ak->deployment_cputime = $row['cputime'];
                $ak->deployment_memory = $row['memory'];
                $ak->ak_error_cause = $row['ak_error_cause'];
                $ak->ak_error_message = $row['ak_error_message'];
                $ak->ak_queue_time = $row['ak_queue_time'];
            }
        }

        return true;
    }

    // --------------------------------------------------------------------------------
    // Load the internal list of application kernel metrics and the application
    // kernels that they are associated with.
    //
    // @param $loadDisabled Load metrics that are associated with app kernels
    //   marked as disabled in the database.
    // --------------------------------------------------------------------------------

    private function loadAkMetrics($loadDisabled = false)
    {
        $sql = "SELECT * FROM v_ak_metrics" .
            ($loadDisabled ? "" : " WHERE enabled=1");
        $result = $this->db->query($sql);

        reset($result);
        $this->akMetrics = array();
        $this->akMetricGuids = array();

        while (false !== ($row = current($result))) {
            $name = $row['name'];
            $numUnits = $row['num_units'];
            $guid = $row['guid'];
            $metricId = $row['metric_id'];
            $this->akMetrics[$name][$numUnits][$guid] = $metricId;
            $this->akMetricGuids[$guid] = $metricId;
            next($result);
        }
    }

    // --------------------------------------------------------------------------------
    // Load the internal list of application kernel parameters and the application
    // kernels that they are associated with.
    //
    // @param $loadDisabled Load parameters that are associated with app kernels
    //   marked as disabled in the database.
    // --------------------------------------------------------------------------------

    private function loadAkParameters($loadDisabled = false)
    {
        $sql = "SELECT * FROM v_ak_parameters" .
            ($loadDisabled ? "" : " WHERE enabled=1");
        $result = $this->db->query($sql);

        reset($result);
        $this->akParameters = array();
        $this->akParameterGuids = array();

        while (false !== ($row = current($result))) {
            $name = $row['name'];
            $numUnits = $row['num_units'];
            $guid = $row['guid'];
            $parameterId = $row['parameter_id'];
            $this->akParameters[$name][$numUnits][$guid] = $parameterId;
            $this->akParameterGuids[$guid] = $parameterId;
            next($result);
        }
    }

    // --------------------------------------------------------------------------------
    // Compare the list of application kernels configured in an external source
    // with the list loaded from the database.  Return a list of application
    // kernels that match the base name (e.g., the name without any processing
    // unit information) for each resource.
    //
    // @param $appKernels Associative array of application kernels where the key
    //   is a resource nickname and the values are the application kernels
    //   configured for that resource.
    //
    // @returns The list of active application kernels where keys are the resource
    //   nicknames and the values for each key are the application kernels
    //   configured to execute on that resource.
    // --------------------------------------------------------------------------------

    public function activeAppKernels(array $appKernels)
    {
        if (null === $this->akBaseNameList) {
            $this->loadAppKernels();
        }

        $activeAppKernels = array();

        foreach ($this->akBaseNameList as $basename) {
            foreach ($appKernels as $resourceNickname => $resourceAkList) {
                foreach ($resourceAkList as $akName) {
                    if (0 !== strpos($akName, $basename)) {
                        continue;
                    }
                    $activeAppKernels[$resourceNickname][] = $akName;
                }
            }
        }
        return $activeAppKernels;
    }

    // --------------------------------------------------------------------------------
    // Store data from an application kernel instance in the database.
    //
    // @param $ak An AppKernelInstance containing data about an application kernel
    //   instance.
    // --------------------------------------------------------------------------------

    public function storeAppKernelInstance(
        InstanceData $ak,
        $replace = false,
        $add_to_a_data = true,
        $calc_controls = true,
        $dryRunMode = false
    ) {
        // Get the list of existing app kernels if we haven't done so already

        if (null === $this->appKernelDefinitions) {
            $this->loadAppKernelDefinitions();
        }
        if (null === $this->akIdMap) {
            $this->loadAppKernels();
        }
        if (null === $this->akMetricGuids) {
            $this->loadAkMetrics();
        }
        if (null === $this->akParameterGuids) {
            $this->loadAkParameters();
        }

        // Control region?

        // Ensure that the base app kernel name exists in the database

        if (!isset($this->appKernelDefinitions[$ak->deployment_ak_base_name])) {
            throw new Exception("Undefined app kernel '{$ak->deployment_ak_base_name}'");
        }

        // Create an app kernel instance

        try {
            $this->log("Store app kernel $ak", \CCR\Log::DEBUG);

            $this->db->handle()->beginTransaction();
            if ($replace) {
                $this->db->execute('set foreign_key_checks=0;');
            }

            try {
                $this->createInstance($ak, $replace, $dryRunMode);
            } catch (Exception $e) {
                // If this is a PDOException get the mysql-specific error code and
                // handle it appropriately.  The exception error is almost always 23000
                // which is pretty useless.
                if ($e instanceof \PDOException && 23000 == $e->getCode()) {
                    // If this is a duplicate key skip the app kernel, otherwise re-throw
                    // the exception.  This allows us to re-ingest data and skip what is
                    // already in the database.
                    //
                    // See http://dev.mysql.com/doc/refman/5.5/en/error-messages-server.html

                    list (, $driverCode,) = $e->errorInfo;
                    if (1022 == $driverCode || 1062 == $driverCode || 1557 == $driverCode || 1586 == $driverCode) {
                        $msg = "App kernel instance already exists: $ak, skipping. ($driverCode, '{$e->getMessage()}')";
                        throw new AppKernelException($msg, AppKernelException::DuplicateInstance);
                    }
                }

                // Not a duplicate, re-throw the exception
                throw $e;
            }

            foreach ($ak->ak_metrics as $metric) {
                $metricId = $this->getMetricId($ak, $metric);
                $metric->id = $metricId;
                $this->addMetricData($ak, $metric, $replace, $add_to_a_data, $calc_controls, $dryRunMode);
            }

            foreach ($ak->ak_parameters as $parameter) {
                $parameterId = $this->getParameterId($ak, $parameter);
                $parameter->id = $parameterId;
                $this->addParameterData($ak, $parameter, $replace, $dryRunMode);
            }

            $this->addDebugData($ak, $replace, $dryRunMode);
            if ($replace) {
                $this->db->execute('set foreign_key_checks=1;');
            }
            $this->db->handle()->commit();
        } catch (Exception $e) {
            $this->log("Rolling back transaction", \CCR\Log::DEBUG);
            if ($replace) {
                $this->db->execute('set foreign_key_checks=1;');
            }
            $this->db->handle()->rollback();
            throw $e;
        }
        return true;
    }

    // --------------------------------------------------------------------------------
    // Create a record for an application kernel instance in the database.  Upon
    // success, $ak will be modified with the resource id and application kernel
    // id that it is associated with.
    //
    // @param $ak An AppKernelInstance containing data about an application kernel
    //   instance.
    // --------------------------------------------------------------------------------
    private function createInstance(InstanceData &$ak, $replace, $dryRunMode = false)
    {
        $akId = $this->getAkId($ak);

        if (false === ($resourceId = $this->getResourceId($ak))) {
            throw new Exception("Unknown resource '{$ak->deployment_hostname}'");
        }

        $appKernelDefinitionId = $this->appKernelDefinitions[$ak->deployment_ak_base_name]->id;
        $status = $ak->status;
        $controlStatus = 'undefined';
        if ($status !== 'success') {
            $controlStatus = 'failed';
        }

        $sql = ($replace ? 'replace' : 'insert') . " INTO ak_instance (ak_id, collected, resource_id, instance_id, job_id, " .
            "status, ak_def_id, env_version,controlStatus) VALUES (?, FROM_UNIXTIME(?), ?, ?, ?, ?, ?, ?, ?)";
        $params = array(
            $akId,
            $ak->deployment_time,
            $resourceId,
            $ak->deployment_instance_id,
            $ak->deployment_job_id,
            $status,
            $appKernelDefinitionId,
            $ak->environmentVersion(),
            $controlStatus
        );
        if (!$dryRunMode) {
            $this->db->execute($sql, $params);
        }
        $this->log("-> Created new app kernel instance: $ak", \CCR\Log::DEBUG);

        // Set some data that will be needed for adding metrics and parameters
        // TODO These should be preset during InstanceData &$ak query
        //$ak->db_resource_id = $resourceId;
        $ak->db_ak_id = $akId;

        return true;
    }

    // --------------------------------------------------------------------------------
    // Store debug data for an application kernel instance in the database.
    //
    // @param $ak An AppKernelInstance containing data about an application kernel
    //   instance.
    // --------------------------------------------------------------------------------
    private function addDebugData(InstanceData $ak, $replace, $dryRunMode = false)
    {
        $akId = $this->getAkId($ak);
        if (false === ($resourceId = $this->getResourceId($ak))) {
            throw new Exception("Unknown resource '{$ak->deployment_hostname}'");
        }

        $sql = ($replace ? 'replace' : 'insert') . " INTO ak_instance_debug (ak_id, collected, resource_id, instance_id, " .
            "message, stderr, walltime, cputime, memory, ak_error_cause, ak_error_message, ak_queue_time) " .
            "VALUES (?, FROM_UNIXTIME(?), ?, ?, COMPRESS(?), COMPRESS(?), ?, ?, ?, COMPRESS(?), COMPRESS(?), ?)";
        $params = array(
            $akId,
            $ak->deployment_time,
            $resourceId,
            $ak->deployment_instance_id,
            $ak->deployment_message,
            $ak->deployment_stderr,
            $ak->deployment_walltime,
            $ak->deployment_cputime,
            $ak->deployment_memory,
            $ak->ak_error_cause,
            $ak->ak_error_message,
            $ak->ak_queue_time
        );
        if (!$dryRunMode) {
            $this->db->execute($sql, $params);
        }
        $this->log("-> Logged debug info for app kernel instance $ak", \CCR\Log::DEBUG);

        return true;
    }

    // --------------------------------------------------------------------------------
    // Add metric data to an application kernel instance.  Associate the metric
    // with the app kernel if it wasn't already.
    //
    // @param $ak An AppKernelInstance containing data about an application kernel
    //   instance.
    // @param $metric An AppKernelMetric containing the metric information
    // --------------------------------------------------------------------------------

    private function addMetricData(
        InstanceData $ak,
        InstanceMetric $metric,
        $replace,
        $add_to_a_data = true,
        $calc_controls = true,
        $dryRunMode = false
    ) {
        $guid = $metric->guid();


        if (!isset($this->akMetrics[$ak->deployment_ak_base_name][$ak->deployment_num_proc_units][$guid])) {
            $sql = ($replace ? 'replace' : 'insert') . " INTO ak_has_metric (ak_id, metric_id, num_units) VALUES (?,?,?)";
            $params = array($ak->db_ak_id, $metric->id, $ak->deployment_num_proc_units);
            if (!$dryRunMode) {
                $this->db->execute($sql, $params);
            } else {
                $this->log("$sql  " . print_r($params, 1), \CCR\Log::DEBUG);
            }
            $this->akMetrics[$ak->deployment_ak_base_name][$ak->deployment_num_proc_units][$guid] = $this->akMetricGuids[$guid];
            $this->log(
                "-> Associated metric '{$metric->name}' (id = {$metric->id}) with app kernel '{$ak->deployment_ak_name}' (ak_id = {$ak->db_ak_id})",
                \CCR\Log::DEBUG
            );
        }

        //metric_data
        $sql = ($replace ? 'replace' : 'insert') . " INTO metric_data (metric_id, ak_id, collected, " .
            "resource_id, value_string) VALUES (?, ?, FROM_UNIXTIME(?), ?, ?)";
        $params = array($metric->id, $ak->db_ak_id, $ak->deployment_time, $ak->db_resource_id, $metric->value);
        if (!$dryRunMode) {
            $this->db->execute($sql, $params);
        }
        if ($add_to_a_data) {
            //a_data and a_tree
            if ($ak->db_resource_visible && $ak->db_ak_def_visible && $ak->status === 'success') {
                //a_tree
                $sql = "SELECT UNIX_TIMESTAMP(start_time) as start_time,UNIX_TIMESTAMP(end_time) as end_time ,status
                    FROM a_tree WHERE ak_def_id=? AND resource_id=? AND metric_id=? AND num_units=?";
                $params = array($ak->db_ak_def_id, $ak->db_resource_id, $metric->id, $ak->deployment_num_proc_units);
                $rows = $this->db->query($sql, $params);

                if (count($rows) > 1) {
                    $this->log(
                        "a_tree has more then one entries for ak_def_id, resource_id, metric_id, num_units",
                        \CCR\Log::ERR
                    );
                }

                $start_time = $ak->deployment_time;
                $end_time = $ak->deployment_time;
                $status = $ak->status;
                if (count($rows) >= 1) {
                    if ($rows[0]['start_time'] < $start_time) {
                        $start_time = $rows[0]['start_time'];
                    }
                    if ($rows[0]['end_time'] > $end_time) {
                        $end_time = $rows[0]['end_time'];
                        $status = $rows[0]['status'];
                    }
                }
                if (count($rows) != 0) {
                    $sql = "DELETE FROM a_tree WHERE ak_def_id=? AND resource_id=? AND metric_id=? AND num_units=?";
                    $params = array(
                        $ak->db_ak_def_id,
                        $ak->db_resource_id,
                        $metric->id,
                        $ak->deployment_num_proc_units
                    );
                    if (!$dryRunMode) {
                        $rows = $this->db->execute($sql, $params);
                    }
                }
                $sql = "insert INTO a_tree " .
                    "(ak_name, resource, metric, unit, processor_unit, " .
                    "num_units, ak_def_id,resource_id, metric_id, " .
                    "start_time, end_time, status)" .
                    " VALUES (?, ?, ?, ?, ?, " .
                    " ?, ?, ?, ?, " .
                    "  FROM_UNIXTIME(?),  FROM_UNIXTIME(?), ?)";
                $params = array(
                    $ak->db_ak_def_name,
                    $ak->db_resource_name,
                    $metric->name,
                    $metric->unit,
                    $ak->db_proc_unit_type,
                    $ak->deployment_num_proc_units,
                    $ak->db_ak_def_id,
                    $ak->db_resource_id,
                    $metric->id,
                    $start_time,
                    $end_time,
                    $status
                );
                if (!$dryRunMode) {
                    $this->db->execute($sql, $params);
                }

                //a_data
                $sql = ($replace ? 'replace' : 'insert') .
                    " INTO a_data " .
                    "(ak_name, resource, metric, num_units, processor_unit," .
                    " collected, env_version, unit, metric_value," .
                    " ak_def_id,resource_id, metric_id, status)" .
                    " VALUES (?, ?, ?, ?, ?, " .
                    " ?, ?, ?, ?, " .
                    " ?, ?, ?, ?)";
                $params = array(
                    $ak->db_ak_def_name,
                    $ak->db_resource_name,
                    $metric->name,
                    $ak->deployment_num_proc_units,
                    $ak->db_proc_unit_type,
                    $ak->deployment_time,
                    $ak->environmentVersion,
                    $metric->unit,
                    $metric->value,
                    $ak->db_ak_def_id,
                    $ak->db_resource_id,
                    $metric->id,
                    $ak->status
                );
                if (!$dryRunMode) {
                    $this->db->execute($sql, $params);
                }
            }
            if ($ak->db_resource_visible && $ak->db_ak_def_visible && $ak->status !== 'queued') {
                //a_tree2
                $sql = "SELECT UNIX_TIMESTAMP(start_time) as start_time,UNIX_TIMESTAMP(end_time) as end_time ,status
                    FROM a_tree2 WHERE ak_def_id=? AND resource_id=? AND metric_id=? AND num_units=?";
                $params = array($ak->db_ak_def_id, $ak->db_resource_id, $metric->id, $ak->deployment_num_proc_units);
                $rows = $this->db->query($sql, $params);

                if (count($rows) > 1) {
                    $this->log(
                        "a_tree2 has more then one entries for ak_def_id, resource_id, metric_id, num_units",
                        \CCR\Log::ERR
                    );
                }

                $start_time = $ak->deployment_time;
                $end_time = $ak->deployment_time;
                $status = $ak->status;
                if (count($rows) >= 1) {
                    $status = $rows[0]['status'];
                    if ($rows[0]['start_time'] < $start_time) {
                        $start_time = $rows[0]['start_time'];
                    }
                    if ($rows[0]['end_time'] > $end_time) {
                        $end_time = $rows[0]['end_time'];
                        $status = $rows[0]['status'];
                    }
                }

                if (count($rows) !== 0) {
                    $sql = "DELETE FROM a_tree2 WHERE ak_def_id=? AND resource_id=? AND metric_id=? AND num_units=?";
                    $params = array(
                        $ak->db_ak_def_id,
                        $ak->db_resource_id,
                        $metric->id,
                        $ak->deployment_num_proc_units
                    );
                    if (!$dryRunMode) {
                        $rows = $this->db->execute($sql, $params);
                    }
                }
                $sql = "insert INTO a_tree2 " .
                    "(ak_name, resource, metric, unit, processor_unit, " .
                    "num_units, ak_def_id,resource_id, metric_id, " .
                    "start_time, end_time, status)" .
                    " VALUES (?, ?, ?, ?, ?, " .
                    " ?, ?, ?, ?, " .
                    "  FROM_UNIXTIME(?),  FROM_UNIXTIME(?), ?)";
                $params = array(
                    $ak->db_ak_def_name,
                    $ak->db_resource_name,
                    $metric->name,
                    $metric->unit,
                    $ak->db_proc_unit_type,
                    $ak->deployment_num_proc_units,
                    $ak->db_ak_def_id,
                    $ak->db_resource_id,
                    $metric->id,
                    $start_time,
                    $end_time,
                    $status
                );
                if (!$dryRunMode) {
                    $this->db->execute($sql, $params);
                }

                //a_data2
                $sql = ($replace ? 'replace' : 'insert') .
                    " INTO a_data2 " .
                    "(ak_name, resource, metric, num_units, processor_unit," .
                    " collected, env_version, unit, metric_value," .
                    " ak_def_id,resource_id, metric_id, status)" .
                    " VALUES (?, ?, ?, ?, ?, " .
                    " ?, ?, ?, ?, " .
                    " ?, ?, ?, ?)";
                $params = array(
                    $ak->db_ak_def_name,
                    $ak->db_resource_name,
                    $metric->name,
                    $ak->deployment_num_proc_units,
                    $ak->db_proc_unit_type,
                    $ak->deployment_time,
                    $ak->environmentVersion,
                    $metric->unit,
                    $metric->value,
                    $ak->db_ak_def_id,
                    $ak->db_resource_id,
                    $metric->id,
                    $ak->status
                );
                if (!$dryRunMode) {
                    $this->db->execute($sql, $params);
                }
            }
        }
        $this->log("-> Stored metric '{$metric->name}' for app kernel $ak", \CCR\Log::DEBUG);
        return true;
    }

    // --------------------------------------------------------------------------------
    // Add parameter data to an application kernel instance.  Associate the parameter
    // with the app kernel if it wasn't already.
    //
    // @param $ak An AppKernelInstance containing data about an application kernel
    //   instance.
    // @param $metric An AppKernelParameter containing the parameter information
    // --------------------------------------------------------------------------------

    private function addParameterData(InstanceData $ak, InstanceParameter $parameter, $replace, $dryRunMode = false)
    {
        $guid = $parameter->guid();

        if (!isset($this->akParameters[$ak->deployment_ak_base_name][$ak->deployment_num_proc_units][$guid])) {
            $sql = ($replace ? 'replace' : 'insert') . " INTO ak_has_parameter (ak_id, parameter_id) " .
                "VALUES (?, ?)";
            $params = array($ak->db_ak_id, $parameter->id);
            if (!$dryRunMode) {
                $this->db->execute($sql, $params);
            } else {
                $this->log("$sql  " . print_r($params, 1), \CCR\Log::DEBUG);
            }
            $this->akParameters[$ak->deployment_ak_base_name][$ak->deployment_num_proc_units][$guid] = $this->akParameterGuids[$guid];
            $this->log(
                "-> Associated parameter '{$parameter->name}' (id = {$parameter->id}) with app kernel '{$ak->deployment_ak_name}' (ak_id = {$ak->db_ak_id})",
                \CCR\Log::DEBUG
            );
        }

        $sql = ($replace ? 'replace' : 'insert') . " INTO parameter_data (parameter_id, ak_id, collected, " .
            "resource_id, value_string, value_md5) VALUES (?, ?, FROM_UNIXTIME(?), ?, COMPRESS(?), ?)";
        $params = array(
            $parameter->id,
            $ak->db_ak_id,
            $ak->deployment_time,
            $ak->db_resource_id,
            $parameter->value,
            md5($parameter->value)
        );
        if (!$dryRunMode) {
            $this->db->execute($sql, $params);
        }
        $this->log("-> Stored parameter '{$parameter->name}' for app kernel $ak", \CCR\Log::DEBUG);

        return true;
    }

    // --------------------------------------------------------------------------------
    // Look up the database identifier for the specified app kernel.  If it
    // doesn't exist in the database, add it.
    //
    // @param $ak An AppKernelInstance containing data about an application kernel
    //   instance.
    //
    // @returns The database id of the app kernel
    // --------------------------------------------------------------------------------

    private function getAkId(InstanceData $ak)
    {
        if (isset($this->akIdMap[$ak->deployment_ak_base_name][$ak->deployment_num_proc_units])) {
            return $this->akIdMap[$ak->deployment_ak_base_name][$ak->deployment_num_proc_units];
        }

        // The app kernel didn't exist, add it to the database and internal data
        // structures.

        $appKernelDefId = $this->appKernelDefinitions[$ak->deployment_ak_base_name]->id;
        $sql = "INSERT INTO app_kernel (name, num_units, ak_def_id) VALUES (?,?,?)";
        $params = array($ak->deployment_ak_base_name, $ak->deployment_num_proc_units, $appKernelDefId);
        $this->db->execute($sql, $params);
        $id = $this->db->handle()->lastInsertId();
        $this->akIdMap[$ak->deployment_ak_base_name][$ak->deployment_num_proc_units] = $id;
        $this->log("Added new app kernel {$ak->deployment_ak_name}", \CCR\Log::DEBUG);

        return $id;
    }

    // --------------------------------------------------------------------------------
    // Look up the database identifier for the specified metric.  If it doesn't
    // exist in the database, add it.
    //
    // @param $ak An AppKernelInstance containing data about an application kernel
    //   instance.
    // @param $metric An AppKernelMetric containing the metric information
    //
    // @returns The database id of the metric
    // --------------------------------------------------------------------------------

    private function getMetricId(InstanceData $ak, InstanceMetric $metric)
    {
        $guid = $metric->guid();

        // If the metric already exists return its id.

        if (isset($this->akMetricGuids[$guid])) {
            return $this->akMetricGuids[$guid];
        }

        // The metric didn't exist for that ak
        $sql = "SELECT * FROM metric WHERE guid=?";
        $params = array($guid);
        $result = $this->db->query($sql, $params);

        if (count($result) > 0) {
            $metricId = $result[0]['metric_id'];

            $sql = "INSERT INTO ak_has_metric (ak_id, metric_id, num_units) VALUES (?,?,?)";
            $params = array($ak->db_ak_id, $metricId, $ak->deployment_num_proc_units);
            $this->log("$sql  " . print_r($params, 1), \CCR\Log::DEBUG);
            $this->db->execute($sql, $params);

            $this->akMetricGuids[$metric->guid()] = $metricId;
            $this->log(
                "Add metric to ak: name='{$metric->name}', unit='{$metric->unit}' ak='{$ak->deployment_ak_name}' (id = $metricId)",
                \CCR\Log::DEBUG
            );

            return $metricId;
        }
        // The metric didn't exist, add it to the database and internal data
        // structures.

        $sql = "INSERT INTO metric (name, unit, guid) VALUES (?,?,?)";
        $params = array($metric->name, $metric->unit, $guid);
        $this->log("$sql  " . print_r($params, 1), \CCR\Log::DEBUG);
        $this->db->execute($sql, $params);
        $metricId = $this->db->handle()->lastInsertId();
        $this->akMetricGuids[$metric->guid()] = $metricId;
        $this->log(
            "Created new metric: name='{$metric->name}', unit='{$metric->unit}' (id = $metricId)",
            \CCR\Log::DEBUG
        );

        return $metricId;
    }

    // --------------------------------------------------------------------------------
    // Look up the database identifier for the specified parameter.  If it doesn't
    // exist in the database, add it.
    //
    // @param $ak An AppKernelInstance containing data about an application kernel
    //   instance.
    // @param $metric An AppKernelParameter containing the parameter information
    //
    // @returns The database id of the parameter
    // --------------------------------------------------------------------------------

    private function getParameterId(InstanceData $ak, InstanceParameter $parameter)
    {
        $guid = $parameter->guid();

        // If the parameter already exists return its id.

        if (isset($this->akParameterGuids[$guid])) {
            return $this->akParameterGuids[$guid];
        }

        // The parameter didn't exist, add it to the database and internal data
        // structures.

        $sql = "INSERT INTO parameter (tag, name, unit, guid) VALUES (?,?,?,?)";
        $params = array($parameter->tag, $parameter->name, $parameter->unit, $guid);
        $this->log("$sql  " . print_r($params, 1), \CCR\Log::DEBUG);
        $this->db->execute($sql, $params);
        $parameterId = $this->db->handle()->lastInsertId();
        $this->akParameterGuids[$guid] = $parameterId;
        $this->log(
            "Created new parameter: name='{$parameter->name}' unit='{$parameter->unit}' tag='{$parameter->tag}' (id = $parameterId)",
            \CCR\Log::DEBUG
        );

        return $parameterId;
    }

    // --------------------------------------------------------------------------------

    private function getResourceId(InstanceData $ak)
    {
        // Hack to remove ".sdsc.edu" from the value returned by Inca.  Not sure
        // why Inca returns the full resource name as the nickname only for SDSC
        // resources.
        // $tmpHostname = str_replace(".sdsc.edu", "", $ak->deployment_hostname);

        if (!isset($this->resourceList[$ak->deployment_hostname])) {
            return false;
        }
        return $this->resourceList[$ak->deployment_hostname]->id;
    }

    // --------------------------------------------------------------------------------

    private function log($message, $level = \CCR\Log::INFO)
    {
        if (null === $this->logger) {
            return;
        }
        $this->logger->log($level, $message);
    }

    // --------------------------------------------------------------------------------

    public function getResourceNicknames()
    {
        return array_keys($this->resourceList);
    }

    // --------------------------------------------------------------------------------

    public function getAppKernelBaseNames()
    {
        return $this->akBaseNameList;
    }

    public function getControlRegions($resource_id, $ak_def_id)
    {
        $sql =
            "SELECT control_region_def_id,resource_id,ak_def_id,control_region_type,control_region_starts," .
            "       control_region_ends,control_region_points,comment " .
            "FROM control_region_def " .
            "WHERE resource_id = :resource_id AND ak_def_id = :ak_def_id " .
            "ORDER BY control_region_starts";
        $params = [
            ":resource_id" => $resource_id,
            ":ak_def_id" => $ak_def_id,
        ];
        return $this->db->query($sql, $params);
    }

    /**
     * Get array with ak_id for ak_def_id
     * @param $ak_def_id
     * @return array
     */
    protected function getAppKernelIdsForAppKernelDefId($ak_def_id)
    {
        $ak_def_id = intval($ak_def_id);
        $sql = "SELECT ak_id FROM mod_appkernel.app_kernel WHERE ak_def_id=:ak_def_id";
        $params = [":ak_def_id" => $ak_def_id];
        $response = $this->db->query($sql, $params);

        $ak_ids = [];
        foreach ($response as $v) {
            $ak_ids[] = intval($v['ak_id']);
        }

        return $ak_ids;
    }

    public function newControlRegions(
        $resource_id,
        $ak_def_id,
        $control_region_type,
        $startDateTime,
        $endDateTime,
        $n_points,
        $comment,
        $update = false,
        $control_region_def_id = null
    ) {
        $resource_id = intval($resource_id);
        $ak_def_id = intval($ak_def_id);
        $n_points = ($n_points !== null ? intval($n_points) : null);

        $ak_ids = $this->getAppKernelIdsForAppKernelDefId($ak_def_id);
        $ak_ids_in_sql_param = [];
        $ak_ids_in_sql = '';
        for ($i = 0; $i < count($ak_ids); $i++) {
            $ak_ids_in_sql .= ':ak_id_' . $i;
            if ($i < count($ak_ids) - 1) {
                $ak_ids_in_sql .= ',';
            }
            $ak_ids_in_sql_param[':ak_id_' . $i] = $ak_ids[$i];
        }


        //make query for control recalculation
        $sql =
            "SELECT control_region_def_id,resource_id,ak_def_id,control_region_type,control_region_starts," .
            "       control_region_ends,control_region_points,comment " .
            "FROM control_region_def " .
            "WHERE resource_id = :resource_id  AND ak_def_id = :ak_def_id AND control_region_starts>:start_date_time " .
            "ORDER BY control_region_starts";
        $params = [
            ":resource_id" => $resource_id,
            ":ak_def_id" => $ak_def_id,
            ":start_date_time" => $startDateTime,
        ];
        $controlRegionDef = $this->db->query($sql, $params);

        $metric_data_update_sql =
            "UPDATE metric_data " .
            "SET control = null, " .
            "    running_average = null, " .
            "    controlStatus = 'undefined' " .
            "WHERE ak_id IN (" . $ak_ids_in_sql . ") AND resource_id=:resource_id " .
            "  AND collected >= :start_date_time";

        $metric_data_update_params = array_merge(
            [
                ":resource_id" => $resource_id,
                ":start_date_time" => $startDateTime,
            ],
            $ak_ids_in_sql_param
        );

        if (count($controlRegionDef) > 0) {
            $new_region_end_collected = $controlRegionDef[0]['control_region_starts'];
            $metric_data_update_sql .= " AND collected<:new_region_end_collected";
            $metric_data_update_params[":new_region_end_collected"] = $new_region_end_collected;
        }

        //insert of update control_region_def
        $sql = "SELECT control_region_def_id FROM  control_region_def WHERE ";
        $params = [];
        if ($control_region_def_id != null && $update) {
            $sql .= "control_region_def_id={$control_region_def_id}";
            $params[":control_region_def_id"] = $control_region_def_id;
        } else {
            $sql .= "resource_id=:resource_id AND ak_def_id=:ak_def_id AND control_region_starts=:start_date_time";
            $params[":resource_id"] = $resource_id;
            $params[":ak_def_id"] = $ak_def_id;
            $params[":start_date_time"] = $startDateTime;
        }
        $control_region_def_id = $this->db->query($sql, $params);
        if (count($control_region_def_id) > 0) {
            if ($update) {
                $sql =
                    "UPDATE control_region_def " .
                    "SET control_region_starts=:start_date_time," .
                    "    control_region_type=:control_region_type," .
                    "    control_region_ends=:end_date_time," .
                    "    control_region_points=:control_region_points," .
                    "    comment=:comment " .
                    "WHERE control_region_def_id=:control_region_def_id";
                $params = [
                    ":start_date_time" => $startDateTime,
                    ":control_region_type" => $control_region_type,
                    ":end_date_time" => $endDateTime,
                    ":control_region_points" => $n_points,
                    ":comment" => $comment,
                    ":control_region_def_id" => $control_region_def_id[0]['control_region_def_id']
                ];
                $this->db->execute($sql, $params);
                $this->db->execute($metric_data_update_sql, $metric_data_update_params);

                return array(
                    'success' => true,
                    'message' => "Control region time interval was updated"
                );
            } else {
                return array(
                    'success' => false,
                    'message' => "Control region already exists for such time interval"
                );
            }
        } else {
            if ($update) {
                return array(
                    'success' => false,
                    'message' => "Such control region time interval do not exist, can not update it."
                );
            } else {
                $sql =
                    "INSERT INTO control_region_def " .
                    "(resource_id,ak_def_id,control_region_type," .
                    "control_region_starts,control_region_ends,control_region_points,comment) " .
                    "VALUES(?,?,?,?,?,?,?) ";
                $params = [
                    $resource_id,
                    $ak_def_id,
                    $control_region_type,
                    $startDateTime,
                    $endDateTime,
                    $n_points,
                    $comment
                ];
                $this->db->execute($sql, $params);
                $this->db->execute($metric_data_update_sql, $metric_data_update_params);
                return array(
                    'success' => true,
                    'message' => "Control region time interval was created"
                );
            }
        }
    }

    public function deleteControlRegion($control_region_def_id)
    {
        //get full info about region to delete
        $controlRegionDefQuery =
            "SELECT control_region_def_id,resource_id,ak_def_id,
                    control_region_type,
                    control_region_starts,
                    control_region_ends,
                    control_region_points,
                    comment
             FROM control_region_def
             WHERE control_region_def_id=$control_region_def_id;
        ";
        $controlRegionDefToDelete = $this->db->query($controlRegionDefQuery);
        if (count($controlRegionDefToDelete) === 0) {
            return array(
                'success' => false,
                'message' => "Such control region time interval do not exist, can not delete it."
            );
        }
        $controlRegionDefToDelete = $controlRegionDefToDelete[0];
        $resource_id = $controlRegionDefToDelete['resource_id'];
        $ak_def_id = $controlRegionDefToDelete['ak_def_id'];
        $control_region_starts = $controlRegionDefToDelete['control_region_starts'];

        //get all ak_ids for this ak_def_id
        $sqlAKcond = array();
        $response = $this->db->query(
            "SELECT ak_id FROM mod_appkernel.app_kernel WHERE ak_def_id='{$controlRegionDefToDelete['ak_def_id']}'"
        );
        foreach ($response as $v) {
            $sqlAKcond[] = "ak_id='" . $v['ak_id'] . "'";
        }
        $sqlAKcond = implode(" OR ", $sqlAKcond);

        //find region after this region
        $controlRegionDefQuery =
            "SELECT control_region_def_id,resource_id,ak_def_id,
                    control_region_type,
                    control_region_starts,
                    control_region_ends,
                    control_region_points,
                    comment
             FROM control_region_def
             WHERE resource_id = $resource_id
               AND ak_def_id = $ak_def_id
               AND control_region_starts>'$control_region_starts'
               AND control_region_def_id !=$control_region_def_id
             ORDER BY control_region_starts;
        ";
        $controlRegionDefAfter = $this->db->query($controlRegionDefQuery);

        $metric_data_updateQuery = "UPDATE metric_data
            SET control = null,
                running_average = null,
                controlStatus = 'undefined'
            WHERE ({$sqlAKcond}) AND resource_id=$resource_id
              AND collected >= '$control_region_starts'";
        if (count($controlRegionDefAfter) > 0) {
            $new_region_end_collected = $controlRegionDefAfter[0]['control_region_starts'];
            $metric_data_updateQuery .= " AND collected<'$new_region_end_collected';";
        } else {
            $metric_data_updateQuery .= ";";
        }

        $this->db->execute("DELETE FROM control_region_def WHERE control_region_def_id=$control_region_def_id");
        $this->db->execute($metric_data_updateQuery);
        return array(
            'success' => true,
            'message' => "Control region time interval was deleted"
        );
    }

    public function setInitialControlRegions($initial = true, $envbased = false, $controlIntervalSize = 20)
    {
        //Get resourceIdMap
        $resourceIdMap = array();
        $sql = "SELECT resource_id, resource, nickname, description , enabled, visible FROM resource";
        $result = $this->db->query($sql);
        foreach ($result as $row) {
            $resourceIdMap[$row['resource_id']] = array(
                'name' => $row['nickname']
            );
        }

        $this->log("recalculate control_region_def based on enviroment change\n", \CCR\Log::INFO);
        $this->db->execute("TRUNCATE TABLE control_region_def");
        if ($initial) {
            //Set initial region
            $initial_regions_start = $this->db->query(
                "SELECT  ak_def_id,resource_id,MIN(collected) as collected,name as ak_name
                FROM
                (SELECT ak_id, MIN(collected) as collected,resource_id FROM `metric_data` GROUP BY resource_id,ak_id) AS smallesttime,
                app_kernel
                WHERE smallesttime.ak_id=app_kernel.ak_id
                GROUP BY resource_id,ak_def_id
                ORDER BY ak_def_id,resource_id"
            );
            foreach ($initial_regions_start as $first_run) {
                $this->log(
                    "Adding initial control region for app kernel: {$first_run['ak_name']} on resource: {$resourceIdMap[$first_run['resource_id']]['name']}",
                    \CCR\Log::INFO
                );
                $t = date_format(date_create($first_run['collected']), "Y-m-d") . " 00:00:00";
                $t = date_format(
                    date_sub(date_create($t), date_interval_create_from_date_string('5 days')),
                    "Y-m-d H:i:s"
                );
                $sql = "INSERT INTO control_region_def
                    (resource_id,ak_def_id,control_region_type,control_region_starts,control_region_points,comment)
                    VALUES({$first_run['resource_id']},{$first_run['ak_def_id']},'data_points','{$t}',{$controlIntervalSize},
                    'initial control region')
                ";
                $this->db->execute($sql);
            }
        }
        if ($envbased) {
            //Set control regions based on enviroment change
            $newenv_regions_start = $this->db->query(
                "SELECT ak_def_id,resource_id,MIN(collected) as collected,name as ak_name, env_version
                FROM
                (SELECT ak_id, MIN(collected) as collected,resource_id,env_version
                FROM `ak_instance`
                GROUP BY ak_id,resource_id,env_version) as smallesttime,
                                app_kernel
                WHERE smallesttime.ak_id=app_kernel.ak_id
                GROUP BY ak_def_id,resource_id,env_version
                ORDER BY ak_def_id,resource_id,collected"
            );

            for ($i = 1; $i < count($newenv_regions_start); $i++) {
                if ($newenv_regions_start[$i - 1]['ak_def_id'] === $newenv_regions_start[$i]['ak_def_id']
                    && $newenv_regions_start[$i - 1]['resource_id'] === $newenv_regions_start[$i]['resource_id']
                    && $newenv_regions_start[$i - 1]['collected'] !== $newenv_regions_start[$i]['collected']
                ) {
                    $ak_def_id = $newenv_regions_start[$i]['ak_def_id'];
                    $resource_id = $newenv_regions_start[$i]['resource_id'];
                    $collected = $newenv_regions_start[$i]['collected'];
                    $t = $collected;
                    $this->log(
                        "Adding control region due enviroment change, app kernel: {$newenv_regions_start[$i]['ak_name']} on resource: {$resourceIdMap[$newenv_regions_start[$i]['resource_id']]['name']}",
                        \CCR\Log::INFO
                    );

                    $sql = "INSERT INTO control_region_def
                                    (resource_id,ak_def_id,control_region_type,control_region_starts,control_region_points,comment)
                                    VALUES({$resource_id},{$ak_def_id},'data_points','{$t}',{$controlIntervalSize},
                                    'enviroment change, automatically added')
                                ";
                    $this->db->execute($sql);
                }
            }
        }
    }

    /**
     * Calculates the running average and control values for each metric value
     *
     * @param bool $recalculateControls
     * @param int $controlIntervalSize
     * @param int $runningAverageSize
     * @param null $restrictToResource
     * @param null $restrictToAppKernel
     * @throws Exception
     */
    public function calculateControls(
        $recalculateControls = false,
        $controlIntervalSize = 20,
        $runningAverageSize = 5,
        $restrictToResource = null,
        $restrictToAppKernel = null
    ) {
        $this->log("Calculating control metrics");
        $runningAverageSize = intval($runningAverageSize);
        if ($runningAverageSize < 1) {
            echo "calculateControls: runningAverageSize must be greater than zero. Aborting...\n";
            return;
        }
        if ($controlIntervalSize < 1) {
            echo "calculateControls: controlIntervalSize must be greater than zero. Aborting...\n";
        }
        //Get akId2akDefIdMap
        $akId2akDefIdMap = array();
        $akDefId2akIdMap = array();
        $sql = "SELECT ak_base_name as name, ak_id, num_units, ak_def_id, control_criteria " .
            "FROM app_kernel_def JOIN app_kernel USING(ak_def_id)";
        $result = $this->db->query($sql);
        foreach ($result as $row) {
            $akId2akDefIdMap[$row['ak_id']] = array(
                'name' => $row['name'],
                'ak_def_id' => $row['ak_def_id'],
                'num_units' => $row['num_units'],
                'control_criteria' => $row['control_criteria'],
            );
            if (!array_key_exists($row['ak_def_id'], $akDefId2akIdMap)) {
                $akDefId2akIdMap[$row['ak_def_id']] = array();
            }
            $akDefId2akIdMap[$row['ak_def_id']][] = $row['ak_id'];
        }

        // Get resourceIdMap
        $resourceIdMap = array();
        $sql = "SELECT resource_id, resource, nickname, description , enabled, visible FROM resource";
        $result = $this->db->query($sql);
        foreach ($result as $row) {
            $resourceIdMap[$row['resource_id']] = array(
                'name' => $row['nickname']
            );
        }

        // Conditions for restrictToResource
        $resource_id = null;
        if ($restrictToResource !== null) {
            if (!isset($this->resourceList)) {
                $this->loadResources();
            }
            $resource_id = $this->resourceList[$restrictToResource]->id;
        }
        // Conditions for restrictToAppKernel
        $ak_ids = null;
        $ak_ids_in_sql_param = [];
        $ak_ids_in_sql = '';
        if ($restrictToAppKernel !== null) {
            if (!isset($this->appKernelDefinitions)) {
                $this->loadAppKernelDefinitions();
            }

            $ak_def_id = $this->appKernelDefinitions[$restrictToAppKernel]->id;
            $ak_ids = $this->getAppKernelIdsForAppKernelDefId($ak_def_id);

            for ($i = 0; $i < count($ak_ids); $i++) {
                $ak_ids_in_sql .= ':ak_id_' . $i;
                if ($i < count($ak_ids) - 1) {
                    $ak_ids_in_sql .= ',';
                }
                $ak_ids_in_sql_param[':ak_id_' . $i] = $ak_ids[$i];
            }
        }

        // first, load the metric attributes that help figure out whether a larger value is better or a smaller value
        $metricsLookupById = array();
        $metricsLookupByName = array();
        $metricsQuery =
            "SELECT  metric_id, lower(name) as name, lower(unit) as unit
             FROM `metric`
            ORDER BY 1, 2, 3
            ";
        $metricsResults = $this->db->query($metricsQuery);
        $metrics_walltime_id = null;
        foreach ($metricsResults as $mr) {
            $metricsLookupById[$mr['metric_id']] = $mr;
            $metricsLookupByName[$mr['name']] = $mr;
            if (strtolower($mr['name']) === strtolower('wall clock time')) {
                $metrics_walltime_id = $mr['metric_id'];
            }
        }

        $largerAttributeMap = array();
        $metricIdToLargerMap = array();
        $metricsPath = xd_utilities\getConfiguration('appkernel', 'ak_metrics_path');
        $metricAttributes = explode("\n", file_get_contents($metricsPath));
        foreach ($metricAttributes as $metricAttribute) {
            $metricName = null;
            $larger = true;
            $attr = explode(',', $metricAttribute);

            if (isset($attr[2]) && count($attr[2]) > 0) {
                $larger = substr($attr[2], 0, 1) != 'S';
            }
            if (isset($attr[1]) && $attr[1] !== '') {
                $metricName = strtolower($attr[1]);
                if (isset($metricsLookupByName[$metricName])) {
                    $largerAttributeMap[$metricName] = $larger;
                }
            }
        }
        //figure out the remaining metrics' larger/smaller property
        foreach ($metricsLookupById as $metric_id => $metric) {
            if (!isset($largerAttributeMap[$metric['name']])) {
                $largerAttributeMap[$metric['name']] = strcasecmp($metric['unit'], 'second') === 0 ? false : true;
            }
        }
        $this->db->execute('truncate table metric_attribute');
        foreach ($largerAttributeMap as $metricName => $larger) {
            $metric_id = $metricsLookupByName[$metricName]['metric_id'];

            $insertStatement =
                "insert into metric_attribute (metric_id, larger) values (:metric_id, :larger)";

            $this->db->execute($insertStatement, array('metric_id' => $metric_id, 'larger' => $larger));
            $metricIdToLargerMap[$metric_id] = $larger;
        }


        if ($recalculateControls) {
            $time_start = microtime(true);
            $sql = "UPDATE metric_data SET control = null,running_average = null,controlStatus = 'undefined'";
            $params = [];

            if ($resource_id !== null && $ak_ids !== null) {
                $sql .= " WHERE resource_id=:resource_id AND ak_id IN (" . $ak_ids_in_sql . ")";
                $params[':resource_id'] = $resource_id;
                $params = array_merge($params, $ak_ids_in_sql_param);
            } elseif ($resource_id === null && $ak_ids !== null) {
                $sql .= " WHERE ak_id IN (" . $ak_ids_in_sql . ")";
                $params = array_merge($params, $ak_ids_in_sql_param);
            } elseif ($resource_id !== null && $ak_ids === null) {
                $sql .= " WHERE resource_id=:resource_id";
                $params[':resource_id'] = $resource_id;
            }

            $this->db->execute($sql, $params);
            $time_end = microtime(true);
            $this->log(
                "Timing(update metric_data set control = null, running_average = null)=" . ($time_end - $time_start),
                \CCR\Log::DEBUG
            );
        }

        // Get a list of possible unique datasets (datasetsQuery).
        // For each one:
        // 1. pick/update the control interval
        // 2. for each value beyond the control interval
        //    i. calculate the running average (last 5 points, maybe try weighted average)
        //    ii. decide whether the value is in control (0),better than in control (1), or out of control(-1).
        //    iv. store the running average and control value in the metric_data table

        //this query enumeterates all possible unique datasets by their key (resource_id, ak_id, metric_id)
        $time_start = microtime(true);

        $sql = "SELECT distinct ak_id, metric_id, resource_id FROM `metric_data`";
        $params = [];
        if ($resource_id !== null && $ak_ids !== null) {
            $sql .= " WHERE resource_id=:resource_id AND ak_id IN (" . $ak_ids_in_sql . ")";
            $params[':resource_id'] = $resource_id;
            $params = array_merge($params, $ak_ids_in_sql_param);
        } elseif ($resource_id === null && $ak_ids !== null) {
            $sql .= " WHERE ak_id IN (" . $ak_ids_in_sql . ")";
            $params = array_merge($params, $ak_ids_in_sql_param);
        } elseif ($resource_id !== null && $ak_ids === null) {
            $sql .= " WHERE resource_id=:resource_id";
            $params[':resource_id'] = $resource_id;
        }
        $sql .= " ORDER BY 1, 2, 3";
        $datasets = $this->db->query($sql, $params);
        $datasetsLength = count($datasets);
        $time_end = microtime(true);

        $this->log(
            "Timing(Get a list of possible unique datasets (datasetsQuery))=" . ($time_end - $time_start),
            \CCR\Log::DEBUG
        );

        $time_start_bigcycle = microtime(true);
        $progressVerbosity = 1;
        $timing = array(
            'dataQuery' => 0.0,
            'N_dataQuery' => 0,
            'contRegCalc' => 0.0,
            'N_contRegCalc' => 0,
            'contCalc' => 0.0,
            'N_contCalc' => 0,
            'sqlupdate1' => 0.0,
            'N_sqlupdate1' => 0,
            'sqlupdate2' => 0.0,
            'N_sqlupdate2' => 0
        );
        foreach ($datasets as $di => $dataset) {
            $dataset_params = [
                ":resource_id" => $dataset["resource_id"],
                ":ak_id" => $dataset["ak_id"],
                ":metric_id" => $dataset["metric_id"]
            ];
            if ($progressVerbosity === 1) {
                $message = "Calculating running average and control values. " .
                    number_format(100.0 * $di / $datasetsLength, 2) . "% " . json_encode($dataset);
                $this->log($message, \CCR\Log::DEBUG);
            }
            $control_criteria = $this->control_criteria;
            if ($akId2akDefIdMap[$dataset['ak_id']]['control_criteria'] !== null) {
                $control_criteria = $akId2akDefIdMap[$dataset['ak_id']]['control_criteria'];
            }

            // if we dont know whether smaller or larger is better it (hopefully :) ) means
            // we don't want to calculate control on it so skip
            if (!isset($metricIdToLargerMap[$dataset['metric_id']])) {
                $this->log(
                    "Skipping metric {$dataset['metric_id']} {$metricsLookupById[$dataset['metric_id']]['name']} " .
                    "as there was no value in metric_attributes for its larger/smaller property",
                    \CCR\Log::WARNING
                );
                continue;
            }

            $time_start0 = microtime(true);

            // whether larger values are good for this metric or not
            $larger = $metricIdToLargerMap[$dataset['metric_id']];

            $dataQuery =
                "SELECT md.collected, md.value_string, aki.env_version,md.controlStatus " .
                "FROM `metric_data` md, `ak_instance` aki " .
                "WHERE md.resource_id = :resource_id AND md.ak_id = :ak_id AND md.metric_id = :metric_id" .
                "  AND md.control is NULL AND md.value_string is not NULL" .
                "  AND aki.ak_id = md.ak_id AND aki.collected = md.collected AND aki.resource_id = md.resource_id " .
                "ORDER BY collected";
            $data = $this->db->query($dataQuery, $dataset_params);
            $time_query = microtime(true);
            $timing['dataQuery'] += $time_query - $time_start0;
            $timing['N_dataQuery']++;

            $length = count($data);
            // only process datasets of length 1+
            if ($length > 0) {
                $time_start = microtime(true);
                //query for the control region definitions if none present initiate a new one
                $controlRegionDef = array();
                while (true) {
                    $controlRegionDefQuery =
                        "SELECT control_region_def_id,resource_id,ak_def_id,control_region_type," .
                        "    control_region_starts,control_region_ends,control_region_points " .
                        "FROM control_region_def " .
                        "WHERE resource_id = :resource_id AND ak_def_id = :ak_def_id " .
                        "ORDER BY control_region_starts";
                    $controlRegionDefParams = [
                        ":resource_id" => $dataset['resource_id'],
                        ":ak_def_id" => $akId2akDefIdMap[$dataset['ak_id']]['ak_def_id']
                    ];
                    $controlRegionDef = $this->db->query($controlRegionDefQuery, $controlRegionDefParams);

                    // if there is no control regions initiate the first one
                    if (count($controlRegionDef) == 0) {
                        $this->log(
                            "Adding initial control region for app kernel: {$akId2akDefIdMap[$dataset['ak_id']]['name']} " .
                            "on resource: {$resourceIdMap[$dataset['resource_id']]['name']}",
                            \CCR\Log::INFO
                        );
                        $t = date_format(date_create($data[0]['collected']), "Y-m-d") . " 00:00:00";
                        $t = date_format(
                            date_sub(date_create($t), date_interval_create_from_date_string('5 days')),
                            "Y-m-d H:i:s"
                        );
                        $sql =
                            "INSERT INTO control_region_def " .
                            "(resource_id,ak_def_id,control_region_type,control_region_starts,control_region_points,comment) " .
                            "VALUES(?,?,?,?,?,?)";
                        $params = [
                            $dataset['resource_id'],
                            $akId2akDefIdMap[$dataset['ak_id']]['ak_def_id'],
                            'data_points',
                            $t,
                            $controlIntervalSize,
                            'initial control region'
                        ];
                        $this->db->execute($sql, $params);
                    } elseif (date_create($controlRegionDef[0]['control_region_starts']) > date_create($data[0]['collected'])) {
                        $this->log(
                            "Updating initial control region for app kernel: " .
                            "{$akId2akDefIdMap[$dataset['ak_id']]['name']} on resource: " .
                            "{$resourceIdMap[$dataset['resource_id']]['name']}",
                            \CCR\Log::INFO
                        );
                        $t = date_format(date_create($data[0]['collected']), "Y-m-d") . " 00:00:00";
                        $t = date_format(
                            date_sub(date_create($t), date_interval_create_from_date_string('5 days')),
                            "Y-m-d H:i:s"
                        );
                        $sql = "UPDATE control_region_def SET control_region_starts=? WHERE control_region_def_id=?";
                        $params = [$t, $controlRegionDef[0]['control_region_def_id']];
                        $this->db->execute($sql, $params);
                    } else {
                        break;
                    }
                }
                //query for the control region definitions if none present initiate a new one
                $num_recalculated_CR = 0;
                $controlRegions = array();
                while (true) {
                    $controlRegionQuery =
                        "SELECT r.control_region_id,
                            d.control_region_def_id,d.resource_id,d.ak_def_id,
                            r.ak_id,r.metric_id,
                            d.control_region_type,
                            d.control_region_starts,
                            d.control_region_ends,
                            d.control_region_points,
                            r.completed,
                            r.controlStart,
                            r.controlEnd,
                            r.controlMin,
                            r.controlMax
                         FROM control_region_def AS d,control_regions AS r
                         WHERE d.resource_id = :resource_id
                           AND d.ak_def_id = :ak_def_id
                           AND r.ak_id = :ak_id
                           AND r.metric_id = :metric_id
                           AND r.control_region_def_id=d.control_region_def_id
                         ORDER BY d.control_region_starts;
                    ";
                    $controlRegionParams = [
                        ":resource_id" => $dataset['resource_id'],
                        ":ak_def_id" => $akId2akDefIdMap[$dataset['ak_id']]['ak_def_id'],
                        ":ak_id" => $dataset['ak_id'],
                        ":metric_id" => $dataset['metric_id']
                    ];
                    $controlRegions = $this->db->query($controlRegionQuery, $controlRegionParams);
                    //if there is no control regions initiate the first one
                    if (count($controlRegions) !== count($controlRegionDef)) {
                        $CRdefs = array();
                        foreach ($controlRegionDef as $cdef) {
                            $CRdefs[$cdef['control_region_def_id']] = $cdef;
                        }

                        $CR = array();
                        foreach ($controlRegions as $cr) {
                            if (key_exists($cr['control_region_def_id'], $CRdefs)) {
                                $CR[$cr['control_region_def_id']] = $cr;
                            } else {
                                $sql = "DELETE FROM control_regions WHERE control_region_id=?";
                                $params = [$cr['control_region_id']];
                                $this->db->execute($sql, $params);
                            }
                        }

                        foreach ($controlRegionDef as $crdef) {
                            if (!key_exists($crdef['control_region_def_id'], $CR)) {
                                $sql = "INSERT INTO control_regions (control_region_def_id,ak_id,metric_id) VALUES(?,?,?)";
                                $params = [$crdef['control_region_def_id'], $dataset['ak_id'], $dataset['metric_id']];
                                $this->db->execute($sql, $params);
                            }
                        }
                        continue;
                    }
                    //if there control regions are not completed recalculate them
                    if ($num_recalculated_CR == 0) {
                        foreach ($controlRegions as $controlRegion) {
                            if ($controlRegion['completed'] == 0 || $recalculateControls) {
                                $num_recalculated_CR++;
                                $completed = 0;
                                //caculate controls for that region
                                //Query for calculation properties of the control interval
                                $controlIntervalDataQuery =
                                    "SELECT md.resource_id,md.ak_id,md.metric_id,md.collected,md.value_string
                                     FROM `metric_data` md
                                     WHERE md.resource_id = :resource_id AND md.ak_id = :ak_id AND md.metric_id = :metric_id
                                       AND md.collected >= :control_region_starts";
                                $controlIntervalDataParams = $dataset_params;
                                $controlIntervalDataParams[":control_region_starts"] = $controlRegion['control_region_starts'];

                                if ($controlRegion['control_region_type'] == 'data_points') {
                                    $controlIntervalDataQuery .= " LIMIT " . intval(
                                        $controlRegion['control_region_points']
                                    );
                                } else {
                                    $controlIntervalDataQuery .= " AND md.collected <= :control_region_ends";
                                    $controlIntervalDataParams[":control_region_ends"] = $controlRegion['control_region_ends'];
                                }
                                $controlIntervalData = $this->db->query(
                                    $controlIntervalDataQuery,
                                    $controlIntervalDataParams
                                );
                                if ($controlRegion['control_region_type'] == 'data_points') {
                                    if (count($controlIntervalData) == $controlRegion['control_region_points']) {
                                        $completed = 1;
                                    }
                                } else {
                                    $completed = 1;
                                }

                                //pack $controlValues
                                $controlValues = array();
                                foreach ($controlIntervalData as $controlValue) {
                                    $controlValues[] = floatval($controlValue['value_string']);
                                }
                                //calculate control values
                                $controlLength = count($controlValues);
                                $controlMin = null;
                                $controlMax = null;
                                $controlStart = null;
                                $controlEnd = null;

                                if ($controlLength > 0) {
                                    $controlMin = min($controlValues);
                                    $controlMax = max($controlValues);

                                    if (false) {
                                        $controlSum = array_sum($controlValues);
                                        $controlAverage = $controlSum / $controlLength;

                                        $controlStart = $controlMin;
                                        $controlEnd = $controlMax;

                                        //use advanced technique to figure out (override) control start and end.
                                        //divided the set into two regions based on median, find the
                                        //average of each region and use as start and end.
                                        if ($controlLength > 4) {
                                            $middlePoint = $controlLength / 2;
                                            sort($controlValues);

                                            $startRegion = array_slice($controlValues, 0, $middlePoint);
                                            $endRegion = array_slice($controlValues, $middlePoint, $controlLength);

                                            $controlStart = array_sum($startRegion) / count($startRegion);
                                            $controlEnd = array_sum($endRegion) / count($endRegion);
                                        }
                                    }
                                    $controlSum = array_sum($controlValues);
                                    $controlAverage = $controlSum / $controlLength;
                                    $controlStDev = stats_standard_deviation($controlValues);

                                    $controlStart = $controlAverage - $control_criteria * $controlStDev;
                                    $controlEnd = $controlAverage + $control_criteria * $controlStDev;

                                    $controlDiff = abs($controlEnd - $controlStart);
                                    if ($controlDiff === 0) {
                                        $controlDiff = 1;
                                    }

                                    $updateStatement =
                                        "UPDATE control_regions
                                         SET completed = :completed,
                                             controlStart = :controlStart,
                                             controlEnd = :controlEnd,
                                             controlMin = :controlMin,
                                             controlMax = :controlMax
                                         WHERE control_region_id = :control_region_id";
                                    $updateStatementParams = [
                                        ":control_region_id" => $controlRegion['control_region_id'],
                                        ":completed" => $completed,
                                        ":controlStart" => $controlStart,
                                        ":controlEnd" => $controlEnd,
                                        ":controlMin" => $controlMin,
                                        ":controlMax" => $controlMax
                                    ];

                                    foreach ($controlIntervalData as $data_point) {
                                        $controlStatusUpdateStatement = "UPDATE metric_data
                                            SET controlStatus=:controlStatus
                                            WHERE resource_id = :resource_id
                                            and ak_id = :ak_id
                                            and metric_id = :metric_id
                                            and collected = :collected";
                                        $controlStatusUpdateStatementParams = [
                                            ":controlStatus" => 'control_region_time_interval',
                                            ":resource_id" => $data_point['resource_id'],
                                            ":ak_id" => $data_point['ak_id'],
                                            ":metric_id" => $data_point['metric_id'],
                                            ":collected" => $data_point['collected'],
                                        ];
                                        $this->db->execute(
                                            $controlStatusUpdateStatement,
                                            $controlStatusUpdateStatementParams
                                        );
                                        for ($i = 0; $i < $length; $i++) {
                                            if ($data[$i]['collected'] === $data_point['collected']) {
                                                $data[$i]['controlStatus'] = 'control_region_time_interval';
                                                break;
                                            }
                                        }
                                    }
                                } else {
                                    $updateStatement =
                                        "UPDATE control_regions
                                         SET completed = 0,controlStart = NULL,controlEnd = NULL,
                                             controlMin = NULL,controlMax = NULL
                                         WHERE control_region_id = :control_region_id";
                                    $updateStatementParams = [
                                        ":control_region_id" => $controlRegion['control_region_id']
                                    ];
                                }
                                $this->db->execute($updateStatement, $updateStatementParams);
                            }
                        }
                        if ($num_recalculated_CR == 0) { //no recalculation is done
                            break;
                        } else {
                            continue;
                        }
                    } else { //already been here once
                        break;
                    }
                }
                $time_end = microtime(true);
                $timing['contRegCalc'] += $time_end - $time_start;
                $timing['N_contRegCalc']++;

                $time_start_1 = microtime(true);
                //set last fake controlRegions for ease of controlRegion search
                $controlRegions[] = array(
                    'control_region_starts' => date_format(
                        date_add(
                            date_create("now"),
                            date_interval_create_from_date_string('1 year')
                        ),
                        "Y-m-d H:i:s"
                    )
                );
                //Set $controlApplicationRegions
                for ($idControlRegion = 0; $idControlRegion < count($controlRegions); $idControlRegion++) {
                    $controlRegions[$idControlRegion]['control_region_starts_timestamp'] = date_timestamp_get(
                        date_create($controlRegions[$idControlRegion]['control_region_starts'])
                    );
                }

                //Loop over data points
                $Nallupdate_metric_data = 0;

                $update_running_average = "";
                $update_control = "";
                $update_controlStart = "";
                $update_controlEnd = "";
                $update_controlMin = "";
                $update_controlMax = "";
                $update_controlStatus = "";
                $update_param = [];
                $controlStatusParam = [];
                $collected_array = [];

                for ($i = 0; $i < $length; $i++) {
                    //get control region
                    $date_collected_timestamp = date_timestamp_get(date_create($data[$i]['collected']));
                    for ($idControlRegion = 0; $idControlRegion < count($controlRegions) - 1; $idControlRegion++) {
                        if ($date_collected_timestamp >= $controlRegions[$idControlRegion]['control_region_starts_timestamp']
                            && $date_collected_timestamp < $controlRegions[$idControlRegion + 1]['control_region_starts_timestamp']
                        ) {
                            break;
                        }
                    }
                    $controlRegion = $controlRegions[$idControlRegion];
                    $controlMin = $controlRegion['controlMin'];
                    $controlMax = $controlRegion['controlMax'];
                    $controlStart = $controlRegion['controlStart'];
                    $controlEnd = $controlRegion['controlEnd'];

                    // set $controlDiff to 1 if $controlDiff is near zero
                    $controlDiff = abs($controlEnd - $controlStart);
                    if ($controlDiff / max($controlEnd, $controlStart, 1.0) < 1e-12) {
                        $controlDiff = 1;
                    }

                    //calculate if the data point is in control or what
                    if ($i < $runningAverageSize) {
                        $offset = ($i - $runningAverageSize < 0) ? 0 : ($i - $runningAverageSize);
                        $l = $i - $offset + 1;
                    } else {
                        $offset = $i - $runningAverageSize + 1;
                        $l = $runningAverageSize;
                    }

                    $ra_values = array();
                    for ($j = $offset, $m = $offset + $l; $j < $m; $j++) {
                        $ra_values[] = floatval($data[$j]['value_string']);
                    }
                    $ra_count = count($ra_values);
                    if ($ra_count < $runningAverageSize) ///try to find the previous points and continue the running average
                    {
                        $dataQuery2 = $this->db->prepare(
                            "SELECT md.collected, md.value_string, aki.env_version
                            FROM `metric_data` md,
                                     `ak_instance` aki
                            WHERE md.resource_id = :resource_id
                            and md.ak_id = :ak_id
                            and md.metric_id = :metric_id
                            and md.control is not NULL
                            and md.value_string is not NULL
                            and aki.ak_id = md.ak_id
                            and aki.collected = md.collected
                            and aki.resource_id = md.resource_id
                            ORDER BY collected DESC
                            LIMIT :running_average_size"
                        );
                        $dataQuery2->bindValue(":resource_id", $dataset_params[":resource_id"]);
                        $dataQuery2->bindValue(":ak_id", $dataset_params[":ak_id"]);
                        $dataQuery2->bindValue(":metric_id", $dataset_params[":metric_id"]);
                        $dataQuery2->bindValue(":running_average_size", $runningAverageSize, PDO::PARAM_INT);

                        $dataQuery2->execute();
                        $data2 = $dataQuery2->fetchAll();
                        $data2Count = count($data2);
                        for ($j = 0; $j < $data2Count && $j < $runningAverageSize - $ra_count; $j++) {
                            $ra_values[] = $data2[$j]['value_string'];
                        }
                    }

                    $runningAverage = array_sum($ra_values) / count($ra_values);
                    $decision_value = floatval($data[$i]['value_string']);

                    $controlStatus = 'undefined';
                    if ($controlStart !== null) {
                        //method 1: use running average to calcualte control
                        if ($decision_value < $controlStart) {
                            $control = ($larger ? -1.0 : 1.0) * abs($controlStart - $decision_value) / $controlDiff;
                        } elseif ($decision_value > $controlEnd) {
                            $control = ($larger ? 1.0 : -1.0) * abs($decision_value - $controlEnd) / $controlDiff;
                        } else {
                            $control = 0;
                        }

                        //TODO: set $controlPivot globally
                        $controlPivot = 0.0;//-0.5;
                        if ($control > 0) {
                            $controlStatus = 'over_performing';
                        } elseif ($control < $controlPivot) {
                            $controlStatus = 'under_performing';
                        } else {
                            $controlStatus = 'in_contol';
                        }
                    } else {
                        $control = 0;
                    }

                    if ($data[$i]['controlStatus'] === 'control_region_time_interval') {
                        $controlStatus = 'control_region_time_interval';
                    }

                    $update_param[":collected" . $i] = $data[$i]['collected'];
                    $update_param[":runningAverage" . $i] = $runningAverage;
                    $update_param[":control" . $i] = $control;
                    $update_param[":controlStart" . $i] = $controlStart;
                    $update_param[":controlEnd" . $i] = $controlEnd;
                    $update_param[":controlMin" . $i] = $controlMin;
                    $update_param[":controlMax" . $i] = $controlMax;
                    $update_param[":controlStatus" . $i] = $controlStatus;
                    $controlStatusParam[":controlStatus" . $i] = $controlStatus;

                    $update_running_average .= " WHEN :collected$i THEN :runningAverage$i \n";
                    $update_control .= " WHEN :collected$i THEN :control$i \n";
                    $update_controlStart .= " WHEN :collected$i THEN :controlStart$i \n";
                    $update_controlEnd .= " WHEN :collected$i THEN :controlEnd$i \n";
                    $update_controlMin .= " WHEN :collected$i THEN :controlMin$i \n";
                    $update_controlMax .= " WHEN :collected$i THEN :controlMax$i \n";
                    $update_controlStatus .= " WHEN :collected$i THEN :controlStatus$i \n";

                    $collected_array[":collected" . $i] = $data[$i]['collected'];

                    $Nallupdate_metric_data += 1;
                    if ($Nallupdate_metric_data >= 500 || $i >= $length - 1) {
                        $time_start_sqlupdate = microtime(true);

                        $allupdate_metric_data = "UPDATE metric_data SET
                            running_average = CASE collected {$update_running_average} ELSE running_average END,
                            control = CASE collected {$update_control} ELSE control END,
                            controlStart = CASE collected {$update_controlStart} ELSE controlStart END,
                            controlEnd = CASE collected {$update_controlEnd} ELSE controlEnd END,
                            controlMin = CASE collected {$update_controlMin} ELSE controlMin END,
                            controlMax = CASE collected {$update_controlMax} ELSE controlMax END,
                            controlStatus = CASE collected {$update_controlStatus} ELSE controlStatus END
                            WHERE resource_id = :resource_id AND ak_id = :ak_id AND metric_id = :metric_id
                              AND collected IN (" . implode(',', array_keys($collected_array)) . ")";

                        $allupdate_metric_data_params = array_merge(
                            [
                                ":resource_id" => $dataset_params[":resource_id"],
                                ":ak_id" => $dataset_params[":ak_id"],
                                ":metric_id" => $dataset_params[":metric_id"]
                            ],
                            $update_param,
                            $collected_array
                        );

                        $this->db->execute($allupdate_metric_data, $allupdate_metric_data_params);
                        $time_end_sqlupdate1 = microtime(true);
                        $timing['sqlupdate1'] += $time_end_sqlupdate1 - $time_start_sqlupdate;

                        $allupdate_a_data2 = "UPDATE a_data2 SET
                            running_average = CASE FROM_UNIXTIME(collected) {$update_running_average} ELSE running_average END,
                            control = CASE FROM_UNIXTIME(collected) {$update_control} ELSE control END,
                            controlStart = CASE FROM_UNIXTIME(collected) {$update_controlStart} ELSE controlStart END,
                            controlEnd = CASE FROM_UNIXTIME(collected) {$update_controlEnd} ELSE controlEnd END,
                            controlMin = CASE FROM_UNIXTIME(collected) {$update_controlMin} ELSE controlMin END,
                            controlMax = CASE FROM_UNIXTIME(collected) {$update_controlMax} ELSE controlMax END,
                            controlStatus = CASE FROM_UNIXTIME(collected) {$update_controlStatus} ELSE controlStatus END
                        WHERE resource_id = :resource_id AND ak_def_id = :ak_def_id AND num_units = :num_units
                          AND metric_id = :metric_id
                          AND FROM_UNIXTIME(collected) IN (" . implode(', ', array_keys($collected_array)) . ")";

                        $allupdate_a_data2_params = array_merge(
                            [
                                ":resource_id" => $dataset_params[":resource_id"],
                                ":ak_def_id" => $akId2akDefIdMap[$dataset['ak_id']]['ak_def_id'],
                                ":num_units" => $akId2akDefIdMap[$dataset['ak_id']]['num_units'],
                                ":metric_id" => $dataset_params[":metric_id"]
                            ],
                            $update_param,
                            $collected_array
                        );

                        $this->db->execute($allupdate_a_data2, $allupdate_a_data2_params);
                        $time_end_sqlupdate2 = microtime(true);

                        // ak_instance
                        if ($metrics_walltime_id !== null && $dataset['metric_id'] === $metrics_walltime_id) {
                            $allupdate_ak_instance =
                                "UPDATE ak_instance SET
                                    controlStatus = CASE collected {$update_controlStatus} ELSE controlStatus END
                                WHERE resource_id = :resource_id AND ak_id = :ak_id
                                  and collected IN (" . implode(', ', array_keys($collected_array)) . ")";
                            $allupdate_ak_instance_params = array_merge(
                                [
                                    ":resource_id" => $dataset_params[":resource_id"],
                                    ":ak_id" => $dataset_params[":ak_id"],
                                ],
                                $controlStatusParam,
                                $collected_array
                            );
                            $this->db->execute($allupdate_ak_instance, $allupdate_ak_instance_params);
                        }

                        $timing['sqlupdate1'] += $time_end_sqlupdate1 - $time_start_sqlupdate;
                        $timing['sqlupdate2'] += $time_end_sqlupdate2 - $time_end_sqlupdate1;

                        $timing['N_sqlupdate1'] += $Nallupdate_metric_data;
                        $timing['N_sqlupdate2'] += $Nallupdate_metric_data;

                        $Nallupdate_metric_data = 0;

                        $update_running_average = "";
                        $update_control = "";
                        $update_controlStart = "";
                        $update_controlEnd = "";
                        $update_controlMin = "";
                        $update_controlMax = "";
                        $update_controlStatus = "";
                        $update_param = [];
                        $controlStatusParam = [];
                        $collected_array = [];
                    }
                }

                $time_end = microtime(true);
                $timing['contCalc'] += $time_end - $time_start_1;
                $timing['N_contCalc']++;
            }
        }

        $time_end_bigcycle = microtime(true);
        $t_bigcycle = $time_end_bigcycle - $time_start_bigcycle;

        $this->log("Timing(Cycle for calculating running average and control values)=" . ($t_bigcycle), \CCR\Log::DEBUG);
        $this->log(
            "    Timing(data for control calc)=" . sprintf("%.4f", $timing['dataQuery']) . " (" . sprintf(
                "%.2f",
                100.0 * $timing['dataQuery'] / $t_bigcycle
            ) . "%)",
            \CCR\Log::DEBUG
        );
        $this->log(
            "    Timing(Control region calculation)=" . sprintf(
                "%.4f",
                $timing['contRegCalc']
            ) . " (" . sprintf("%.2f", 100.0 * $timing['contRegCalc'] / $t_bigcycle) . "%)",
            \CCR\Log::DEBUG
        );
        $this->log(
            "    Timing(data for control calc)=" . sprintf("%.4f", $timing['contCalc']) . " (" . sprintf(
                "%.2f",
                100.0 * $timing['contCalc'] / $t_bigcycle
            ) . "%)",
            \CCR\Log::DEBUG
        );
        $this->log(
            "        Timing(sql update)=" . sprintf("%.4f", $timing['sqlupdate1']) . " (" . sprintf(
                "%.2f",
                100.0 * $timing['sqlupdate1'] / $t_bigcycle
            ) . "%)",
            \CCR\Log::DEBUG
        );
        $this->log(
            "        Timing(sql update)=" . sprintf("%.4f", $timing['sqlupdate2']) . " (" . sprintf(
                "%.2f",
                100.0 * $timing['sqlupdate2'] / $t_bigcycle
            ) . "%)",
            \CCR\Log::DEBUG
        );
    }

    // --------------------------------------------------------------------------------
    // Generate aggreate tables rather than views.  MySQL does not have
    // materialized views and periodically it takes 10+ seconds to query the view.
    // --------------------------------------------------------------------------------

    public function createAggregateTables($includeControlData = false)
    {
        $this->db->handle()->beginTransaction();

        try {
            // Create a table containing data needed to generate a hierarchical tree
            // view of the application kernels including a start and end date for the
            // collection of metrics.

            $sql = "DROP TABLE IF EXISTS a_tree";
            $this->db->execute($sql);

            $sql = "CREATE TABLE a_tree (
                ak_name varchar(64) NOT NULL,
                resource varchar(128) NOT NULL,
                metric varchar(128) NOT NULL,
                unit varchar(32) DEFAULT NULL,
                processor_unit enum('node','core') DEFAULT NULL,
                num_units int(10) unsigned NOT NULL DEFAULT '1',
                ak_def_id int(10) unsigned NOT NULL DEFAULT '0',
                resource_id int(10) unsigned NOT NULL,
                metric_id int(10) unsigned NOT NULL DEFAULT '0',
                start_time datetime,
                end_time datetime,
                `status` enum('success','failure','error','queued') DEFAULT NULL,
                KEY ak_def_id (ak_def_id, resource_id, metric_id, num_units),
                KEY resource_id (resource_id),
                KEY start_time (start_time),
                KEY end_time (end_time),
                KEY `status` (`status`)
                ) ENGINE=MyISAM
                AS
                SELECT
                def.name as ak_name, r.resource, m.name as metric, m.unit as unit,
                def.processor_unit, ak.num_units, def.ak_def_id, ai.resource_id, m.metric_id,
                MIN(ai.collected) as start_time, MAX(ai.collected) as end_time, ai.status
                FROM app_kernel_def def
                JOIN app_kernel ak USING(ak_def_id)
                JOIN ak_instance ai USING(ak_id)
                JOIN resource r USING(resource_id)
                JOIN ak_has_metric map USING(ak_id, num_units)
                JOIN metric m USING(metric_id)
                WHERE def.visible = 1 AND r.visible = 1 AND ai.status = 'success'
                GROUP BY def.ak_def_id, r.resource_id, m.metric_id, ak.num_units
                ORDER BY def.name, r.resource, m.name, ak.num_units";
            $this->db->execute($sql);

            // Create a table containing all of the application kernel metric data

            $sql = "DROP TABLE IF EXISTS a_data";
            $this->db->execute($sql);

            $sql = "CREATE TABLE a_data (
                ak_name varchar(64) NOT NULL,
                resource varchar(128) NOT NULL,
                metric varchar(128) NOT NULL,
                num_units int(10) unsigned NOT NULL DEFAULT '1',
                processor_unit enum('node','core') DEFAULT NULL,
                collected int(10) NOT NULL DEFAULT '0',
                env_version varchar(64) DEFAULT NULL,
                unit varchar(32) DEFAULT NULL,
                metric_value varchar(255) DEFAULT NULL,
                ak_def_id int(10) unsigned NOT NULL DEFAULT '0',
                resource_id int(10) unsigned NOT NULL DEFAULT '0',
                metric_id int(10) unsigned NOT NULL DEFAULT '0',
                `status` enum('success','failure','error','queued') DEFAULT NULL,
                KEY ak_def_id (ak_def_id, resource_id, metric_id, num_units),
                KEY ak_name (ak_name, resource, metric, num_units),
                KEY resource_id (resource_id),
                KEY metric_id (metric_id),
                KEY num_units (num_units),
                KEY env_version (env_version),
                KEY collected (collected),
                KEY `status` (`status`)
                ) ENGINE=MyISAM
                AS
                SELECT
                def.name as ak_name, r.resource, m.name as metric, ak.num_units, def.processor_unit,
                UNIX_TIMESTAMP(ai.collected) as collected, ai.env_version, m.unit,
                md.value_string as metric_value, def.ak_def_id, r.resource_id, m.metric_id, ai.status
                FROM app_kernel_def def
                JOIN app_kernel ak USING(ak_def_id)
                JOIN ak_instance ai USING(ak_id)
                JOIN resource r USING(resource_id)
                JOIN ak_has_metric map USING(ak_id, num_units)
                JOIN metric m USING(metric_id)
                JOIN metric_data md USING(metric_id, ak_id, collected, resource_id)
                WHERE def.visible = 1 AND r.visible = 1 AND ai.status = 'success'
                ORDER BY def.name, r.resource, m.name, ak.num_units, ai.collected";
            $this->db->execute($sql);

            if ($includeControlData) {
                $sql = "DROP TABLE IF EXISTS a_tree2";
                $this->db->execute($sql);

                $sql = "CREATE TABLE a_tree2 (
                ak_name varchar(64) NOT NULL,
                resource varchar(128) NOT NULL,
                metric varchar(128) NOT NULL,
                unit varchar(32) DEFAULT NULL,
                processor_unit enum('node','core') DEFAULT NULL,
                num_units int(10) unsigned NOT NULL DEFAULT '1',
                ak_def_id int(10) unsigned NOT NULL DEFAULT '0',
                resource_id int(10) unsigned NOT NULL,
                metric_id int(10) unsigned NOT NULL DEFAULT '0',
                start_time datetime,
                end_time datetime,
                `status` enum('success','failure','error','queued') DEFAULT NULL,
                KEY ak_def_id (ak_def_id, resource_id, metric_id, num_units),
                KEY resource_id (resource_id),
                KEY start_time (start_time),
                KEY end_time (end_time),
                KEY `status` (`status`)
                ) ENGINE=MyISAM
                AS
                SELECT
                def.name as ak_name, r.resource, m.name as metric, m.unit as unit,
                def.processor_unit, ak.num_units, def.ak_def_id, ai.resource_id, m.metric_id,
                MIN(ai.collected) as start_time, MAX(ai.collected) as end_time, ai.status
                FROM app_kernel_def def
                JOIN app_kernel ak USING(ak_def_id)
                JOIN ak_instance ai USING(ak_id)
                JOIN resource r USING(resource_id)
                JOIN ak_has_metric map USING(ak_id, num_units)
                JOIN metric m USING(metric_id)
                WHERE def.visible = 1 AND r.visible = 1 AND ai.status <> 'queued'
                GROUP BY def.ak_def_id, r.resource_id, m.metric_id, ak.num_units
                ORDER BY def.name, r.resource, m.name, ak.num_units";
                $this->db->execute($sql);

                $sql = "DROP TABLE IF EXISTS a_data2";
                $this->db->execute($sql);

                $sql = "CREATE TABLE a_data2 (
                ak_name varchar(64) NOT NULL,
                resource varchar(128) NOT NULL,
                metric varchar(128) NOT NULL,
                num_units int(10) unsigned NOT NULL DEFAULT '1',
                processor_unit enum('node','core') DEFAULT NULL,
                collected int(10) NOT NULL DEFAULT '0',
                env_version varchar(64) DEFAULT NULL,
                unit varchar(32) DEFAULT NULL,
                metric_value varchar(255) DEFAULT NULL,
                running_average double DEFAULT NULL,
                control double DEFAULT NULL,
                controlStart double DEFAULT NULL,
                controlEnd double DEFAULT NULL,
                controlMin double DEFAULT NULL,
                controlMax double DEFAULT NULL,
                ak_def_id int(10) unsigned NOT NULL DEFAULT '0',
                resource_id int(10) unsigned NOT NULL DEFAULT '0',
                metric_id int(10) unsigned NOT NULL DEFAULT '0',
                `status` enum('success','failure','error','queued') DEFAULT NULL,
                `controlStatus` ENUM('undefined','control_region_time_interval','in_contol','under_performing','over_performing') NOT NULL DEFAULT 'undefined',
                KEY ak_def_id (ak_def_id, resource_id, metric_id, num_units),
                KEY ak_name (ak_name, resource, metric, num_units),
                KEY ak_collected (ak_def_id, collected, status),
                KEY resource_id (resource_id),
                KEY metric_id (metric_id),
                KEY num_units (num_units),
                KEY env_version (env_version),
                KEY collected (collected),
                KEY `status` (`status`)
                ) ENGINE=MyISAM
                AS
                SELECT
                def.name as ak_name, r.resource, m.name as metric, ak.num_units, def.processor_unit,
                UNIX_TIMESTAMP(ai.collected) as collected, ai.env_version, m.unit,
                md.value_string as metric_value, md.running_average, md.control, md.controlStart, md.controlEnd, md.controlMin, md.controlMax, def.ak_def_id, r.resource_id, m.metric_id, ai.status, md.controlStatus
                FROM app_kernel_def def
                JOIN app_kernel ak USING(ak_def_id)
                JOIN ak_instance ai USING(ak_id)
                JOIN resource r USING(resource_id)
                JOIN ak_has_metric map USING(ak_id, num_units)
                JOIN metric m USING(metric_id)
                left outer JOIN metric_data md on
                (ai.ak_id = md.ak_id
                AND ai.collected = md.collected
                AND ai.resource_id = md.resource_id
                AND map.metric_id = md.metric_id )
                WHERE def.visible = 1 AND r.visible = 1 AND ai.status <> 'queued'
                ORDER BY def.name, r.resource, m.name, ak.num_units, ai.collected";
                $this->db->execute($sql);
            }
        } catch (Exception $e) {
            $this->log("Error building aggregate tables '$sql': " . $e->getMessage());
            $this->db->handle()->rollback();
            throw $e;
        }

        $this->db->handle()->commit();

        return true;
    }

    // --------------------------------------------------------------------------------

    public function updateEnvironmentVersions($dryRunMode = false)
    {
        $appKernelDefs = $this->loadAppKernelDefinitions();
        $resources = $this->loadResources();
        $instanceData = new InstanceData;

        $this->db->handle()->beginTransaction();

        foreach ($resources as $resource) {
            print "Scan resource {$resource->nickname}\n";

            foreach ($appKernelDefs as $appKernelDef) {
                print "  Scan app kernel {$appKernelDef->basename}\n";

                $akInstances = $this->loadAppKernelInstances($appKernelDef->id, $resource->id);

                foreach ($akInstances as $akInstance) {
                    $timestamp = strtotime($akInstance->deployment_time);
                    $options = array(
                        'ak_def_id' => $appKernelDef->id,
                        'collected' => $timestamp,
                        'resource_id' => $resource->id,
                        'num_units' => 1
                    );
                    $this->loadAppKernelInstanceInfo($options, $instanceData);
                    $recalculatedEnvVersion = $instanceData->environmentVersion();

                    if ($recalculatedEnvVersion != $instanceData->environmentVersion) {
                        print "    * " . $resource->nickname . "@" .
                            $appKernelDef->basename . "." . $instanceData->num_proc_units .
                            " (" . date("Y-m-d", $timestamp) . ") UPDATE " .
                            $recalculatedEnvVersion . " != " . $instanceData->environmentVersion . "\n";

                        if ($dryRunMode) {
                            continue;
                        }

                        try {
                            $sql = "UPDATE ak_instance SET env_version=? WHERE " .
                                "ak_id=? AND collected=FROM_UNIXTIME(?) AND resource_id=? AND env_version=?";
                            $params = array(
                                $recalculatedEnvVersion,
                                $instanceData->db_ak_id,
                                $timestamp,
                                $resource->id,
                                $instanceData->environmentVersion
                            );
                            $this->db->execute($sql, $params);
                            print "      UPDATE ak_instance SET env_version='$recalculatedEnvVersion' WHERE " .
                                "ak_id={$instanceData->db_ak_id} AND collected=FROM_UNIXTIME($timestamp) AND " .
                                "resource_id={$resource->id} AND env_version='{$instanceData->environmentVersion}'\n";
                        } catch (Exception $e) {
                            $this->log("Error executing query '$sql': " . $e->getMessage());
                            $this->db->handle()->rollback();
                        }
                    }
                }
            }
        }
        $this->db->handle()->commit();
    }
}


// ================================================================================
// An N-Tuple is a set of N ordered values.  Set up an N-Tuple that can be used
// to track when database results change.
// ================================================================================
// phpcs:disable PSR1.Classes.ClassDeclaration
class Tuple
{
    // phpcs:enable PSR1.Classes.ClassDeclaration
    private $size = 0;
    private $data = array();

    // --------------------------------------------------------------------------------
    // Set up the tuple and initialize the values to an array of NULL values.
    //
    // @param $size Number of elements in the tuple.
    // --------------------------------------------------------------------------------

    public function __construct($size)
    {
        $this->size = $size;
        $this->data = array_fill(0, $size, null);
    }

    // --------------------------------------------------------------------------------
    // Ensure that the number of parameters matches the size of the tuple and Set
    // the tuple values.
    //
    // @throws Exception of the number of arguments does not match the tuple size.
    // --------------------------------------------------------------------------------

    public function set()
    {
        if (func_num_args() != $this->size) {
            throw new Exception("Not enough data elements");
        }
        $this->data = func_get_args();
    }
}

// ================================================================================
// A dataset containing three vectors containing values to be plotted,
// timestamps for each value, and a version vector where a value of 1 indicates
// that the version has changed since the previous vector element.  Also
// includes information such as application kernel name, resource name, metric,
// metric unit, and number of processing units, app kernel and resource
// descriptions.
// ================================================================================
// phpcs:disable PSR1.Classes.ClassDeclaration
class Dataset
{
    // phpcs:enable PSR1.Classes.ClassDeclaration
    public $akName;
    public $resourceName;
    public $metric;
    public $metricUnit;
    public $numProcUnits;
    public $description;
    public $resourceDescription;
    public $rawNumProcUnits;
    public $note;
    public $akId;
    public $resourceId;
    public $metricId;
    public $valueVector = array();
    public $valueLowVector = array();
    public $valueHighVector = array();
    public $controlVector = array();
    public $controlStartVector = array();
    public $controlEndVector = array();
    public $controlMinVector = array();
    public $controlMaxVector = array();
    public $runningAverageVector = array();
    public $timeVector = array();
    public $versionVector = array();
    public $controlStatus = array();

    public function __construct(
        $name,
        $akId,
        $resource,
        $resourceId,
        $metric,
        $metricId,
        $metricUnit,
        $numProcUnits,
        $description,
        $rawNumProcUnits,
        $resourceDescription = 'resource description'
    ) {
        $this->akName = $name;
        $this->akId = $akId;
        $this->resourceName = $resource;
        $this->resourceId = $resourceId;
        $this->metric = $metric;
        $this->metricId = $metricId;
        $this->metricUnit = $metricUnit;
        $this->numProcUnits = $numProcUnits;
        $this->description = $description;
        $this->resourceDescription = $resourceDescription;
        $this->rawNumProcUnits = $rawNumProcUnits;
        $this->note = "";
    }

    public function getChartTimeVector()
    {
        $chartTimeVector = array();
        foreach ($this->timeVector as $time) {
            $chartTimeVector[] = chartTime2($time);
        }
        return $chartTimeVector;
    }

    public function durationInDays()
    {
        $length = count($this->valueVector);
        if ($length <= 0) {
            return 0;
        }
        return ($this->timeVector[$length - 1] - $this->timeVector[0]) / (3600.00 * 24.0);
    }

    public function export()
    {
        date_default_timezone_set('UTC');
        $headers = array();
        $rows = array();
        $duration_info = array('start' => '', 'end' => '');
        $title = array('title' => 'title');
        $title2 = array();
        $length = count($this->valueVector);

        if ($length > 0) {
            $duration_info['start'] = date('Y-m-d', $this->timeVector[0]);
            $duration_info['end'] = date('Y-m-d', $this->timeVector[$length - 1]);

            $title['title'] = "App Kernel Data";
            $title2['parameters'] = array(
                "App Kernel = " . $this->akName,
                "Resource = " . $this->resourceName,
                "Metric = " . $this->metric,
                "Processing Units = " . $this->numProcUnits
            );
            $headers = array('Date', 'Value', 'Control', 'Changed');
            for ($i = 0; $i < $length; $i++) {
                $rows[$this->timeVector[$i]] = array(
                    date('Y-m-d H:i:s', $this->timeVector[$i]),
                    $this->valueVector[$i],
                    $this->controlVector[$i],
                    $this->versionVector[$i] == 1 ? 'yes' : 'no'
                );
            }
        }

        return array(
            'title' => $title,
            'title2' => $title2,
            'duration' => $duration_info,
            'headers' => $headers,
            'rows' => $rows
        );
    }

    // --------------------------------------------------------------------------------
    // Aggregate dataset
    //
    // @param aggTime aggregation time in hours
    // @return Dataset with aggregated values
    // --------------------------------------------------------------------------------

    public function aggregate($aggTime)
    {
        $aggTime = intval($aggTime);
        $aggDataset = new Dataset(
            $this->akName,
            $this->akId,
            $this->resourceName,
            $this->resourceId,
            $this->metric,
            $this->metricId,
            $this->metricUnit,
            $this->numProcUnits,
            $this->description,
            $this->rawNumProcUnits,
            $this->resourceDescription
        );

        if (count($this->timeVector) === 0) {
            return $aggDataset;
        }

        if ($aggTime === 24 * 3600) {
            $aggDataset->note .= "(One day avg.)";
        } elseif ($aggTime === 7 * 24 * 3600) {
            $aggDataset->note .= "(Avr. by week)";
        } elseif ($aggTime === 30 * 24 * 3600) {
            $aggDataset->note .= "(Avr. by 30 days)";
        } else {
            $aggDataset->note .= sprintf("(Avr. by %.1f days)", $aggTime / (24 * 3600));
        }

        $prevTime = intval(intval($this->timeVector[0]) / $aggTime);
        $meanVal = 0.0;
        $minVal = $this->valueVector[0];
        $maxVal = $this->valueVector[0];
        $n = 0;
        $totalPoints = count($this->timeVector);
        for ($i = 0; $i < $totalPoints; $i++) {
            $curTime = intval(intval($this->timeVector[$i]) / $aggTime);
            if ($curTime !== $prevTime || $i === $totalPoints - 1) {
                $aggDataset->timeVector[] = strval($curTime * $aggTime);
                $aggDataset->valueVector[] = strval($meanVal / $n);
                $aggDataset->valueHighVector[] = strval($maxVal);
                $aggDataset->valueLowVector[] = strval($minVal);

                // for following arragy let's keep only last record for aggregated period
                if (count($this->controlVector) > $i) {
                    $aggDataset->controlVector[] = $this->controlVector[$i];
                }
                if (count($this->controlStartVector) > $i) {
                    $aggDataset->controlStartVector[] = $this->controlStartVector[$i];
                }
                if (count($this->controlEndVector) > $i) {
                    $aggDataset->controlEndVector[] = $this->controlEndVector[$i];
                }
                if (count($this->controlMinVector) > $i) {
                    $aggDataset->controlMinVector[] = $this->controlMinVector[$i];
                }
                if (count($this->controlMaxVector) > $i) {
                    $aggDataset->controlMaxVector[] = $this->controlMaxVector[$i];
                }
                if (count($this->runningAverageVector) > $i) {
                    $aggDataset->runningAverageVector[] = $this->runningAverageVector[$i];
                }
                if (count($this->versionVector) > $i) {
                    $aggDataset->versionVector[] = $this->versionVector[$i];
                }
                if (count($this->controlStatus) > $i) {
                    $aggDataset->controlStatus[] = $this->controlStatus[$i];
                }

                $prevTime = $curTime;
                $meanVal = 0.0;
                $minVal = $this->valueVector[$i];
                $maxVal = $this->valueVector[$i];
                $n = 0;
            }
            $n++;
            $val = floatval($this->valueVector[$i]);
            $meanVal += floatval($val);
            $minVal = min($minVal, $val);
            $maxVal = max($maxVal, $val);
        }
        return $aggDataset;
    }
    // --------------------------------------------------------------------------------
    // Aggregate dataset if needed
    //
    // @return Dataset with aggregated values
    // --------------------------------------------------------------------------------
    public function autoAggregate()
    {
        // aggregate output if number of points is large
        $num_points = count($this->timeVector);
        if ($num_points < 201) {
            return $this;
        } else {
            $dt = intval($this->timeVector[0]) - intval($this->timeVector[$num_points - 1]);
            $dt /= 24 * 3600;
            if ($dt < 365 / 2) {
                return $this->aggregate(24 * 3600);
            } elseif ($dt < 365) {
                return $this->aggregate(7 * 24 * 3600);
            } else {
                return $this->aggregate(30 * 24 * 3600);
            }
        }
    }
}
