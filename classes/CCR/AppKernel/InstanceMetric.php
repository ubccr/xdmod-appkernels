<?php

namespace CCR\AppKernel;

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

    public static function sort_cmp(InstanceMetric $a, InstanceMetric $b)
    {
        return strcmp($a->name, $b->name);
    }

    // --------------------------------------------------------------------------------

    public function __construct($name, $value, $unit = null, $id = null)
    {
        if($id!==null) {
            $this->id = intval($id);
        }
        $this->name = $name;
        $this->value = $value;
        $this->unit = $unit;
    }

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
