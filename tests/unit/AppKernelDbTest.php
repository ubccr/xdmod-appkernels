<?php


use PHPUnit\Framework\TestCase;

require_once "AppKernel/InstanceData.php";

use AppKernel\ProcessingUnit;
use AppKernel\AppKernelDefinition;
use AppKernel\InstanceMetric;
use AppKernel\InstanceData;

function create_instance_data($db_ak_id, $deployment_num_proc_units, $deployment_time, $status)
{
    $ak = new InstanceData;
    $ak->db_ak_id = $db_ak_id;
    $ak->deployment_num_proc_units = strtotime($deployment_num_proc_units);
    $ak->deployment_time = $deployment_time;
    $ak->status = $status;
    return $ak;
}

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
        $proc_unit_node_1to8 = array_slice($proc_unit_node_1to32, 0, 4);
        $proc_unit_node_1to4 = array_slice($proc_unit_node_1to32, 0, 3);
        $proc_unit_node_2to8 = array_slice($proc_unit_node_1to32, 1, 3);
        $proc_unit_node_2to4 = array_slice($proc_unit_node_1to32, 1, 2);
        $proc_unit_node_1to16 = array_slice($proc_unit_node_1to32, 0, 5);
        $proc_unit_node_2to16 = array_slice($proc_unit_node_1to32, 1, 4);

        return [
            [null, null, [], [], array_merge($proc_unit_node_1to32, $proc_unit_core_8to128)],
            ["2020-04-01", null, [], [], $proc_unit_node_1to16],
            ["2020-04-01", "2020-05-01", [], [], $proc_unit_node_1to16],
            ["2020-04-01", "2020-05-01", [28], [], $proc_unit_node_1to8],
            ["2020-04-01", "2020-05-01", [288], [], []], // wrong resource id
            ["2020-04-01", "2020-05-01", [28], ['ak_23_metric_4'], $proc_unit_node_1to4],
            ["2020-04-01", "2020-05-01", [28], ['ak_7_metric_4'], $proc_unit_node_2to8],
            ["2020-04-01", "2020-05-01", [28, 0], ['ak_23_metric_4'], $proc_unit_node_1to4],
            ["2020-04-01", "2020-05-01", [28], ['ak_7_metric_4', 'ak_7_metric_4'], $proc_unit_node_2to8],

        ];
    }

    /**
     * @dataProvider processingUnitProvider
     */
    public function testProcessingUnit($start_date, $end_date, array $resource_ids, array $metrics, $expected)
    {
        $ak_db = new \AppKernel\AppKernelDb();
        $proc_unit = $ak_db->getProcessingUnits($start_date, $end_date, $resource_ids, $metrics);
        #var_dump($proc_unit);
        $this->assertEquals($expected, $proc_unit);
        if (sizeof($proc_unit) > 0) {
            $this->assertInstanceOf('AppKernel\ProcessingUnit', $proc_unit[0]);
        }
    }

    public function getUniqueAppKernelsProvider()
    {
        $akd_list = [
            29 => new AppKernelDefinition(29, "Enzo", "enzo", null, "node", true, true, 1537524242, 1589170859),
            22 => new AppKernelDefinition(22, "GAMESS", "gamess", null, "node", true, true, 1537530111, 1589162281),
            28 => new AppKernelDefinition(28, "Graph500", "graph500", null, "node", true, true, 1537523226, 1589169778),
            25 => new AppKernelDefinition(25, "HPCC", "hpcc", null, "node", true, true, 1537271498, 1589170329),
            7 => new AppKernelDefinition(7, "IMB", "imb", null, "node", true, true, 1537294454, 1589171649),
            23 => new AppKernelDefinition(23, "NAMD", "namd", null, "node", true, true, 1536972644, 1589149529),
            24 => new AppKernelDefinition(24, "NWChem", "nwchem", null, "node", true, true, 1537537457, 1589169723),
        ];
        return [
            [[], [], [], 27, null],
            [[28, 1], [], [], null, $akd_list],
            [[28], [], [], null, $akd_list],
            [[28], [1], [], null, [
                29 => new AppKernelDefinition(29, "Enzo", "enzo", null, "node", true, true, 1537780756, 1589170859),
                22 => new AppKernelDefinition(22, "GAMESS", "gamess", null, "node", true, true, 1537530111, 1589162281),
                28 => new AppKernelDefinition(28, "Graph500", "graph500", null, "node", true, true, 1537780749, 1589167423),
                25 => new AppKernelDefinition(25, "HPCC", "hpcc", null, "node", true, true, 1537286604, 1589159946),
                23 => new AppKernelDefinition(23, "NAMD", "namd", null, "node", true, true, 1537179935, 1589149529),
                24 => new AppKernelDefinition(24, "NWChem", "nwchem", null, "node", true, true, 1537537457, 1589169723),
            ]],
            [[28], [1, 2], [], null, [
                29 => new AppKernelDefinition(29, "Enzo", "enzo", null, "node", true, true, 1537524242, 1589170859),
                22 => new AppKernelDefinition(22, "GAMESS", "gamess", null, "node", true, true, 1537530111, 1589162281),
                28 => new AppKernelDefinition(28, "Graph500", "graph500", null, "node", true, true, 1537523226, 1589167423),
                25 => new AppKernelDefinition(25, "HPCC", "hpcc", null, "node", true, true, 1537271498, 1589159946),
                7 => new AppKernelDefinition(7, "IMB", "imb", null, "node", true, true, 1537294454, 1589169470),
                23 => new AppKernelDefinition(23, "NAMD", "namd", null, "node", true, true, 1536972644, 1589149529),
                24 => new AppKernelDefinition(24, "NWChem", "nwchem", null, "node", true, true, 1537537457, 1589169723),
            ]],
        ];
    }

    /**
     * @dataProvider getUniqueAppKernelsProvider
     */
    public function testGetUniqueAppKernels($resource_ids, $node_counts, $core_counts, $n_expected = null, $expected = null)
    {
        $ak_db = new \AppKernel\AppKernelDb();
        $actual = $ak_db->getUniqueAppKernels($resource_ids, $node_counts, $core_counts);

        if ($n_expected === null && $expected !== null) {
            $n_expected = count($expected);
        }

        if ($n_expected !== null) {
            $this->assertEquals($n_expected, count($actual));
        }
        if (sizeof($actual) > 0) {
            $this->assertInstanceOf('AppKernel\AppKernelDefinition', $actual[array_keys($actual)[0]]);
        }

        if ($expected !== null) {
            foreach (array_keys($expected) as $key) {
                $this->assertSame($expected[$key]->id, $actual[$key]->id);
                $this->assertSame($expected[$key]->name, $actual[$key]->name);
                $this->assertSame($expected[$key]->basename, $actual[$key]->basename);
                $this->assertSame($expected[$key]->processor_unit, $actual[$key]->processor_unit);
                $this->assertSame($expected[$key]->enabled, $actual[$key]->enabled);
                $this->assertSame($expected[$key]->visible, $actual[$key]->visible);
                $this->assertSame($expected[$key]->start_ts, $actual[$key]->start_ts);
                $this->assertSame($expected[$key]->end_ts, $actual[$key]->end_ts);
            }
        }
    }

    public function getMetricsProvider()
    {
        $inst1 = [
            317 => new InstanceMetric("App kernel executable exists", null, "", 317),
            318 => new InstanceMetric("App kernel input exists", null, "", 318),
            76 => new InstanceMetric("Average Double-Precision General Matrix Multiplication (DGEMM) Floating-Point Performance", null, "MFLOP per Second", 76),
            77 => new InstanceMetric("Average STREAM 'Add' Memory Bandwidth", null, "MByte per Second", 77),
        ];
        return [
            [25, null, null, [], [], 20, $inst1],
            [25, null, null, [28], [], 20, $inst1],
            [25, null, null, [28, 1], [], 20, $inst1],
            [25, null, null, [28, 1], [1], 20, $inst1],
            [25, null, null, [28, 1], [1, 2], 20, $inst1],
            [25, "2020-04-01", null, [], [], 20, $inst1],
            [25, "2020-04-01", null, [28], [], 20, $inst1],
            [25, "2020-04-01", null, [28, 1], [], 20, $inst1],
            [25, "2020-04-01", null, [28, 1], [1], 20, $inst1],
            [25, "2020-04-01", null, [28, 1], [1, 2], 20, $inst1],
            [25, "2020-04-01", "2020-05-01", [], [], 20, $inst1],
            [25, "2020-04-01", "2020-05-01", [28], [], 20, $inst1],
            [25, "2020-04-01", "2020-05-01", [28, 1], [], 20, $inst1],
            [25, "2020-04-01", "2020-05-01", [28, 1], [1], 20, $inst1],
            [25, "2020-04-01", "2020-05-01", [28, 1], [1, 2], 20, $inst1],
            [null, null, null, [], [], 320, null],
        ];
    }

    /**
     * @dataProvider getMetricsProvider
     */
    public function testGetMetrics($ak_def_id, $start_date, $end_date, $resource_ids, $pu_counts, $n_expected = null, $expected = null)
    {
        $ak_db = new \AppKernel\AppKernelDb();
        $actual = $ak_db->getMetrics($ak_def_id, $start_date, $end_date, $resource_ids, $pu_counts);

        if ($n_expected === null && $expected !== null) {
            $n_expected = count($expected);
        }

        if ($n_expected !== null) {
            $this->assertEquals($n_expected, count($actual));
        }

        if ($expected === null && $n_expected === null) {
            print(count($actual) . "\n");
            print("[\n");
            foreach ($actual as $key => $val) {
                print("$key => new InstanceMetric(\"$val->name\", null, \"$val->unit\", $val->id),\n");
            }
            print("]\n");
        }

        if ($expected !== null) {
            foreach (array_keys($expected) as $key) {
                $this->assertEquals($expected[$key], $actual[$key]);
            }
        }
    }

    public function loadAppKernelInstancesProvider()
    {

        return [
            [null, null, 821, [
                create_instance_data(90, 8, "2020-05-01 16:49:14", "success"),
                create_instance_data(59, 8, "2020-05-01 16:40:38", "success"),
                create_instance_data(80, 1, "2020-05-01 11:57:11", "success"),
                create_instance_data(103, 1, "2020-05-01 11:52:35", "success"),
                create_instance_data(104, 2, "2020-05-01 11:28:52", "success"),
                create_instance_data(75, 2, "2020-05-01 11:21:48", "failure")
            ]],
            [23, null, 105, [
                create_instance_data(80, 1, "2020-05-01 11:57:11", "success"),
                create_instance_data(80, 1, "2020-05-01 10:59:01", "success"),
                create_instance_data(81, 2, "2020-05-01 10:57:03", "success"),
                create_instance_data(82, 4, "2020-05-01 10:56:56", "success"),
            ]],
            [23,28,45, [
                create_instance_data(80, 1, "2020-05-01 11:57:11", "success"),
                create_instance_data(81, 2, "2020-05-01 10:57:03", "success"),
                create_instance_data(82, 4, "2020-05-01 10:56:56", "success"),
                create_instance_data(82, 4, "2020-05-01 10:34:33", "success")
            ]],
            [23001,null, 0, null]
        ];
    }

    /**
     * @dataProvider loadAppKernelInstancesProvider
     */
    public function testLoadAppKernelInstances($ak_def_id, $resource_id, $n_expected = null, $expected = null)
    {
        $ak_db = new \AppKernel\AppKernelDb();
        $actual = $ak_db->loadAppKernelInstances($ak_def_id, $resource_id);

        if ($expected === null && $n_expected === null) {
            print(count($actual) . ", [\n");
            foreach (array_slice($actual,0,min(10,count($actual))) as $val) {
                print("create_instance_data($val->db_ak_id, $val->deployment_num_proc_units, \"$val->deployment_time\", \"$val->status\"),\n");
            }
            print("]\n");
        }

        if ($n_expected === null && $expected !== null) {
            $n_expected = count($expected);
        }

        if ($n_expected !== null) {
            $this->assertEquals($n_expected, count($actual));
        }
        if (sizeof($actual) > 0) {
            $this->assertInstanceOf('AppKernel\InstanceData', $actual[array_keys($actual)[0]]);
        }

        if ($expected !== null) {
            foreach (array_keys($expected) as $key) {
                $this->assertSame($expected[$key]->db_ak_id, $actual[$key]->db_ak_id);
                $this->assertSame($expected[$key]->deployment_num_proc_units, $actual[$key]->deployment_num_proc_units);
                $this->assertSame($expected[$key]->deployment_time, $actual[$key]->deployment_time);
                $this->assertSame($expected[$key]->status, $actual[$key]->status);
            }
        }
    }
}
