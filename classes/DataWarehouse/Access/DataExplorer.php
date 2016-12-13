<?php

namespace DataWarehouse\Access;

class DataExplorer extends Common
{
    public function get_ak_plot($user) 
    {
        $ak_db = new \AppKernel\AppKernelDb();

        $selectedResourceIds = $this->getSelectedResourceIds();
        $selectedProcessingUnits = $this->getSelectedPUCounts();
        $selectedMetrics = $this->getSelectedMetrics();
        $showChangeIndicator = $this->getShowChangeIndicator();
        $format = \DataWarehouse\ExportBuilder::getFormat($this->request, 'png', array('svg', 'png', 'png_inline', 'svg_inline', 'xml', 'csv', 'jsonstore', 'hc_jsonstore'));
        $inline = true;
        if(isset($this->request['inline']))
        {
            $inline = $this->request['inline'] == 'true' || $this->request['inline'] === 'y';
        }	

        list($start_date, $end_date, $start_ts, $end_ts) = $this->checkDateParameters();

        $limit = $this->getLimit();
        $offset = $this->getOffset();

        $show_title = $this->getShowtitle();
        $title = $this->getTitle();

        $width = $this->getWidth();
        $height = $this->getHeight();
        $scale = $this->getScale();
        $swap_xy = $this->getSwapXY();
        $legend_location = $this->getLegendLocation();

        $font_size = $this->getFontSize();

        $datasets = array();

        foreach($selectedMetrics as $metric)
        {
            foreach($selectedResourceIds as $resourceId)
            {

                if(preg_match('/ak_(?P<ak>\d+)_metric_(?P<metric>\d+)_(?P<pu>\d+)/', $metric, $matches))
                {
                    $akId = $matches['ak'];
                    $metricId = $matches['metric'];
                    $puCount = $matches['pu'];

                    if(count($selectedProcessingUnits) == 0 || in_array($puCount, $selectedProcessingUnits))
                    {
                        $datasetList = $ak_db->getDataset($akId,
                            $resourceId,
                            $metricId,
                            $puCount,
                            $start_ts, 
                            $end_ts, 
                            false,
                            false,
                            true, 
                            false);

                        foreach($datasetList as $result)
                        {				
                            $datasets[] = $result;
                        }
                    }

                }
                elseif(preg_match('/ak_(?P<ak>\d+)_metric_(?P<metric>\d+)/', $metric, $matches))
                {
                    $akId = $matches['ak'];
                    $metricId = $matches['metric'];	

                    if(count($selectedProcessingUnits) == 0)
                    {
                        $pus = $ak_db->getProcessingUnits($start_date,$end_date,  
                            $selectedResourceIds, $selectedMetrics);
                        foreach($pus as $pu)
                        {
                            $selectedProcessingUnits[] = $pu->count;
                        }
                    }

                    foreach($selectedProcessingUnits as $puCount)
                    {
                        $datasetList = $ak_db->getDataset($akId,
                            $resourceId,
                            $metricId,
                            $puCount,
                            $start_ts, 
                            $end_ts, 
                            false,
                            false,
                            true, 
                            false);

                        foreach($datasetList as $result)
                        {
                            $datasets[] = $result;
                        }
                    }

                }

            }
        }

        $filename_kernels = array();
        $filename_resources = array();
        $filename_metrics = array();
        foreach($datasets as $result)
        {
            $filename_kernels[$result->akName] = $result->akName;
            $filename_resources[$result->resourceName] = $result->resourceName;
            $filename_metrics[$result->metric] = $result->metric;
        }
        $filename = 'data_explorer_'.$start_date.'_to_'.$end_date.'_'.implode('_',$filename_resources).'_'.implode('_',$filename_kernels).'_'.implode('_',$filename_metrics);

        $filename = substr($filename,0,100);
        if($format === 'hc_jsonstore' || $format === 'png' || $format === 'svg' || $format === 'png_inline' || $format === 'svg_inline')
        {
            $hc = new \DataWarehouse\Visualization\HighChartAppKernel($start_date, $end_date, $scale, $width, $height, $swap_xy);
			$title=$title?$title:implode(', ',$filename_resources).'; '.implode(', ',$filename_kernels).'; '.implode(', ',$filename_metrics);
            $hc->setTitle($show_title?($title):NULL, $font_size);
            $hc->setLegend($legend_location, $font_size);//called before and after
            $hc->configure($datasets,						
                $font_size,
                $limit,
                $offset,
                $format === 'svg',
                false,
                false,
                true,
                $showChangeIndicator
            );
            $hc->setLegend($legend_location, $font_size);

            $message = NULL;

            if(count($selectedMetrics) < 1)
            {
                $message = "<- Select a metric from the left";
            } else
                if(count($selectedResourceIds) < 1) 
                {
                    $message = "<- Select a resource from the left";
                }


            if($message !== NULL)
            {
                $hc->setTitle($message);
            }
            $returnData = $hc->exportJsonStore();


            $requestDescripter = new \User\Elements\RequestDescripter($this->request);
            $chartIdentifier = $requestDescripter->__toString();
            $chartPool = new \XDChartPool($user);
            $includedInReport = $chartPool->chartExistsInQueue($chartIdentifier, $title);

            $returnData['data'][0]['reportGeneratorMeta'] = array(
                'chart_args' => $chartIdentifier,
                'title' => $title,
                'params_title' => $hc->getSubtitleText(),
                'start_date' => $start_date,
                'end_date' => $end_date,
                'included_in_report' => $includedInReport?'y':'n'
            );

            return $this->exportImage($returnData, $width, $height, $scale, $format, $filename);
        }
        else if($format === 'csv' || $format === 'xml')
        {
            $exportedDatas = array();
            foreach($datasets as $result)
            {
                $exportedDatas[] = $result->export();
            }

            return \DataWarehouse\ExportBuilder::export($exportedDatas, $format, $inline, $filename);
        }

        throw \Exception("Internal Error");
    }

    private function getSelectedResourceIds()
    {
        return isset($this->request['selectedResourceIds']) && 
            $this->request['selectedResourceIds'] != '' ? explode(',',$this->request['selectedResourceIds']):array();
    }

    private function getSelectedPUCounts()
    {
        return isset($this->request['selectedPUCounts']) && 
            $this->request['selectedPUCounts'] != '' ? explode(',',$this->request['selectedPUCounts']):array();
    }

    private function getSelectedMetrics()
    {
        return isset($this->request['selectedMetrics']) && 
            $this->request['selectedMetrics'] != '' ? explode(',',$this->request['selectedMetrics']):array();
    }

    private function getShowChangeIndicator()
    {
        return  ( isset($this->request['show_change_indicator']) 
            ? $this->request['show_change_indicator'] === 'y' : false );
    }
}

?>
