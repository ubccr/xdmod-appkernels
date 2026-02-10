<?php
namespace AppKernel;

// ================================================================================
// Container for instance data returned by getAvalableInstances() and
// getInstanceData().  This class contains only a single member "data" that
// holds generic data specific to the source.  The parser for this data
// (implementing iAppKernelParser) must be able to parse data returned by the
// associated AppKernelExplorer.
//
// Extend this data structure for use by the explorer and parser for a
// particular data source.  PHP also allows adding data members on the fly by
// simply assigning them to an object.
// ================================================================================

class AppKernelInstanceData
{
  // Generic data returned by an application kernel.  The format will vary by
  // source.

  public $data;

  // --------------------------------------------------------------------------------
  // Generate a representation of this object
  // --------------------------------------------------------------------------------

  public function __toString()
  {
    return "";
  }  // __toString()

}  // class AppKernelInstanceData
