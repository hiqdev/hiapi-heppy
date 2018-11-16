<?php

namespace hiapi\heppy\modules;

use hiapi\heppy\HeppyTool;

class AbstractModule
{
    public $tool;
    public $base;

    public function __construct(HeppyTool $tool)
    {
        $this->tool = $tool;
        $this->base = $tool->getBase();
    }

    /**
     * @param int $length
     * @return string
     */
    public function generatePassword(int $length = 10): string
    {
        $charsets = [
            'abcdefghijklmnopqrstuvwxyz',
            'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
            '0123456789',
            '!@#$%^&*',
        ];

        $result = '';
        for ($i = 0; $i < $length; $i++) {
            $n = $i % 4;
            $max = strlen($charsets[$n]) - 1;
            $index = rand(0, $max);
            $result .= substr($charsets[$n], $index, 1);
        }

        return $result;
    }

    protected function getFilterCallback(): \Closure
    {
        return function ($value) {
            return !is_null($value);
        };
    }

    protected function prepareDataForUpdate(array $local, array $remote, array $map): array
    {
        $res = [
            'add' => [],
            'chg' => [],
            'rem' => [],
        ];

        foreach ($map as $apiName => $eppName) {
            if (key_exists($apiName, $local)
                && !key_exists($apiName, $remote)
                && !is_null($local[$apiName])) {
                $res['add'][$eppName] = $local[$apiName];
            } else if (key_exists($apiName, $local)
                && key_exists($apiName, $remote)
                && !is_null($local[$apiName])
                && $local[$apiName] !== $remote[$apiName]) {
                $res['chg'][$eppName] = $local[$apiName];
            } else if (key_exists($apiName, $remote)
                && !key_exists($apiName, $local)
                && !is_null($remote[$apiName])) {
                $res['rem'][$eppName] = $remote[$apiName];
            }
        }

        return array_merge($local, array_filter($res));
    }
}
