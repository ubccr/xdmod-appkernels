<?php
// ================================================================================
// Data structures for describing application kernel information including
// definitions, resources, runtime metadata, instance data, metrics, and
// parameters.
// ================================================================================

namespace AppKernel;

// ================================================================================
// Application kernel data.  Data is classified into three categories and named
// based on where it was originally collected.
// - Taken from the app kernel database (prefixed with "db_")
// - Returned by the deployment infrastructure (e.g., Inca) (prefixed with "deployment_")
// - Returned by the app kernel itself (prefixed with "ak_")
// ================================================================================

class InstanceData
{
    const STATUS_SUCCESS = 'success';
    const STATUS_FAILURE = 'failure';
    const STATUS_ERROR = 'error';
    const STATUS_QUEUED = 'queued';
    const STATUS_UNKNOWN = null;

    // Status of the job (complete, error, queued)
    public $status = null;

    // --------------------------------------------------------------------------------
    // Information from the app kernel database.  Prefixed with "db_"

    // Application kernel id (takes into account the number of proc units)
    public $db_ak_id = null;

    // Database id for the application kernel definition
    public $db_ak_def_id = null;

    // Name of the app kernel in the database definition
    public $db_ak_def_name = null;

    // app kernel visability in the database definition
    public $db_ak_def_visible = null;

    // Database id for the resource
    public $db_resource_id = null;

    // Name of resource in the database definition
    public $db_resource_name = null;

    // Nickname of resource in the database definition
    public $db_resource_nickname = null;

    // resource visability in the database definition
    public $db_resource_visible = null;

    // Type of processing unit specified in the app kernel definition (e.g., node
    // or core)
    public $db_proc_unit_type = null;

    // --------------------------------------------------------------------------------
    // Information taken from the deployment infrastructure (e.g., Inca or ARR) Prefixed
    // with "deployment_"

    // Instance identifier from the ak deployment infrastructure (inca)
    public $deployment_instance_id = null;

    // Job identifier from the resource manager
    public $deployment_job_id = null;

    // The name of the app kernel including the number of processing units
    public $deployment_ak_name = null;

    // The name of the app kernel without number of processing units (taken from the database)
    public $deployment_ak_base_name = null;

    // The number of processing units used by this app kernel
    public $deployment_num_proc_units = null;

    // The resource that the app kernel was run on
    public $deployment_hostname = null;

    // The optional cluster node that the app kernel was run on
    public $deployment_execution_hostname = null;

    // Execution time (unix timestamp)
    public $deployment_time = null;

    // Error message returned by the deployment infrastructure
    public $deployment_message = null;

    // Stderr returned by the deployment infrastructure
    public $deployment_stderr = null;

    // Wall clock time spent running the app kernel
    public $deployment_walltime = null;

    // CPU time spend running the app kernel
    public $deployment_cputime = null;

    // Memory consumed by the app kernel
    public $deployment_memory = null;

    // A aggregate value computed by examining environmental data returned by the
    // app kernel such as library versions, application signature, etc.
    public $environmentVersion = null;

    // --------------------------------------------------------------------------------
    // Information returned by the app kernel.  Prefixed with "ak_"

    // Error cause as reported by the app kernel itself
    public $ak_error_cause = null;

    // Error message as reported by the app kernel itself
    public $ak_error_message = null;

    // Time waiting in the queue (in seconds)
    public $ak_queue_time = null;

    // List of parameters provided to the app kernel (InstanceParameter objects)
    public $ak_parameters = array();

    // List of metrics returned by the app kernel (InstanceMetric objects)
    public $ak_metrics = array();

    // --------------------------------------------------------------------------------

    public function __construct()
    {
        $this->reset();
    }

    // --------------------------------------------------------------------------------
    // Return a string representation of the app kernel instance
    //
    // @returns A string representation of the app kernel instance
    // --------------------------------------------------------------------------------

    public function __toString()
    {
        return "(#{$this->deployment_instance_id} {$this->deployment_hostname}:{$this->deployment_ak_base_name}.{$this->deployment_num_proc_units} @" . date("Y-m-d H:i:s", $this->deployment_time) . ")";
    }

    // --------------------------------------------------------------------------------
    // Return a string representation of the app kernel instance
    //
    // @returns A string representation of the app kernel instance
    // --------------------------------------------------------------------------------

