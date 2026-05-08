<?php

namespace CCR\AppKernel;

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

    public function __construct(
        $id = null,
        $name = null,
        $basename = null,
        $description = null,
        $processor_unit = null,
        $enabled = false,
        $visible = false,
        $start_ts = null,
        $end_ts = null
    ) {
        $this->id = intval($id);
        $this->name = $name;
        $this->basename = $basename;
        $this->description = $description;
        $this->processor_unit = $processor_unit;
        $this->enabled = (1 == $enabled ? true : false);
        $this->visible = (1 == $visible ? true : false);
        $this->start_ts = intval($start_ts);
        $this->end_ts = intval($end_ts);
    }
}
