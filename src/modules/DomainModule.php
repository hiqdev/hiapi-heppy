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
        return $this->tool->commonRequest('domain:info', array_filter([
            'name'      => $row['domain'],
            'pw'        => $row['password'] ?? null,
        ], $this->getFilterCallback()), [
            'domain'            => 'name',
            'name'              => 'name',
            'roid'              => 'roid',
            'created_by'        => 'crID',
            'created_date'      => 'crDate',
            'updated_by'        => 'upID',
            'updated_date'      => 'upDate',
            'expiration_date'   => 'exDate',
            'transfer_date'     => 'trDate',
            'registrant'        => 'registrant',
            'admin'             => 'admin',
            'billing'           => 'billing',
            'tech'              => 'tech',
            'password'          => 'pw',
            'epp_client_id'     => 'clID',
            'statuses'          => 'statuses',
            'nss'               => 'nss',
            'hosts'             => 'hosts',
        ]);
    }

    /**
     * @param array $row
     * @return array
     */
    public function domainsCheck(array $row): array
    {
        $res = $this->tool->commonRequest('domain:check', [
            'names'     => $row['domains'],
        ], [
            'avails'    => 'avails',
            'reasons'   => 'reasons',
            'fee'       => 'fee',
        ]);

        return $res['avails'];
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
                $remoteId = $response['epp_id'];
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

    /**
     * @param array $row
     * @return array
     */
    public function domainRenew(array $row): array
    {
        return $this->tool->commonRequest('domain:renew', [
            'name'          => $row['domain'],
            'curExpDate'    => $row['expires'],
            'period'        => $row['period'],
        ], [
            'domain'            => 'name',
            'expiration_date'   => 'exDate',
        ]);
    }

    /**
     * @param array $row
     * @param string $op
     * @return array
     */
    private function performTransfer(array $row, string $op): array
    {
        return $this->tool->commonRequest('domain:transfer', [
            'op'        => $op,
            'name'      => $row['domain'],
            'pw'        => $row['password'],
            'period'    => $row['period'],
            'roid'      => $row['roid'],
        ], [
            'domain'            => 'name',
            'expiration_date'   => 'exDate',
            'action_date'       => 'acDate',
            'action_client_id'  => 'acID',
            'request_date'      => 'reDate',
            'request_client_id' => 'reID',
            'transfer_status'   => 'trStatus'
        ]);
    }

    /**
     * @param array $row
     * @return array
     */
    public function domainTransfer(array $row): array
    {
        return $this->performTransfer($row, 'request');
    }

    /**
     * @param array $row
     * @return array
     */
    public function domainCancelTransfer(array $row): array
    {
        return $this->performTransfer($row, 'cancel');
    }

    /**
     * @param array $row
     * @return array
     */
    public function domainApproveTransfer(array $row): array
    {
        return $this->performTransfer($row, 'approve');
    }

    /**
     * @param array $row
     * @return array
     */
    public function domainRejectTransfer(array $row): array
    {
        return $this->performTransfer($row, 'reject');
    }

    /**
     * @param array $row
     * @return array
     */
    private function domainUpdate(array $row): array
    {
        return $this->tool->commonRequest('domain:update', array_filter([
            'name'      => $row['domain'],
            'add'       => $row['add'] ?? null,
            'rem'       => $row['rem'] ?? null,
            'chg'       => $row['chg'] ?? null,
        ]), [], [
            'id'        => $row['id'],
            'domain'    => $row['domain'],
        ]);
    }

    /**
     * @param $row
     * @return array
     */
    public function domainSetPassword(array $row): array
    {
        $info = $this->domainInfo(['domain' => $row['domain']]);

        $row = $this->prepareDataForUpdate($row, $info, [
            'password' => 'pw',
        ]);

        return $this->domainUpdate($row);
    }

    /**
     * @param array $row
     * @return array
     */
    public function domainSetNSs(array $row): array
    {
        $info = $this->domainInfo($row);

        $row = $this->prepareDataForUpdate($row, $info, [
            'nss' => 'nss',
        ]);

        return $this->domainUpdate($row);
    }

    /**
     * @param array $row
     * @param string $action
     * @param array $statuses
     * @return array
     */
    private function domainUpdateStatuses(
        array $row,
        string $action,
        array $statuses
    ): array {
        $row[$action]['statuses'] = $statuses;

        return $this->domainUpdate($row);
    }

    /**
     * @param array $rows
     * @param string $action
     * @param array $statuses
     * @return array
     */
    private function domainsUpdateStatuses(
        array $rows,
        string $action,
        array $statuses
    ): array {
        $res = [];
        foreach ($rows as $domainId => $domainData) {
            $res[$domainId] = $this->domainUpdateStatuses($domainData, $action, $statuses);
        }

        return $res;
    }

    /**
     * @param array $rows
     * @return array
     */
    public function domainsEnableLock(array $rows): array
    {
        return $this->domainsUpdateStatuses($rows, 'add', [
           'clientDeleteProhibited'     => null,
           'clientTransferProhibited'   => null,
        ]);
    }

    /**
     * @param array $rows
     * @return array
     */
    public function domainsDisableLock(array $rows): array
    {
        return $this->domainsUpdateStatuses($rows, 'rem', [
            'clientUpdateProhibited'    => null,
            'clientDeleteProhibited'    => null,
            'clientTransferProhibited'  => null,
        ]);
    }

    /**
     * @param array $row
     * @return array
     */
    public function domainEnableHold(array $row): array
    {
        return $this->domainUpdateStatuses($row, 'add', [
            'clientHold' => null,
        ]);
    }

    /**
     * @param array $row
     * @return array
     */
    public function domainDisableHold(array $row): array
    {
        return $this->domainUpdateStatuses($row, 'rem', [
            'clientHold' => null,
        ]);
    }

    public function domainRestore(array $row): array
    {
        return $this->tool->commonRequest('domain:restore', [
            'name'      => $row['domain'],
        ]);
    }
}
