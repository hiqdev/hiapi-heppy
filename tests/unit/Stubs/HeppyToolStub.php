<?php

namespace hiapi\heppy\tests\unit\Stubs;

use hiapi\heppy\HeppyTool;

/**
 * HeppyTool subclass for unit tests.
 * Overrides requestHello() so modules can initialise without a real EPP connection.
 */
class HeppyToolStub extends HeppyTool
{
    public function requestHello(int $tries = 0): ?array
    {
        return [
            'svID'    => 'Test EPP Server',
            'objURIs' => [],
            'extURIs' => [],
        ];
    }
}
