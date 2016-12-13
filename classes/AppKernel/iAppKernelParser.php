<?php
namespace AppKernel;

// --------------------------------------------------------------------------------
// Define an interface that all applicaiton kernel parser classes must implement
// so that we can instantiate them from a single point.
//
// Parse data returned by the associated app kernel explorer
// (iAppKernelExplorer) and populate the InstanceData object.  Data will be
// contained in an AppKernelInstanceData object and will contain a data member
// with raw data to be parsed plus any additional fields specific to this
// Explorer/Parser combination.
// --------------------------------------------------------------------------------

interface iAppKernelParser
{
  // Create an instance of the reporter parser
  public static function factory(array $config = NULL, \Log $logger = NULL);

  // --------------------------------------------------------------------------------
  // Parse the application kernel data and return an AppKernelInstance object
  // contaning the parsed data.
  //
  // @param $data Application kernel specific data to parse (text, xml, etc.) as
  //   returned by iAppKernelExplorer::getInstanceData()
  // @param $parsedData Reference to an data structure that will contain the
  //   parsed data
  //
  // @returns TRUE if successful, FALSE otherwise
  // --------------------------------------------------------------------------------

  public function parse(AppKernelInstanceData $data, InstanceData &$parsedData);

}  // interface iAppKernelParser
?>
