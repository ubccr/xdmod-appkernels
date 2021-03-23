<?php
// --------------------------------------------------------------------------------
// Define an interface for exploring application kernels from various sources
// such as Inca, Ganglia, etc.
// --------------------------------------------------------------------------------

namespace AppKernel;

use Psr\Log\LoggerInterface;

require_once("AppKernelInstanceData.php");

interface iAppKernelExplorer
{
  // -------------------------------------------------------------------------
  // Create an instance of an Explorer for the specified source.
  //
  // @param $config An associative array containing source-specific
  //   configuration information
  // @param $log An optional PEAR::Log object for logging
  //
  // @returns An instance of the Explorer written for the specified source.
  // -------------------------------------------------------------------------

  public static function factory(array $config, LoggerInterface $logger = NULL);

  // -------------------------------------------------------------------------
  // Set the time interval for the (start, end) times for any subsequent
  // requests.
  //
  // @param $start Unix timestamp for the query start interval or NULL for
  //   restriction
  // @param $end Unix timestamp for the query end interval or NULL for
  //   restriction
  // -------------------------------------------------------------------------

  public function setQueryInterval($start, $end);

  // -------------------------------------------------------------------------
  // Query the source for the list of resource nicknames that were available
  // during the specified time period.
  //
  // @param $summary TRUE if a summary of application kernels run on each
  //   resource should be returned, FALSE (default) if a list of distinct resource
  //   nicknames should be returned instead.
  //
  // @returns An array containing resource nicknames (if $summary == FALSE) or
  //
  // array('edge', 'u2', 'alamo')
  // -------------------------------------------------------------------------

  public function getAvailableResources($summary = FALSE);

  // -------------------------------------------------------------------------
  // Query the source for the list of application kernels that were available
  // on each resource during the specified time period.
  //
  // @param $options An associative array of options for filtering the
  //   application kernels returned.  Supported keys are:
  //
  //   - resources => An array of resource nicknames to be searched
  //   - base_name_only => Boolean.  TRUE to return the app kernel name without
  //     the number of compute elements used.
  //   - name => Return only app kernels whose name contains this string
  //
  // @returns An associative array where the keys are resource nicknames and the
  // values are lists of reporter names configured for those resources.
  //
  // array('edge' => array('tgmod.buffalo.benchmark.hpcc.8',
  //                       'tgmod.buffalo.benchmark.hpcc.16',
  //                       'tgmod.buffalo.app.chem.nwchem.8'));
  // -------------------------------------------------------------------------

  public function getAvailableAppKernels(array $options);

  // -------------------------------------------------------------------------
  // Query the source for a list of instance identifiers for all available
  // application kernel execution instances for the specified resource and
  // application kernel (and time period).
  //
  // @param $options An associative ar ray of options for filtering the
  //   application kernels returned.  Supported keys are:
  //
  //   - resource => Required.  A single resource nickname.
  //   - app_kernel => Optional.  An string used to filter application kernels.
  //     Any kernel containing this string will be returned
  //
  // @returns An array of instance identifiers
  //   -------------------------------------------------------------------------

  public function getAvailableInstanceIds(array $options);

  // -------------------------------------------------------------------------
  // Query the source for full data on all available application kernel
  // execution instances for the specified resource and application kernel (and
  // time period).
  //
  // @param $options An associative ar ray of options for filtering the
  //   application kernels returned.  Supported keys are:
  //
  //   - resource => Required.  A single resource nickname.
  //   - app_kernel => Optional.  An string used to filter application kernels.
  //     Any kernel containing this string will be returned
  //
  // @returns An associative array where the key is an application kernel
  //   instance id and the value is an AppKerneInstanceData object.
  // -------------------------------------------------------------------------

  public function getAvailableInstances(array $options);

  // -------------------------------------------------------------------------
  // Query the source for detailed data associated with the specified
  // application kernel execution instance.  The format of the data is specific
  // to the souce and should be passed to a parser for that source.
  //
  // @param $instance A instance identifier returned by Explorer::getAvailableInstances().
  //
  // @returns An AppKerneInstanceData object, or FALSE if no instance was found
  // with the specified id.
  // -------------------------------------------------------------------------

  public function getInstanceData($instance);

}  // interface iAppKernelExplorer
