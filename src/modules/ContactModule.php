<?php

namespace hiapi\heppy\modules;

use Throwable;
use Exception;

class ContactModule extends AbstractModule
{
    const NON_ALPHANUMERIC_EXCEPTION = 'authInfo code is invalid: password must contain at least one non-alphanumeric character';
    const INCORECT_AUTHINFO_EXCEPTION = 'Parameter value syntax error Incorrect authInfo';

    const UNIMPLEMENTED_OPTION_DISCLOSE = 'Unimplemented option Disclose element not supported';
    const UNSUPPORTED_DISCLOSE_FLAG = 'Data management policy violation Unsupported disclose flag';
    const WHOIS_PRIVACY_NOT_AVAILABLE = 'Invalid attribute value; whois privacy not available for';

    /** {@inheritdoc} */
    public array $uris = [
        'contact' => 'urn:ietf:params:xml:ns:contact-1.0',
        'contact_hm' => 'http://hostmaster.ua/epp/contact-1.1',
    ];

    public array $extURIs = [
        'namestoreExt' => 'http://www.verisign-grs.com/epp/namestoreExt-1.1',
    ];

    public ?string $object = 'contact';

    /** {@inheritdoc} */
    public function isAvailable() : bool
    {
        $exts = $this->tool->getExtensions();
        return empty($exts['namestoreExt']) || $this->tool->getSvID() !== 'VeriSign Com/Net EPP Registration Server';
    }

    /**
     * @param array $row
     * @return array
     */
    public function contactSet(array $row): array
    {
        if (!$this->isAvailable()) {
            return $row;
        }

        $row['epp_id'] = $this->fixContactID($row['epp_id']);

        try {
            $info = $this->tool->contactInfo($row);
        } catch (\Throwable $e) {
            if (strpos($e->getMessage(), 'Broken pipe') !== false) {
                return $this->contactSet($row);
            }

            if ($e->getMessage() === self::UNIMPLEMENTED_COMMAND) {
                throw $e;
            }

            return $this->contactCreate($row);
        }

        return $this->contactUpdate($row, $info);
    }

    /**
     * @param array $row
     * @return array
     */
    public function contactInfo(array $row): array
    {
        if (!$this->isAvailable()) {
            return $row;
        }

        $map = [
            'name'          => 'name',
            'organization'  => 'org',
            'country'       => 'cc',
            'city'          => 'city',
            'org'           => 'org',
            'roid'          => 'roid',
            'postal_code'   => 'pc',
            'street1'       => 'street',
            'province'      => 'sp',
            'password'      => 'pw',
        ];

        $res = $this->tool->commonRequest("{$this->object}:info", array_filter([
            'id'        => $row['epp_id'],
            'pw'        => $row['password'] ?? null,
            'domain'    => $row['domain'] ?? null,
        ]), array_merge([
            'epp_id'        => 'id',
            'password'      => 'password',
            'fax_phone'     => 'fax',
            'voice_phone'   => 'voice',
            'statuses'      => 'statuses',
            'loc'           => 'loc',
            'int'           => 'int',
            'email'         => 'email',
            'disclose'      => 'disclose',
        ], $map));

        return $this->parseEPPInfo($res, $map);
    }

