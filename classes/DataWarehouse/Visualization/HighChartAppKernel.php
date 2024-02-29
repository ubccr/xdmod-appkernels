<?php

namespace DataWarehouse\Visualization;

/**
 * Base class for server generated chart for app kernel data.
 *
 * @author Amin Ghadersohi
 */
class HighChartAppKernel extends AggregateChart
{
    const IN_CONTROL_COLOR = 'rgba(170, 255, 170, 0.75)';

    const CONTROL_REGION_TIME_INTERVAL_COLOR = '#E0F8F7';

    const OVERPERFORMING_COLOR = '#F2F5A9';

    const UNDERPERFORMING_COLOR = '#F5A9BC';

    const MINMAX_COLOR = '#cc99ff';

    public $_axis_index;

    /**
     * CSS url containing Base 64 encoded indicator image.
     *
     * @var string
     */
    protected $_indicator_url;

    public function __construct(
        $start_date,
        $end_date,
        $scale,
        $width,
        $height,
        $user,
        $swap_xy = false
    ) {
        parent::__construct(
            'auto',
            $start_date,
            $end_date,
            $scale,
            $width,
            $height,
            $user,
            $swap_xy
        );

        $this->_axis = array();
        $this->_datasetCount = 0;
        $this->_axisCount = 0;

        $this->_indicator_url = '/gui/images/exclamation_ak.png';
    }

