<?php

namespace hiapi\heppy\tests\unit\modules\host_module;

use hiapi\heppy\tests\unit\TestCase;

class HostCreateTest extends TestCase
{
    public function testHostCreate()
    {
        $host = 'ns42.silverfires1.me';

        $tool = $this->createTool([
            'command' => 'host:create',
            'name'    => $host,
            'ips'     => [
                0 => '192.0.4.2',
            ],
        ], $this->addCommonSuccessResponse([
            'name'   => $host,
            'crDate' => '2018-11-22T10:49:18.0Z',
        ]));

        $result = $tool->hostCreate([
            'host'      => $host,
            'domain'    => 'silverfires42.me',
            'ip'        => '192.0.4.2',
            'ips'       => [
                0 => '192.0.4.2',
            ],
            'domain_id' => 25844481,
        ]);

        $this->assertSame($result, $this->addMappedCommonSuccessResponse([
            'host' => $host,
        ]));
    }
}