    public function toHtml()
    {
        $s = array();
        $s[] = "<table border=0>";
        $s[] = "<tr><td>AK Name:</td><td>{$this->db_ak_def_name}</td></tr>";
        $s[] = "<tr><td>Instance Name:</td><td>{$this->deployment_ak_name}</td></tr>";
        $s[] = "<tr><td>Processing Units:</td><td>{$this->deployment_num_proc_units} {$this->db_proc_unit_type}</td></tr>";
        $s[] = "<tr><td>Date Collected:</td><td>" . date("Y-m-d H:i:s", $this->deployment_time) . "</td></tr>";
        $s[] = "<tr><td>Resource:</td><td>{$this->deployment_hostname}</td></tr>";
        $s[] = "<tr><td>Status:</td><td>{$this->status}</td></tr>";
        $s[] = "<tr><td>Deployment Instance Id:</td><td>{$this->deployment_instance_id}</td></tr>";
        $s[] = "<tr><td>Deployment Job Id:</td><td>{$this->deployment_job_id}</td></tr>";
        $s[] = "<tr><td>Env Version:</td><td>" .
            (null !== $this->environmentVersion ? $this->environmentVersion : $this->environmentVersion()) .
            "</td></tr>";
        $s[] = "</table>";

        if (count($this->ak_metrics) > 0) {
            $s[] = "<br><br><table border=1>";
            $s[] = "<tr><th>Metric</th><th>Value</th><th>Unit</th></tr>";
            foreach ($this->ak_metrics as $data) {
                $name = $data->name;
                $value = $data->value;
                $unit = ($data->unit ? $data->unit : "&nbsp");
                $s[] = "<tr><td>$name</td><td>$value</td><td>$unit</td></tr>";
            }
            $s[] = "</table>";
        } else {
            $s[] = "<br><br> No Metrics";
        }

        if (count($this->ak_parameters) > 0) {
            $s[] = "<br><br><table border=1>";
            $s[] = "<tr><th>Parameter</th><th>Value</th><th>Unit</th></tr>";
            foreach ($this->ak_parameters as $data) {
                $name = $data->name;
                $value = $data->value;
                $unit = ($data->unit ? $data->unit : "&nbsp");
                $s[] = "<tr><td>$name</td><td>" . ("RunEnv" == $data->tag || "App" == $data->tag ? "<pre>$value</pre>" : $value) . "</td><td>$unit</td></tr>";
            }
            $s[] = "</table>";
        } else {
            $s[] = "<br><br> No Parameters";
        }

        $s[] = "<br><br><table border=1>";
        $s[] = "<tr><td colspan=2 align='center'><b>Deployment (Inca) Data</b></td></tr>";
        $s[] = "<tr><td>Message</td><td>" . (null !== $this->deployment_message ? "<pre>{$this->deployment_message}</pre>" : "&nbsp") . "</td></tr>";
        $s[] = "<tr><td>Stderr</td><td>" . (null !== $this->deployment_stderr ? "<pre>{$this->deployment_stderr}</pre>" : "&nbsp") . "</td></tr>";
        $s[] = "<tr><td>Walltime</td><td>{$this->deployment_walltime}</td></tr>";
        $s[] = "<tr><td>Cputime</td><td>{$this->deployment_cputime}</td></tr>";
        $s[] = "<tr><td>Memory</td><td>{$this->deployment_memory}</td></tr>";
        $s[] = "<tr><td colspan=2 align='center'><b>App Kernel Data</b></td></tr>";
        $s[] = "<tr><td>Error Cause</td><td>" . (null !== $this->ak_error_cause ? $this->ak_error_cause : "&nbsp") . "</td></tr>";
        $s[] = "<tr><td>Error Message</td><td>" . (null !== $this->ak_error_message ? "<pre>{$this->ak_error_message}</pre>" : "&nbsp") . "</td></tr>";
        $s[] = "<tr><td>Queue Time</td><td>" . (null !== $this->ak_queue_time ? $this->ak_queue_time : "&nbsp") . "</td></tr>";
        $s[] = "</table>";

        return implode("\n", $s);
    }

    // --------------------------------------------------------------------------------
    // Construct a version identifier based on the current inputs and environment
    // of the app kernel.  Currently, this is an md5 taken on the set of
    // normalized parameters.  Normalization includes removing parameters tagged
    // with RunEnv, sorting the parameters on their name, sorting the parameter
    // value, and removing all spaces.
    //
    // NOTE: As per Charng-Da only use App:ExeBinSignature when computing the
    // environment version.  This leaves out all other input parameters until we
    // can consistently tag parameters and decide what to do.  SMG 2012-09-27

