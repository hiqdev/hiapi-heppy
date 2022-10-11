<?php

namespace hiapi\heppy\modules;

use hiapi\legacy\lib\deps\err;
use hiapi\legacy\lib\deps\check;

class EPPModule extends AbstractModule
{
    /**
     * @param array $row
     * @return array|null
     */
    public function eppHello(array $row = []) : ?array
    {
        return $this->tool->requestHello();
    }
}
