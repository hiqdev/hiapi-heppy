<?php

namespace hiapi\heppy\modules;

use err;

class HostModule extends AbstractModule
{
    /**
     * @param array $row
     * @return array
     */
    public function hostSet(array $row): array
    {
        $info = $this->hostInfo($row);
        if (err::is($info) || empty($info['host'])) {
            $data = $this->hostCreate($row);
        } else {
            $row = $this->prepareDataForUpdate($row, $info);
            $data = $this->hostUpdate($row);
        }

        return $data;
    }

    /**
     * @param array $row
     * @return array
     */
    public function hostCreate(array $row): array
    {
        return $this->tool->request('host:create', [
            'name'      => $row['host'],
            'ips'       => $row['ips'],
        ]);
    }

    /**
     * @param array $row
     * @return array
     */
    public function hostInfo(array $row): array
    {
        return $this->tool->request('host:info', [
            'name'      => $row['host'],
        ], [
            'host'              => 'name',
            'ips'               => 'ips',
            'roid'              => 'roid',
            'statuses'          => function ($data) {
                return array_keys($data['statuses']);
            },
            'created_by'        => 'crID',
            'created_date'      => 'crDate',
        ]);
    }

    /**
     * @param array $row
     * @return array
     */
    public function hostUpdate(array $row): array
    {
        return $this->tool->request('host:update', array_filter([
            'name'      => $row['host'],
            'add'       => $row['add'],
            'rem'       => $row['rem'],
            'chg'       => $row['chg'],
        ]));
    }

    /**
     * @param array $local
     * @param array $remote
     * @return array
     */
    private function prepareDataForUpdate(array $local, array $remote): array
    {
        $add = array_diff($local['ips'], $remote['ips']);
        empty($add) ?: $local['add'] = $add;
        $rem = array_diff($remote['ips'], $local['ips']);
        empty($add) ?: $local['rem'] = $rem;

        return $local;
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
    private function hostDelete(array $row): array
    {
        return $this->tool->request('host:delete', [
            'name'      => $row['host'],
        ]);
    }
}