    // If no parameters have been provided then the environment version will be
    // null.  This may happen if there was an error executing the instance.
    //
    // @returns A version identifier based on the current inputs and environment
    //   of the app kernel.
    // --------------------------------------------------------------------------------

    public function environmentVersion()
    {

        if (0 == count($this->ak_parameters)) return null;
        $versionStr = "";

        // For the time being only use App:ExeBinSignature to calculate the
        // environment version.  There are many parameters that do not include a
        // tag.  SMG 2012-09-27

        foreach ($this->ak_parameters as $parameter) {
            if (!(0 === strpos($parameter->tag, "App") &&
                0 === strpos($parameter->name, "ExeBinSignature"))) continue;

            // Sort the value of the parameter in case the order of the data returned
            // changes over time.

            $valueList = preg_split('/[\n\r]+/', $parameter->value);
            sort($valueList);
            $valueList = array_unique($valueList);

            $paramStr = "";
            foreach ($valueList as $v) {
                if (substr($v, 0, 4) == 'MD5:') {
                    $paramStr .= $v;
                }
            }

            $paramStr = $parameter->name . $paramStr . $parameter->unit;

            // Remove all spaces from the version string to account for random extra spaces
            $versionStr .= strtolower(str_replace(" ", "", $paramStr));
            break;
        }

        $this->environmentVersion = md5($versionStr);
        return $this->environmentVersion;

    }

    // --------------------------------------------------------------------------------
    // Reset the data in the class.  This allows us to reuse the object.
    // --------------------------------------------------------------------------------

    public function reset()
    {
        $this->db_ak_id = null;
        $this->db_ak_def_id = null;
        $this->db_ak_def_name = null;
        $this->db_ak_def_visible = null;
        $this->db_resource_id = null;
        $this->db_resource_name = null;
        $this->db_resource_nickname = null;
        $this->db_resource_visible = null;
        $this->db_proc_unit_type = null;
        $this->deployment_instance_id = null;
        $this->deployment_job_id = null;
        $this->deployment_ak_name = null;
        $this->deployment_ak_base_name = null;
        $this->deployment_num_proc_units = null;
        $this->deployment_hostname = null;
        $this->deployment_execution_hostname = null;
        $this->deployment_time = null;
        $this->deployment_stderr = null;
        $this->deployment_message = null;
        $this->deployment_walltime = null;
        $this->deployment_cputime = null;
        $this->deployment_memory = null;

        $this->ak_error_cause = null;
        $this->ak_error_message = null;
        $this->ak_queue_time = null;
        $this->ak_parameters = array();
        $this->ak_metrics = array();

        $this->status = false;
        $this->environmentVersion = null;
    }

    // --------------------------------------------------------------------------------
    // Some parameter and/or metric values are gzipped and base64-encoded to save
    // space.  Decode and unzip these fields and return the result, or false if
    // the string was not base64 encoded.  Note that a string can be made up of
    // characters from the base64 alphabet but not actually base64 encoded.
    //
    // @param $str Reference to the string to decode.  This will be replaced with
    //   the decoded and unzipped string on success
    //
    // @returns true on success or false if the string was not encoded
    // --------------------------------------------------------------------------------

    public static function decode(&$str)
    {

        $decodedStr = null;
        $unzippedStr = "";

        // If the string was not base64 encoded bail out now.  Note that a string
        // made up of characters from the base64 alphabet will return true but
        // maynot acutally encoded.

        if (false === ($decodedStr = base64_decode($str, true))) return false;

        // Test to see if the string has a mime type of "application/x-gzip"

        $f = finfo_open(FILEINFO_MIME_TYPE);
        $mt = finfo_buffer($f, $decodedStr);
        if ($mt != "application/x-gzip") return false;

        // I had problems unzipping the decoded string directly but writing it to a
        // temporary file seems to work.  -smg 20110608

        $tmpFile = tempnam(sys_get_temp_dir(), "akexplorer_");
        @file_put_contents($tmpFile, $decodedStr);
        $fp = gzopen($tmpFile, "r");
        while (!gzeof($fp))
            $unzippedStr .= gzread($fp, 1024);
        gzclose($fp);
        unlink($tmpFile);

        $str = $unzippedStr;
        return true;
    }
}

/**
 * Class InstanceMetric Application kernel metric/statistics
 * @package AppKernel
 */
class InstanceMetric
{
    /**
     * @var int|null Database id of the metric.
     */
    public $id = null;

    // Metric data parsed from the app kernel
    /**
     * @var string|null Metrics name
     */
    public $name = null;

