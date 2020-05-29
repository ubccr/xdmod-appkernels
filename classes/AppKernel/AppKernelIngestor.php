<?php

namespace AppKernel;

use CCR\Log;
use AppKernel;

//use AppKernel\AppKernelDb;
//use AppKernel\IngestionLogEntry;
//use AppKernel\InstanceData;
use Exception;

/**
 * Class AppKernelIngestor
 * @package AppKernel
 */
class AppKernelIngestor
{
    /**
     * Handle to the database resource
     *
     * @var \AppKernel\AppKernelDb|null
     */
    private $db = null;

    /**
     * Optional PEAR::Log for logging
     *
     * @var Log|\Log|object|null
     */
    private $logger = null;

    /**
     * Operate in dry-run mode.  Query and parse files but do not update the
     * database in any way.
     *
     * @var bool
     */
    private $dryRunMode = false;

    /**
     * Ingestion start timestamp.
     *
     * @var int|null
     */
    private $startTimestamp = null;

    /**
     * Ingestion end timestamp.
     *
     * @var int|null
     */
    private $endTimestamp = null;

    // Loading restrictions

    /**
     * If set, the app kernel to ingest.
     *
     * @var string|null
     */
    private $restrictToAppKernel = null;

    /**
     * If set, the resource to ingest.
     *
     * @var string|null
     */
    private $restrictToResource = null;

    /**
     * Remove ingested data for given time period prior to reingesting it.
     *
     * @var bool
     */
    private $removeIngestedDataVar = false;

    /**
     * Use replace statements to replace duplicate metric/intance data.
     * Default behavior is to ignore duplicates.
     *
     * @var bool
     */
    private $replace = false;

    /**
     * Recalculate the controls and control intervals
     *
     * @var bool
     */
    private $recalculateControls = false;

    /**
     * Logs for data on the ingestion process.
     *
     * @var IngestionLogEntry|null
     */
    private $ingestionLog = null;

    /**
     * Number of app kernels ingested.
     *
     * This is not currently used.
     *
     * @var int
     */
    private $appKernelCounter = 0;

    /**
     * Explorer class to query and parse the deployment engine.
     *
     * NOTE: This should be renamed to Akrr or AKRR.
     *
     * @var string
     */
    private $explorerType = 'Arr';

    /**
     * List of enabled resources from the app kernel database.
     *
     * @var array
     */
    private $dbResourceList = [];
    private $dbAKList = [];
    private $dbAKIdMap = [];

    /**
     * Deployment engine explorer.
     *
     * @var \AppKernel\iAppKernelExplorer
     */
    private $deploymentExplorer = null;
    private $parser = null;
    /**
     * Summary of app kernel ingestion.
     *
     * @var array
     */
    private $appKernelSummaryReport = array(
        'examined' => 0,
        'loaded' => 0,
        'incomplete' => 0,
        'parse_error' => 0,
        'queued' => 0,
        'unknown_type' => 0,
        'sql_error' => 0,
        'error' => 0,
        'duplicate' => 0,
        'exception' => 0,
    );

    /**
     * Timeframes available for the "last N" option
     *
     * @var array
     */
    public static $validTimeframes = array(
        'hour' => 3600,
        'day' => 86400,
        'week' => 604800,
        'month' => 2618784,

        // All data since the last successful load recorded in the database
        'load' => null,
    );