    public function configure(
        &$datasets,
        $font_size = 0,
        $limit = null,
        $offset = null,
        $isSVG = false,
        $drillDown = false,
        $colorsPerCore = false,
        $longLegend = false,
        $showChangeIndicator = true,
        $showControls = false,
        $discreteControls = false,
        $showControlZones = false,
        $showRunningAverages = false,
        $showControlInterval = false,
        $showMinMax = false,
        $contextMenuOnClick = null,
        $resultCount = 0
    ) {
        $this->_chart['layout']['annotations'][0]['font']['color'] = '#000000';
        $this->_chart['layout']['annotations'][0]['font']['size'] = (16 + $font_size);

        $this->_chart['layout']['annotations'][1]['font']['color'] = '#5078a0';
        $this->_chart['layout']['annotations'][1]['font']['size'] = (12 + $font_size);

        if ($resultCount > 1) {
            $this->_chart['layout']['hovermode'] = 'closest';
        }

        $this->_chart['layout']['xaxis'] = array(
            'automargin' => true,
            'layer' => 'below traces',
            'type' => 'date',
            'tickfont' => array(
                'size' => (11 + $font_size),
                'color' => '#606060',
            ),
            'ticksuffix' => ' ',
            'rangemode' => 'tozero',
            'tickformat' => '%d. %b',
            'tickmode' => 'date',
            'tick0' => date('Y-m-d H:i:s', strtotime($this->_startDate)),
            'hoverformat' => '%Y/%m/%d %H:%M:%S',
            'spikedash' => 'solid',
            'spikecolor' => '#c0c0c0',
            'showgrid' => false,
            'zeroline' => false,
            //'dtick' => 14 * 24 * 60 * 60 * 1000, Need to look into if keep this off is better
            'linewidth' => 1 + $font_size/4,
            'linecolor' => '#c0d0e0',
        );

        $colors = \DataWarehouse\Visualization::getColors(33);
        $colors = array_reverse($colors);


        foreach($datasets as $index => $dataset)
        {
            if(!isset($this->_axis[$dataset->metricUnit])) {
                $yMin=min($dataset->valueVector);
                if(count($dataset->valueLowVector)>0) {
                    $yMin = min($yMin, min($dataset->valueLowVector));
                }
                if(count($dataset->controlMinVector)>0) {
                    $yMin = min($yMin, min($dataset->controlMinVector));
                }

                $yMax=max($dataset->valueVector);
                if(count($dataset->valueHighVector)>0) {
                    $yMax=max($yMax, max($dataset->valueHighVector));
                }
                if(count($dataset->controlMaxVector)>0) {
                    $yMax=max($yMax, max($dataset->controlMaxVector));
                }
                $dy=$yMax-$yMin;

                $yMin-=0.05*$dy;
                $yMax+=0.05*$dy;
                if($yMin<0) {
                    $yMin=0;
                }

                $yAxisColorValue = $colors[$this->_axisCount % 33];
                $yAxisColor = '#'.str_pad(dechex($yAxisColorValue), 6, '0', STR_PAD_LEFT);
                $yIndex = $this->_axisCount + 1;
                $yAxisName = $this->_axisCount == 0 ? 'yaxis' : "yaxis{$yIndex}";
                $yAxis = array(
                    'automargin' => true,
                    'layer' => 'below traces',
                    'title' => array(
                        'text' => '<b>' . $dataset->metricUnit . '</b>',
                        'font' => array(
                            'color'=> $yAxisColor,
                            'size' => (12 + $font_size)
                        )
                    ),
                    'tickfont' => array(
                        'size' => (11 + $font_size),
                        'color' => '#606060'
                    ),
                    'tickformat' => ',.2f',
                    'tickmode' => 'auto',
                    'ticksuffix' => ' ',
                    'tickprefix' => $this->_axisCount > 0 ? ' ' : null,
                    'exponentformat' => 'SI',
                    'type' => 'linear',
                    'rangemode' => 'tozero',
                    'range' => [$yMin, $yMax],
                    'index' => $yIndex,
                    'axisname' => $yAxisName,
                    'separatethousands' => true,
                    'overlaying' => $this->_axisCount == 0 ? null : 'y',
                    'linewidth' => 1 + $font_size/4,
                    'linecolor' => '#c0d0e0',
                    'side' => 'left',
                    'anchor' => 'x',
                    'autoshift' => true,
                    'gridwidth' => $this->_axisCount > 1 ? 0 : 1 + ($font_size/8),
                    'zeroline' => false,
                );

                if ($this->_axisCount > 0){
                    if ($this->_axisCount % 2 == 0) {
                        $yAxis = array_merge($yAxis, array(
                            'side' => 'left',
                            'anchor' => 'free',
                            'autoshift' => true,
                        ));
                    }
                    else {
                        $yAxis = array_merge($yAxis, array(
                            'side' => 'right',
                            'anchor' => $this->_axisCount > 1 ? 'free' : 'x',
                            'autoshift' => true,
                        ));
                   }
                }


                $this->_axis[$dataset->metricUnit] = $yAxis;
                $this->_axis_index[$dataset->metricUnit]= $yIndex - 1;
                $this->_chart['layout']["{$yAxisName}"] = $yAxis;

                $this->_axisCount++;
            } else{
                $yMin=min($dataset->valueVector);
                $yMax=max($dataset->valueVector);
                $dy=$yMax-$yMin;

                $yMin-=0.05*$dy;
                $yMax+=0.05*$dy;
                if($yMin<0) {
                    $yMin=0;
                }

                $axisIndex = $this->_axis_index[$dataset->metricUnit] + 1;
                $axis = $axisIndex == 1 ? 'yaxis' : "yaxis{$axisIndex}";
                if($this->_axis[$dataset->metricUnit]['range'][0]>$yMin) {
                    $this->_axis[$dataset->metricUnit]['range'][0]=$yMin;
                    $this->_chart['layout'][$axis]['range'][0]=$yMin;
                }
                if($this->_axis[$dataset->metricUnit]['range'][1]<$yMax) {
                    $this->_axis[$dataset->metricUnit]['range'][1]=$yMax;
                    $this->_chart['layout'][$axis]['range'][1]=$yMax;
                }
            }
        }
        $controlPivot = -0.5;
        foreach($datasets as $index => $dataset)
        {
            $dataCount = count($dataset->valueVector);
            $yAxis = $this->_axis[$dataset->metricUnit];
            $yAxisColorValue = $colors[($yAxis['index']-1) % 33];
            $yAxisColor = '#'.str_pad(dechex($yAxisColorValue), 6, '0', STR_PAD_LEFT);

            $color_value =  $colorsPerCore?self::getAppKernelColor($dataset->rawNumProcUnits):  $colors[$this->_datasetCount % 33];
            $color = '#'.str_pad(dechex($color_value), 6, '0', STR_PAD_LEFT);
            $lineColor = '#'.str_pad(dechex(\DataWarehouse\Visualization::alterBrightness($color_value, -70)), 6, '0', STR_PAD_LEFT);

            if($longLegend) {
                $datasetName = '['.$dataset->numProcUnits.': '.$dataset->resourceName.'] <br>'.$dataset->akName.' '.
                    $dataset->metric.$dataset->note.' [<span style="color:'.$yAxisColor.'">'.$dataset->metricUnit.'</span>]';
            } else {
                if($dataset->note==="") {
                    $datasetName = $dataset->numProcUnits;
                } else {
                    $datasetName = $dataset->numProcUnits . " " . $dataset->note;
                }
            }

            if ($this->_datasetCount == 0) {
                $this->_chart['layout']['hoverlabel']['bordercolor'] = $color;
            }

            $xValues = array();
            $yValues = array();
            $seriesData = array();
            foreach($dataset->valueVector as $i => $v)
            {
                $sv = array(
                    'x' => date('Y-m-d H:i:s', $dataset->timeVector[$i]),
                    'y' => (double)$v
                );

                if($v===null) {
                    $sv['y'] = null;
                }

                $seriesData[] = array(
                    'x' => $dataset->timeVector[$i],
                    'y' => $sv['y']
                );

                if($showChangeIndicator && $dataset->versionVector[$i] > 0) {
                    $this->_chart['layout']['images'][] = array(
                        'source' => $this->_indicator_url,
                        'name' => 'Change Indicator',
                        'sizex' => 2*24*60*60*1000,
                        'sizey' => 40, // Need to find the right sizing here
                        'xref' => 'x',
                        'yref' => 'y',
                        'xanchor' => 'center',
                        'yanchor' => 'middle',
                        'x' => $this->_swapXY ? $sv['y'] : $sv['x'],
                        'y' => $this->_swapXY ? $sv['x'] : $sv['y'],
                    );
                }

                $xValues[] = $sv['x'];
                $yValues[] = $sv['y'];
            }
            $trace = array(
                'name' => $datasetName,
                'zIndex' => 10,
                'cliponaxis' => false,
                'yaxis' => "y{$yAxis['index']}",
                'showlegend' => true,
                'legendrank' => 1000,
                'marker' => array(
                    'color' => $color,
                    'line' => array(
                        'width' => 1,
                        'color' => $lineColor,
                    ),
                    'size' => ($font_size/4 + 5) * 2,
                    'symbol' => self::getAppKernelSymbol($dataset->rawNumProcUnits)
                ),
                'line' => array(
                    'color' => $color,
                    'width' => 2 + $font_size/4,
                ),
                'type' => 'scatter',
                'mode' => 'lines+markers',
                'hovertext' => $xValues,
                'hovertemplate' => $datasetName . ': <b>%{y:,}</b> <extra></extra>',
                'hoverlabel' => array(
                    'align' => 'left',
                    'bgcolor' => 'rgba(255, 255, 255, 0.8)',
                    'bordercolor' => $yAxisColor,
                    'font' => array(
                        'size' => 12.8,
                        'color' => '#000000',
                        'family' => 'Lucida Grande, Lucida Sans Unicode, Arial, Helvetica, sans-serif',
                    ),
                    'namelength' => -1,
                ), 
                'x' => $this->_swapXY ? $yValues : $xValues,
                'y' => $this->_swapXY ? $xValues : $yValues,
                'seriesData' => $seriesData,
            );

            if($drillDown&&$contextMenuOnClick===null) {
                $trace['rawNumProcUnits'] = $dataset->rawNumProcUnits;
            }

            if ($resultCount > 1) {
                $trace['hovertemplate'] = '%{hovertext|%Y/%m/%d %H:%M:%S} <br>' . "<span style=\"color:$color\";> ●</span> "
                                          . $datasetName . ": <b>%{y:,}</b> <extra></extra>";
            }

            // Set null connectors
            if (in_array(null, $yValues)) {
                $null_trace = array(
                    'name' => 'gap connector for ' . $datasetName,
                    'zIndex' => 10,
                    'x' => $this->_swapXY ? $yValues : $xValues,
                    'y' => $this->_swapXY ? $xValues : $yValues,
                    'showlegend' => false,
                    'mode' => 'lines',
                    'line' => array(
                        'color' => $color,
                        'dash' => 'dash'
                    ),
                    'connectgaps' => true,
                    'hoverinfo' => 'skip',
                    'type' => 'scatter',
                    'yaxis' => "y{$yAxis['index']}",
                );

                if ($this->_swapXY) {
                    $null_trace['xaxis'] = "x{$yAxis['index']}";
                    unset($null_trace['yaxis']);
                }

                $this->_chart['data'][] = $null_trace;
            } 

            // Handle swap axis
            $yAxisName = $yAxis['axisname'];
            $xAxisName = substr_replace($yAxisName, 'xaxis', 0, 5);
            $yIndex = $yAxis['index'];
            $yAxisIndex = $yIndex - 1;
            $yAxisCount = $this->_axisCount;
            $xAxisStep = 0.115;
            $xAxisBottomBoundStart = 0 + ($xAxisStep * ceil($yAxisCount/2));
            $xAxisTopBoundStart = 1 - ($xAxisStep * floor($yAxisCount/2));
            $topShift = floor($yAxisCount/2) - floor($yAxisIndex/2);
            $bottomShift = ceil($yAxisCount/2) - ceil($yAxisIndex/2);

            if ($this->_swapXY) {
                $this->_chart['layout']['hovermode'] = 'y unified';
                $trace['hovertemplate'] = $datasetName . ": <b>%{x:,}</b> <extra></extra>";
                if ($resultCount > 1) {
                    $this->_chart['layout']['hovermode'] = 'closest';
                    $trace['hovertemplate'] = '%{hovertext} <br>' . "<span style=\"color:$color\";> ●</span> "
                                              . $datasetName . ": <b>%{x:,}</b> <extra></extra>";
                }

                unset($trace['yaxis']);
                $trace['xaxis'] = "x{$yIndex}";

                $xtmp = $this->_chart['layout']["{$xAxisName}"];
                $ytmp = $this->_chart['layout']["{$yAxisName}"];
                $this->_chart['layout']['yaxis'] = $xtmp;
                $this->_chart['layout']["{$xAxisName}"] = $ytmp;

                $this->_chart['layout']["{$xAxisName}"]['side'] = ($yAxisIndex % 2 != 0) ? 'top' : 'bottom';
                if ($this->_chart['layout']["{$xAxisName}"]['side'] == 'top') {
                    $this->_chart['layout']["{$xAxisName}"]['title']['standoff'] = 0;
                }
                $this->_chart['layout']["{$xAxisName}"]['anchor'] = 'free';
                if (isset($this->_chart['layout']["{$xAxisName}"]['overlaying'])) {
                    $this->_chart['layout']["{$xAxisName}"]['overlaying'] = 'x';
                }

                $this->_chart['layout']["{$xAxisName}"]['position'] = $this->_chart['layout']["{$xAxisName}"]['side'] == 'top' ? min(1 - ($xAxisStep * $topShift), 1) :
                    max(0 + ($xAxisStep * $bottomShift), 0);

                $this->_chart['layout']["{$xAxisName}"]['domain'] = array(0,1);
                $this->_chart['layout']["{$xAxisName}"]['title']['standoff'] = 0;
                $this->_chart['layout']["{$xAxisName}"]['type'] = 'linear';
                $this->_chart['layout']["{$xAxisName}"]['showgrid'] =$yAxisCount > 1 ? false : true;

                $this->_chart['layout']['yaxis']['linewidth'] = 2 + $font_size / 4;
                $this->_chart['layout']['yaxis']['linecolor'] = '#c0d0e0';
                $this->_chart['layout']['yaxis']['domain'] = array($xAxisBottomBoundStart, $xAxisTopBoundStart);
                $this->_chart['layout']['yaxis']['autorange'] = 'reversed';
                $this->_chart['layout']['yaxis']['showgrid'] = false;
                unset($this->_chart['layout']['yaxis']['position']);
            }

            $this->_chart['data'][] = $trace;

            $versionSum = array_sum($dataset->versionVector);
            if($showChangeIndicator && $versionSum > 0 && !isset($this->changeIndicatorInLegend) ) {
                $versionXValues = array();
                $versionYValues = array();
                $versionSeries = array();
                foreach($dataset->versionVector as $i => $v)
                {
                    $versionXValues[] = date('Y-m-d H:i:s', $dataset->timeVector[$i]);
                    $versionYValues[] = null;
                    $versionSeries[] = array(
                        'x' => $dataset->timeVector[$i],
                        'y' => null
                    );
                }

                $version_trace = array_merge($trace, array(
                    'name' => 'Change Indicator',
                    'yaxis' => "y{$yAxis['index']}",
                    'zIndex' => 9,
                    'mode' => 'markers',
                    'marker' => array(
                        'symbol' => 'hourglass'
                    ),
                    'type' => 'scatter',
                    'showlegend' => !isset($this->changeIndicatorInLegend),
                    'legendrank' => 1004,
                    'hoverinfo' => 'skip',
                    'x' => $this->_swapXY ? $versionYValues : $versionXValues,
                    'y' => $this->_swapXY ? $versionXValues : $versionYValues,
                    'seriesData' => $versionSeries,
                ));

                if ($this->_swapXY) {
                    $version_trace['xaxis'] = "x{$yIndex}";
                    unset($version_trace['yaxis']);
                }

                $this->changeIndicatorInLegend = true;
                $this->_chart['data'][] = $version_trace;
            }

            if($showRunningAverages) {
                $averageXValues = array();
                $averageYValues = array();
                $averageSeries = array();
                foreach($dataset->runningAverageVector as $i => $v)
                {
                    $averageXValues[] = date('Y-m-d H:i:s', $dataset->timeVector[$i]);
                    $averageYValues[] = $v ? (double)$v : null;
                    $averageSeries[] = array(
                        'x' => $dataset->timeVector[$i],
                        'y' => $v ? (double)$v : null
                    );
                }

                $aColor = '#'.str_pad(dechex(\DataWarehouse\Visualization::alterBrightness($color, -200)), 6, '0', STR_PAD_LEFT);
                $average_trace = array_merge($trace, array(
                    'name' => 'Running Average',
                    'zIndex' => 8,
                    'marker' => array(
                        'color' => $aColor,
                    ),
                    'line' => array(
                        'width' => 1 + $font_size/4,
                        'dash' => 'dash',
                    ),
                    'showInLegend' => true,
                    'legendrank' => 1001,
                    'hovertemplate' => 'Running Average: <b>%{y:,}</b> <extra></extra>',
                    'x' => $this->_swapXY ? $averageYValues : $averageXValues,
                    'y' => $this->_swapXY ? $averageXValues : $averageYValues,
                    'seriesData' => $averageSeries,
                ));

                if ($this->_swapXY) {
                    $average_trace['xaxis'] = "x{$yIndex}";
                    $average_trace['hovertemplate'] = 'Running Average: <b>%{x:,}</b> <extra></extra>';
                    unset($average_trace['yaxis']);
                }


                $this->_chart['data'][] = $average_trace;
            }

            if($showControls) {
                if(!isset($this->_axis['control'])) {
                    $yAxisControl = array(
                        'automargin' => true,
                        'layer' => 'below traces',
                        'title' => array(
                            'text' => '<b>Control</b>',
                            'font' => array(
                                'color'=> '#7cb5ec',
                                'size' => (12 + $font_size)
                            )
                        ),
                        'tickfont' => array(
                            'size' => (11 + $font_size)
                        ),
                        'tickformat' => ',.2f',
                        'tickmode' => 'auto',
                        'ticksuffix' => ' ',
                        'tickprefix' => $this->_axisCount > 0 ? ' ' : null,
                        'exponentformat' => 'SI',
                        'type' => 'linear',
                        'range' => [null, null],
                        'index' => $this->_axisCount + 1,
                        'axisname' => $yAxisName,
                        'separatethousands' => true,
                        'overlaying' => $this->_axisCount == 0 ? null : 'y',
                        'linewidth' => 1 + $font_size/4,
                        'linecolor' => '#c0d0e0', 
                        'side' => $this->_axisCount % 2 == 0 ? 'left' : 'right',
                        'anchor' => 'free',
                        'autoshift' => true,
                        'gridwidth' => $this->_axisCount > 1 ? 0 : 1 + ($font_size/8),
                        'zeroline' => false,
                    );
                    $index = $this->_axisCount + 1;
                    $yControlAxisName = "yaxis{$index}";
                    $xControlAxisName = "xaxis{$index}";
                    if ($this->_swapXY) {
                        $yAxisControl['side'] = ($yAxisIndex % 2 != 0) ? 'top' : 'bottom';
                        if ($yAxisControl['side'] == 'top') {
                            $yAxisControl['title']['standoff'] = 0;
                        }
                        $yAxisControl['anchor'] = 'free';
                        if (isset($yAxisControl['overlaying'])) {
                            $yAxisControl['overlaying'] = 'x';
                        }

                        $yAxisControl['position'] = $this->_chart['layout']["{$xAxisName}"]['side'] == 'top' ? min(1 - ($xAxisStep * $topShift), 1) :
                                                    max(0 + ($xAxisStep * $bottomShift), 0);

                        $yAxisControl['domain'] = array(0,1);
                        $yAxisControl['title']['standoff'] = 0;
                        $yAxisControl['type'] = 'linear';
                        $yAxisControl['showgrid'] =$yAxisCount > 1 ? false : true;
                        $this->_chart['layout']["{$xControlAxisName}"] = $yAxisControl;
                    }
                    else {
                        $this->_chart['layout']["{$yControlAxisName}"] = $yAxisControl;
                    }
                    $this->_axis['control'] = $yAxisControl;
                    $this->_axisCount++;
                }

                $controlVectorXValues = array();
                $controlVectorYValues = array();
                $controlVectorSeries = array();
                foreach($dataset->controlVector as $i => $control)
                {
                    if($discreteControls) {
                        if($control > 0) {
                            $control = 1;
                        } elseif($control < $controlPivot) {
                            $control = -1;
                        } else {
                            $control = 0;
                        }
                    }
                    $controlVectorXValues[] = date('Y-m-d H:i:s', $dataset->timeVector[$i]);
                    $controlVectorYValues[] = (double)$control;
                    $controlVectorSeries[] = array(
                        'x' => $dataset->timeVector[$i],
                        'y' => (double)$control
                    );
                }

                $control_vector_trace = array_merge($trace, array(
                    'name' => 'Control',
                    'zIndex' => 7,
                    'marker' => array(
                        'color' => '#7cb5ec',
                        'symbol' => 'diamond'
                    ),
                    'line' => array(
                        'color' => '#7cb5ec',
                        'width' => 1 + $font_size/4,
                        'dash' => 'dot',
                    ),
                    'yaxis' => "y{$this->_axis['control']['index']}",
                    'legendrank' => 1002,
                    'hovertemplate' => 'Control : <b>%{y:,}</b> <extra></extra>',
                    'x' => $this->_swapXY ? $controlVectorYValues : $controlVectorXValues,
                    'y' => $this->_swapXY ? $controlVectorXValues : $controlVectorYValues,
                    'seriesData' => $controlVectorSeries
                ));

                if ($this->_swapXY) {
                    $controlIndex = $this->_axis['control']['index'];
                    $control_vector_trace['xaxis'] = "x{$controlIndex}";
                    $control_vector_trace['hovertemplate'] = 'Control : <b>%{x:,}</b> <extra></extra>'; 
                    unset($control_vector_trace['yaxis']);
                }

                $this->_chart['data'][] = $control_vector_trace;
            }
            if($showMinMax && count($dataset->valueLowVector)>0 && count($dataset->valueHighVector)>0) {
                $min = array();
                $max = array();
                $minMaxXValues = array();
                $minMaxSeries = array();
                foreach($dataset->valueLowVector as $i => $v)
                {
                    $v2 = $dataset->controlEndVector[$i];
                    $minMaxXValues[] = date('Y-m-d H:i:s', $dataset->timeVector[$i]);
                    $min[] = $v?(double)$v:null;
                    if (!is_null($v) && !is_null($v2)) {
                        $max[] = $v2 - $v1;
                    }
                    else {
                        $max[] = null;
                    }
                    $minMaxSeries[] = array(
                        'x' => $dataset->timeVector[$i],
                        'y' => $v2 - $v1
                    );
                }
                $min_max_start = array_merge($trace, array(
                    'name' => 'MinMax Start',
                    'zIndex' => 6,
                    'fill' => 'tozeroy',
                    'fillcolor'=> '#ffffff',
                    'line' => array(
                        'color' => '#ffffff'
                    ),
                    'marker' => array(
                        'color' => '#ffffff'
                    ),
                    'type' => 'area',
                    'yaxis' => "y{$yAxis['index']}",
                    'mode' => 'none',
                    'hoverinfo' => 'skip',
                    'hovertemplate' => '',
                    'stackgroup' => '1',
                    'x' => $this->_swapXY ? $min : $minMaxXValues,
                    'y' => $this->_swapXY ? $minMaxXValues : $min
                ));

                $this->_chart['data'][] = $min_max_start;

                if ($this->_swapXY) {
                    $min_max_start['xaxis'] = "x{$yIndex}";
                    $min_max_start['fill'] = 'tozerox';
                    unset($min_max_start['yaxis']);
                }

                $min_max_end = array_merge($trace, array(
                    'name' => 'MinMax',
                    'zIndex' => 6,
                    'fill' => 'tonexty',
                    'fillcolor' => self::MINMAX_COLOR,
                    'type' => 'area',
                    'hovertext' => $minMaxXValues,
                    'customdata' => $min,
                    'yaxis' => "y{$yAxis['index']}",
                    'mode' => 'none',
                    'legendrank' => 1003,
                    'hovertemplate' => 'MinMax: <b>%{y:,} - %{customdata}</b> <extra></extra>',
                    'stackgroup' => '1',
                    'x' => $this->_swapXY ? $max : $minMaxXValues,
                    'y' => $this->_swapXY ? $minMaxXValues : $max,
                    'seriesData' => $minMaxSeries
                ));

                if ($this->_swapXY) {
                    $min_max_end['xaxis'] = "x{$yIndex}";
                    $min_max_end['fill'] = 'tonextx';
                    $min_max_end['hovertemplate'] = 'MinMax: <b>%{x:,} - %{customdata}</b> <extra></extra>';
                    unset($min_max_end['yaxis']);
                }

                $this->_chart['data'][] = $min_max_end;
            }
            if($showControlInterval) {
                $startValues = array();
                $endValues = array();
                $controlIntervalXValues = array();
                $controlIntervalSeries = array();
                foreach($dataset->controlStartVector as $i => $v)
                {
                    $v2 = $dataset->controlEndVector[$i];
                    $controlIntervalXValues[] = date('Y-m-d H:i:s', $dataset->timeVector[$i]);
                    $startValues[] = $v?(double)$v:null;
                    if (!is_null($v) && !is_null($v2)) {
                        $endValues[] = $v2 - $v1;
                    }
                    else {
                        $endValues[] = null;
                    }
                    $controlIntervalSeries[] = array(
                        'x' => $dataset->timeVector[$i],
                        'y' => $v2 - $v1
                    );
                }

                $control_interval_start = array_merge($trace, array(
                    'name' => 'Control Band Start',
                    'zIndex' => 0,
                    'fill' => 'toself',
                    'fillcolor'=> '#ffffff',
                    'line' => array(
                        'color' => '#ffffff'
                    ),
                    'marker' => array(
                        'color' => '#ffffff'
                    ),
                    'type' => 'area',
                    'yaxis' => "y{$yAxis['index']}",
                    'mode' => 'none',
                    'hoverinfo' => 'skip',
                    'hovertemplate' => '',
                    'showlegend' => false,
                    'x' => $this->_swapXY ? $startValues : $controlIntervalXValues,
                    'y' => $this->_swapXY ? $controlIntervalXValues : $startValues
                ));

                $this->_chart['data'][] = $control_interval_start;

                if ($this->_swapXY) {
                    $control_interval_start['xaxis'] = "x{$yIndex}";
                    $control_interval_start['fill'] = 'tozerox';
                    unset($control_interval_start['yaxis']);
                }
                $controlColor = self::IN_CONTROL_COLOR; 
                $control_interval_end = array_merge($trace, array(
                    'name' => 'Control Band',
                    'zIndex' => 0,
                    'fill' => 'tonexty',
                    'fillcolor' => self::IN_CONTROL_COLOR,
                    'type' => 'area',
                    'hovertext' => $controlIntervalXValues,
                    'customdata' => $startValues,
                    'yaxis' => "y{$yAxis['index']}",
                    'mode' => 'none',
                    'legendrank' => 1004,
                    'hovertemplate' => 'Control Band: <b>%{y} - %{customdata}</b> <extra></extra>', 
                    'x' => $this->_swapXY ? $endValues : $controlIntervalXValues,
                    'y' => $this->_swapXY ? $controlIntervalXValues : $endValues,
                    'seriesData' => $controlInvervalSeries
                ));

                if ($this->_swapXY) {
                    $control_interval_end['xaxis'] = "x{$yIndex}";
                    $control_interval_end['fill'] = 'tonextx';
                    $control_interval_end['hovertemplate'] = 'Control Band: <b>%{x} - %{customdata}</b> <extra></extra>';
                    unset($control_interval_end['yaxis']);
                }

                $this->_chart['data'][] = $control_interval_end;
            }

            if($showControlZones) {
                $times = $dataset->timeVector;
                $controlStatus = $dataset->controlStatus;

                for($i=0; $i<count($controlStatus); $i++) {
                    if($controlStatus[$i]==='under_performing') {
                        $i0=$i;
                        while($i<count($controlStatus)&&$controlStatus[$i]==='under_performing') {
                            $i++;
                        }
                        $i1=$i;
                        if($i1>=count($controlStatus)) {
                            $i1=count($controlStatus)-1;
                        }

                        $t0=$times[$i0];
                        $t1=$times[$i1];
                        if($i0!==0) {
                            $t0-=0.5*($times[$i0]-$times[$i0-1]);
                        }
                        else {
                            $t0-=12*60*60;
                        }
                        if($i1!==count($controlStatus)-1 && count($controlStatus)>1) {
                            $t1+=0.5*($times[$i1+1]-$times[$i1]);
                        }
                        else {
                            $t1+=12*60*60;
                        }

                        if($i0!=$i1) {
                            $this->_chart['layout']['shapes'][] = array(
                                'name' => 'Out of Control',
                                'fillcolor' => self::UNDERPERFORMING_COLOR,
                                'layer' => 'below',
                                'line' => array(
                                    'width' => 0
                                ),
                                'type' => 'rect',
                                'xref' => $this->_swapXY ? 'paper' : 'x',
                                'yref' => $this->_swapXY ? 'y' : 'paper',
                                'x0' => $this->_swapXY ? 0 : date('Y-m-d H:i:s', $t0),
                                'x1' => $this->_swapXY ? 1 : date('Y-m-d H:i:s', $t1),
                                'y0' => $this->_swapXY ? date('Y-m-d H:i:s', $t0) : 0,
                                'y1' => $this->_swapXY ? date('Y-m-d H:i:s', $t1) : 1,
                           );
                        }
                    }
                    if($i>=count($controlStatus)) {
                        break;
                    }

                    if($controlStatus[$i]==='over_performing') {
                        $i0=$i;
                        while($i<count($controlStatus)&&$controlStatus[$i]==='over_performing'){
                            $i++;
                        }
                        $i1=$i;
                        if($i1>=count($controlStatus)) {
                            $i1=count($controlStatus)-1;
                        }

                        $t0=$times[$i0];
                        $t1=$times[$i1];
                        if($i0!==0) {
                            $t0-=0.5*($times[$i0]-$times[$i0-1]);
                        } else {
                            $t0-=12*60*60;
                        }
                        if($i1!==count($controlStatus)-1 && count($controlStatus)>1) {
                            $t1+=0.5*($times[$i1+1]-$times[$i1]);
                        } else {
                            $t1+=12*60*60;
                        }

                        if($i0!=$i1) {
                            $this->_chart['layout']['shapes'][] = array(
                                'name' => 'Better than Control',
                                'fillcolor' => self::OVERPERFORMING_COLOR,
                                'layer' => 'below',
                                'line' => array(
                                    'width' => 0
                                ),
                                'type' => 'rect',
                                'xref' => $this->_swapXY ? 'paper' : 'x',
                                'yref' => $this->_swapXY ? 'y' : 'paper',
                                'x0' => $this->_swapXY ? 0 : date('Y-m-d H:i:s', $t0),
                                'x1' => $this->_swapXY ? 1 : date('Y-m-d H:i:s', $t1),
                                'y0' => $this->_swapXY ? date('Y-m-d H:i:s', $t0) : 0,
                                'y1' => $this->_swapXY ? date('Y-m-d H:i:s', $t1) : 1,
                            );
                        }
                    }
                    if($i>=count($controlStatus)) {
                        break;
                    }
                    if($controlStatus[$i]==='control_region_time_interval') {
                        $i0=$i;
                        while($i<count($controlStatus)&&$controlStatus[$i]==='control_region_time_interval') {
                            $i++;
                        }
                        $i1=$i;
                        if($i1>=count($controlStatus)) {
                            $i1=count($controlStatus)-1;
                        }

                        $t0=$times[$i0];
                        $t1=$times[$i1];
                        if($i0!==0) {
                            $t0-=0.5*($times[$i0]-$times[$i0-1]);
                        } else {
                            $t0-=12*60*60;
                        }
                        if($i1!==count($controlStatus)-1 && count($controlStatus)>1) {
                            $t1+=0.5*($times[$i1+1]-$times[$i1]);
                        } else {
                            $t1+=12*60*60;
                        }

                        if($i0!=$i1) {
                            $this->_chart['layout']['shapes'][] = array(
                                'name' => 'Control Region Time Interval',
                                'fillcolor' => self::CONTROL_REGION_TIME_INTERVAL_COLOR,
                                'layer' => 'below',
                                'line' => array(
                                    'width' => 0
                                ),
                                'type' => 'rect',
                                'xref' => $this->_swapXY ? 'paper' : 'x',
                                'yref' => $this->_swapXY ? 'y' : 'paper',
                                'x0' => $this->_swapXY ? 0 : date('Y-m-d H:i:s', $t0),
                                'x1' => $this->_swapXY ? 1 : date('Y-m-d H:i:s', $t1),
                                'y0' => $this->_swapXY ? date('Y-m-d H:i:s', $t0) : 0,
                                'y1' => $this->_swapXY ? date('Y-m-d H:i:s', $t1) : 1,
                            );
                        }
                    }
                    if($i>=count($controlStatus)) {
                        break;
                    }
                }
                if(!isset($this->outOfControlInLegend) ) {
                    $outOfControlXValues = array();
                    $outOfControlYValues = array(); 
                    foreach($dataset->versionVector as $i => $v)
                    {
                        $outOfControlXValues[] = date('Y-m-d H:i:s', $dataset->timeVector[$i]);
                        $outOfControlYValues[] = null;
                    }

                    $ooc_trace = array(
                            'name' => 'Out of Control',
                            'yaxis' => "y{$yAxis['index']}",
                            'type' => 'bar',
                            'line' => array(
                                'width' => 0
                            ),
                            'marker' => array(
                                'color' => self::UNDERPERFORMING_COLOR
                            ),
                            'showlegend' => !isset($this->outOfControlInLegend),
                            'legendrank' => 1005,
                            'x' => $this->_swapXY ? $outOfControlYValues : $outOfControlXValues,
                            'y' => $this->_swapXY ? $outOfControlXValues : $outOfControlYValues,
                    );
                    if ($this->_swapXY) {
                        $ooc_trace['xaxis'] = "x{$yIndex}";
                        unset($ooc_trace['yaxis']);
                    }
  
                    $this->outOfControlInLegend = true;
                    $this->_chart['data'][] = $ooc_trace;
                }
                if(!isset($this->betterThanControlInLegend) ) {
                    $betterThanControlXValues = array();
                    $betterThanControlYValues = array(); 
                    foreach($dataset->versionVector as $i => $v)
                    {
                        $betterThanControlXValues[] = date('Y-m-d H:i:s', $dataset->timeVector[$i]);
                        $betterThanControlYValues[] = null;
                    }

                    $btc_trace = array(
                            'name' => 'Better Than Control',
                            'yaxis' => "y{$yAxis['index']}",
                            'type' => 'bar',
                            'line' => array(
                                'width' => 0
                            ),
                            'marker' => array(
                                'color' => self::OVERPERFORMING_COLOR
                            ),
                            'showlegend' => !isset($this->betterThanControlInLegend),
                            'legendrank' => 1006,
                            'x' => $this->_swapXY ? $betterThanControlYValues : $betterThanControlXValues,
                            'y' => $this->_swapXY ? $betterThanControlXValues : $betterThanControlYValues,
                        );
                    if ($this->_swapXY) {
                        $btc_trace['xaxis'] = "x{$yIndex}";
                        unset($btc_trace['yaxis']);
                    }

                    $this->betterThanControlInLegend = true;
                    $this->_chart['data'][] = $btc_trace;
                }
                if(!isset($this->controlRegionTimeIntervalInLegend) ) {
                    $controlRegionTimeIntervalXValues = array();
                    $controlRegionTimeIntervalYValues = array(); 
                    foreach($dataset->versionVector as $i => $v)
                    {
                        $controlRegionTimeIntervalXValues[] = date('Y-m-d H:i:s', $dataset->timeVector[$i]);
                        $controlRegionTimeIntervalYValues[] = null;
                    }

                    $crti_trace = array(
                            'name' => 'Control Region Time Interval',
                            'yaxis' => "y{$yAxis['index']}",
                            'type' => 'bar',
                            'line' => array(
                                'width' => 0
                            ),
                            'marker' => array(
                                'color' => self::CONTROL_REGION_TIME_INTERVAL_COLOR
                            ),
                            'showlegend' => !isset($this->controlRegionTimeIntervalInLegend),
                            'legendrank' => 1007,
                            'x' => $this->_swapXY ? $controlRegionTimeIntervalYValues : $controlRegionTimeIntervalXValues,
                            'y' => $this->_swapXY ? $controlRegionTimeIntervalXValues : $controlRegionTimeIntervalYValues,
                        );
                    if ($this->_swapXY) {
                        $crti_trace['xaxis'] = "x{$yIndex}";
                        unset($crti_trace['yaxis']);
                    }

                    $this->controlRegionTimeIntervalInLegend = true;
                    $this->_chart['data'][] = $crti_trace;
                }
            }
            $this->_datasetCount++;
        }
        $this->setDataSource(array('XDMoD App Kernels'));
    }

