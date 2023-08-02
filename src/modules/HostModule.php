<?php

namespace hiapi\heppy\modules;

class HostModule extends AbstractModule
{
    /** {@inheritdoc} */
    public array $uris = [
        'host' => 'urn:ietf:params:xml:ns:host-1.0',
        'host_hm' => 'http://hostmaster.ua/epp/host-1.1',
    ];

    public ?string $object = 'host';

    /**
     * @param array $row
     * @return $array
     */
    public function hostCheck($row): array
    {
        $zone = $this->getZOne($row, true);
        $res = $this->tool->commonRequest("{$this->object}:check", [
            'names' => [$row['host']],
            'reasons' => 'reasons',
            'zone' => $zone,
        ], [
            'avails' => 'avails',
        ]);

        return [
            'avail' => $res['avails'][$row['host']],
        ];
    }

    /**
     * @param array $row
     * @return array
     */
    public function hostSet(array $row): array
    {
        try {
            $check = $this->hostCheck($row);
        } catch (\Throwable $e) {
            throw new \Exception($e->getMessage());
        }

        return  (int) $check['avail'] === 1 ? $this->hostCreate($row) : $this->hostUpdate($row);
    }

    /**
     * @param array $row
     * @return array
     */
    public function hostCreate(array $row): array
    {
        $zone = $this->getZone($row, true);
        return $this->tool->commonRequest("{$this->object}:create", [
            'name'      => $row['host'],
            'ips'       => $row['ips'] ?? null,
            'zone'      => $zone,
        ], [
            'host'      => 'name',
        ]);
    }

    /**
     * @param array $row
     * @return array
     */
    public function hostInfo(array $row): array
    {
        return $this->tool->commonRequest("{$this->object}:info", [
            'name'      => $row['host'],
        ], [
            'host'          => 'name',
            'ips'           => 'ips',
            'roid'          => 'roid',
            'created_by'    => 'crID',
            'created_date'  => 'crDate',
            'statuses'      => 'statuses',
        ]);
    }

    /**
     * @param array $row
     * @param array $info
     * @return array
     */
    public function hostUpdate(array $row, array $info = null): array
    {
        if (empty($info)) {
            $info = $this->hostInfo($row);
        }

        $row = $this->prepareDataForHostUpdate($row, $info);
        return $this->tool->commonRequest("{$this->object}:update", array_filter([
            'name'  => $row['host'],
            'add'   => $row['add'] ?? null,
            'rem'   => $row['rem'] ?? null,
            'chg'   => $row['chg'] ?? null,
        ]), [], [
            'host'  => $row['host']
        ]);
    }

    /**
     * @param array $hosts
     * @return array
     */
    public function hostsDelete(array $hosts): array
    {
        $data = [];
        foreach ($hosts as $id => $hostData) {
            $data[$id] = $this->hostDelete($hostData);
        }

        return $data;
    }

    /**
     * @param array $row
     * @return array
     */
    public function hostDelete(array $row): array
    {
        return $this->tool->commonRequest("{$this->object}:delete", [
            'name'  => $row['host'],
        ], [], [
            'id'    => $row['id'],
            'host'  => $row['host'],
        ]);
    }

    /**
     * @param array $local
     * @param array $remote
     * @return array
     */
    private function prepareDataForHostUpdate(array $local, array $remote): array
    {
        return $this->prepareDataForUpdate($local, $remote, [
            'ips' => 'ips'
        ]);
    }
}
