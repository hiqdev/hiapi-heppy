<?php

namespace hiapi\heppy\tests\unit\modules\domain_module;

use hiapi\heppy\tests\unit\TestCase;

class DomainsLockTest extends TestCase
{
    private $id = 25844450;
    private $domain = 'silverfires1.me';

    public function testDomainsEnableLock()
    {
        $tool = $this->createTool([
            'name'    => 'silverfires1.me',
            'add'     => [
                'statuses' => [
                    'clientDeleteProhibited'   => null,
                    'clientTransferProhibited' => null,
                ],
            ],
            'command' => 'domain:update',
        ], $this->getCommonSuccessResponse());

        $result = $tool->domainsEnableLock([
            $this->id => [
                'domain' => $this->domain,
                'id'     => $this->id,
            ],
        ]);

        $this->assertSame($result, [
            25844450 => array_merge([
                'id'          => $this->id,
                'domain'      => $this->domain,
            ], $this->getMappedCommonSuccessResponse()),
        ]);
    }

//    public function testDomainsDisableLock()
//    {
//        $tool = $this->createTool();
//
//        $result = $tool->domainsDisableLock();
//
//        $this->assertSame($result, );
//    }
}
