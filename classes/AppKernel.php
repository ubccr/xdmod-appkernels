<?php
// --------------------------------------------------------------------------------
// This class provides a mechanism for instantiating applicaiton kernel
// explorers and parsers based on the source of the data.  Parsers must
// implement the iAppKernelParser interface and explorers must implement the
// iAppKernelExplorer interface.
// --------------------------------------------------------------------------------

class AppKernel
{
  const PARSER_CLASS = "Parser";  // Parser class name
  const EXPLORER_CLASS = "Explorer";  // Explorer class name
  const MYNAMESPACE = "AppKernel";  // App kernel namespace

  // -------------------------------------------------------------------------
  // Instantiate an application kernel explorer based on specified source.
  //
  // @param $source The source of the data to parse
  // @param $config An array containing source-specific configuration directives
  // @param $logger An optional PEAR::Log class used for logging
  //
  // @returns An instance of the appropriate application kernel explorer, which
  //   must implement the iAppKernelExplorer interface
  //
  // @throws Exception If the explorer class was not found/defined
  // @throws Exception If the explorer class does not implement iAppKernelExplorer
  //
  // @see iAppKernelExplorer
  // -------------------------------------------------------------------------

  public static function explorer($source, array $config = NULL, \Psr\Log\LoggerInterface $logger = NULL)
  {
    $reporterType = NULL;
    if ( empty($source) )
    {
      throw new Exception("No application kernel source provided");
    }

    return self::instantiate($source, "Explorer", "iAppKernelExplorer", $config, $logger);

  }  // explorer()

  // --------------------------------------------------------------------------------
  // Instantiate an application kernel parser based on the specified source
  //
  // @param $source The source of the data to parse
  // @param $config An array containing source-specific configuration directives
  // @param $logger An optional PEAR::Log class used for logging
  //
  // @returns An instance of the appropriate application kernel parser, which
  //   must implement the iAppKernelParser interface
  //
  // @throws Exception If the parser class was not found
  // @throws Exception If the parser class does not implement iAppKernelParser
  //
  // @see iAppKernelParser
  // --------------------------------------------------------------------------------

  public static function parser($source, array $config = NULL, Log $logger)
  {
    $reporterType = NULL;
    if ( empty($source) )
    {
      throw new Exception("No application kernel source provided");
    }

    return self::instantiate($source, "Parser", "iAppKernelParser", $config, $logger);

  }  // parser()

  // --------------------------------------------------------------------------------
  // Instantiate an application kernel object after checking to see that the
  // class file exists and it implements the correct interface.
  //
  // @param $source The source of the data to parse
  // @param $sourceClassName The name of the class to instantiate
  // @param $sourceInterface The interface that the class must implement
  // @param $config An array containing source-specific configuration directives
  // @param $logger An optional PEAR::Log class used for logging
  //
  // @returns An instance of the appropriate object
  //
  // @throws Exception If the class was not found
  // @throws Exception If the class does not implement iAppKernelParser
  // --------------------------------------------------------------------------------

  private static function instantiate($source,
                                      $sourceClassName,
                                      $sourceInterface,
                                      array $config = NULL,
                                      \Psr\Log\LoggerInterface $logger = NULL)
  {
    // Load the correct parser based on the data source

    $obj = NULL;
    $dir = ucfirst($source);
    $className = self::MYNAMESPACE . "\\" . ucfirst($source) . $sourceClassName;
    $classFile = "$dir/" . $sourceClassName . ".php";
    $sourceInterface = self::MYNAMESPACE . "\\$sourceInterface";

    require_once($classFile);

    if ( ! class_exists($className) )
    {
      $msg = "Unsupported source '$source' ($className)";
      throw new Exception($msg);
    }

    /*
    if ( ! in_array($sourceInterface,  class_implements($className) ) )
    {
      $msg = "$className does not implement $sourceInterface";
      throw new Exception($msg);
    }
    */

    $logger->log("Instantiating $classFile");
    return $className::factory($config, $logger);

  }  // instantiate()

  // -------------------------------------------------------------------------
  // Private constructor so this class cannot be instantiated
  // -------------------------------------------------------------------------

  private function __construct() {}

}  // class AppKernel
?>
