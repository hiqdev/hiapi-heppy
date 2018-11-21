<?php

namespace hiapi\heppy\tests\unit\modules\domain_module;

use hiapi\heppy\tests\unit\TestCase;

class DomainsCheckTest extends TestCase
{
    public function testDomainsCheck()
    {
        $domain1 = 'silverfires1.me';
        $domain42 = 'silverfires42.me';

        $tool = $this->createTool([
            'names'         => [
                0 => $domain1,
                1 => $domain42,
            ],
            'command'       => 'domain:check',
        ], $this->addCommonSuccessResponse([
            'reasons'       => [
                $domain1   => 'In use',
            ],
            'avails'        => [
                $domain1   => '0',
                $domain42  => '1',
            ],
        ]));

        $result = $tool->domainsCheck([
            'domains' => [
                0 => $domain1,
                1 => $domain42,
            ],
        ]);

        $this->assertSame($result, $this->addMappedCommonSuccessResponse([
            'avails' => [
                $domain1   => '0',
                $domain42  => '1',
            ],
            'reasons' => [
                $domain1   => 'In use',
            ],
        ]));
    }
}
