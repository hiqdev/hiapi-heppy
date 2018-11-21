<?php

namespace hiapi\heppy\tests\unit\modules\domain_module;

use hiapi\heppy\tests\unit\TestCase;

class DomainsLockTest extends TestCase
{
    /**
     * @var int
     */
    private $id = 25844450;

    /**
     * @var string
     */
    private $domain = 'silverfires1.me';

    /**
     * @return array
     */
    private function getApiData(): array
    {
        return [
            $this->id => [
                'domain' => $this->domain,
                'id'     => $this->id,
            ],
        ];
    }

    public function testDomainsEnableLock()
    {
        $tool = $this->createTool([
            'name'    => $this->domain,
            'command' => 'domain:update',
            'add'     => [
                'statuses' => [
                    'clientDeleteProhibited'   => null,
                    'clientTransferProhibited' => null,
                ],
            ],
        ], $this->getCommonSuccessResponse());

        $result = $tool->domainsEnableLock($this->getApiData());

        $this->assertSame($result, [
            $this->id => $this->addMappedCommonSuccessResponse([
                'id'     => $this->id,
                'domain' => $this->domain,
            ]),
        ]);
    }

    public function testDomainsDisableLock()
    {
        $tool = $this->createTool([
            'name' => $this->domain,
            'command' => 'domain:update',
            'rem'  => [
                'statuses' => [
                    'clientUpdateProhibited'   => null,
                    'clientDeleteProhibited'   => null,
                    'clientTransferProhibited' => null,
                ],
            ],
        ], $this->getCommonSuccessResponse());

        $result = $tool->domainsDisableLock($this->getApiData());

        $this->assertSame($result, [
            $this->id => $this->addMappedCommonSuccessResponse([
                'id'     => $this->id,
                'domain' => $this->domain,
            ]),
        ]);
    }
}