    /**
     * AppKernelIngestor constructor.
     * @param \Log|null $logger A Pear::Log object (http://pear.php.net/package/Log/
     * @param array $config The configuration section for ingestion
     * @param bool $config ['dryRunMode']
     * @param int|null $config ['startTimestamp']
     * @param int|null $config ['endTimestamp']
     * @param string|null $config ['sinceLastLoadTime']
     * @param int $config ['offsetStartTimestampBy']
     * @param string|null $config ['restrictToAppKernel']
     * @param string|null $config ['restrictToResource']
     * @param bool $config ['removeIngestedData']
     *
     * @throws Exception on incorrect config
     */
    public function __construct(\Log $logger = null, $config = [])
    {
        $this->logger = $logger !== null ? $logger : \Log::singleton('null');

        // set options
        if (array_key_exists('dryRunMode', $config)) {
            if (!is_bool($config['dryRunMode'])) {
                throw new Exception("dryRunMode should be boolean!");
            }
            $this->dryRunMode = $config['dryRunMode'];
            unset($config['dryRunMode']);
        }
        if (array_key_exists('endTimestamp', $config)) {
            if ($config['endTimestamp'] !== null && !is_integer($config['endTimestamp'])) {
                throw new Exception("endTimestamp should be integer timestamp!");
            }
            $this->endTimestamp = $config['endTimestamp'];
            unset($config['endTimestamp']);
        }
        if (array_key_exists('startTimestamp', $config)) {
            if ($config['startTimestamp'] !== null && !is_integer($config['startTimestamp'])) {
                throw new Exception("startTimestamp should be integer timestamp!");
            }
            $this->startTimestamp = $config['startTimestamp'];
            unset($config['startTimestamp']);
        }
        if (array_key_exists('restrictToAppKernel', $config)) {
            if ($config['restrictToAppKernel'] !== null && !is_string($config['restrictToAppKernel'])) {
                throw new Exception("restrictToAppKernel should be string!");
            }
            $this->restrictToAppKernel = $config['restrictToAppKernel'];
            unset($config['restrictToAppKernel']);
        }
        if (array_key_exists('restrictToResource', $config)) {
            if ($config['restrictToResource'] !== null && !is_string($config['restrictToResource'])) {
                throw new Exception("restrictToResource should be string!");
            }
            $this->restrictToResource = $config['restrictToResource'];
            unset($config['restrictToResource']);
        }
        if (array_key_exists('removeIngestedData', $config)) {
            if (!is_bool($config['removeIngestedData'])) {
                throw new Exception("removeIngestedData should be boolean!");
            }
            $this->removeIngestedDataVar = $config['removeIngestedData'];
            unset($config['removeIngestedData']);
        }
        if (array_key_exists('replace', $config)) {
            if (!is_bool($config['replace'])) {
                throw new Exception("replace should be boolean!");
            }
            $this->replace = $config['replace'];
            unset($config['replace']);
        }
        if (array_key_exists('recalculateControls', $config)) {
            if (!is_bool($config['recalculateControls'])) {
                throw new Exception("recalculateControls should be boolean!");
            }
            $this->recalculateControls = $config['recalculateControls'];
            unset($config['recalculateControls']);
        }
        /**
         * A time period to use for ingestion.
         *
         * @see $validTimeframes
         *
         * @var string|null
         */
        $sinceLastLoadTime = null;
        if (array_key_exists('sinceLastLoadTime', $config)) {
            $sinceLastLoadTime = $config['sinceLastLoadTime'];
            if ($sinceLastLoadTime !== null && !array_key_exists($sinceLastLoadTime,
                    AppKernelIngestor::$validTimeframes)) {
                throw new Exception("sinceLastLoadTime should be [hour,day,week,month,load]");
            }
            unset($config['sinceLastLoadTime']);
        }
        /**
         * Offset the ingesting starting date by "o" days.
         *
         * @var int
         */
        $offsetStartTimestampBy = 0;
        if (array_key_exists('offsetStartTimestampBy', $config)) {
            $offsetStartTimestampBy = intval($config['offsetStartTimestampBy']);
            unset($config['offsetStartTimestampBy']);
        }

        if (count($config) > 0) {
            throw new Exception("Unknown config options: " . implode(",", array_keys($config)));
        }

        // Create handles to both databases
        $this->db = new AppKernelDb($this->logger, 'appkernel');
        $this->ingestionLog = new IngestionLogEntry();

        // Determine the startTimestamp from sinceLastLoadTime the import
        if ($sinceLastLoadTime !== null) {
            $this->endTimestamp = time();

            if ("load" == $sinceLastLoadTime) {
                // Load the last ingestion time from the database.  The production database takes priority.
                $loaded = $this->db->loadMostRecentIngestionLogEntry($this->ingestionLog);
                if ($loaded === false) {
                    throw new Exception("AppKernelIngestor::__construct can not load loadMostRecentIngestionLogEntry");
                }
                $this->startTimestamp = $this->ingestionLog->end_time + 1;
            } else {
                $this->startTimestamp = $this->endTimestamp - AppKernelIngestor::$validTimeframes[$sinceLastLoadTime];
            }
        }

        if ($this->startTimestamp !== null && $this->endTimestamp === null) {
            $this->endTimestamp = time();
        }

        // offset the start time
        $this->startTimestamp -= $offsetStartTimestampBy;


        // Get enabled resources configured in the app kernel database

        // If we are restricting the resource do it now
        $options = array('inc_hidden' => true);
        if ($this->restrictToResource !== null) {
            $options['filter'] = $this->restrictToResource;
        }

        try {
            $this->dbResourceList = $this->db->loadResources($options);
            $this->logger->debug("Loaded database resources: " . json_encode(array_keys($this->dbResourceList)));
        } catch (\PDOException $e) {
            $msg = "Error querying database for resoures: " . formatPdoExceptionMessage($e);
            $this->logger->crit(array(
                'message' => $msg,
                'stacktrace' => $e->getTraceAsString(),
            ));
            $this->ingestionLog->setStatus(false, $msg);
            return false;
        }

        if (count($this->dbResourceList) == 0) {
            $msg = "No resources enabled in the database, skipping import.";
            $this->logger->warning($msg);
            $this->ingestionLog->setStatus(true, $msg);
            return false;
        }

        try {
            $this->dbAKList = $this->db->loadAppKernelDefinitions();
            $this->dbAKIdMap = $this->db->loadAppKernels(true, true);
            $this->logger->debug('DB AK List: ' . json_encode($this->dbAKList));
            $this->logger->debug('DB AK ID Map: ' . json_encode($this->dbAKIdMap));
        } catch (\PDOException $e) {
            $msg = "Error querying database for appkernels: " . formatPdoExceptionMessage($e);
            $this->logger->crit(array(
                'message' => $msg,
                'stacktrace' => $e->getTraceAsString(),
            ));
            $this->ingestionLog->setStatus(false, $msg);
            return false;
        }
    }

