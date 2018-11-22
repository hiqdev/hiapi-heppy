<?php

namespace hiapi\heppy\tests\unit\modules\host_module;

use hiapi\heppy\tests\unit\TestCase;

class HostInfoTest extends TestCase
{
    public function testHostInfo()
    {
        $host = 'ns1.silverfires1.me';

        $tool = $this->createTool([
            'command' => 'host:info',
            'name'    => $host,
        ], $this->addCommonSuccessResponse([
            'name'        => $host,
            'roid'        => 'H158180-AGRS',
            'result_msg'  => 'Command completed successfully',
            'crID'        => 'OTE1186-EP1',
            'crDate'      => '2018-11-21T09:55:19.0Z',
            'ips'         => [
                0 => '192.0.1.1',
            ],
            'statuses'    => [
                'ok'     => null,
                'linked' => null,
            ],
        ]));

        $result = $tool->hostInfo([
            'host'      => $host,
            'domain'    => 'silverfires1.me',
            'ip'        => null,
            'ips'       => [
                0 => null,
            ],
            'domain_id' => 25844450,
        ]);

        $this->assertSame($result, $this->addMappedCommonSuccessResponse([
            'host'         => $host,
            'ips'          => [
                0 => '192.0.1.1',
            ],
            'roid'         => 'H158180-AGRS',
            'created_by'   => 'OTE1186-EP1',
            'created_date' => '2018-11-21T09:55:19.0Z',
            'statuses'     => [
                'ok'     => null,
                'linked' => null,
            ],
        ]));
    }
}
