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

    public function testHostDeleteWithoutIdSendsDeleteAndLeavesIdOut()
    {
        $host = 'ns42.silverfires1.me';

        $tool = $this->createTool([
            'name'    => $host,
            'command' => 'host:delete',
        ], $this->getCommonSuccessResponse());

        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });
        try {
            $result = $tool->hostDelete([
                'host' => $host,
            ]);
        } finally {
            restore_error_handler();
        }

        $this->assertSame($result, $this->addMappedCommonSuccessResponse([
            'host' => $host,
        ]));
    }
}