    public function __destruct()
    {
        if ($this->dryRunMode == false && $this->db !== null && $this->ingestionLog !== null) {
            if ($this->db instanceof \AppKernel\AppKernelDb) {
                if ($this->ingestionLog->source !== null) {
                    $this->db->storeIngestionLogEntry($this->ingestionLog);
                }
            }
        }
    }

    /**
     * Remove Ingested Data (For clean re-ingestion)
     */
    public function removeIngestedData()
    {
        $this->logger->notice('Removing old ingested data (if present) in new ingestion period');

        $akdb = $this->db->getDB();

        $sqlcondDatetime = "collected >= :startDateTime AND collected <= :endDateTime";
        $sqlcondTimestamp = "collected >= UNIX_TIMESTAMP(:startDateTime) AND collected <= UNIX_TIMESTAMP(:endDateTime)";

        $sqlcondDatetimeParam = [
            ':startDateTime' => date("Y-m-d H:i:s", $this->startTimestamp),
            ':endDateTime' => date("Y-m-d H:i:s", $this->endTimestamp)
        ];

        if ($this->restrictToResource !== null) {
            $sqlcondDatetime .= " AND resource_id = :resource_id";
            $sqlcondTimestamp .= " AND resource_id = :resource_id";
            $sqlcondDatetimeParam[':resource_id'] = $this->dbResourceList[$this->restrictToResource]->id;
        }

        $sqlAKDefcond = "";
        $sqlAKDefcondParam = [];
        $sqlAKcond = "";
        $sqlAKcondParam = [];
        $sqlNumUnitsCond = "";
        $sqlNumUnitsCondParam = [];

        if ($this->restrictToAppKernel !== null) {
            $sqlAKDefcond .= " AND ak_def_id = :ak_def_id";
            $sqlAKDefcondParam[':ak_def_id'] = $this->dbAKList[$this->restrictToAppKernel]->id;

            $response = $akdb->query("SELECT ak_id, num_units FROM mod_appkernel.app_kernel WHERE ak_def_id=?",
                [$this->dbAKList[$this->restrictToAppKernel]->id]);

            foreach ($response as $i => $v) {
                $sqlAKcondParam[':ak_id_' . $i] = $v['ak_id'];
                $sqlNumUnitsCondParam[':num_units_' . $i] = $v['ak_id'];
            }
            $sqlAKcond = " AND ak_id IN (" . implode(",", array_keys($sqlAKcondParam)) . ")";
            $sqlNumUnitsCond = " AND num_units IN (" . implode(",", array_keys($sqlNumUnitsCondParam)) . ")";
        }

        $sqlcond = $sqlcondDatetime . $sqlAKcond;
        $sqlcondParam = array_merge($sqlcondDatetimeParam, $sqlAKcondParam);

        $akdb->execute("DELETE FROM mod_appkernel.metric_data WHERE " . $sqlcond, $sqlcondParam);
        $akdb->execute("DELETE FROM mod_appkernel.parameter_data WHERE " . $sqlcond, $sqlcondParam);
        $akdb->execute("DELETE FROM mod_appkernel.ak_instance_debug WHERE " . $sqlcond, $sqlcondParam);
        $akdb->execute("DELETE FROM mod_appkernel.ak_instance WHERE " . $sqlcond, $sqlcondParam);

        $sqlcond = $sqlcondTimestamp . $sqlAKDefcond . $sqlNumUnitsCond;
        $sqlcondParam = array_merge($sqlcondDatetimeParam, $sqlAKDefcondParam, $sqlNumUnitsCondParam);

        $akdb->execute("DELETE FROM mod_appkernel.a_data WHERE " . $sqlcond, $sqlcondParam);
        $akdb->execute("DELETE FROM mod_appkernel.a_data2 WHERE " . $sqlcond, $sqlcondParam);
    }