    /**
     * @param array $row
     * @return array
     */
    public function contactCreate(array $row, ?bool $addsymbols = false, ?bool $disclose = true): array
    {
        if (!$this->isAvailable()) {
            return $row;
        }

        if ($addsymbols === true) {
            $row['password'] = $this->generatePassword(16, true);
        }

        try {
            return $this->tool->commonRequest("{$this->object}:create", array_filter([
                'id'        => $this->fixContactID($row['epp_id']),
                'name'      => $row['name'],
                'email'     => $row['email'],
                'voice'     => $row['voice_phone'],
                'fax'       => $row['fax_phone']    ?? null,
                'org'       => $row['organization'] ?? null,
                'cc'        => !empty($row['country']) ? strtoupper($row['country'] === 'gdpr' ? 'cy' : $row['country']) : null,
                'city'      => $row['city']         ?? null,
                'street1'   => $row['street1']      ?? null,
                'street2'   => $row['street2']      ?? null,
                'street3'   => $row['street3']      ?? null,
                'pc'        => !empty($row['postal_code'])  ? substr($row['postal_code'], 0, 15) : null,
                'sp'        => $row['province']     ?? null,
                'pw'        => $row['password'] ?: $this->generatePassword(16, $addsymbols),
                'disclose'  => $disclose !== false ? ($row['whois_protected'] ? '0' : '1') : null,
                'domain'    => $row['domain'] ?? null,
                'neulevel'  => $this->setNexusData($row),
            ], $this->getFilterCallback()), [
                'epp_id'        => 'id',
                'created_date'  => 'crDate',
            ]);
        } catch (Throwable $e) {
            if (strpos($e->getMessage(), self::NON_ALPHANUMERIC_EXCEPTION) !== false) {
                return $this->contactCreate($row, true, $disclose);
            }

            if (strpos($e->getMessage(), self::INCORECT_AUTHINFO_EXCEPTION) !== false) {
                return $this->contactCreate($row, true, $disclose);
            }

            if (strpos($e->getMessage(), self::UNIMPLEMENTED_OPTION_DISCLOSE) !== false && $disclose !== false) {
                return $this->contactCreate($row, $addsymbols, false);
            }

            if (strpos($e->getMessage(), self::UNSUPPORTED_DISCLOSE_FLAG) !== false && $disclose !== false) {
                return $this->contactCreate($row, $addsymbols, false);
            }

            if (strpos($e->getMessage(), self::WHOIS_PRIVACY_NOT_AVAILABLE) !== false && $disclose !== false) {
                return $this->contactCreate($row, $addsymbols, false);
            }

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @param array $row
     * @param array|null $info
     * @return array
     */
    public function contactUpdate(array $row, array $info, ?bool $disclose = true): array
    {
        if (!$this->isAvailable()) {
            return $row;
        }

        $row = array_merge($row, array_filter([
            'country' => !empty($row['country']) ? strtoupper($row['country'] === 'gdpr' ? 'cy' : $row['country']) : null,
            'postal_code' => !empty($row['postal_code'])  ? substr($row['postal_code'], 0, 15) : null,
        ]));

        $data = $this->prepareDataForContactUpdate($row, $info, $disclose);
        try {
            return $this->tool->commonRequest("{$this->object}:update", array_filter([
                'id'        => $data['epp_id'],
                'domain'    => $row['domain'] ?? null,
                'chg'       => array_filter($data['chg'], function($v){return $v !== '' && !is_null($v);}),
                'neulevel'  => $this->setNexusData($row),
            ]), [], [
                'epp_id'    => $this->fixContactID($data['epp_id']),
            ]);
        } catch (Throwable $e) {
            if (strpos($e->getMessage(), self::UNIMPLEMENTED_OPTION_DISCLOSE) !== false && $disclose !== false) {
                return $this->contactUpdate($row, $info, false);
            }

            if (strpos($e->getMessage(), self::UNSUPPORTED_DISCLOSE_FLAG) !== false && $disclose !== false) {
                return $this->contactUpdate($row, $info, false);
            }

            if (strpos($e->getMessage(), self::WHOIS_PRIVACY_NOT_AVAILABLE) !== false && $disclose !== false) {
                return $this->contactUpdate($row, $info, false);
            }

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @param array $row
     * @return array
     */
    public function contactDelete(array $row): array
    {
        if (!$this->isAvailable()) {
            return $row;
        }

        return $this->tool->commonRequest("{$this->object}:delete", [
            'id'    => $this->fixContactID($row['epp_id']),
        ]);
    }

    /**
     * @param array $local
     * @param array $remote
     * @return array
     */
    private function prepareDataForContactUpdate(array $local, array $remote, ?bool $disclose = true): array
    {
        return [
            'epp_id' => $local['epp_id'],
            'chg' => array_filter([
                'name'      => $local['name'],
                'org'       => $local['organization'] ?? '',
                'email'     => $local['email'],
                'fax'       => $local['fax_phone'] ?? null,
                'voice'     => $local['voice_phone'],
                'cc'        => strtoupper($local['country']),
                'city'      => $local['city'],
                'pc'        => $local['postal_code'],
                'street1'   => $local['street1'],
                'street2'   => $local['street2'] ?? null,
                'street3'   => $local['street3'] ?? null,
                'sp'        => $local['province'] ?? null,
                'pw'        => $local['password'] ?? '/1yIv!QaQ(6U',
                'disclose'  => $disclose !== false ? (strval((int) (!$local['whois_protected']))) : null,
            ], function($v) {return !is_null($v);}),
        ];
    }

    private function parseEPPInfo(array $info, array $map): array
    {
        foreach (['int', 'loc'] as $type) {
            if (empty($info[$type])) {
                continue;
            }

            if (isset($info[$type]['name']) && empty($first_name)) {
                if (strpos($info[$type]['name'], " ") !== false) {
                    [$first_name, $last_name] = explode(" ", $info[$type]['name'] ?? '', 2);
                } else {
                    $first_name = $info[$type]['name'];
                }
            }

            $org = $org ?? ($info[$type]['org'] ?? null);
            $first_name = $first_name ?? $org ?? null;
            $last_name = $last_name ?? $org ?? null;
            $addr = $addr ?? ($info[$type]['addr'] ?? null);
        }

        $data['organization'] = $org ?? null;
        foreach ($map as $api => $epp) {
            if (isset($addr[$epp])) {
                $data[$api] = $addr[$epp];
            }
        }

        return array_merge([
            'first_name' => $first_name,
            'last_name' => $last_name,
        ], $data, $info);
    }

    private function setNexusData($row): ?string
    {
        $zone = $this->getZone($row);
        if ($zone !== 'us') {
            return null;
        }
        if (empty($row['organization'])) {
            $nexusCategory = $row['country'] === 'us' ? 'C11' : 'C12';
            $appPurpose = 'P3';
        } else {
            $nexusCategory =  $row['country'] === 'us' ? 'C21' : ('C31/' . strtoupper($row['country']));
            $appPurpose = 'P2';
        }

        return implode(" ", [
            "NexusCategory={$nexusCategory}",
            "AppPurpose={$appPurpose}",
        ]);


    }
}
