<?php

namespace hiapi\heppy\extensions;


class RGPExtension
{
    public function apply(string $command, array $data): array
    {
        if ($command === 'domain:restore') {
            return $this->addRGPExt($data);
        }

        return $data;
    }

    public function isApplicable(string $command, array $data = []): bool
    {
        return $command === 'domain:restore';
    }

    /**
     * @param array $data
     * @param string|null $zone
     * @return array
     */
    public function addRGPExt(array $data = []): array
    {
        $data['extensions'][] = [
            'command' => 'rgp',
        ];

        return $data;
    }
}