    /**
     * Ingest AppKernel Instances executed on $resourceNickname and time period.
     *
     * @param string $resourceNickname
     * @return bool true on success
     */
    public function ingestAppKernelInstances($resourceNickname)
    {
        // Use a single AppKernelData object and
        // re-initialize it for each app kernel instance.  This'll keep us lean
        // and mean.

        $parsedInstanceData = new InstanceData();

        $this->logger->info("Start resource: $resourceNickname");

        $resourceReport = array();
        $resource_id = $this->dbResourceList[$resourceNickname]->id;
        $resource_name = $this->dbResourceList[$resourceNickname]->name;
        $resource_nickname = $this->dbResourceList[$resourceNickname]->nickname;
        $resource_visible = $this->dbResourceList[$resourceNickname]->visible;

        $options = array('resource' => $resourceNickname);

        if ($this->restrictToAppKernel !== null) {
            $options['app_kernel'] = $this->restrictToAppKernel;
        }

        $instanceList = array();

        try {
            $instanceListGroupedByAK = $this->deploymentExplorer->getAvailableInstances($options, true);
            $numInstances = 0;

            foreach ($instanceListGroupedByAK as $ak_basename => $instanceListGroupedByNumUnits) {
                foreach ($instanceListGroupedByNumUnits as $num_units => $instanceList) {
                    $numInstances += count($instanceList);
                }
            }

            $msg = "Resource: $resourceNickname found $numInstances instances";
            if ($numInstances == 0) {
                $this->logger->warning($msg . " from " . date("Y-m-d H:i:s", $this->startTimestamp) . " to " .
                    date("Y-m-d H:i:s", $this->endTimestamp));
            } else {
                $this->logger->info($msg);
            }
        } catch (Exception $e) {
            $msg = "Error retrieving app kernel instances: " . $e->getMessage();
            $this->logger->crit(array(
                'message' => $msg,
                'stacktrace' => $e->getTraceAsString(),
            ));
            return false;
        }

        foreach ($instanceListGroupedByAK as $ak_basename => $instanceListGroupedByNumUnits) {
            $this->logger->debug("Current AK: $ak_basename");

            if (!isset($dbAKList[$ak_basename])) {
                $this->logger->warning("$ak_basename not in AK list");
                #continue;
            }

            $ak_def_id = $this->dbAKList[$ak_basename]->id;
            $ak_name = $this->dbAKList[$ak_basename]->name;
            $processor_unit = $this->dbAKList[$ak_basename]->processor_unit;
            $ak_def_visible = $this->dbAKList[$ak_basename]->visible;

            foreach ($instanceListGroupedByNumUnits as $num_units => $instanceList) {
                if (!isset($dbAKIdMap[$ak_basename])) {
                    $this->logger->warning("$ak_basename not in AK id map");
                    #continue;
                }

                $ak_id = $this->dbAKIdMap[$ak_basename][$num_units];

                foreach ($instanceList as $instanceId => $akInstance) {
                    $this->logger->info("Processing $akInstance");

                    if (!isset($resourceReport[$akInstance->akNickname])) {
                        $resourceReport[$akInstance->akNickname] = array(
                            'examined' => 0,
                            'loaded' => 0,
                            'incomplete' => 0,
                            'parse_error' => 0,
                            'queued' => 0,
                            'error' => 0,
                            'sql_error' => 0,
                            'unknown_type' => 0,
                            'duplicate' => 0,
                            'exception' => 0,
                        );
                    }

                    $resourceReport[$akInstance->akNickname]['examined']++;
                    $this->appKernelSummaryReport['examined']++;

                    try {
                        try {
                            // The parser should throw 4 types of exception: general, inca error, queued job, invalid xml
                            $success = $this->parser->parse($akInstance, $parsedInstanceData);

                            // Set some data that will be needed for adding metrics and parameters
                            // TODO These should be preset during InstanceData &$ak query
                            $parsedInstanceData->db_resource_id = $resource_id;
                            $parsedInstanceData->db_resource_name = $resource_name;
                            $parsedInstanceData->db_resource_nickname = $resource_nickname;
                            $parsedInstanceData->db_resource_visible = $resource_visible;
                            $parsedInstanceData->db_proc_unit_type = $processor_unit;
                            $parsedInstanceData->db_ak_id = $ak_id;
                            $parsedInstanceData->db_ak_def_id = $ak_def_id;
                            $parsedInstanceData->db_ak_def_name = $ak_name;
                            $parsedInstanceData->db_ak_def_visible = $ak_def_visible;
                        } catch (AppKernel\AppKernelException $e) {
                            $msg = $e->getMessage();

                            // Handle errors during parsing.  In most cases log the error, increment
                            // a counter, and skip the saving of the data.

                            switch ($e->getCode()) {
                                case AppKernel\AppKernelException::ParseError:
                                    $resourceReport[$akInstance->akNickname]['parse_error']++;
                                    $this->appKernelSummaryReport['parse_error']++;
                                    $this->logger->err("Parse error: '$msg'");
                                    $this->logger->debug("Raw instance data:\n{$akInstance->data}\n");
                                    continue;
                                    break;
                                case AppKernel\AppKernelException::Queued:
                                    $resourceReport[$akInstance->akNickname]['queued']++;
                                    $this->appKernelSummaryReport['queued']++;
                                    $this->logger->notice("Queued: '$msg'");
                                    continue;
                                    break;
                                case AppKernel\AppKernelException::Error:
                                    $resourceReport[$akInstance->akNickname]['error']++;
                                    $this->appKernelSummaryReport['error']++;
                                    $this->logger->err("Error: '$msg'");
                                    continue;
                                    break;
                                case AppKernel\AppKernelException::UnknownType:
                                    $resourceReport[$akInstance->akNickname]['unknown_type']++;
                                    $this->appKernelSummaryReport['unknown_type']++;
                                    $this->logger->warning("Unknown Type: '$msg'");
                                    continue;
                                    break;
                                default:
                                    $this->logger->err(array(
                                        'message' => "AppKernelException: '$msg'",
                                        'stacktrace' => $e->getTraceAsString(),
                                    ));
                                    continue;
                                    break;
                            }
                        }

                        $this->logger->debug(print_r($parsedInstanceData, 1));

                        // Store data in the appropriate databases.  Need a
                        // better way to handle errors here so we know what
                        // actually happened.  Use AppKernelException!!
                        try {
                            $add_to_a_data = true;
                            $calc_controls = true;

                            if (array_key_exists($resourceNickname, $this->dbResourceList)) {
                                $stored = $this->db->storeAppKernelInstance(
                                    $parsedInstanceData,
                                    $this->replace,
                                    $add_to_a_data,
                                    $calc_controls,
                                    $this->dryRunMode
                                );
                            }
                        } catch (AppKernel\AppKernelException $e) {
                            switch ($e->getCode()) {
                                case AppKernel\AppKernelException::DuplicateInstance:
                                    $resourceReport[$akInstance->akNickname]['duplicate']++;
                                    $this->appKernelSummaryReport['duplicate']++;
                                    $this->logger->warning($e->getMessage());
                                    break;
                                default:
                                    $this->logger->warning($e->getMessage());
                                    break;
                            }
                            continue;
                        }

                        if (!$akInstance->completed) {
                            $this->logger->err("$akInstance did not complete. message: '{$akInstance->message}'");
                            $this->logger->debug("$akInstance did not complete. message: '{$akInstance->message}', stderr: '{$akInstance->stderr}'");
                            $resourceReport[$akInstance->akNickname]['incomplete']++;
                            $this->appKernelSummaryReport['incomplete']++;
                        } elseif (false !== $stored) {
                            $resourceReport[$akInstance->akNickname]['loaded']++;
                            $this->appKernelSummaryReport['loaded']++;
                            $this->appKernelCounter++;
                        }
                    } catch (\PDOException $e) {
                        $msg = formatPdoExceptionMessage($e);
                        $this->logger->err(array(
                            'message' => $msg,
                            'stacktrace' => $e->getTraceAsString(),
                        ));
                        $resourceReport[$akInstance->akNickname]['sql_error']++;
                        $this->appKernelSummaryReport['sql_error']++;
                        continue;
                    } catch (Exception $e) {
                        $this->logger->warning("Error: {$e->getMessage()} ({$e->getCode()}), skipping.");
                        $resourceReport[$akInstance->akNickname]['exception']++;
                        $this->appKernelSummaryReport['exception']++;
                        continue;
                    }
                }
            }
        }

        $appKernelReport[$resourceNickname] = $resourceReport;

        $this->logger->info("End resource: $resourceNickname");

        unset($parsedInstanceData);
        return true;
    }

