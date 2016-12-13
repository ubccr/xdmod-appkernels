<?php
namespace AppKernel;

// ================================================================================
// Application kernel exceptions to experiment using exception codes for various
// exception conditions.
// ================================================================================

class AppKernelException extends \Exception
{
  // Error parsing app kernel data
  const ParseError = 1;

  // The app kernel did not run for some reason
  const Error = 2;

  // App kernel is queued on the resource
  const Queued = 3;

  // No data returned by app kernel
  const NoDataReturned = 4;
  
  // Could not determine app kernel type
  const UnknownType = 5;

  // An instance with this key was already stored in the database
  const DuplicateInstance = 6;
}
?>
