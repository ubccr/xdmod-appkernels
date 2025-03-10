<?php

namespace IntegrationTests\REST;

use IntegrationTests\BaseTest;
use IntegrationTests\TestHarness\XdmodTestHelper;
use ReflectionFunction;

class AppKernelControllerProviderTest extends BaseTest
{
    private static $helper;

    public static function setUpBeforeClass()
    {
        self::$helper = new XdmodTestHelper();
    }

    /**
     * @dataProvider provideGetDetails
     */
    public function testGetDetails($id, $role, $input, $output)
    {
        parent::authenticateRequestAndValidateJson(
            self::$helper,
            $role,
            $input,
            $output
        );
    }

    public function provideGetDetails()
    {
        $validInput = [
            'path' => 'rest/app_kernels/details',
            'method' => 'get',
            'params' => [],
            'data' => null
        ];
        // Run some standard endpoint tests.
        return parent::provideRestEndpointTests(
            $validInput,
            [
                'int_params' => [
                    'ak',
                    'resource',
                    'instance_id',
                    'metric',
                    'num_proc_units',
                    'collected'
                ],
                'string_params' => ['status'],
                'bool_params' => ['debug', 'resource_first'],
                'unix_ts_params' => ['start_time', 'end_time']
            ]
        );
    }

    /**
     * @dataProvider provideGetDatasets
     */
    public function testGetDatasets($id, $role, $input, $output)
    {
        parent::authenticateRequestAndValidateJson(
            self::$helper,
            $role,
            $input,
            $output
        );
    }

    public function provideGetDatasets()
    {
        $validInput = [
            'path' => 'rest/app_kernels/datasets',
            'method' => 'get',
            'params' => ['ak' => '0'],
            'data' => null
        ];
        // Run some standard endpoint tests.
        return parent::provideRestEndpointTests(
            $validInput,
            [
                'int_params' => ['ak', 'resource', 'num_proc_units'],
                'string_params' => ['metric', 'format'],
                'bool_params' => ['debug', 'metadata_only', 'inline'],
                'unix_ts_params' => ['start_time', 'end_time']
            ]
        );
    }

    /**
     * @dataProvider provideGetPlots
     */
    public function testGetPlots($id, $role, $input, $output)
    {
        parent::authenticateRequestAndValidateJson(
            self::$helper,
            $role,
            $input,
            $output
        );
    }

    public function provideGetPlots()
    {
        $validInput = [
            'path' => 'rest/app_kernels/plots',
            'method' => 'get',
            'params' => [],
            'data' => null
        ];
        // Run some standard endpoint tests.
        return parent::provideRestEndpointTests(
            $validInput,
            [
                'int_params' => ['limit', 'offset', 'num_proc_units'],
                'float_params' => ['width', 'height', 'scale'],
                'string_params' => [
                    'legend_type',
                    'font_size',
                    'contextMenuOnClick',
                    'format'
                ],
                'bool_params' => [
                    'show_title',
                    'swap_xy',
                    'inline',
                    'show_change_indicator',
                    'show_control_plot',
                    'show_control_zones',
                    'discrete_controls',
                    'show_running_averages',
                    'show_control_interval',
                    'show_num_proc_units_separately'
                ],
                'unix_ts_params' => ['start_time', 'end_time']
            ]
        );
    }

    /**
     * @dataProvider provideGetControlRegions
     */
    public function testGetControlRegions($id, $role, $input, $output)
    {
        parent::authenticateRequestAndValidateJson(
            self::$helper,
            $role,
            $input,
            $output
        );
    }

    public function provideGetControlRegions()
    {
        $validInput = [
            'path' => 'rest/app_kernels/control_regions',
            'method' => 'get',
            'params' => [
                'resource_id' => '0',
                'ak_def_id' => '0'
            ],
            'data' => null
        ];
        // Run some standard endpoint tests.
        return parent::provideRestEndpointTests(
            $validInput,
            ['int_params' => ['resource_id', 'ak_def_id']]
        );
    }

    /**
     * @dataProvider provideUpdateControlRegions
     */
    public function testUpdateControlRegions(
        $id,
        $role,
        $input,
        $output
    ) {
        parent::authenticateRequestAndValidateJson(
            self::$helper,
            $role,
            $input,
            $output
        );
    }

