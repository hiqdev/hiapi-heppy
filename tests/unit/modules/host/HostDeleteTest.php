<?php

namespace hiapi\heppy\tests\unit\modules\host;

use hiapi\heppy\tests\unit\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class HostDeleteTest extends TestCase
{
    public function testHostDelete()
    {
        $host = 'ns42.silverfires1.me';

        $tool = $this->createTool([
            'name'    => $host,
            'command' => 'host:delete',
        ], $this->getCommonSuccessResponse());

        $result = $tool->hostDelete([
            'id'   => 25844515,
            'host' => $host,
        ]);

        $this->assertSame($result, $this->addMappedCommonSuccessResponse([
            'id'   => 25844515,
            'host' => $host,
        ]));
    }
}
