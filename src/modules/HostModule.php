<?php

namespace hiapi\heppy\modules;


use err;

class HostModule extends AbstractModule
{
    public function hostSet(array $row): array
    {
        $info = $this->hostInfo($row);

        if (err::is($info) || empty($info['host'])) {
            $data = $this->hostCreate($row);
        }
        else {

        }
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

        return array_filter([
            'host'              => $data['name'],
            'reason'            => $data['result_reason'],
            'result_msg'        => $data['result_msg'],
            'result_code'       => $data['result_code'],
            'result_lang'       => $data['result_lang'],
            'created_date'      => $data['crDate'],
            'server_trid'       => $data['svTRID'],
            'client_trid'       => $data['clTRID'],
        ]);
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

        return array_filter([
            'host'              => $data['name'],
            'ips'               => $data['ips'],
            'result_msg'        => $data['result_msg'],
            'result_code'       => $data['result_code'],
            'result_lang'       => $data['result_lang'],
            'result_reason'     => $data['result_reason'],
            'server_trid'       => $data['svTRID'],
            'client_trid'       => $data['clTRID'],
            'roid'              => $data['roid'],
            'statuses'          => implode(',', array_keys($data['statuses'])),
            'created_by'        => $data['crID'],
            'created_date'      => $data['crDate'],
        ]);
    }
}
