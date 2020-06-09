<?php

namespace hiapi\heppy\modules;

use hiapi\heppy\exceptions\EppErrorException;
use arr;
use err;

class DomainModule extends AbstractModule
{
    const DOMAIN_STANDART = 'standard';
    const DOMAIN_PREMIUM = 'premium';
    /**
     * @param array $row
     * @return array
     */
    public function domainInfo(array $row): array
    {
        $info =  $this->tool->commonRequest('domain:info', array_filter([
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
            'nameservers'       => 'nss',
            'hosts'             => 'hosts',
            'secDNS'            => 'secDNS',
        ]);

        foreach (['domain', 'name'] as $key) {
            if (!empty($info[$key])) {
                $info[$key] = mb_strtolower($info[$key]);
            }
        }

        foreach (['nameservers','hosts','statuses'] as $key) {
            if (!empty($info[$key])) {
                $info[$key] = implode(",", $info[$key]);
            }
        }

        return $info;
    }

    public function domainGetInfo(array $row): array
    {
        return $this->domainInfo($row);
    }

    public function domainsGetInfo(array $rows): array
    {
        foreach ($rows as $id => $row) {
            $res[$id] = $this->domainInfo($row);
        }

        return $res;
    }

    public function domainsLoadInfo(array $rows): array
    {
        return $rows;
    }

    /**
     * @param array $row
     * @return array
     */
    public function domainsCheck(array $row): array
    {
        foreach ($row['domains'] as $domain) {
            $res[$domain] = $this->domainCheck($domain);
        }

        return $res;
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

        return $this->domainPerformOperation('domain:create', array_filter([
            'name'          => $row['domain'],
            'period'        => $row['period'],
            'registrant'    => $row['registrant_remote_id'],
            'admin'         => $row['admin_remote_id'],
            'tech'          => $row['tech_remote_id'],
            'billing'       => $row['billing_remote_id'],
            'nss'           => $row['nss'],
            'pw'            => $row['password'],
            'secDNS'        => $row['secDNS'],
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

    protected function domainPerformOperation(
        string $command,
        array $input,
        array $returns = [],
        array $payload = [],
        bool $clearContact = false
    ): array {
        $input['clearContact'] = $this->isNamestoreExtensionEnabled() && $clearContact;
        try {
            return $this->tool->commonRequest($command, $input, $returns, $payload);
        } catch (EppErrorException $e) {
            if (strpos($e->getMessage(), 'does NOT support contact') === false) {
                throw new EppErrorException($e->getMessage());
            }

            if (!$this->isNamestoreExtensionEnabled() || $input['clearContact'] === true) {
                throw new EppErrorException($e->getMessage());
            }

            return $this->domainPerformOperation($command, $input, $returns, $payload, true);
        }

        throw new \Exception('FIX Domain Perfom Code!');
    }

    protected function domainCheck(string $domain) : array
    {
        $res = $this->_domainCheck($domain, true);
        if ((int) $res['avails'][$domain] === 0) {
            return [
                'avail' => (int) $res['avails'][$domain],
                'reason' => $res['reasons'][$domain] ?? null,
            ];
        }

        $checkPremium = $this->_domainCheck($domain);
        if (!empty($checkPremium['price'])) {
            return $this->_parseCheckPrice($domain, $res, $checkPremium);
        }

        return $this->_parseCheckFee($domain, $res, $checkPremium);
    }

    protected function _parseCheckFee(string $domain, array $data, array $res) : array
    {
        if (empty($res['fee']) || empty($res['fee'][$domain]) || empty($res['fee'][$domain]['class'])) {
            return [
                'avail' => (int) $data['avails'][$domain],
            ];
        }

        if ($res['fee'][$domain]['class'] === self::DOMAIN_STANDART) {
            return [
                'avail' => (int) $data['avails'][$domain],
            ];
        }

        $res['fee'][$domain]['premium'] = 1;
        return [
            'avail' => (int) $data['avails'][$domain],
            'reason' => 'PREMIUM DOMAIN',
            'fee' => $res['fee'][$domain],
        ];
    }

    protected function _parseCheckPrice(string $domain, array $data, array $res) : array
    {
        $priceD = [];
        foreach ($res['price'][$domain] as $key => $value) {
            $key = str_replace('Price', '', $key);
            $priceD[$key] = $value;
        }

        $res['fee'][$domain] = $priceD;
        return [
            'avail' => (int) $data['avails'][$domain],
            'reason' => 'PREMIUM DOMAIN',
            'fee' => $res['fee'][$domain],
        ];
    }

    protected function _domainCheck(string $domain, $withoutExt = false) : array
    {
        return $this->tool->commonRequest('domain:check', [
            'names'     => [$domain],
            'reasons'   => 'reasons',
            'withoutExt' => $withoutExt,
        ], [
            'avails'    => 'avails',
            'reasons'   => 'reasons',
            'fee'       => 'fee',
            'price'     => 'price',
        ]);
    }
}
