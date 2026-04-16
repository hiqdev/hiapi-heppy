<?php

namespace hiapi\heppy\tests\unit\Stubs;

/**
 * Minimal stub replacing mrdpBase for unit tests.
 * HeppyTool only needs getCache() from its $base dependency.
 */
class HeppyBaseStub
{
    public function getCache(): \yii\caching\CacheInterface
    {
        return new \yii\caching\ArrayCache();
    }
}