    /**
     * Format the summary report.
     *
     * @param array $reportData
     *
     * @return string
     */
    public function format_summary_report(array $reportData)
    {
        $reportString = "";

        foreach ($reportData as $resourceNickname => $resourceData) {
            $reportString .= "Resource nickname: $resourceNickname\n";

            foreach ($resourceData as $appKernelName => $akData) {
                $reportString .= "  App Kernel: $appKernelName\n";
                $tmp = array();

                foreach ($akData as $key => $value) {
                    $tmp[] = "$key = $value";
                }

                $reportString .= "    " . implode(", ", $tmp) . "\n";
            }
        }

        return $reportString;
    }

    /**
     * @return bool true on success
     */
    public function run()
    {
        // NOTE: "process_start_time" is needed for the log summary.
        $this->logger->notice(array(
            'message' => 'Ingestion start',
            'process_start_time' => date('Y-m-d H:i:s'),
        ));

        if ($this->dryRunMode) {
            $this->logger->info("OPTION: Running in dryrun mode - discarding database updates");
        }

        if ($this->restrictToResource !== null) {
            $this->logger->info("OPTION: Load only resource (nickname) '$this->restrictToResource'");
        }

        if ($this->restrictToAppKernel !== null) {
            $this->logger->info("OPTION: Load only app kernels starting with '$this->restrictToAppKernel'");
        }

        $this->logger->notice(array(
            'message' => 'Ingestion data time period',
            'data_start_time' => date("Y-m-d H:i:s", $this->startTimestamp),
            'data_end_time' => date("Y-m-d H:i:s", $this->endTimestamp),
        ));

        // Reset and initialize the ingestion logs
        $this->ingestionLog->reset();
        $this->ingestionLog->source = $this->explorerType;
        $this->ingestionLog->url = null;
        $this->ingestionLog->last_update = time();
        $this->ingestionLog->start_time = $this->startTimestamp;
        $this->ingestionLog->end_time = $this->endTimestamp;

        // Instantiate the Explorer
        try {
            $config = array(
                'config_appkernel' => 'appkernel',
                'config_akrr' => 'akrr-db',
                'add_supremm_metrix' => false,
            );
            $this->deploymentExplorer = AppKernel::explorer($this->explorerType, $config, $this->logger);
            $this->deploymentExplorer->setQueryInterval($this->startTimestamp, $this->endTimestamp);
        } catch (Exception $e) {
            $msg = "Error creating explorer ($this->explorerType): " . $e->getMessage();
            $this->logger->crit(array(
                'message' => $msg,
                'stacktrace' => $e->getTraceAsString(),
            ));
            $this->ingestionLog->setStatus(false, $msg);
            return false;
        }

        // Instantiate the Parser
        try {
            $this->parser = AppKernel::parser($this->explorerType, null, $this->logger);
        } catch (Exception $e) {
            $msg = "Error creating parser ($this->explorerType): " . $e->getMessage();
            $this->logger->crit(array(
                'message' => $msg,
                'stacktrace' => $e->getTraceAsString(),
            ));
            $this->ingestionLog->setStatus(false, $msg);
            return false;
        }

        $resourceNicknames = array_keys($this->dbResourceList);

        // remove old ingested data
        if ($this->removeIngestedDataVar === true) {
            $this->removeIngestedData();
        }

        // Iterate over each resource and query for all app kernels for that
        // resource and time period.
        $this->logger->info("Start processing application kernel data");

        if ($this->restrictToAppKernel !== null) {
            $this->logger->info("Restrict to app kernels containing string '$this->restrictToAppKernel'");
        }

        foreach ($resourceNicknames as $resourceNickname) {
            $status = $this->ingestAppKernelInstances($resourceNickname);
            if ($status == false) {
                return false;
            }
        }

        $this->logger->info("Loaded $this->appKernelCounter application kernels");

        if (!$this->dryRunMode) {
            try {
                $this->logger->info("Caculate running average and control values");
                $this->db->calculateControls($this->recalculateControls, $this->recalculateControls, 20, 5,
                    $this->restrictToResource, $this->restrictToAppKernel);
            } catch (\PDOException $e) {
                $msg = formatPdoExceptionMessage($e);
                $this->logger->err(array(
                    'message' => $msg,
                    'stacktrace' => $e->getTraceAsString(),
                ));
            } catch (Exception $e) {
                $this->logger->err(array(
                    'message' => "Error: {$e->getMessage()} ({$e->getCode()})",
                    'stacktrace' => $e->getTraceAsString(),
                ));
            }
        }

        $summaryReport
            = "Summary report for " . date("Y-m-d H:i:s", $this->startTimestamp)
            . " to " . date("Y-m-d H:i:s", $this->endTimestamp) . "\n"
            . "examined = {$this->appKernelSummaryReport['examined']}, "
            . "loaded = {$this->appKernelSummaryReport['loaded']}, "
            . "incomplete = {$this->appKernelSummaryReport['incomplete']}, "
            . "parse error = {$this->appKernelSummaryReport['parse_error']}, "
            . "queued = {$this->appKernelSummaryReport['queued']}, "
            . "unknown type = {$this->appKernelSummaryReport['unknown_type']}, "
            . "sql_error = {$this->appKernelSummaryReport['sql_error']}, "
            . "error = {$this->appKernelSummaryReport['error']}, "
            . "duplicate = {$this->appKernelSummaryReport['duplicate']}, "
            . "exception = {$this->appKernelSummaryReport['exception']}\n";
        //. $this->format_summary_report($this->appKernelReport);

        $this->logger->info($summaryReport);

        // NOTE: This is needed for the log summary.
        $this->logger->notice(array(
            'message' => 'Summary data',
            'records_examined' => $this->appKernelSummaryReport['examined'],
            'records_loaded' => $this->appKernelSummaryReport['loaded'],
            'records_incomplete' => $this->appKernelSummaryReport['incomplete'],
            'records_parse_error' => $this->appKernelSummaryReport['parse_error'],
            'records_queued' => $this->appKernelSummaryReport['queued'],
            'records_unknown_type' => $this->appKernelSummaryReport['unknown_type'],
            'records_sql_error' => $this->appKernelSummaryReport['sql_error'],
            'records_error' => $this->appKernelSummaryReport['error'],
            'records_duplicate' => $this->appKernelSummaryReport['duplicate'],
            'records_exception' => $this->appKernelSummaryReport['exception']
        ));

        // Test sending errors and queued info

        $this->ingestionLog->num = $this->appKernelCounter;

        if ($this->appKernelSummaryReport['sql_error'] == 0) {
            $this->ingestionLog->setStatus(true, serialize($this->appKernelSummaryReport));
        } else {
            $this->ingestionLog->setStatus(false, "SQL errors present");
        }

        //$this->ingestionLog->reportObj = serialize($this->appKernelReport);

        // NOTE: "process_end_time" is needed for the log summary.
        $this->logger->notice(array(
            'message' => 'Ingestion End',
            'process_end_time' => date('Y-m-d H:i:s'),
        ));

        return true;
    }
}
