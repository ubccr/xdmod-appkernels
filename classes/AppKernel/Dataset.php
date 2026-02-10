<?php

namespace AppKernel;

// ================================================================================
// A dataset containing three vectors containing values to be plotted,
// timestamps for each value, and a version vector where a value of 1 indicates
// that the version has changed since the previous vector element.  Also
// includes information such as application kernel name, resource name, metric,
// metric unit, and number of processing units, app kernel and resource
// descriptions.
// ================================================================================
// phpcs:disable PSR1.Classes.ClassDeclaration
class Dataset
{
    // phpcs:enable PSR1.Classes.ClassDeclaration
    public $akName;
    public $resourceName;
    public $metric;
    public $metricUnit;
    public $numProcUnits;
    public $description;
    public $resourceDescription;
    public $rawNumProcUnits;
    public $note;
    public $akId;
    public $resourceId;
    public $metricId;
    public $valueVector = array();
    public $valueLowVector = array();
    public $valueHighVector = array();
    public $controlVector = array();
    public $controlStartVector = array();
    public $controlEndVector = array();
    public $controlMinVector = array();
    public $controlMaxVector = array();
    public $runningAverageVector = array();
    public $timeVector = array();
    public $versionVector = array();
    public $controlStatus = array();

    public function __construct(
        $name,
        $akId,
        $resource,
        $resourceId,
        $metric,
        $metricId,
        $metricUnit,
        $numProcUnits,
        $description,
        $rawNumProcUnits,
        $resourceDescription = 'resource description'
    ) {
        $this->akName = $name;
        $this->akId = $akId;
        $this->resourceName = $resource;
        $this->resourceId = $resourceId;
        $this->metric = $metric;
        $this->metricId = $metricId;
        $this->metricUnit = $metricUnit;
        $this->numProcUnits = $numProcUnits;
        $this->description = $description;
        $this->resourceDescription = $resourceDescription;
        $this->rawNumProcUnits = $rawNumProcUnits;
        $this->note = "";
    }

    public function getChartTimeVector()
    {
        $chartTimeVector = array();
        foreach ($this->timeVector as $time) {
            $chartTimeVector[] = chartTime2($time);
        }
        return $chartTimeVector;
    }

    public function durationInDays()
    {
        $length = count($this->valueVector);
        if ($length <= 0) {
            return 0;
        }
        return ($this->timeVector[$length - 1] - $this->timeVector[0]) / (3600.00 * 24.0);
    }

    public function export()
    {
        date_default_timezone_set('UTC');
        $headers = array();
        $rows = array();
        $duration_info = array('start' => '', 'end' => '');
        $title = array('title' => 'title');
        $title2 = array();
        $length = count($this->valueVector);

        if ($length > 0) {
            $duration_info['start'] = date('Y-m-d', $this->timeVector[0]);
            $duration_info['end'] = date('Y-m-d', $this->timeVector[$length - 1]);

            $title['title'] = "App Kernel Data";
            $title2['parameters'] = array(
                "App Kernel = " . $this->akName,
                "Resource = " . $this->resourceName,
                "Metric = " . $this->metric,
                "Processing Units = " . $this->numProcUnits
            );
            $headers = array('Date', 'Value', 'Control', 'Changed');
            for ($i = 0; $i < $length; $i++) {
                $rows[$this->timeVector[$i]] = array(
                    date('Y-m-d H:i:s', $this->timeVector[$i]),
                    $this->valueVector[$i],
                    $this->controlVector[$i],
                    $this->versionVector[$i] == 1 ? 'yes' : 'no'
                );
            }
        }

        return array(
            'title' => $title,
            'title2' => $title2,
            'duration' => $duration_info,
            'headers' => $headers,
            'rows' => $rows
        );
    }

    // --------------------------------------------------------------------------------
    // Aggregate dataset
    //
    // @param aggTime aggregation time in hours
    // @return Dataset with aggregated values
    // --------------------------------------------------------------------------------

