<?php

namespace hiapi\heppy;


class Extension
{
    /**
     * @param array $data
     * @param string|null $zone
     * @return array
     */
    public function addNamestoreExt(array $data, string $zone = null): array
    {
        $zone = strtoupper($zone ?: $this->findZone($data));
        if (in_array($zone, ['COM', 'NET'])) {
            $data['extensions']['namestoreExt:subProduct'] = 'namestoreExt:subProduct';
            $data['subProduct'] = $zone;
        }

        return $data;
    }

    /**
     * @param array $data
     * @param string|null $name
     * @return null|string
     */
    private function findZone(array $data, string $name = null): ?string
    {
        if (isset($data['zone'])) {
            return $data['zone'];
        }
        if (!$name) {
            $name = $name ?: $data['name'] ?? null;
        }

        return array_pop(explode('.', $name));
    }
}
