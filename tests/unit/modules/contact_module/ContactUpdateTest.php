<?php

namespace hiapi\heppy\tests\unit\modules\contact_module;

class ContactUpdateTest extends ContactTestCase
{
    public function testContactUpdate()
    {
        $contactData = $this->changedData;
        $contactInfo = $this->contactData;

        $tool = $this->createTool([
            'id'      => 'MR_25844511f',
            'chg'     => [
                'street2' => 'Avenue2',
                'street3' => 'Avenue3',
                'sp'      => 'California',
            ],
            'command' => 'contact:update',
        ], $this->getCommonSuccessResponse());

        $result = $tool->contactUpdate($contactData, $contactInfo);

        $this->assertSame($result, $this->addMappedCommonSuccessResponse([
            'epp_id' => $this->eppId
        ]));
    }
}