    public function provideUpdateControlRegions()
    {
        $validInput = [
            'path' => 'rest/app_kernels/control_regions',
            'method' => 'post',
            'params' => null,
            'data' => [
                'resource_id' => '0',
                'ak_def_id' => '0',
                'control_region_time_interval_type' => 'foo',
                'startDateTime' => 'foo'
            ]
        ];
        // Run some standard endpoint tests.
        return parent::provideRestEndpointTests(
            $validInput,
            [
                'authentication' => true,
                'authorization' => 'mgr',
                'int_params' => ['resource_id', 'ak_def_id', 'n_points'],
                'string_params' => [
                    'control_region_time_interval_type',
                    'startDateTime',
                    'endDateTime',
                    'comment',
                    'control_region_def_id'
                ]
            ]
        );
    }

    /**
     * @dataProvider provideDeleteControlRegions
     */
    public function testDeleteControlRegions(
        $id,
        $role,
        $input,
        $output
    ) {
        parent::authenticateRequestAndValidateJson(
            self::$helper,
            $role,
            $input,
            $output
        );
    }

    public function provideDeleteControlRegions()
    {
        $validInput = [
            'path' => 'rest/app_kernels/control_regions',
            'method' => 'delete',
            'params' => [
                'resource_id' => '0',
                'ak_def_id' => '0',
                'controlRegiondIDs' => 'foo'
            ],
            'data' => null
        ];
        // Run some standard endpoint tests.
        return parent::provideRestEndpointTests(
            $validInput,
            [
                'authentication' => true,
                'authorization' => 'mgr',
                'int_params' => ['resource_id', 'ak_def_id'],
                'string_params' => ['controlRegiondIDs']
            ]
        );
    }

    /**
     * @dataProvider provideGetNotifications
     */
    public function testGetNotifications(
        $id,
        $role,
        $input,
        $output
    ) {
        parent::authenticateRequestAndValidateJson(
            self::$helper,
            $role,
            $input,
            $output
        );
    }

    public function provideGetNotifications($path)
    {
        return $this->provideNotificationTests(
            'rest/app_kernels/notifications',
            'get',
            'load load_default_notification_settings'
        );
    }

    private function provideNotificationTests($path, $method, $action)
    {
        return [
            [
                'missing_curent_tmp_settings',
                'usr',
                [
                    'path' => $path,
                    'method' => $method,
                    'params' => [],
                    'data' => null
                ],
                parent::validateSuccessResponse([
                    'success' => false,
                    'errorMessage' => (
                        "Can not $action. curent_tmp_settings is a"
                        . ' required parameter.'
                    )
                ])
            ]
        ];
    }

    /**
     * @dataProvider providePutNotifications
     */
    public function testPutNotifications(
        $id,
        $role,
        $input,
        $output
    ) {
        parent::authenticateRequestAndValidateJson(
            self::$helper,
            $role,
            $input,
            $output
        );
    }

    public function providePutNotifications()
    {
        return $this->provideNotificationTests(
            'rest/app_kernels/notifications',
            'put',
            'save notification_settings'
        );
    }

    /**
     * @dataProvider provideGetDefaultNotifications
     */
    public function testGetDefaultNotifications(
        $id,
        $role,
        $input,
        $output
    ) {
        parent::authenticateRequestAndValidateJson(
            self::$helper,
            $role,
            $input,
            $output
        );
    }

    public function provideGetDefaultNotifications()
    {
        return $this->provideNotificationTests(
            'rest/app_kernels/notifications/default',
            'get',
            'load load_default_notification_settings'
        );
    }

    /**
     * @dataProvider provideSendNotification
     */
    public function testSendNotification(
        $id,
        $role,
        $input,
        $output
    ) {
        parent::authenticateRequestAndValidateJson(
            self::$helper,
            $role,
            $input,
            $output
        );
    }

    public function provideSendNotification()
    {
        $validInput = [
            'path' => 'rest/app_kernels/notifications/send',
            'method' => 'get',
            'params' => [
                'report_type' => 'foo',
                'report_param' => 'foo'
            ],
            'data' => null
        ];
        return $this->provideRestEndpointTestsGivenCustomExceptionHandler(
            $validInput,
            ['string_params' => [
                'report_type',
                'start_date',
                'end_date',
                'report_param'
            ]],
            200,
            'Can not send report\\. .* '
        );
    }

