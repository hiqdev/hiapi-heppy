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

    protected function filterCommonResponsePart(array $data): array
    {
        return array_filter([
            'result_msg'    => $data['result_msg'],
            'result_code'   => $data['result_code'],
            'result_lang'   => $data['result_lang'],
            'result_reason' => $data['result_reason'],
            'server_trid'   => $data['svTRID'],
            'client_trid'   => $data['clTRID'],
        ]);
    }
}
