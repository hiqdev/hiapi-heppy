<?php

namespace hiapi\heppy\tests\unit\modules\host_module;

use hiapi\heppy\tests\unit\TestCase;

class HostUpdateTest extends TestCase
{
    public function testHostUpdate()
    {
        $tool = $this->createTool([
            'name'    => 'ns42.silverfires1.me',
            'add'     => [
                'ips' => [
                    0 => '192.0.1.1',
                    1 => '192.0.1.2',
                ],
            ],
            'rem'     => [
                'ips' => [
                    0 => '192.0.4.2',
                ],
            ],
            'command' => 'host:update',
        ], $this->getCommonSuccessResponse());

        $result = $tool->hostUpdate([
            'host'      => 'ns42.silverfires1.me',
            'domain'    => 'silverfires42.me',
            'ip'        => null,
            'ips'       => [
                0 => '192.0.1.1',
                1 => '192.0.1.2',
            ],
            'domain_id' => 25844481,
        ], [
            'host'         => 'ns42.silverfires1.me',
            'ips'          => [
                0 => '192.0.4.2',
            ],
            'roid'         => 'H158395-AGRS',
            'created_by'   => 'OTE1186-EP1',
            'created_date' => '2018-11-22T10:49:18.0Z',
            'statuses'     => [
                'ok' => null,
            ],
            'result_msg'   => 'Command completed successfully',
            'result_code'  => '1000',
            'result_lang'  => 'en-US',
            'server_trid'  => 'SRO-1542884993447',
            'client_trid'  => 'AA-00',
        ]);

        $this->assertSame($result, $this->addMappedCommonSuccessResponse([
            'host' => 'ns42.silverfires1.me',
        ]));
    }
}
