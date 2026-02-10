<?php

namespace CCR\AppKernel;

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
