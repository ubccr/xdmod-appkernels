<?php
use PHPUnit\Framework\TestCase;

require_once "AppKernel/InstanceData.php";
use AppKernel\ProcessingUnit;

final class AppKernelDbTest extends TestCase
{
    public function processingUnitProvider()
    {
        $proc_unit_node_1to32 = [
            new ProcessingUnit("node", "1"),
            new ProcessingUnit("node", "2"),
            new ProcessingUnit("node", "4"),
            new ProcessingUnit("node", "8"),
            new ProcessingUnit("node", "16"),
            new ProcessingUnit("node", "32"),
            ];
        $proc_unit_core_8to128 = [
            new ProcessingUnit("core", "8"),
            new ProcessingUnit("core", "16"),
            new ProcessingUnit("core", "32"),
            new ProcessingUnit("core", "64"),
            new ProcessingUnit("core", "128"),
        ];
        $proc_unit_node_1to8 = array_slice($proc_unit_node_1to32, 0,4);
        $proc_unit_node_1to4 = array_slice($proc_unit_node_1to32, 0,3);
        $proc_unit_node_2to8 = array_slice($proc_unit_node_1to32, 1,3);
        $proc_unit_node_2to4 = array_slice($proc_unit_node_1to32, 1,2);
        $proc_unit_node_1to16 = array_slice($proc_unit_node_1to32, 0,5);
        $proc_unit_node_2to16 = array_slice($proc_unit_node_1to32, 1,4);

        return [
            [null, null, [], [], array_merge($proc_unit_node_1to32,$proc_unit_core_8to128)],
            ["2020-04-01", null, [], [], $proc_unit_node_1to16],
            ["2020-04-01", "2020-05-01", [], [], $proc_unit_node_1to16],
            ["2020-04-01", "2020-05-01", [28], [], $proc_unit_node_1to8],
            ["2020-04-01", "2020-05-01", [288], [], []], // wrong resource id
            ["2020-04-01", "2020-05-01", [28], ['ak_23_metric_4'], $proc_unit_node_1to4],
            ["2020-04-01", "2020-05-01", [28], ['ak_7_metric_4'], $proc_unit_node_2to8],
            ["2020-04-01", "2020-05-01", [28,0], ['ak_23_metric_4'], $proc_unit_node_1to4],
            ["2020-04-01", "2020-05-01", [28], ['ak_7_metric_4', 'ak_7_metric_4'], $proc_unit_node_2to8],

        ];
    }
    /**
     * @dataProvider processingUnitProvider
     */
    public function testProcessingUnit($start_date, $end_date , array $resource_ids, array $metrics, $expected)
    {
        $ak_db = new \AppKernel\AppKernelDb();
        $proc_unit =  $ak_db->getProcessingUnits($start_date, $end_date, $resource_ids, $metrics);
        #var_dump($proc_unit);
        $this->assertEquals($expected, $proc_unit);
        if(sizeof($proc_unit)>0) {
            $this->assertInstanceOf('AppKernel\ProcessingUnit', $proc_unit[0]);
        }
    }
}
