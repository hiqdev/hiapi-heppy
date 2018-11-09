<?php

namespace hiapi\heppy\modules;


class HostModule extends AbstractModule
{
    public function hostSet(array $row): array
    {
        $data = $this->hostCreate($row);
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
}
