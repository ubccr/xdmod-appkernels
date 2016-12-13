<?php
namespace AppKernel;

// ================================================================================
// Data describing an application kernels ingestion session.  This includes
// information such as the source, url, number of instances imported, the window
// start/end times, error messages, etc.
// ================================================================================

class IngestionLogEntry
{
  public $source = NULL;      // Import source name (e.g., Inca)
  public $url = NULL;         // Import source url
  public $num = 0;            // Number of AK instances imported for this session
  public $start_time = NULL;  // Import window start timestamp
  public $end_time = NULL;    // Import window end timestamp
  public $last_update = NULL; // Timestamp of last update
  public $success = FALSE;    // TRUE on success
  public $message = NULL;     // Error (or informational) message
  public $reportObj = NULL;   // Serialized report object

  // --------------------------------------------------------------------------------
  // Reset the state of the object
  // --------------------------------------------------------------------------------

  public function reset()
  {
    $this->source = NULL;
    $this->url = NULL;
    $this->num = 0;
    $this->start_time = NULL;
    $this->end_time = NULL;
    $this->last_update = NULL;
    $this->success = FALSE;
    $this->message = NULL;
    $this->reportObj = NULL;
  }  // reset()

  // --------------------------------------------------------------------------------
  // Set the status
  //
  // @param $status TRUE or FALSE
  // @param $msg Optional status message
  // --------------------------------------------------------------------------------

  public function setStatus($status, $msg = NULL)
  {
    $this->success = ( $status ? 1 : 0 );
    $this->message = $msg;
  }  // setStatus()

}  // class IngestionLogEntry
?>
