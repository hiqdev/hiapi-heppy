<?php

namespace hiapi\heppy\extensions;


class FeeExtension
{
    public function apply(string $command, array $data): array
    {
        $zone = $this->findZone($command, $data);
        if ($zone) {
            return $this->addNamestoreExt($data, $zone);
        }

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
    public function addNamestoreExt(array $data, string $zone): array
    {
        $zone = strtoupper($zone);
        if (in_array($zone, ['COM', 'NET', 'CC', 'TV', 'NAME'])) {
            $extension = [
                'command' => 'namestoreExt',
                'subProduct' => "dot$zone",
            ];
            $data['extensions'][] = $extension;
        }

        return $data;
    }

    /**
     * @param array $data
     * @param string|null $name
     * @return null|string
     */
    private function findZone(string $command, array $data, string $name = null): ?string
    {
        if (isset($data['zone'])) {
            return $data['zone'];
        }
        if (!$name && isset($data['name'])) {
            $name = $data['name'];
        }
        if (!$name && isset($data['names'])) {
            $name = reset($data['names']);
        }

        return array_pop(explode('.', $name));
    }
}
