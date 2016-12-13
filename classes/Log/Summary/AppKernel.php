<?php
/**
 * Log summary class for application kernels. Extends the Summary class to define $recordCountKeys
 * specific to the AppKernel logger.
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 */

namespace Log\Summary;
use Log\Summary;

class AppKernel
extends Summary
{
    /**
     * The array keys used by the logger to indicate record counts.
     *
     * @var array
     */

  protected $recordCountKeys = array(
        'records_examined',
        'records_loaded',
        'records_incomplete',
        'records_parse_error',
        'records_queued',
        'records_unknown_type',
        'records_sql_error',
        'records_error',
        'records_duplicate',
        'records_exception'
    );

}  // class AppKernel
