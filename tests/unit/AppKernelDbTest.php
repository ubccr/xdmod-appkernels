<?php

namespace AppKernelTest;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . "/../../classes/AppKernel/AppKernelDb.php";

use AppKernel\ProcessingUnit;
use AppKernel\AppKernelDefinition;
use AppKernel\InstanceMetric;
use AppKernel\InstanceData;

function create_instance_data($ak_name, $deployment_num_proc_units, $deployment_time, $status)
{
    $ak_db = new \AppKernel\AppKernelDb();
    $db = $ak_db->getDB();

    $db_ak_id = $db->query(
        "SELECT ak_id FROM mod_appkernel.app_kernel WHERE name=\"$ak_name\" AND num_units=$deployment_num_proc_units;"
    )[0]['ak_id'];
    $ak = new InstanceData;
    $ak->db_ak_id = intval($db_ak_id);
    $ak->deployment_num_proc_units = intval($deployment_num_proc_units);
    $ak->deployment_time = intval($deployment_time);
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
        $proc_unit_node_1to8 = array_slice($proc_unit_node_1to32, 0, 4);
        $proc_unit_node_1to4 = array_slice($proc_unit_node_1to32, 0, 3);
        $proc_unit_node_2to8 = array_slice($proc_unit_node_1to32, 1, 3);

        $ak_db = new \AppKernel\AppKernelDb();
        $metrics_id = intval(
            $ak_db->getDB()->query(
                'SELECT metric_id FROM mod_appkernel.metric WHERE name="Wall Clock Time"'
            )[0]['metric_id']
        );

        return [
            [null, null, [], [], $proc_unit_node_1to8],
            ["2020-04-01", null, [], [], $proc_unit_node_1to8],
            ["2020-04-01", "2020-05-01", [], [], $proc_unit_node_1to8],
            ["2020-04-01", "2020-05-01", [28], [], $proc_unit_node_1to8],
            ["2020-04-01", "2020-05-01", [288], [], []], // wrong resource id
            ["2020-04-01", "2020-05-01", [28], ["ak_23_metric_$metrics_id"], $proc_unit_node_1to4],
            ["2020-04-01", "2020-05-01", [28], ["ak_7_metric_$metrics_id"], $proc_unit_node_2to8],
            ["2020-04-01", "2020-05-01", [28, 0], ["ak_23_metric_$metrics_id"], $proc_unit_node_1to4],
            [
                "2020-04-01",
                "2020-05-01",
                [28],
                ["ak_7_metric_$metrics_id", "ak_7_metric_$metrics_id"],
                $proc_unit_node_2to8
            ],

        ];
    }

    /**
     * @dataProvider processingUnitProvider
     */
    public function testProcessingUnit($start_date, $end_date, array $resource_ids, array $metrics, $expected)
    {
        $ak_db = new \AppKernel\AppKernelDb();
        $actual = $ak_db->getProcessingUnits($start_date, $end_date, $resource_ids, $metrics);

        if ($expected === null) {
            print(count($actual) . ", [\n");
            foreach (array_slice($actual, 0, min(10, count($actual))) as $val) {
                print("new ProcessingUnit(\"$val->unit\", \"$val->count\"),\n");
            }
            print("]\n");
        }

        $this->assertEquals($expected, $actual);
        if (sizeof($actual) > 0) {
            $this->assertInstanceOf('AppKernel\ProcessingUnit', $actual[0]);
        }
    }

    public function getUniqueAppKernelsProvider()
    {
        return [
            [[], [], [], 7, null],
            [[28, 1], [], [], 7, null],
            [
                [28],
                [],
                [],
                null,
                [
                    29 => new AppKernelDefinition(29, "Enzo", "enzo", null, "node", true, true, 1585713559, 1588331144),
                    22 => new AppKernelDefinition(
                        22,
                        "GAMESS",
                        "gamess",
                        null,
                        "node",
                        true,
                        true,
                        1585704478,
                        1588329295
                    ),
                    28 => new AppKernelDefinition(
                        28,
                        "Graph500",
                        "graph500",
                        null,
                        "node",
                        true,
                        true,
                        1585707582,
                        1588331351
                    ),
                    25 => new AppKernelDefinition(25, "HPCC", "hpcc", null, "node", true, true, 1585701706, 1588351754),
                    7 => new AppKernelDefinition(7, "IMB", "imb", null, "node", true, true, 1585706634, 1588351238),
                    23 => new AppKernelDefinition(23, "NAMD", "namd", null, "node", true, true, 1585868136, 1588334231),
                    24 => new AppKernelDefinition(
                        24,
                        "NWChem",
                        "nwchem",
                        null,
                        "node",
                        true,
                        true,
                        1585704196,
                        1588329513
                    ),

                ]
            ],
            [
                [28],
                [1],
                [],
                null,
                [
                    29 => new AppKernelDefinition(29, "Enzo", "enzo", null, "node", true, true, 1585714858, 1588330625),
                    22 => new AppKernelDefinition(
                        22,
                        "GAMESS",
                        "gamess",
                        null,
                        "node",
                        true,
                        true,
                        1585704478,
                        1588329295
                    ),
                    28 => new AppKernelDefinition(
                        28,
                        "Graph500",
                        "graph500",
                        null,
                        "node",
                        true,
                        true,
                        1585711421,
                        1588329038
                    ),
                    25 => new AppKernelDefinition(25, "HPCC", "hpcc", null, "node", true, true, 1585703946, 1588328720),
                    23 => new AppKernelDefinition(23, "NAMD", "namd", null, "node", true, true, 1585868136, 1588334231),
                    24 => new AppKernelDefinition(
                        24,
                        "NWChem",
                        "nwchem",
                        null,
                        "node",
                        true,
                        true,
                        1585711919,
                        1588329513
                    ),
                ]
            ],
            [
                [28],
                [1, 2],
                [],
                null,
                [
                    29 => new AppKernelDefinition(29, "Enzo", "enzo", null, "node", true, true, 1585713559, 1588330625),
                    22 => new AppKernelDefinition(
                        22,
                        "GAMESS",
                        "gamess",
                        null,
                        "node",
                        true,
                        true,
                        1585704478,
                        1588329295
                    ),
                    28 => new AppKernelDefinition(
                        28,
                        "Graph500",
                        "graph500",
                        null,
                        "node",
                        true,
                        true,
                        1585707582,
                        1588329038
                    ),
                    25 => new AppKernelDefinition(25, "HPCC", "hpcc", null, "node", true, true, 1585703391, 1588328720),
                    7 => new AppKernelDefinition(7, "IMB", "imb", null, "node", true, true, 1585711665, 1588329286),
                    23 => new AppKernelDefinition(23, "NAMD", "namd", null, "node", true, true, 1585868136, 1588334231),
                    24 => new AppKernelDefinition(
                        24,
                        "NWChem",
                        "nwchem",
                        null,
                        "node",
                        true,
                        true,
                        1585704196,
                        1588329513
                    ),
                ]
            ]
        ];
    }

    /**
     * @dataProvider getUniqueAppKernelsProvider
     */
    public function testGetUniqueAppKernels(
        $resource_ids,
        $node_counts,
        $core_counts,
        $n_expected = null,
        $expected = null
    ) {
        $ak_db = new \AppKernel\AppKernelDb();
        $actual = $ak_db->getUniqueAppKernels($resource_ids, $node_counts, $core_counts);

        if ($expected === null && $n_expected === null) {
            print(count($actual) . ", [\n");
            foreach (array_slice($actual, 0, min(10, count($actual))) as $val) {
                print("$val->id => new AppKernelDefinition($val->id, \"$val->name\", \"$val->basename\"," .
                    " null, \"node\", true, true, $val->start_ts, $val->end_ts),\n");
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
        $ak_db = new \AppKernel\AppKernelDb();
        $db = $ak_db->getDB();
        # Get metric id
        $ak_exec_exists_id = intval(
            $db->query(
                'SELECT metric_id FROM mod_appkernel.metric WHERE name="App kernel executable exists"'
            )[0]['metric_id']
        );
        $ak_input_exists_id = intval(
            $db->query(
                'SELECT metric_id FROM mod_appkernel.metric WHERE name="App kernel input exists"'
            )[0]['metric_id']
        );
        $ak_dgemm_flops_id = intval(
            $db->query(
                'SELECT metric_id FROM mod_appkernel.metric WHERE name="Average Double-Precision General Matrix Multiplication (DGEMM) Floating-Point Performance"'
            )[0]['metric_id']
        );
        $ak_stream_band_id = intval(
            $db->query(
                'SELECT metric_id FROM mod_appkernel.metric WHERE name="Average STREAM \'Add\' Memory Bandwidth"'
            )[0]['metric_id']
        );

        $inst1 = [
            $ak_exec_exists_id => new InstanceMetric("App kernel executable exists", null, "", $ak_exec_exists_id),
            $ak_input_exists_id => new InstanceMetric("App kernel input exists", null, "", $ak_input_exists_id),
            $ak_dgemm_flops_id => new InstanceMetric(
                "Average Double-Precision General Matrix Multiplication (DGEMM) Floating-Point Performance",
                null,
                "MFLOP per Second",
                $ak_dgemm_flops_id
            ),
            $ak_stream_band_id => new InstanceMetric(
                "Average STREAM 'Add' Memory Bandwidth",
                null,
                "MByte per Second",
                $ak_stream_band_id
            ),
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
            [null, null, null, [], [], 79, null],
        ];
    }

    /**
     * @dataProvider getMetricsProvider
     */
    public function testGetMetrics(
        $ak_def_id,
        $start_date,
        $end_date,
        $resource_ids,
        $pu_counts,
        $n_expected = null,
        $expected = null
    ) {
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
            [
                null,
                null,
                461,
                [
                    create_instance_data("hpcc", 8, "1588351754", "success"),
                    create_instance_data("imb", 8, "1588351238", "success"),
                    create_instance_data("namd", 1, "1588334231", "success"),
                    create_instance_data("gamess", 2, "1588332108", "failure"),
                    create_instance_data("graph500", 4, "1588331351", "success"),
                    create_instance_data("enzo", 4, "1588331144", "success"),
                    create_instance_data("namd", 1, "1588330741", "success"),
                    create_instance_data("enzo", 1, "1588330625", "success"),
                    create_instance_data("namd", 2, "1588330623", "success"),
                    create_instance_data("namd", 4, "1588330616", "success"),
                ]
            ],
            [
                23,
                null,
                105,
                [
                    create_instance_data("namd", 1, "1588334231", "success"),
                    create_instance_data("namd", 1, "1588330741", "success"),
                    create_instance_data("namd", 2, "1588330623", "success"),
                    create_instance_data("namd", 4, "1588330616", "success"),
                ]
            ],
            [
                23,
                28,
                45,
                [
                    create_instance_data("namd", 1, "1588334231", "success"),
                    create_instance_data("namd", 2, "1588330623", "success"),
                    create_instance_data("namd", 4, "1588330616", "success"),
                    create_instance_data("namd", 4, "1588329273", "success"),
                ]
            ],
            [23001, null, 0, null]
        ];
    }

    /**
     * @dataProvider loadAppKernelInstancesProvider
     */
    public function testLoadAppKernelInstances($ak_def_id, $resource_id, $n_expected = null, $expected = null)
    {
        $ak_db = new \AppKernel\AppKernelDb();
        $actual = $ak_db->loadAppKernelInstances($ak_def_id, $resource_id);
        $db = $ak_db->getDB();

        if ($expected === null && $n_expected === null) {
            print(count($actual) . ", [\n");
            foreach (array_slice($actual, 0, min(10, count($actual))) as $val) {
                $ak_name = $db->query(
                    "SELECT name FROM mod_appkernel.app_kernel WHERE ak_id=$val->db_ak_id;"
                )[0]['name'];
                print("create_instance_data(\"$ak_name\", $val->deployment_num_proc_units, \"$val->deployment_time\", \"$val->status\"),\n");
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

    public function testNewControlRegions()
    {
        $ak_db = new \AppKernel\AppKernelDb();

        // update initial control for 5 points
        $ak_db->newControlRegions(
            28,
            23,
            'data_points',
            "2020-03-28",
            null,
            5,
            "short initial control region",
            true,
            null
        );
        $ak_db->calculateControls(false, 5, 5, "UBHPC_32core", "namd");

        $control_regions = $ak_db->getControlRegions(28, 23);

        $this->assertSame(1, count($control_regions));
        $this->assertEquals(
            [
                'control_region_def_id' => $control_regions[0]['control_region_def_id'],
                'resource_id' => "28",
                'ak_def_id' => "23",
                'control_region_type' => "data_points",
                'control_region_starts' => "2020-03-28 00:00:00",
                'control_region_ends' => null,
                'control_region_points' => "5",
                'comment' => "short initial control region"
            ],
            $control_regions[0]
        );
    }
}
