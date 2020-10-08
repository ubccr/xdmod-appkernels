<?php
namespace AppKernel;
use \Exception, \SimpleXmlElement;
use libXMLError;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

// ================================================================================
// Parse data returned by the associated app kernel explorer (ArrExplorer)
// and populate the InstanceData object.  Data will be contained in an
// AppKernelInstanceData object and will contain a data member with raw data to
// be parsed plus any additional fields specific to this Explorer/Parser
// combination.

// --------------------------------------------------------------------------------

class ArrParser implements iAppKernelParser
{
    /**
     * @var LoggerInterface
     */
  private $logger = NULL;

  public static function factory(array $config = NULL, LoggerInterface $logger = NULL)
  {
    return new ArrParser($config, $logger);
  }

  // -------------------------------------------------------------------------
  // Private constructor so this class cannot be instantiated
  // -------------------------------------------------------------------------

  private function __construct(array $config = NULL, LoggerInterface $logger)
  {
    $this->logger = $logger;
  }

  // -------------------------------------------------------------------------
  // @see iAppKernelParser::parse()
  // -------------------------------------------------------------------------

  public function parse(AppKernelInstanceData $data, InstanceData &$parsedData)
  {
    $parsedData->reset();
    if ( ! ($data instanceof AppKernelInstanceData_Arr) )
    {
      throw new Exception("Data is not an instance of AppKernelInstanceData_Arr");
    }

    // Information returned by the deployment infrastructure

    $parsedData->deployment_instance_id = $data->instance_id;
    $parsedData->deployment_job_id = $data->job_id;
    $parsedData->deployment_hostname = $data->hostname;
    $parsedData->deployment_execution_hostname = $data->execution_hostname;
    $parsedData->deployment_time = strtotime($data->time);
    $parsedData->status = ( $data->completed ? InstanceData::STATUS_SUCCESS : InstanceData::STATUS_FAILURE );
    $parsedData->deployment_message = $data->message;
    $parsedData->deployment_stderr = $data->stderr;
    $parsedData->deployment_walltime = $data->walltime;
    $parsedData->deployment_memory = $data->memory;
    $parsedData->deployment_cputime = $data->cputime;

    $parsedData->deployment_ak_base_name = $data->akName;
    $parsedData->deployment_ak_name = $data->akNickname;

    if ( preg_match('/\.([0-9]+)$/', $data->akNickname, $matches) )
    {
      $parsedData->deployment_num_proc_units = $matches[1];
    }

    // If the status is 0 then this indicates an error there was an error.  We
    // won't be able to process the body XML.

    if ( ! $data->completed ) return false;

    libxml_use_internal_errors(true);
    $body = simplexml_load_string($data->data);

    if ( $body === false ) {
      $msg = implode("\n", array_map(array($this, 'formatLibXmlError'), libxml_get_errors()));
      libxml_clear_errors();
      libxml_use_internal_errors(false);
      throw new AppKernelException("Failed to parse data: $msg", AppKernelException::ParseError);
    }
    libxml_use_internal_errors(false);

    // --------------------------------------------------------------------------------
    // Extract TAS specific info
    //
    // <body>
    //  <xdtas>
    //   <batchJob>
    //    <status>Error</status>
    //    <errorCause>Cannot find any output from Inca reporter</errorCause>
    //    <reporter>xdmod.benchmark.hpcc</reporter>
    //    <errorMsg> base64 encoded, gzipped message... </errorMsg>
    //   </batchJob>
    //  </xdtas>
    // </body>

    if ( isset($body->xdtas) && isset($body->xdtas->batchJob) )
    {
      $batchJob = $body->xdtas->batchJob;

      $status = (string) $batchJob->status;
      $reporter = (string) $batchJob->reporter;
      if ( "Error" == $status )
      {
        $cause = (string) $batchJob->errorCause;
        $errMsg = (string) $batchJob->errorMsg;
        // <errorMsg> is gzipped and base64 encoded
        InstanceData::decode($errMsg); //$parsedData->decode($errMsg);
        $msg = sprintf("Error executing reporter %s@%s at %s.\nCause: '%s'",
                       $parsedData->deployment_hostname,
                       $reporter,
                       date("Y-m-d H:i:s", $parsedData->deployment_time),
                       $cause);
        $this->logger->log(Logger::DEBUG,  $msg . "\nMessage: $errMsg");

        $parsedData->status = InstanceData::STATUS_ERROR;
        $parsedData->ak_error_cause = $cause;
        $parsedData->ak_error_message = $errMsg;
        throw new AppKernelException($msg, AppKernelException::Error);
      }
      else if ( "Queued" == $status )
      {
        $waitTime = $batchJob->waitingTime;
        $msg = sprintf("Reporter %s@%s has been queued %d hours as of %s",
                       $parsedData->deployment_hostname,
                       $reporter,
                       $waitTime,
                       date("Y-m-d H:i:s", $parsedData->deployment_time));
        $this->logger->log(Logger::DEBUG,  $msg);

        $parsedData->status = InstanceData::STATUS_QUEUED;
        $parsedData->ak_queue_time = $waitTime;
        throw new AppKernelException($msg, AppKernelException::Queued);
      }

    }  // if ( isset($body->xdtas) && isset($body->xdtas->batchJob) )

    // Determine the type of reporter based on the name of the enclosing tag.
    // Normally Inca would wrap a <body> tag around this if we used their web
    // services.

    $reporterType = NULL;
    if ( isset($body->performance) ) $reporterType = "Performance";

    // If the reporter type could not be determined and the reporter did not
    // complete this is considered an acceptable outcome and the error message
    // should be displayed.  There are typically no paramerters or stderr when a
    // reporter doesn't complete.

    if ( NULL === $reporterType )
    {
      $msg = "Could not determine reporter type by examining body XML for '" .
        $parsedData->deployment_ak_name . "' on '" . $parsedData->deployment_hostname . "' at " .
        date("Y-m-d H:i:s", $parsedData->deployment_time);
      if ( InstanceData::STATUS_SUCCESS == $parsedData->status && isset($exitStatus->deployment_message) )
        $msg .= "  Message: '" . $exitStatus->deployment_message . "'";
      $this->logger->log(Logger::WARNING,  $msg);
      throw new AppKernelException($msg, AppKernelException::UnknownType);
    }  // if ( NULL === $reporterType )

    $bodyParserClass = "AppKernel\\IncaParser_" . $reporterType;
    $bodyParserClassFile = "IncaParser_" . $reporterType . ".php";
    require_once($bodyParserClassFile);

    if ( ! class_exists($bodyParserClass) )
    {
      $msg = "Unsupported body parser '$bodyParserClass' ($bodyParserClassFile)";
      throw new Exception($msg);
    }

    $retval = FALSE;
    $bodyParser = new $bodyParserClass;
    $retval = $bodyParser->parse($body, $parsedData);

    //add supremm
    if($data->supremm!==NULL){
        foreach ($data->supremm as $metric_name => $metric) {
            $parsedData->ak_metrics[]=new InstanceMetric($metric['name'],$metric['value'],$metric['unit']);
        }
    }
    return $retval;

  }  // parse()

  // -------------------------------------------------------------------------
  // Format a libXML error.
  //
  // @param libXMLError $err
  //
  // @return string
  // -------------------------------------------------------------------------

  protected function formatLibXmlError(libXMLError $err)
  {
    switch ($err->level) {
      case LIBXML_ERR_WARNING:
        $level = 'Warning';
        break;
      case LIBXML_ERR_ERROR:
        $level = 'Error';
        break;
      case LIBXML_ERR_FATAL:
        $level = 'Fatal Error';
        break;
      default:
        $level = 'Unknown Error';
        break;
    }

    $msg = sprintf(
      'libXML %s (%d): %s (line %d, column %d)',
      $level,
      $err->code,
      rtrim($err->message),
      $err->line,
      $err->column
    );

    return $msg;
  }
}  // class ArrParser
