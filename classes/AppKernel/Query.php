<?php
namespace Inca;
use CCR\DB;

// --------------------------------------------------------------------------------
// Implement the Inca Query action.
// --------------------------------------------------------------------------------

class Query extends \aRestAction
{

  // --------------------------------------------------------------------------------
  // @see aRestOperation::__call()
  // --------------------------------------------------------------------------------

  public function __call($target, $arguments)
  {
    // Verify that the target method exists and call it.

    $method = $target . ucfirst($this->_operation);
    if ( ! method_exists($this, $method) )
    {
      throw new \Exception("Unknown target $target on action " . strtolower(__CLASS__));
    }
    return $this->$method($arguments);
  }  // __call()

  // --------------------------------------------------------------------------------
  // @see aRestOperation::factory()
  // --------------------------------------------------------------------------------

  public static function factory()
  {
    return new Query();
  }  // factory()

  // ================================================================================
  // Define targets
  // ================================================================================

  // --------------------------------------------------------------------------------
  // --------------------------------------------------------------------------------

  private function resourcesAction()
  {
    $db = DB::factory("inca");
    $sql = "SELECT
  kernel_id, ak.name as app_kernel, description,
  reporter_id, num_units, version, r.name as reporter, processor_unit, enabled
  FROM app_kernel ak JOIN reporter r USING(kernel_id)";
    $result = $db->query($sql);
    return \RestResponse::factory(TRUE, "", NULL, $result);
  }  // resourcesAction()

  // --------------------------------------------------------------------------------
  // --------------------------------------------------------------------------------

  private function resourcesHelp()
  {
    print "<br> This is " . __FUNCTION__;
  }  // resourcesAction()

  private function otherAction()
  {
    print "<br> This is " . __FUNCTION__;
  }  // resourcesAction()

}  // class Query

?>
