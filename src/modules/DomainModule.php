<?php

namespace hiapi\heppy\modules;


class DomainModule extends AbstractModule
{
    /**
     * @param array $row
     * @return array
     */
    public function domainInfo(array $row): array
    {
        $data = $this->tool->request([
            'command'   => 'domain:info',
            'name'      => $row['domain'],
        ]);

        return array_filter([
            'domain'            => $data['name'],
            'result_msg'        => $data['result_msg'],
            'result_code'       => $data['result_code'],
            'result_lang'       => $data['result_lang'],
            'result_reason'     => $data['result_reason'],
            'server_trid'       => $data['svTRID'],
            'client_trid'       => $data['clTRID'],
            'name'              => $data['name'],
            'roid'              => $data['roid'],
            'statuses'          => implode(',', array_keys($data['statuses'])),
            'nameservers'       => implode(',', $data['nss']),
            'hosts'             => implode(',', $data['hosts']),
            'created_by'        => $data['crID'],
            'created_date'      => $data['crDate'],
            'updated_by'        => $data['upID'],
            'updated_date'      => $data['upDate'],
            'expiration_date'   => $data['exDate'],
            'transfer_date'     => $data['trDate'],
            'password'          => $data['pw'],
            'epp_client_id'     => $data['clID'],
        ]);
    }
}
