<?php

namespace hiapi\heppy\tests\unit\modules\domain;

use hiapi\heppy\RabbitMQClient;
use hiapi\heppy\tests\unit\Stubs\HeppyToolStub;
use hiapi\heppy\tests\unit\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class DomainSetContactsTest extends TestCase
{
    public function testRegistryIsNotAskedWhenItKeepsNoContacts(): void
    {
        $client = $this->createMock(RabbitMQClient::class);
        $client->expects($this->never())->method('request');
        $tool = new HeppyToolStub($this->mockBase(), ['contacts' => ['disabled' => true]]);
        $tool->setClient($client);
        $row = ['domain' => 'example.com', 'id' => 1, 'whois_protected' => false];

        $result = $tool->domainSetContacts($row);

        $this->assertSame($row, $result);
    }
}
