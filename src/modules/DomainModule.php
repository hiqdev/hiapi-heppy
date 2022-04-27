<?php

namespace hiapi\heppy\modules;

use hiapi\heppy\exceptions\EppErrorException;
use Exception;
use Throwable;

class DomainModule extends AbstractModule
{
    const DOMAIN_STANDART = 'standard';
    const DOMAIN_PREMIUM = 'premium';

    const RENEW_DOMAIN_NOT_AVAILABLE_EXCEPTION = "Invalid command name; Renew Domain not available";
    const RENEW_DOMAIN_AUTORENEW_RENEWONCE_EXCEPTION = "Invalid attribute value; explicit renewals not allowed for this TLD; please set domain to AUTORENEW or RENEWONCE";

    const NON_ALPHANUMERIC_EXCEPTION = 'authInfo code is invalid: password must contain at least one non-alphanumeric character';


    const DOMAIN_PREMIUM_REASON = 'PREMIUM DOMAIN';

    /** {@inheritdoc} */
    public $uris = [
        'domain' => 'urn:ietf:params:xml:ns:domain-1.0',
        'domain_hm' => 'http://hostmaster.ua/epp/domain-1.1',
    ];

    public $extURIs = [
        'rgp' => 'urn:ietf:params:xml:ns:rgp-1.0',
        'rgp_hm' => 'http://hostmaster.ua/epp/rgp-1.1',
    ];

    public $object = 'domain';

    protected $contactTypes = ['registrant', 'admin', 'tech', 'billing'];

    protected $KeySYSDelete = [
        'de' => 'TRANSIT',
        'at' => 'REGISTRY',
        'uk' => 'DETAGGED',
    ];

