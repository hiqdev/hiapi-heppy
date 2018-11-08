<?php

namespace hiapi\heppy\modules;


use err;

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

    /**
     * @param array $row
     * @return array
     */
    public function domainsCheck(array $row): array
    {
        $data = $this->tool->request([
            'command'   => 'domain:check',
            'names'     => $row['domains'],
        ]);

        return array_filter([
            'avails'            => $data['avails'],
            'reasons'           => $data['reasons'],
            'result_msg'        => $data['result_msg'],
            'result_code'       => $data['result_code'],
            'result_lang'       => $data['result_lang'],
            'server_trid'       => $data['svTRID'],
            'client_trid'       => $data['clTRID'],
        ]);
    }

    public function domainRegister(array $row): array
    {
        $row = $this->domainPrepareContacts($row);

        $data = $this->tool->request(array_filter([
            'command'       => 'domain:create',
            'name'          => $row['domain'],
            'period'        => $row['period'],
            'registrant'    => $row['registrant'],
            'admin'         => $row['admin'],
            'tech'          => $row['tech'],
            'billing'       => $row['billing'],
            'nss'           => $row['nss'],
            'pw'            => $row['password']
        ]));
    }

    public function domainPrepareContacts(array $row): array
    {
        $contacts = $this->base->domainGetWPContactsInfo($row);
        if (err::is($contacts)) {
            return $contacts;
        }
        $remoteIds = [];
        foreach ($this->base->getContactTypes() as $type) {
            $contactId = $contacts[$type]['id'];
            $remoteId = $remoteIds[$contactId];
            if (!$remoteId) {
                $response = $this->tool->contactSet($contacts[$type]);
                if (err::is($response)) {
                    return $response;
                }
                $remoteId = $response['id'];
                $remoteIds[$contactId] = $remoteId;
            }
            $row[$type . '_remoteid'] = $remoteId;
        }

        return $row;
    }

}
