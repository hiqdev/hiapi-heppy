<?php

namespace hiapi\heppy\tests\unit\modules\domain;

use hiapi\heppy\tests\unit\TestCase;

/**
 * domainsCheck() now iterates domains one-by-one via domainCheck(), which itself
 * makes two _domainCheck() calls per domain.  The result is a map of
 * domain → per-domain check result, not the old combined avails/reasons array.
 */
class DomainsCheckTest extends TestCase
{
    public function testDomainsCheck()
    {
        $domain1  = 'silverfires1.me';
        $domain42 = 'silverfires42.me';

        // Each _domainCheck() call sees this raw EPP response.
        // domain1 is taken (avail=0), domain42 is free (avail=1).
        $checkResponse = $this->addCommonSuccessResponse([
            'avails'  => [
                $domain1  => '0',
                $domain42 => '1',
            ],
            'reasons' => [
                $domain1 => 'In use',
            ],
        ]);

        $tool = $this->createTool([], $checkResponse);

        $result = $tool->domainsCheck([
            'domains' => [
                0 => $domain1,
                1 => $domain42,
            ],
        ]);

        // domain1 is taken: domainCheck returns early with avail+reason
        $this->assertSame(0,       $result[$domain1]['avail']);
        $this->assertSame('In use', $result[$domain1]['reason']);

        // domain42 is free: domainCheck calls _parseCheckFee which returns avail only
        $this->assertSame(1, $result[$domain42]['avail']);
    }
}