    /**
     * @param array $row
     * @return array
     */
    public function domainInfo(array $row): array
    {
        $info =  $this->tool->commonRequest("{$this->object}:info", array_filter([
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
            'ua_tm'             => 'license',
        ]);

        foreach (['domain', 'name'] as $key) {
            if (!empty($info[$key])) {
                $info[$key] = mb_strtolower($info[$key]);
            }
        }

        foreach (['nameservers', 'hosts'] as $key) {
            if (!empty($info[$key])) {
                if ($key === 'nameservers') {
                    $info['nss'] = $info['nameservers'];
                }

                $info[$key] = implode(",", $info[$key]);
            }
        }

        return $this->getContactsInfo($info);
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
            $row['nss'] = $this->tool->getDefaultNss();
        }

        $row = $this->_domainPrepareNSs($row);
        $row = $this->domainPrepareContacts($row);

        return $this->domainPerformOperation("{$this->object}:create", array_filter([
            'name'          => $row['domain'],
            'period'        => $row['period'],
            'registrant'    => $row['registrant_remote_id'],
            'admin'         => [$row['admin_remote_id']],
            'tech'          => [$row['tech_remote_id']],
            'billing'       => [$row['billing_remote_id']],
            'nss'           => $row['nss'],
            'pw'            => $row['password'] ?: $this->generatePassword(16),
            'secDNS'        => $row['secDNS'] ?? null,
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
        $remoteIds = [];
        foreach ($this->tool->getContactTypes() as $type) {
            $contactId = $row["{$type}_info"]['id'];
            $remoteId = $remoteIds[$contactId] ?? null;
            $row['license'] = $row['license'] ?? $row["{$type}_info"]['ua_tm'] ?? null;
            if (!$remoteId) {
                try {
                    $email = $row['whois_protected'] && !$this->isKeySysExtensionEnabled()
                        ? ($row['contacts']['wp'][$type]['email'] ?? $row['contacts'][$type]['email'] ?? null)
                        : ($row['contacts'][$type]['email'] ?? null);
                    $response = $this->tool->contactSet(array_merge($row["{$type}_info"], array_filter([
                        'whois_protected' => $row['whois_protected'] ? 1 : 0,
                        'email' => $email,
                    ], function($v) {return $v !== null;})));
                } catch (Throwable $e) {
                    throw new Exception($e->getMessage());
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
        return $this->tool->commonRequest("{$this->object}:delete", array_filter([
            'name'     => $row['domain'],
            $this->isKeySysExtensionEnabled() !== true || empty($this->KeySYSDelete[$this->getDomainTopZone($row['domain'])])
                ? null
                : 'keysys' => [
                    'command' => 'keysys:delete',
                    'target' => $this->KeySYSDelete[$this->getDomainTopZone($row['domain'])] ?? null,
                ],

            ])
        );
    }

    public function domainsDelete(array $rows): array
    {
        foreach ($rows as $id => $row) {
            $res[$id] = $this->tool->domainDelete($row);
        }

        return $res;
    }

    /**
     * @param array $row
     * @return array
     */
    public function domainRenew(array $row): array
    {
        $row = $this->_domainSetFee($row, 'renew');

        try {
            return $this->tool->commonRequest("{$this->object}:renew", array_filter([
                'name'          => $row['domain'],
                'curExpDate'    => $row['expires'],
                'period'        => $row['period'],
                'fee'           => $row['fee'] ?? null,
            ]), array_filter([
                'domain'            => 'name',
                'expiration_date'   => 'exDate',
            ]));
        } catch (EppErrorException $e) {
            if (!in_array($e->getMessage(), [self::RENEW_DOMAIN_NOT_AVAILABLE_EXCEPTION, self::RENEW_DOMAIN_AUTORENEW_RENEWONCE_EXCEPTION], true) || !$this->isKeySysExtensionEnabled()) {
                throw $e;
            }

            return $this->domainUpdate([
                'domain' => $row['domain'],
            ], [
                'command' => 'keysys:renew',
                'renewalmode' => 'RENEWONCE',
            ]);
        }
    }

    /**
     * @param array $row
     * @param string $op
     * @return array
     */
    private function performTransfer(array $row, string $op): array
    {
        return $this->tool->commonRequest("{$this->object}:transfer", array_filter([
            'op'        => $op,
            'name'      => $row['domain'],
            'pw'        => $row['password'],
            'period'    => $row['period'],
            'fee'       => $row['fee'] ?? null,
        ]), [
            'domain'            => 'name',
            'expiration_date'   => 'exDate',
            'action_date'       => 'acDate',
            'action_client_id'  => 'acID',
            'request_date'      => 'reDate',
            'request_client_id' => 'reID',
            'transfer_status'   => 'trStatus'
        ]);
    }

    public function domainCheckTransfer(array $row) : array
    {
        $check = $this->domainCheck($row['domain']);
        if ($check['avail'] === 1) {
            throw new Excepion('Object does not exist');
        }

        try {
            $res = $this->domainInfo($row);
        } catch (Throwable $e) {
            throw new Exception($e->getMessage());
        }

        return $this->_domainSetFee($res, 'transfer');
    }

    /**
     * @param array $row
     * @return array
     */
    public function domainTransfer(array $row): array
    {
        $row = $this->_domainSetFee($row, 'transfer');
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

    public function domainSaveContacts($row) : array
    {
        $contactModule = $this->tool->getModule('contact');
        if (!$contactModule->isAvailable()) {
            return $row;
        }

        if (empty($row['contacts'])) {
            return $this->base->_simple_domainSaveContacts($row);
        }

        foreach ($this->tool->getContactTypes() as $type) {
            $epp_id = $this->fixContactID($row['contacts']["{$type}_eppid"]);
            if (empty($epp_id)) {
                continue;
            }

            if (empty($saved[$epp_id])) {
                $contacts[$type] = $epp_id;
                $email = ($row['whois_protected'] && !$this->isKeySysExtensionEnabled())
                    ? ($row['contacts']['wp'][$type]['email'] ?? $row['contacts'][$type]['email'])
                    : $row['contacts'][$type]['email'];
                $data = $this->tool->contactSet(array_merge($row['contacts'][$type], [
                    'epp_id' => $row['contacts']["{$type}_eppid"],
                    'whois_protected' => $row['whois_protected'],
                    'email' => $email,
                ]));

                $contacts[$type] = $data['epp_id'];
                $saved[$epp_id] = $data['epp_id'];
            }

            $row[$type] = $saved[$epp_id];
        }

        return $this->domainSetContacts($row);
    }

    public function domainSetContacts($row) : array
    {
        $contactModule = $this->tool->getModule('contact');
        if (!$contactModule->isAvailable()) {
            return $row;
        }

        $this->domainSetWhoisProtect($row, $row['whois_protected']);

        $info = $this->domainInfo($row);

        $contactTypes = $this->tool->getContactTypes();
        if (empty($contactTypes)) {
            return $row;
        }

        return $this->_domainSetContacts($row, $info, $contactTypes);
    }

    /**
     * @param array $row
     * @return array
     */
    private function domainUpdate(array $row, array $keysys = null): array
    {
        $data = array_filter([
            'add'       => $row['add'] ?? null,
            'rem'       => $row['rem'] ?? null,
            'chg'       => $row['chg'] ?? null,
            'keysys'    => $keysys,
        ]);
        if (empty($data)) {
            return $row;
        }

        return $this->tool->commonRequest("{$this->object}:update", array_filter([
            'name'      => $row['domain'],
            'add'       => $row['add'] ?? null,
            'rem'       => $row['rem'] ?? null,
            'chg'       => $row['chg'] ?? null,
            'keysys'    => $keysys ?? null,
        ]), [], array_filter([
            'id'        => $row['id'] ?? null,
            'domain'    => $row['domain'],
        ]));
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
        $extensions = $this->tool->getExtensions();
        $row = $this->_domainPrepareNSs($row);
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
        $info = $this->domainInfo($row);
        $this->domainDisableUpdateProhibited($info);

        $old_statuses = array_filter($info['statuses'], function($k, $v) {
            $states = [
                self::CLIENT_TRANSFER_PROHIBITED => self::CLIENT_TRANSFER_PROHIBITED,
                self::CLIENT_DELETE_PROHIBITED => self::CLIENT_DELETE_PROHIBITED,
                self::CLIENT_HOLD => self::CLIENT_HOLD,
            ];

            return array_key_exists($k, $states) || in_array($v, $states, true) ? $v : null;
        }, ARRAY_FILTER_USE_BOTH);

        $new_states = array_filter($statuses, function($k, $v) use ($old_statuses, $action) {
            if ($action === 'rem') {
                return array_key_exists($k, $old_statuses ?? []) || in_array($v, $old_statuses ?? [], true);
            }

            if (empty($old_statuses)) {
                return true;
            }

            return !(array_key_exists($k, $old_statuses) || in_array($v, $old_statuses, true));
        }, ARRAY_FILTER_USE_BOTH);

        if (empty($new_states)) {
            return $row;
        }

        $row = [
            'domain' => $row['domain'],
            $action => [['statuses' => $new_states]],
        ];

        return $this->domainUpdate($row);
    }

    private function domainDisableUpdateProhibited(array $row)
    {
        if (!array_key_exists(self::CLIENT_UPDATE_PROHIBITED, $row['statuses']) && !in_array(self::CLIENT_UPDATE_PROHIBITED, $row['statuses'], true)) {
            return $row;
        }

        $data = $row;
        $data['rem'] = [['statuses' => [
            self::CLIENT_UPDATE_PROHIBITED => self::CLIENT_UPDATE_PROHIBITED,
        ]]];

        return $this->domainUpdate($data);
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
            self::CLIENT_TRANSFER_PROHIBITED => self::CLIENT_TRANSFER_PROHIBITED,
            self::CLIENT_DELETE_PROHIBITED => self::CLIENT_DELETE_PROHIBITED,
        ]);
    }

    /**
     * @param array $rows
     * @return array
     */
    public function domainsDisableLock(array $rows): array
    {
        return $this->domainsUpdateStatuses($rows, 'rem', [
            self::CLIENT_TRANSFER_PROHIBITED => self::CLIENT_TRANSFER_PROHIBITED,
            self::CLIENT_DELETE_PROHIBITED => self::CLIENT_DELETE_PROHIBITED,
            self::CLIENT_UPDATE_PROHIBITED => self::CLIENT_UPDATE_PROHIBITED,
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
        $res = $this->tool->commonRequest("{$this->object}:restore", [
            'name'      => $row['domain'],
            'rgp'       => [
                'command' => "{$this->extension}:request",
            ],
        ]);

        $info = $this->domainInfo($row);

        if (empty($info['statuses']['pendingRestore']) && !in_array('rgp', $info['statuses'], true) && !in_array('pendingDelete', $info['statuses'], true)) {
            return $row;
        }

        return $this->tool->commonRequest("{$this->object}:restore", [
            'name'      => $row['domain'],
            'rgp'       => [
                'command' => "{$this->extension}:report",
                'preData' => $row['domain'],
                'postData' => $row['domain'],
                'delTime' => date("Y-m-d\TH:i:s\Z", strtotime($row['delete_time'])),
                'resTime' => date("Y-m-d\TH:i:s\Z"),
            ],
        ]);
    }

    public function domainEnableWhoisProtect($row)
    {
        return $this->domainSetWhoisProtect($row, true);
    }

    public function domainDisableWhoisProtect($row)
    {
        $this->domainSetWhoisProtect($row, false);
    }

    public function domainsEnableWhoisProtect($row)
    {
        return $this->domainsSetWhoisProtect($row, true);
    }

    public function domainsDisableWhoisProtect($row)
    {
        return $this->domainsSetWhoisProtect($row, false);
    }

    public function domainsSetWhoisProtect($rows, $enable = null)
    {
        $res = [];
        foreach ($rows as $k=>$row) {
            $res[$k] = $this->tool->domainSetWhoisProtect($row, $enable);
        }

        return $res;
    }

    public function domainSetWhoisProtect($row, $enable = null)
    {
        if (!$this->isKeySysExtensionEnabled()) {
            return $row;
        }

        if (in_array($this->getDomainZone($row['domain']), $this->tool->getDisabledWPZones(), true)) {
            return $row;
        }

        return $this->domainUpdate($row, [
            'command' => 'keysys:whoisprotect',
            'whois-privacy' => $enable ? '0' : '1',
        ]);
    }

    protected function domainPerformOperation(
        string $command,
        array $input,
        array $returns = [],
        array $payload = [],
        bool $clearContact = false
    ): array {
        $input['clearContact'] = $this->isNamestoreExtensionEnabled() || $clearContact;

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

        throw new Exception('FIX Domain Perfom Code!');
    }

    protected function domainCheck(string $domain, ?string $command = null): array
    {
        try {
            $res = $this->_domainCheck($domain, true);
            $this->_parseCheckCharge($domain, $res);
            if ((int) $res['avails'][$domain] === 0 && $command === null) {
                return [
                    'avail' => (int) $res['avails'][$domain],
                    'reason' => $res['reasons'][$domain] ?? null,
                ];
            }

            $checkPremium = $this->_domainCheck($domain, false, $command ?? 'create');
        } catch (Throwable $e) {
            throw new Exception($e->getMessage());
        }
        if (!empty($checkPremium['price'])) {
            return $this->_parseCheckPrice($domain, $res, $checkPremium);
        }

        return $this->_parseCheckFee($domain, $res, $checkPremium);
    }

    protected function _parseCheckCharge(string $domain, array $data): array
    {
        return array_merge($data, array_filter([
            'premium' => isset($data['category']) && $data['category'] === self::DOMAIN_PREMIUM,
            'reason' => isset($data['category']) && $data['category'] === self::DOMAIN_PREMIUM ? self::DOMAIN_PREMIUM_REASON : ($data[$domain]['reason'] ?? null),
            'category_name' => $data['category_name'] ?? null,
            'fee' => isset($data['category']) && $data['category'] === self::DOMAIN_PREMIUM ? [
                'create' => $data['create'],
                'renew' => $data['renew'],
                'restore' => $data['restore'],
                'transfer' => $data['transfer'],
                'category_name' => $data['category_name'] ?? null,
            ] : null,
        ]));
    }

    protected function _parseCheckFee(string $domain, array $data, array $res): array
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
        $res['fee'][$domain]['currency'] = $this->tool->getCurrency();
        return [
            'avail' => (int) $data['avails'][$domain],
            'reason' => self::DOMAIN_PREMIUM_REASON,
            'fee' => $res['fee'][$domain],
        ];
    }

    protected function _parseCheckPrice(string $domain, array $data, array $res): array
    {
        $priceD = [];
        foreach ($res['price'][$domain] as $key => $value) {
            $key = str_replace('Price', '', $key);
            $priceD[$key] = $value;
        }

        $res['fee'][$domain] = array_merge($priceD, [
            'currency' => $this->tool->getCurrency(),
        ]);
        return [
            'avail' => (int) $data['avails'][$domain],
            'reason' => self::DOMAIN_PREMIUM_REASON,
            'fee' => $res['fee'][$domain],
        ];
    }

    protected function _domainCheck(string $domain, $withoutExt = false, string $action = 'create'): array
    {
        return $this->tool->commonRequest("{$this->object}:check", [
            'names'     => [$domain],
            'reasons'   => 'reasons',
            'withoutExt' => $withoutExt,
            'fee-action' => $action,
        ], [
            'avails'    => 'avails',
            'reasons'   => 'reasons',
            'fee'       => 'fee',
            'price'     => 'price',
            'charge'    => 'charge',
        ]);
    }

    protected function getDomainTopZone(string $domain): string
    {
        $parts = explode('.', $domain);
        return array_pop($parts);
    }

    protected function getDomainZone(string $domain): string
    {
        return substr($domain,strpos($domain,'.'));
    }

    protected function _domainPrepareNSs($row): array
    {
        foreach ($row['nss'] as $host) {
            $parts = explode(".", $row['domain']);
            $zone =  array_pop($parts);

            $avail = $this->tool->hostCheck([
                'host' => $host,
                'zone' => $zone,
            ]);

            if ((int) $avail['avail'] === 1) {
                $this->tool->hostCreate([
                    'host' => $host,
                    'zone' => $zone,
                ]);
            }

        }

        return $row;
    }

    protected function _domainSetFee(array $row, string $op): array
    {
        $data = $this->domainCheck($row['domain'], $op);
        if (empty($data['reason']) || $data['reason'] !== self::DOMAIN_PREMIUM_REASON) {
            return $row;
        }

        if ($data['fee']['fee'] != $row['standart_price'] && in_array($op, ['renew', 'transfer'], true)) {
            return $row;
        }

        return array_merge($row, [
            'fee' => $data['fee']['fee'],
            'reason' => self::DOMAIN_PREMIUM_REASON,
        ]);
    }

    private function _domainSetContacts(array $row, array $info, array $contactTypes, ?bool $fixEPPID = true): array
    {
        $orow = $row;
        foreach ($contactTypes as $type) {
            $row[$type] = $fixEPPID ? $this->fixContactID($row[$type]) : $row[$type];
            $info[$type] = !empty($info[$type]) ? $info[$type] : null;
            if ($type === 'registrant') {
                continue;
            }

            $row[$type] = [$row[$type]];
            $info[$type] = is_array($info[$type]) ? $info[$type] : [$info[$type]];
        }

        $row = $this->prepareDataForUpdate($row, $info, $contactTypes);

        if (!empty($row['chg']) && !empty($row['registrant']) && in_array('registrant', $contactTypes, true)) {
            $res = $this->domainUpdate([
                'domain' => $row['domain'],
                'chg' => [
                    'registrant' => $row['registrant'],
                ],
            ]);

            unset($row['chg']);
        }

        foreach (['add', 'rem'] as $op) {
            foreach ($row[$op] ?? [] as $id => $value) {
                foreach ($contactTypes as $type) {
                    if (empty($row[$op][$id][$type])) {
                        continue;
                    }

                    $row[$op][$id][$type] = array_filter($row[$op][$id][$type]);
                }

                $row[$op][$id] = array_filter($row[$op][$id]);
            }

            $row[$op] = array_filter($row[$op] ?? []);
            if (empty($row[$op])) {
                unset($row[$op]);
            }
        }

        if (empty($row['add']) && empty($row['rem'])) {
            return $row;
        }

        try {
            return $this->domainUpdate($row);
        } catch (Throwable $e) {
            if ($fixEPPID === false) {
                throw new Exception($e->getMessage());
            }

            return $this->_domainSetContacts($orow, $info, $contactTypes, false);
        }
    }

    protected function getContactsInfo(array $info): array
    {
        $contacts = [];
        $mainContact = null;
        foreach ($this->tool->getContactTypes() as $type) {
            if (empty($info[$type])) {
                continue;
            }

            if (isset($contacts[$info[$type]])) {
                $info["{$type}c"] = $contacts[$info[$type]];
                continue;
            }

            try {
                $contact = $this->tool->contactInfo([
                    'epp_id' => $info[$type],
                ]);
            } catch (\Throwable $e) {
                continue;
            }

            $contacts[$info[$type]] = $contact;
            $info['contact'] = $info['contact'] ?? $contact;
        }

        unset($info['contact']['epp_id']);

        return $info;
    }
}
