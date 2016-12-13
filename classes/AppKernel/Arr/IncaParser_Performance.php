<?php
namespace AppKernel;
use \SimpleXmlElement;

class IncaParser_Performance
{
  // SimpleXML object instantiated from the XML string
  private $_sxmlObj = NULL;

  private $_results = NULL;

  // Parsed reporter name
  private $_reporterName = NULL;

  // Parsed benchmark names and information
  //  private $_benchmarkInfo = array();

  // Parsed metrics (name, unit)
  //  private $_metricInfo = array();

  // --------------------------------------------------------------------------------
  // @param $xml XML string returned by the reporter
  // --------------------------------------------------------------------------------

  public function __construct()
  {
  }  // __construct()

  // --------------------------------------------------------------------------------
  // Parse the repoter body
  // --------------------------------------------------------------------------------

  public function parse(SimpleXmlElement $body,
                        InstanceData &$parsedData)
  {
    $this->_results = $parsedData;
    
    // Is the data in long or short format?  If the ID tag exists as a child of
    // the performance tag then it is in long format, if there is an ID
    // attribute in the performance then it is short format.

    if ( isset($body->performance->ID) )
    {
      $this->parseLongFormat($body->performance);
    }
    else if ( isset($body->performance['ID']) )
    {
      $this->_reporterName = $body->performance['ID'];
      $this->parseShortFormat($body->performance);
    }
    else
    {
      $msg = "Unable to determine short or long reporter format";
      throw new AppKernelException($msg, AppKernelException::ParseError);
    }

    return TRUE;
  }  // parse()

  // --------------------------------------------------------------------------------
  // Parse the short format (data as attributes) repoter body.
  //
  // @param $node SimpleXMLElement object to parse
  // --------------------------------------------------------------------------------

  private function parseShortFormat(SimpleXMLElement $node)
  {

    $stats = $node->benchmark->statistics;
    foreach ( $stats->attributes() as $attribute => $value )
    {
      $name = trim((string) $attribute);
      $value = trim((string) $value);
      $this->_results->ak_metrics[] = new InstanceMetric($name, $value);
    }

    $parameters = $node->benchmark->parameters;
    foreach ( $parameters->attributes() as $param => $value )
    {
      $name = (string) $param;
      $value = (string) $value;
      $this->_results->ak_parameters[] = new InstancedParameter($name, $value);
    }

  }  // parseShortFormat()

  // --------------------------------------------------------------------------------

  private function normalizeStatisticId($name)
  {
    $tmp = strtolower(strtr($name, " ", "_"));
    return preg_replace('/[^a-zA-Z-0-9_]/', "", $tmp);
  }

  // --------------------------------------------------------------------------------
  // Parse the long format (data as elements) repoter body.
  //
  // @param $node SimpleXMLElement object to parse
  // --------------------------------------------------------------------------------

  private function parseLongFormat(SimpleXMLElement $node)
  {
    $benchmarkName = (string) $node->benchmark->ID;

    if ( ! isset($node->benchmark->statistics) )
    {
      $msg = "No metrics present in XML for reporter '{$this->_results->deployment_ak_name}' " .
        "collected " . date("Y-m-d H:i:s", $this->_results->deployment_time) .
        " benchmark '$benchmarkName'";
      throw new AppKernelException($msg, AppKernelException::ParseError);
    }

    foreach ( $node->benchmark->statistics->statistic as $statistic )
    {
      // Normalize the id so we can use it as a database column name
      $name = trim((string) $statistic->ID);
      $value = trim((string) $statistic->value);
      $unit = trim((string) $statistic->units);
      $this->_results->ak_metrics[] = new InstanceMetric($name, $value, $unit);
    }

    if ( isset($node->benchmark->parameters) )
    {
      foreach ( $node->benchmark->parameters->parameter as $param )
      {
        $name = (string) $param->ID;
        $value = (string) $param->value;
        $unit = (string) $param->units;
        $this->_results->ak_parameters[] = new InstanceParameter($name, $value, $unit);
      }  // foreach ( $node->benchmark->parameters->parameter as $param )
    }
  }  // parseLongFormat()

  // --------------------------------------------------------------------------------
  // @returns The name of the reporter
  // --------------------------------------------------------------------------------

  public function reporterName() { return $this->_reporterName; }

  // --------------------------------------------------------------------------------
  // @returns An array of available benchmark names
  // --------------------------------------------------------------------------------

  public function benchmarkNames() { return array_keys($this->_benchmarkInfo); }

  // --------------------------------------------------------------------------------
  // @returns An array of units returned by the reporter
  // --------------------------------------------------------------------------------

  public function metrics() { return $this->_metricInfo; }

  // --------------------------------------------------------------------------------
  // Return information about the desired benchmark.  If no benchmark was
  // specified return information about the first benchmark found.
  //
  // @param $benchmarkName The name of the desired benchmark
  // @param $measurement The name of the desired measurement in the named benchmark
  //
  // @returns The value for a single measurement or an array of all measurements
  //   for the named benchmark.
  // --------------------------------------------------------------------------------

  public function statistics($benchmarkName = NULL, $measurement = NULL)
  {
    // If neither a benchmark name nor a measurement is provided simply return
    // the first benchmark available.

    if ( NULL === $benchmarkName && NULL === $measurement )
    {
      if ( 0 == count($this->_benchmarkInfo) )
      {
        $msg = "No benchmarks found";
        throw new AppKernelException($msg, AppKernelException::ParseError);
      }
      return current($this->_benchmarkInfo);
    }  // if ( NULL === $benchmarkName && NULL === $measurement )

    if ( ! array_key_exists($benchmarkName, $this->_benchmarkInfo) )
    {
      $msg = "Benchmark does not exist: '$benchmarkName'.  Valid values are ('" .
        implode("', '", $this->benchmarkNames()) . "')";
      throw new AppKernelException($msg, AppKernelException::ParseError);
    }

    if ( NULL !== $measurement &&
         ! array_key_exists($measurement, $this->_benchmarkInfo[$benchmarkName]) )
    {
      throw new AppKernelException($msg, AppKernelException::ParseError);
    }

    return ( NULL !== $measurement
             ? $this->_benchmarkInfo[$benchmarkName][$measurement]
             :  $this->_benchmarkInfo[$benchmarkName] );

  }  // statistics()

}  // class IncaParser_Performance
