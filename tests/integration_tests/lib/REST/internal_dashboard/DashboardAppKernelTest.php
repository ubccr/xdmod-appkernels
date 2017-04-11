<?php

namespace IntegrationTests\REST\internal_dashboard;

class DashboardAppKernelTest extends \PHPUnit_Framework_TestCase
{
    public function __construct()
    {
        $xdmodConfig = array( "decodetextasjson" => true );
        $this->xdmodhelper = new \TestHarness\XdmodTestHelper($xdmodConfig);

        $this->endpoint = 'rest/v0.1/akrr/';
        $this->xdmodhelper->authenticate("mgr");
    }

    private function validateAkrrResourceEntries($searchparams)
    {
        $this->xdmodhelper->authenticate("mgr");

        $result = $this->xdmodhelper->get($this->endpoint . 'resources', $searchparams);
        $this->assertEquals(200, $result[1]['http_code']);

        $this->assertArrayHasKey('success', $result[0]);
        $this->assertEquals($result[0]['success'], true);

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

        $this->assertArrayHasKey('message', $result[0]);
        $this->assertEquals($result[0]['message'], 'success');

        $data = $result[0]['data'];
        foreach ($data as $item) {
            $this->assertArrayHasKey('nodes_list', $data[0]);
            $this->assertArrayHasKey('name', $data[0]);
            $this->assertArrayHasKey('enabled', $data[0]);
            $this->assertArrayHasKey('id', $data[0]);
        }

        return $data;
    }

    public function testKernelNullParam()
    {
        $data = $this->validateAkrrKernelEntries(null);
        $this->assertGreaterThanOrEqual(1, sizeof($data));
    }
}
