<?php

namespace hiapi\heppy\tests\unit\modules\domain;

use hiapi\heppy\exceptions\EppErrorException;
use hiapi\heppy\exceptions\EppReplyException;
use hiapi\heppy\tests\unit\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class DomainInfoRegistryErrorTest extends TestCase
{
    public function testUnrecognisedErrorKeepsItsMessageAndTheReplyChain(): void
    {
        $tool = $this->createTool([], [
            'result_code'   => '2306',
            'result_msg'    => 'Parameter value policy error',
            'result_reason' => 'Connection Policy',
            'svTRID'        => '1096078890-1792505952818',
            'clTRID'        => 'AA-01',
        ]);

        try {
            $tool->domainInfo(['domain' => 'example.com', 'id' => 1]);
            $this->fail('Exception expected');
        } catch (\Exception $e) {
            $this->assertSame(\Exception::class, $e::class);
            $this->assertSame('Parameter value policy error Connection Policy', $e->getMessage());
            $this->assertSame(2306, $e->getCode());
            $this->assertInstanceOf(EppErrorException::class, $e->getPrevious());
            $this->assertInstanceOf(EppReplyException::class, $e->getPrevious()->getPrevious());
            $this->assertStringContainsString('svTRID=1096078890-1792505952818', $e->getPrevious()->getPrevious()->getMessage());
        }
    }
}
