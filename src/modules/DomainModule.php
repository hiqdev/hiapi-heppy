<?php

namespace hiapi\heppy\modules;

use arr;
use err;

class DomainModule extends AbstractModule
{
    /**
     * @param array $row
     * @return array
     */
    public function domainInfo(array $row): array
    {
        return $this->tool->commonRequest('domain:info', [
            'name'      => $row['domain'],
        ], [
            'domain'            => 'name',
            'name'              => 'name',
            'roid'              => 'roid',
            'created_by'        => 'crID',
            'created_date'      => 'crDate',
            'updated_by'        => 'upID',
            'updated_date'      => 'upDate',
            'expiration_date'   => 'exDate',
            'transfer_date'     => 'trDate',
            'password'          => 'pw',
            'epp_client_id'     => 'clID',
            'statuses'          => function ($data) {
                implode(',', array_keys($data['statuses']));
            },
            'nameservers'       => function ($data) {
                implode(',', $data['nss']);
            },
            'hosts'             => function ($data) {
                implode(',', $data['hosts']);
            },
        ]);
    }

    /**
     * @param array $row
     * @return array
     */
    public function domainsCheck(array $row): array
    {
        return $this->tool->commonRequest('domain:check', [
            'names'     => $row['domains'],
        ], [
            'avails'    => 'avails',
            'reasons'   => 'reasons',
        ]);
    }

    /**
     * @param array $row
     * @return array
     */
    public function domainRegister(array $row): array
    {
        if (!$row['nss']) {
            $row['nss'] = arr::get($this->base->domainGetNSs($row),'nss');
        }
        if (!$row['nss']) {
            $row['nss'] = $this->tool->getDefaultNss();
        }
        $row = $this->domainPrepareContacts($row);

        return $this->tool->commonRequest('domain:create', array_filter([
            'name'          => $row['domain'],
            'period'        => $row['period'],
            'registrant'    => $row['registrant_remote_id'],
            'admin'         => $row['admin_remote_id'],
            'tech'          => $row['tech_remote_id'],
            'billing'       => $row['billing_remote_id'],
            'nss'           => $row['nss'],
            'pw'            => $row['password']
        ]), [
            'domain'            => 'name',
            'created_date'      => 'crDate',
            'expiration_date'   => 'exDate',
        ]);

    }

    /**
     * @param array $row
     * @return array
     */
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
            $row[$type . '_remote_id'] = $remoteId;
        }

        return $row;
    }

    /**
     * @param array $row
     * @return array
     */
    public function domainDelete(array $row): array
    {
        return $this->tool->commonRequest('domain:delete', [
            'name'     => $row['domain'],
        ]);
    }
}
