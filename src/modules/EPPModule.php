<?php

namespace hiapi\heppy\modules;

class EPPModule extends AbstractModule
{
    /**
     * @param array $row
     * @return array
     */
    public function eppHello(array $row = []) : array
    {
        return $this->tool->request('epp:hello', []);
    }
}
