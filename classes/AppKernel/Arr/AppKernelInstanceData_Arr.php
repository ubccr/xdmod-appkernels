<?php
namespace AppKernel;

// ================================================================================
// Extend the AppKernelInstanceData class to include information specific to
// this Explorer.
//
// @see iAppKernelExplorer::AppKernelInstanceData
// ================================================================================

class AppKernelInstanceData_Arr extends AppKernelInstanceData
{
  // Instance identifier from the ak deployment infrastructure
  public $instance_id = NULL;

  // Resource manager job identifier from the ak deployment infrastructure
  public $job_id = NULL;

  // The resource that the app kernel was run on
  public $hostname = NULL;

  // The app kernel name (without number of processing units)
  public $akName = NULL;

  // The app kernel nickname (including the number of processing units)
  public $akNickname = NULL;

  // The optional cluster node that the app kernel was run on
  public $execution_hostname = NULL;

  // Time that the app kernel was collected
  public $time = NULL;

  // TRUE if the app kernel completed correctly (according to Inca)
  public $completed = NULL;

  // Optional message reuturned
  public $message = NULL;

  // Optional standard error returned
  public $stderr = NULL;

  // Wall clock time spent running the app kernel
  public $walltime = NULL;

  // CPU time spend running the app kernel
  public $cputime = NULL;

  // Memory consumed by the app kernel
  public $memory = NULL;

 // --------------------------------------------------------------------------------
  // Generate a representation of this object
  // --------------------------------------------------------------------------------

  public function __toString()
  {
    return "(#{$this->instance_id} {$this->hostname}:{$this->akNickname} @{$this->time})";
  }  // __toString()

}  // class AppKernelInstanceData_Arr
