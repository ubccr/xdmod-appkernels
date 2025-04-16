<?php

namespace IntegrationTests\REST\internal_dashboard;

use IntegrationTests\TestHarness\XdmodTestHelper;

class DashboardAppKernelTest extends \PHPUnit_Framework_TestCase
{
    public function __construct()
    {
        $xdmodConfig = array( "decodetextasjson" => true );
        $this->xdmodhelper = new XdmodTestHelper($xdmodConfig);

        $this->endpoint = 'rest/v0.1/akrr/';
        $this->xdmodhelper->authenticate("mgr");
    }

    private function validateAkrrResourceEntries($searchparams)
    {
        $this->xdmodhelper->authenticate("mgr");

        $result = $this->xdmodhelper->get($this->endpoint . 'resources', $searchparams);
        $this->assertEquals(200, $result[1]['http_code']);

        $this->assertArrayHasKey('status', $result[0]);
        $this->assertEquals($result[0]['status'], 'success');

        $data = $result[0]['data'];
        foreach ($data as $item) {
            $this->assertArrayHasKey('id', $item);
            $this->assertArrayHasKey('name', $item);
        }

        return $data;
    }

    public function testResourceNullParam()
    {
        $data = $this->validateAkrrResourceEntries(null);
        $this->assertGreaterThanOrEqual(1, sizeof($data));
    }

    private function validateAkrrKernelEntries($searchparams)
    {
        $this->xdmodhelper->authenticate("mgr");

        $result = $this->xdmodhelper->get($this->endpoint . 'kernels', $searchparams);
        $this->assertEquals(200, $result[1]['http_code']);

        $this->assertArrayHasKey('status', $result[0]);
        $this->assertEquals($result[0]['status'], 'success');

        $data = $result[0]['data'];
        foreach ($data as $item) {
            $this->assertArrayHasKey('nodes_list', $item);
            $this->assertArrayHasKey('name', $item);
            $this->assertArrayHasKey('enabled', $item);
            $this->assertArrayHasKey('id', $item);
        }

        return $data;
    }

    public function testKernelNullParam()
    {
        $data = $this->validateAkrrKernelEntries(null);
        $this->assertGreaterThanOrEqual(1, sizeof($data));
    }
}
