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
            $res = $this->hostCreate($row);
        } else {
            $row = $this->prepareDataForUpdate($row, $info);
            $res = $this->hostUpdate($row);
        }

        return $res;
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
        return $this->tool->commonRequest('host:info', [
            'name'      => $row['host'],
        ], [
            'host'          => 'name',
            'ips'           => 'ips',
            'roid'          => 'roid',
            'created_by'    => 'crID',
            'created_date'  => 'crDate',
            'statuses'      => function ($data) {
                implode(',', array_keys($data['statuses']));
            },
        ]);
    }

    /**
     * @param array $row
     * @return array
     */
    public function hostUpdate(array $row): array
    {
        return $this->tool->commonRequest('host:update', array_filter([
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
        $res = [
            'add' => [],
            'rem' => [],
        ];
        $res['add']['ips'] = array_diff($local['ips'], $remote['ips']);
        $res['rem']['ips'] = array_diff($remote['ips'], $local['ips']);


        return array_merge($local, array_filter($res));
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
        return $this->tool->commonRequest('host:delete', [
            'name'      => $row['host'],
        ]);
    }
}
