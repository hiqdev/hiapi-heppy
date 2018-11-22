<?php

namespace hiapi\heppy\tests\unit\modules\host_module;

use hiapi\heppy\tests\unit\TestCase;

class HostDeleteTest extends TestCase
{
    public function testHostDelete()
    {
        $tool = $this->createTool([
            'name'    => 'ns42.silverfires1.me',
            'command' => 'host:delete',
        ], $this->getCommonSuccessResponse());

        $result = $tool->hostDelete([
            'id'   => 25844515,
            'host' => 'ns42.silverfires1.me',
        ]);

        $this->assertSame($result, $this->addMappedCommonSuccessResponse([
            'id'   => 25844515,
            'host' => 'ns42.silverfires1.me',
        ]));
    }
}