    public function aggregate($aggTime)
    {
        $aggTime = intval($aggTime);
        $aggDataset = new Dataset(
            $this->akName,
            $this->akId,
            $this->resourceName,
            $this->resourceId,
            $this->metric,
            $this->metricId,
            $this->metricUnit,
            $this->numProcUnits,
            $this->description,
            $this->rawNumProcUnits,
            $this->resourceDescription
        );

        if (count($this->timeVector) === 0) {
            return $aggDataset;
        }

        if ($aggTime === 24 * 3600) {
            $aggDataset->note .= "(One day avg.)";
        } elseif ($aggTime === 7 * 24 * 3600) {
            $aggDataset->note .= "(Avr. by week)";
        } elseif ($aggTime === 30 * 24 * 3600) {
            $aggDataset->note .= "(Avr. by 30 days)";
        } else {
            $aggDataset->note .= sprintf("(Avr. by %.1f days)", $aggTime / (24 * 3600));
        }

        $prevTime = intval(intval($this->timeVector[0]) / $aggTime);
        $meanVal = 0.0;
        $minVal = $this->valueVector[0];
        $maxVal = $this->valueVector[0];
        $n = 0;
        $totalPoints = count($this->timeVector);
        for ($i = 0; $i < $totalPoints; $i++) {
            $curTime = intval(intval($this->timeVector[$i]) / $aggTime);
            if ($curTime !== $prevTime || $i === $totalPoints - 1) {
                $aggDataset->timeVector[] = strval($curTime * $aggTime);
                $aggDataset->valueVector[] = strval($meanVal / $n);
                $aggDataset->valueHighVector[] = strval($maxVal);
                $aggDataset->valueLowVector[] = strval($minVal);

                // for following arragy let's keep only last record for aggregated period
                if (count($this->controlVector) > $i) {
                    $aggDataset->controlVector[] = $this->controlVector[$i];
                }
                if (count($this->controlStartVector) > $i) {
                    $aggDataset->controlStartVector[] = $this->controlStartVector[$i];
                }
                if (count($this->controlEndVector) > $i) {
                    $aggDataset->controlEndVector[] = $this->controlEndVector[$i];
                }
                if (count($this->controlMinVector) > $i) {
                    $aggDataset->controlMinVector[] = $this->controlMinVector[$i];
                }
                if (count($this->controlMaxVector) > $i) {
                    $aggDataset->controlMaxVector[] = $this->controlMaxVector[$i];
                }
                if (count($this->runningAverageVector) > $i) {
                    $aggDataset->runningAverageVector[] = $this->runningAverageVector[$i];
                }
                if (count($this->versionVector) > $i) {
                    $aggDataset->versionVector[] = $this->versionVector[$i];
                }
                if (count($this->controlStatus) > $i) {
                    $aggDataset->controlStatus[] = $this->controlStatus[$i];
                }

                $prevTime = $curTime;
                $meanVal = 0.0;
                $minVal = $this->valueVector[$i];
                $maxVal = $this->valueVector[$i];
                $n = 0;
            }
            $n++;
            $val = floatval($this->valueVector[$i]);
            $meanVal += floatval($val);
            $minVal = min($minVal, $val);
            $maxVal = max($maxVal, $val);
        }
        return $aggDataset;
    }
    // --------------------------------------------------------------------------------
    // Aggregate dataset if needed
    //
    // @return Dataset with aggregated values
    // --------------------------------------------------------------------------------
    public function autoAggregate()
    {
        // aggregate output if number of points is large
        $num_points = count($this->timeVector);
        if ($num_points < 201) {
            return $this;
        } else {
            $dt = intval($this->timeVector[0]) - intval($this->timeVector[$num_points - 1]);
            $dt /= 24 * 3600;
            if ($dt < 365 / 2) {
                return $this->aggregate(24 * 3600);
            } elseif ($dt < 365) {
                return $this->aggregate(7 * 24 * 3600);
            } else {
                return $this->aggregate(30 * 24 * 3600);
            }
        }
    }
}