    public static function getAppKernelColor($cores)
    {
        $colors = array(
            1 => 0xf400ff,
            2 => 0x000fff,
            4 => 0xaaa222,
            8 => 0xff0000,
            16 => 0x11aa11,
            32 => 0x0070ff,
            64 => 0xC000FF,
            128 =>0x0f0f0f
        );

        if(isset($colors[$cores]) ) {
            return $colors[$cores];
        }
        else
        {
            return 0x000000;
        }
    }

    public static function getAppKernelSymbol($cores)
    {
        $colors = array(
            1 => 'circle',
            2 => 'square',
            4 => 'diamond',
            8 => 'triangle-up',
            16 => 'triangle-down',
            32 => 'circle',
            64 => 'square',
            128 =>'diamond'
        );

        if(isset($colors[$cores]) ) {
            return $colors[$cores];
        }
        else
        {
            return null;
        }
    }

    public function getRawImage($format = 'png', $params = array(), $user = null)
    {
        $returnData = $this->exportJsonStore();

        if($format == 'img_tag') {
            return '<img class="xd-img" alt="'.$this->getTitle().'" width="'.$this->_width*$this->_scale.'" height="'.$this->_height*$this->_scale.'"  class="chart_thumb-img" src="data:image/png;base64,'.base64_encode(\xd_charting\exportChart($returnData['data'][0], $this->_width, $this->_height, $this->_scale, 'png')).'" />';
        }

        return \xd_charting\exportChart($returnData['data'][0], $this->_width, $this->_height, $this->_scale, $format);
    }
}