    /**
     * @var string|null  Metrics value
     */
    public $value = null;

    /**
     * @var string|null  Metrics units
     */
    public $unit = null;

    // --------------------------------------------------------------------------------

    static function sort_cmp(InstanceMetric $a, InstanceMetric $b)
    {
        return strcmp($a->name, $b->name);
    }

    // --------------------------------------------------------------------------------

    public function __construct($name, $value, $unit = null, $id=null)
    {
        if($id!==null) {
            $this->id = intval($id);
        }
        $this->name = $name;
        $this->value = $value;
        $this->unit = $unit;
    }  // __construct()

    // --------------------------------------------------------------------------------

    public function __toString()
    {
        return __CLASS__ . ": ({$this->name}, {$this->value}, {$this->unit})";
    }

    // --------------------------------------------------------------------------------
    // Generate the metric guid to uniquely identify this metric.
    //
    // @returns A unique identifier for the metric
    // --------------------------------------------------------------------------------

    public function guid()
    {
        return md5($this->name . $this->unit);
    }
}  // class InstanceMetric

// ================================================================================
// Application knernel parameters (currently the same info as the metrics).
// ================================================================================

class InstanceParameter extends InstanceMetric
{
    // Optional grouping tag supplied with the parameter (e.g., App in App:ExeBinSignature)
    public $tag = null;

    // --------------------------------------------------------------------------------

    public function __construct($name, $value, $unit = null, $tag = null)
    {

        // If the tag was explicitly provided us it, otherwise check to see if it is
        // still in the name.

        if (null !== $tag) {
            $this->name = $name;
            $this->tag = $tag;
        } else if (false !== strpos($name, ":")) {
            $parts = explode(":", $name);
            $this->tag = array_shift($parts);
            $this->name = implode(":", $parts);
        } else {
            $this->name = $name;
        }

        InstanceData::decode($value);
        $this->value = $value;
        $this->unit = $unit;
    }  // __construct()

    // --------------------------------------------------------------------------------
    // Generate the metric guid to uniquely identify this metric.
    //
    // @returns A unique identifier for the metric
    // --------------------------------------------------------------------------------

    public function guid()
    {
        return md5((null === $this->tag ? "" : $this->tag) . $this->name . $this->unit);
    }

}  // class InstanceParameter


/**
 * Class AppKernelDefinition Application kernel definition
 * @package AppKernel
 */
class AppKernelDefinition
{
    /**
     * @var int|null Database id
     */
    public $id = null;

    /**
     * @var string|null Public name
     */
    public $name = null;

    /**
     * @var string|null  Reporter basename
     */
    public $basename = null;

    /**
     * @var string|null App kernel description (html)
     */
    public $description = null;

    /**
     * @var string|null Processor_units node or core
     */
    public $processor_unit = null;

    /**
     * @var bool true if enabled
     */
    public $enabled = false;

    /**
     * @var bool  true if visible outside of the application kernel tools
     */
    public $visible = false;

    /**
     * @var int|null start time (can be limited to conditions like resource)
     */
    public $start_ts = null;

    /**
     * @var int|null end time (can be limited to conditions like resource)
     */
    public $end_ts = null;

    public function __construct($id = null, $name = null, $basename = null, $description = null,
                                $processor_unit = null, $enabled = false, $visible = false,
                                $start_ts = null, $end_ts = null)
    {
        $this->id = intval($id);
        $this->name = $name;
        $this->basename = $basename;
        $this->description = $description;
        $this->processor_unit = $processor_unit;
        $this->enabled = ( 1 == $enabled ? true : false );
        $this->visible = ( 1 == $visible ? true : false );
        $this->start_ts = intval($start_ts);
        $this->end_ts = intval($end_ts);
    }
}

// ================================================================================
// Resource that application kernels are run on
// ================================================================================

class AKResource
{
    public $id = null;  // Database id
    public $nickname = null;  // Resource nickname (e.g., short name such as "edge")
    public $name = null;  // Resource full name (e.g., edge.ccr.buffalo.edu)
    public $description = null;  // Resource description
    public $enabled = false;  // true if the resoure is enabled
    public $visible = false;  // true if visible outside of the application kernel tools
}

class ProcessingUnit
{
    /**
     * @var string|null
     */
    public $unit = null;
    /**
     * @var int|null
     */
    public $count = null;

    public function __construct($unit = null, $count = null)
    {
        $this->unit = $unit;
        $this->count = intval($count);
    }
}
