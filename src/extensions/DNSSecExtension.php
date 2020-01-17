<?php

namespace hiapi\heppy\extensions;


class DNSSecExtension
{
    public function apply(string $command, array $data): array
    {
        return $data;
    }

    public function isApplicable(string $command, array $data): bool
    {
        return !empty($this->findZone($command, $data));
    }

    /**
     * @param array $data
     * @param string|null $zone
     * @return array
     */
    public function addDNSSecExt(array $data, string $zone): array
    {
        return $data;
    }
}
