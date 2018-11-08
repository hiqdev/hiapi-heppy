<?php

namespace hiapi\heppy\modules;

use hiapi\heppy\HeppyTool;

class AbstractModule
{
    public $tool;

    public function __construct(HeppyTool $tool)
    {
        $this->tool = $tool;
    }
}
