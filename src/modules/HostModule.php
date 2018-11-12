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
        }
        else {
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
        $data = $this->tool->request([
            'command'   => 'host:create',
            'name'      => $row['host'],
            'ips'       => $row['ips'],
        ]);

        return array_merge(array_filter([
            'host'              => $data['name'],
            'created_date'      => $data['crDate'],
        ]), $this->filterCommonResponsePart($data));
    }

    /**
     * @param array $row
     * @return array
     */
    public function hostInfo(array $row): array
    {
        $data = $this->tool->request([
            'command'   => 'host:info',
            'name'      => $row['host'],
        ]);

        return array_merge(array_filter([
            'host'              => $data['name'],
            'ips'               => $data['ips'],
            'roid'              => $data['roid'],
            'statuses'          => implode(',', array_keys($data['statuses'])),
            'created_by'        => $data['crID'],
            'created_date'      => $data['crDate'],
        ]), $this->filterCommonResponsePart($data));
    }

    /**
     * @param array $row
     * @return array
     */
    public function hostUpdate(array $row): array
    {
        $data = $this->tool->request(array_filter([
            'command'   => 'host:update',
            'name'      => $row['host'],
            'add'       => $row['add'],
            'rem'       => $row['rem'],
            'chg'       => $row['chg'],
        ]));

        return $this->filterCommonResponsePart($data);
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
     * @param array $row
     * @return array
     */
    public function hostsDelete(array $hosts): array
    {
        $data = [];
        foreach ($hosts as $id => $hostData) {
            $data[$id] = $this->hostDalete($hostData);
        }

        return $data;
    }

    /**
     * @param array $row
     * @return array
     */
    private function hostDalete(array $row): array
    {
        $data = $this->tool->request([
            'command'   => 'host:delete',
            'name'      => $row['host'],
        ]);

        return $this->filterCommonResponsePart($data);
    }
}
