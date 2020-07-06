<?php

namespace DataWarehouse\Visualization;

/**
 * Base class for server generated highcharts chart for app kernel data.
 *
 * @author Amin Ghadersohi
 */
class HighChartAppKernel extends HighChart2
{
    const IN_CONTROL_COLOR = '#aaffaa';

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

        $image = HTML_DIR . '/gui/images/exclamation_ak.png';

        $this->_indicator_url = sprintf(
            'url(data:image/png;base64,%s)',
            base64_encode(file_get_contents($image))
        );
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
        $contextMenuOnClick = null
    ) {
        $this->_chart['title']['style'] = array(
            'color'=> '#000000',
            'fontSize' => (16 + $font_size).'px'
        );
        $this->_chart['subtitle']['style'] = array(
            'color'=> '#5078a0',
            'fontSize' => (12 + $font_size).'px'
        );
        $this->_chart['subtitle']['y'] = 30 + $font_size;
        $this->_chart['legend']['itemStyle'] = array(
            'color' => '#274b6d',
            'fontWeight' => 'normal',
            'fontSize' => (12  + $font_size).'px'
        );
        $tooltipConfig = array(
            'xDateFormat' => '%Y/%m/%d %H:%M:%S',
            'shared' => true,
            'crosshairs' => true
        );
        $this->_chart['tooltip'] = $tooltipConfig;

        $this->_chart['legend']['wordWrap'] = false;

        $this->_chart['xAxis'] = array(
            // min and max of datetime xAxis in milliseconds from 1970-01-01
            'type' => 'datetime',
            'min' => strtotime($this->_startDate)*1000,
            // Don't specify a max xAxis value--highcharts is smart enough to plot the whole dataset.
            'labels' => $this->_swapXY ? array(
                'enabled' => true,
                'step' => round(($font_size<0?0:$font_size+5 ) / 11),
                'style' => array(
                    'fontSize' => (11 + $font_size).'px',
                    'marginTop' => $font_size * 2
                )
            )
            : array(
                'enabled' => true,
                'step' => ceil(($font_size<0?0:$font_size+11  ) / 11),
                'style' => array(
                    'fontSize' => (11 + $font_size).'px',
                    'marginTop' => $font_size * 2
                )
            ),
            'minTickInterval' =>  24 * 3600 * 1000,
            'lineWidth' => 1 + $font_size/4,
            'plotBands' => array()
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
                $yAxis = array(
                    'title' => array(
                         'text' => $dataset->metricUnit,
                        'style' => array(
                            'color'=> $yAxisColor,
                            'fontWeight' => 'bold',
                            'fontSize' => (12 + $font_size).'px'
                        )
                    ),
                    'labels' => array(
                        'style' => array(
                            'fontSize' => (11 + $font_size).'px'
                        ),
                        'decimals' => 2
                    ),
                    'opposite' => $this->_axisCount % 2 == 1,
                    'min' => false?null:$yMin,
                                        'max' => false?null:$yMax,
                    'type' => false? 'logarithmic' : 'linear',
                    'showLastLabel' => true,
                    'endOnTick' => true,
                    'index' => $this->_axisCount,
                    'lineWidth' => 1 + $font_size/4,
                    'allowDecimals' => true,
                    'tickInterval' => false ? 1  :null
                );
                $this->_axis[$dataset->metricUnit] = $yAxis;
                                $this->_axis_index[$dataset->metricUnit]=count($this->_chart['yAxis']);
                                $this->_chart['yAxis'][] = $yAxis;
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

                if($this->_axis[$dataset->metricUnit]['min']>$yMin) {
                    $this->_axis[$dataset->metricUnit]['min']=$yMin;
                    $this->_chart['yAxis'][$this->_axis_index[$dataset->metricUnit]]['min']=$yMin;
                }
                if($this->_axis[$dataset->metricUnit]['max']<$yMax) {
                    $this->_axis[$dataset->metricUnit]['max']=$yMax;
                    $this->_chart['yAxis'][$this->_axis_index[$dataset->metricUnit]]['max']=$yMax;
                }
            }
        }
        $controlPivot = -0.5;
        foreach($datasets as $index => $dataset)
        {
            $dataCount = count($dataset->valueVector);
            $yAxis = $this->_axis[$dataset->metricUnit];
            $yAxisColorValue = $colors[$yAxis['index'] % 33];
            $yAxisColor = '#'.str_pad(dechex($yAxisColorValue), 6, '0', STR_PAD_LEFT);

            $color_value =  $colorsPerCore?self::getAppKernelColor($dataset->rawNumProcUnits):  $colors[$this->_datasetCount % 33];
            $color = '#'.str_pad(dechex($color_value), 6, '0', STR_PAD_LEFT);
            $lineColor = '#'.str_pad(dechex(\DataWarehouse\Visualization::alterBrightness($color_value, -70)), 6, '0', STR_PAD_LEFT);

            if($longLegend) {
                $datasetName = '['.$dataset->numProcUnits.': '.$dataset->resourceName.'] <br/>'.$dataset->akName.' '.
                    $dataset->metric.$dataset->note.' [<span style="color:'.$yAxisColor.'">'.$dataset->metricUnit.'</span>]';
            } else {
                if($dataset->note==="") {
                    $datasetName = $dataset->numProcUnits;
                } else {
                    $datasetName = $dataset->numProcUnits . " " . $dataset->note;
                }
            }

            $enableMarkers = $dataCount < 11 || ($dataCount < 31 && $this->_width > \DataWarehouse\Visualization::$thumbnail_width);
            $seriesValues = array();
            foreach($dataset->valueVector as $i => $v)
            {
                $sv = array(
                    'x' => $dataset->timeVector[$i]*1000.0,
                    'y' => (double)$v
                );
                if($v===null) {
                    $sv['y'] = null;
                }
                $sv['marker'] = array();
                if($showChangeIndicator && $dataset->versionVector[$i] > 0) {
                    $sv['marker']['symbol'] = $this->_indicator_url;
                    $sv['marker']['enabled'] = true;
                }else
                {
                    $sv['marker']['enabled'] = $enableMarkers;
                }

                $seriesValues[] = $sv;
            }
            $data_series_desc = array(
                'name' => $datasetName,
                'zIndex' => 10,
                'color'=>  $color,
                'type' => 'line',
                'shadow' => false,
                'groupPadding' => 0.1,
                'pointPadding' => 0,
                'borderWidth' => 0,
                'yAxis' => $yAxis['index'],
                'lineWidth' => 2 + $font_size/4,
                'showInLegend' => true,
                'connectNulls' => true,
                'marker' => array(
                    'lineWidth' => 1,
                    'lineColor' => $lineColor,
                    'radius' => $font_size/4 + 5,
                    'symbol' => self::getAppKernelSymbol($dataset->rawNumProcUnits)
                ),
                'data' => $seriesValues
            );

            if($drillDown&&$contextMenuOnClick===null) {
                $data_series_desc['cursor'] = 'pointer';
                $data_series_desc['rawNumProcUnits'] = $dataset->rawNumProcUnits;
            }

            $this->_chart['series'][] = $data_series_desc;

            $versionSum = array_sum($dataset->versionVector);
            if($showChangeIndicator && $versionSum > 0 && !isset($this->changeIndicatorInLegend) ) {
                $versionValues = array();
                foreach($dataset->versionVector as $i => $v)
                {
                    $versionValues[] = array('x' => $dataset->timeVector[$i]*1000.0, 'y' => null) ;
                }

                $version_series_desc = array(
                    'name' => 'Change Indicator',
                    'yAxis' => $yAxis['index'],
                    'zIndex' => 9,
                    'type' => 'scatter',
                    'tooltip' => array(
                        'enabled' => false
                    ),
                    'marker' => array(
                        'enabled' => true,
                        'symbol' => $this->_indicator_url,
                    ),
                    'showInLegend' => !isset($this->changeIndicatorInLegend),
                    'legendIndex' => 100000,
                    'data' => $versionValues
                );
                $this->changeIndicatorInLegend = true;
                $this->_chart['series'][] = $version_series_desc;
            }

            if($showRunningAverages) {
                $averageValues = array();
                foreach($dataset->runningAverageVector as $i => $v)
                {
                    $sv = array('x' =>  $dataset->timeVector[$i]*1000.0, 'y' => $v?(double)$v:null);
                    $averageValues[] = $sv;
                }

                $aColor = '#'.str_pad(dechex(\DataWarehouse\Visualization::alterBrightness($color, -200)), 6, '0', STR_PAD_LEFT);
                $data_series_desc = array(
                    'name' => 'Running Average',
                    'zIndex' => 8,
                    'color'=>  $aColor,
                    'type' => 'line',
                    'shadow' => false,
                    'dashStyle' => 'Dash',
                    'groupPadding' => 0.1,
                    'pointPadding' => 0,
                    'borderWidth' => 0,
                    'yAxis' => $yAxis['index'],
                    'lineWidth' => 1 + $font_size/4,
                    'showInLegend' => true,
                    'connectNulls' => false,
                    'marker' => array(
                        'enabled' => false
                    ),
                    'data' => $averageValues
                );
                $this->_chart['series'][] = $data_series_desc;
            }

            if($showControls) {
                if(!isset($this->_axis['control'])) {
                    $yAxisControl = array(
                        'title' => array(
                            'text' => 'Control',
                            'style' => array(
                                'fontWeight' => 'bold',
                                'color'=> '#7cb5ec', // hardcoded to match data series. Where does this color come from?
                                'fontSize' => (12 + $font_size).'px'
                            )
                        ),
                        'labels' => array(
                            'style' => array(
                                'fontSize' => (11 + $font_size).'px'
                            ),
                            'decimals' => 2
                        ),
                        'opposite' => $this->_axisCount % 2 == 1,
                        'type' => 'linear',
                        'showLastLabel' => $this->_chart['title']['text'] != '',
                        'endOnTick' => true,
                        'index' => $this->_axisCount,
                        'lineWidth' => 2 + $font_size/4,
                        'allowDecimals' => true,
                        'tickInterval' => false ? 1 : null
                    );
                    $this->_axis['control'] = $yAxisControl;
                    $this->_chart['yAxis'][] = $yAxisControl;
                    $this->_axisCount++;
                }

                $controlVector = array();
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
                    $sv = array('x' =>  $dataset->timeVector[$i]*1000.0, 'y' => (double)$control);
                    $controlVector[] = $sv;
                }

                $data_series_desc = array(
                    'name' => 'Control',
                    'zIndex' => 7,
                    'type' => 'line',
                    'shadow' => false,
                    'dashStyle' => 'ShortDot',
                    'groupPadding' => 0.1,
                    'pointPadding' => 0,
                    'borderWidth' => 0,
                    'yAxis' => $this->_axis['control']['index'],
                    'lineWidth' => 1 + $font_size/4,
                    'showInLegend' => true,
                    'connectNulls' => false,
                    'marker' => array(
                        'enabled' => false
                    ),
                    'data' => $controlVector
                );
                $this->_chart['series'][] = $data_series_desc;
            }
            if($showMinMax && count($dataset->valueLowVector)>0 && count($dataset->valueHighVector)>0) {
                $rangeValues = array();
                foreach($dataset->valueLowVector as $i => $v)
                {
                    $v2 = $dataset->valueHighVector[$i];
                    $sv = array($dataset->timeVector[$i]*1000.0, $v2?(double)$v2:null, $v?(double)$v:null);

                    $rangeValues[] = $sv;
                }

                $data_series_desc = array(
                    'name' => 'MinMax Values',
                    'zIndex' => 6,
                    'color'=>  self::MINMAX_COLOR,
                    'type' => 'areasplinerange',
                    'shadow' => false,
                    'yAxis' => $yAxis['index'],
                    'lineWidth' => 0,
                    'showInLegend' => true,
                    'connectNulls' => false,
                    'marker' => array(
                        'enabled' => false
                    ),
                    'data' => $rangeValues
                );
                $this->_chart['series'][] = $data_series_desc;
            }
            if($showControlInterval) {
                $rangeValues = array();
                foreach($dataset->controlStartVector as $i => $v)
                {
                    $v2 = $dataset->controlEndVector[$i];
                    $sv = array($dataset->timeVector[$i]*1000.0, $v2?(double)$v2:null, $v?(double)$v:null);

                    $rangeValues[] = $sv;
                }

                $data_series_desc = array(
                    'name' => 'Control Band',
                    'zIndex' => 0,
                    'color'=>  self::IN_CONTROL_COLOR,
                    'type' => 'areasplinerange',
                    'shadow' => false,
                    'yAxis' => $yAxis['index'],
                    'lineWidth' => 0,
                    'showInLegend' => true,
                    'connectNulls' => false,
                    'marker' => array(
                        'enabled' => false
                    ),
                    'data' => $rangeValues
                );
                $this->_chart['series'][] = $data_series_desc;
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
                            $this->_chart['xAxis']['plotBands'][] = array(
                                'from' => $t0*1000,
                                'to' => $t1*1000,
                                'color' =>self::UNDERPERFORMING_COLOR
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
                            $this->_chart['xAxis']['plotBands'][] = array(
                                'from' => $t0*1000,
                                'to' => $t1*1000,
                                'color' =>self::OVERPERFORMING_COLOR
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
                            $this->_chart['xAxis']['plotBands'][] = array(
                                'from' => $t0*1000,
                                'to' => $t1*1000,
                                'color' =>self::CONTROL_REGION_TIME_INTERVAL_COLOR
                            );
                        }
                    }
                    if($i>=count($controlStatus)) {
                        break;
                    }
                }

                if(!isset($this->outOfControlInLegend) ) {
                        $versionValues = array();
                    foreach($dataset->versionVector as $i => $v)
                        {
                                    $versionValues[] = array('x' => $dataset->timeVector[$i]*1000.0, 'y' => null) ;
                    }

                        $ooc_series_desc = array(
                                'name' => 'Out of Control',
                                'yAxis' => $yAxis['index'],
                                'type' => 'area',
                                'color' => self::UNDERPERFORMING_COLOR,
                                'showInLegend' => !isset($this->outOfControlInLegend),
                                'legendIndex' => 100000,
                                'data' => $versionValues
                                            );
                                            $this->outOfControlInLegend = true;
                                            $this->_chart['series'][] = $ooc_series_desc;
                }
                if(!isset($this->betterThanControlInLegend) ) {
                        $versionValues = array();
                    foreach($dataset->versionVector as $i => $v)
                        {
                                    $versionValues[] = array(
                                            'x' => $dataset->timeVector[$i]*1000.0,
                                            'y' => null
                                    ) ;
                    }

                        $inc_series_desc = array(
                                'name' => 'Better than Control',
                                'yAxis' => $yAxis['index'],
                                'type' => 'area',
                                'color' => self::OVERPERFORMING_COLOR,
                                'showInLegend' => !isset($this->betterThanControlInLegend),
                                'legendIndex' => 100001,
                                'data' => $versionValues
                                            );
                                            $this->betterThanControlInLegend = true;
                                            $this->_chart['series'][] = $inc_series_desc;
                }
                if(!isset($this->controlRegionTimeIntervalInLegend) ) {
                        $versionValues = array();
                    foreach($dataset->versionVector as $i => $v)
                        {
                                        $versionValues[] = array(
                                                'x' => $dataset->timeVector[$i]*1000.0,
                                                'y' => null
                                        ) ;
                    }

                        $inc_series_desc = array(
                                'name' => 'Control Region Time Interval',
                                'yAxis' => $yAxis['index'],
                                'type' => 'area',
                                'color' => self::CONTROL_REGION_TIME_INTERVAL_COLOR,
                                'showInLegend' => !isset($this->controlRegionTimeIntervalInLegend),
                                'legendIndex' => 100001,
                                'data' => $versionValues
                                            );
                                            $this->controlRegionTimeIntervalInLegend = true;
                                            $this->_chart['series'][] = $inc_series_desc;
                }

            }
            if($contextMenuOnClick!==null) {
                for($i=0; $i<count($this->_chart['series']); $i++) {
                    $this->_chart['series'][$i]['cursor'] = 'pointer';
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
            8 => 'triangle',
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
            return '<img class="xd-img" alt="'.$this->getTitle().'" width="'.$this->_width*$this->_scale.'" height="'.$this->_height*$this->_scale.'"  class="chart_thumb-img" src="data:image/png;base64,'.base64_encode(\xd_charting\exportHighchart($returnData['data'][0], $this->_width, $this->_height, $this->_scale, 'png')).'" />';
        }

        return \xd_charting\exportHighchart($returnData['data'][0], $this->_width, $this->_height, $this->_scale, $format);
    }
}