    /**
     * This function is used because AppKernelControllerProvider has custom
     * exception handling that changes the response HTTP status code and
     * prepends text to the response message.
     */
    private function provideRestEndpointTestsGivenCustomExceptionHandler(
        array $validInput,
        array $options,
        $expectedStatusCode,
        $messagePrefixRegex
    ) {
        $tests = parent::provideRestEndpointTests($validInput, $options);
        foreach ($tests as $i => $test) {
            // Get the test's expected message text.
            $closure = $test[3]['body_validator'];
            $reflection = new ReflectionFunction($closure);
            $vars = $reflection->getStaticVariables();
            $message = $vars['message'];
            // Replace the output object with a new object that has the custom
            // HTTP status code and message prefix.
            $tests[$i][3] = [
                'status_code' => $expectedStatusCode,
                'body_validator' => function (
                    $body,
                    $assertMessage
                ) use (
                    $messagePrefixRegex,
                    $message
                ) {
                    parent::assertFalse($body['success'], $assertMessage);
                    parent::assertRegExp(
                        (
                            "/$messagePrefixRegex"
                            . preg_quote($message)
                            . '/'
                        ),
                        $body['message'],
                        $assertMessage
                    );
                }
            ];
        }
        return $tests;
    }

    /**
     * @dataProvider provideGetAppKernelSuccessRate
     */
    public function testGetAppKernelSuccessRate(
        $id,
        $role,
        $input,
        $output
    ) {
        parent::authenticateRequestAndValidateJson(
            self::$helper,
            $role,
            $input,
            $output
        );
    }

    public function provideGetAppKernelSuccessRate()
    {
        $validInput = [
            'path' => 'rest/app_kernels/success_rate',
            'method' => 'get',
            'params' => [
                'start_date' => 'foo',
                'end_date' => 'foo'
            ],
            'data' => null
        ];
        // Run the tests of missing and invalid parameters for parameters that
        // are parsed before the try/catch in
        // AppKernelControllerProvider::getAppKernelSuccessRate.
        $tests = parent::provideRestEndpointTests(
            $validInput,
            [
                'string_params' => [
                    'format',
                    'start_date',
                    'end_date',
                    'resources',
                    'appKers',
                    'problemSizes'
                ],
                'bool_params' => [
                    'showAppKer',
                    'showAppKerTotal',
                    'showResourceTotal',
                    'showUnsuccessfulTasksDetails',
                    'showSuccessfulTasksDetails',
                    'showInternalFailureTasks'
                ]
            ]
        );
        // Run the test of the invalid 'node' parameter, which is parsed inside
        // the try/catch in
        // AppKernelControllerProvider::getAppKernelSuccessRate.
        $validInput['params'] = [];
        return array_merge(
            $tests,
            $this->provideRestEndpointTestsGivenCustomExceptionHandler(
                $validInput,
                [
                    'string_params' => ['node'],
                    'additional_params' => [
                        'start_date' => 'foo',
                        'end_date' => 'foo'
                    ]
                ],
                500,
                (
                    'There was an exception while trying to process the'
                    . ' requested operation\\. Message: '
                )
            )
        );
    }

    /**
     * @dataProvider provideGetPerformanceMap
     */
    public function testGetPerformanceMap(
        $id,
        $role,
        $input,
        $output
    ) {
        parent::authenticateRequestAndValidateJson(
            self::$helper,
            $role,
            $input,
            $output
        );
    }

    public function provideGetPerformanceMap()
    {
        $validInput = [
            'path' => 'rest/app_kernels/performance_map',
            'method' => 'get',
            'params' => [],
            'data' => null
        ];
        return $this->provideRestEndpointTestsGivenCustomExceptionHandler(
            $validInput,
            ['string_params' => ['start_date', 'end_date', 'format']],
            200,
            'Can not send performance map\\. .* '
        );
    }

    /**
     * @dataProvider provideGetRawPerformanceMap
     */
    public function testGetRawPerformanceMap(
        $id,
        $role,
        $input,
        $output
    ) {
        parent::authenticateRequestAndValidateJson(
            self::$helper,
            $role,
            $input,
            $output
        );
    }

    public function provideGetRawPerformanceMap()
    {
        $validInput = [
            'path' => 'rest/app_kernels/performance_map/raw',
            'method' => 'get',
            'params' => [
                'start_date' => '9999-01-02',
                'end_date' => '9999-01-01'
            ],
            'data' => null
        ];
        // Run some standard endpoint tests.
        return parent::provideRestEndpointTests(
            $validInput,
            [
                'authentication' => true,
                'run_as' => 'cd',
                'string_params' => [
                    'start_date',
                    'end_date',
                    'app_kernels',
                    'problem_sizes'
                ]
            ]
        );
    }
}
