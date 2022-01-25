<?php

namespace hiapi\heppy\modules;

use Throwable;
use Exception;

class ContactModule extends AbstractModule
{
    const NON_ALPHANUMERIC_EXCEPTION = 'authInfo code is invalid: password must contain at least one non-alphanumeric character';

    /** {@inheritdoc} */
    public $uris = [
        'contact' => 'urn:ietf:params:xml:ns:contact-1.0',
        'contact_hm' => 'http://hostmaster.ua/epp/contact-1.1',
    ];

    public $extURIs = [
        'namestoreExt' => 'http://www.verisign-grs.com/epp/namestoreExt-1.1',
    ];

    public $object = 'contact';

    /** {@inheritdoc} */
    public function isAvailable() : bool
    {
        return !$this->isNamestoreExtensionEnabled();
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
    public function contactCreate(array $row, ?bool $addsympols = false): array
    {
        if (!$this->isAvailable()) {
            return $row;
        }

        if ($addsympols === true) {
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
                'cc'        => $row['country']      ?? null,
                'city'      => $row['city']         ?? null,
                'street1'   => $row['street1']      ?? null,
                'street2'   => $row['street2']      ?? null,
                'street3'   => $row['street3']      ?? null,
                'pc'        => $row['postal_code']  ?? null,
                'pw'        => $row['password'] ?: $this->generatePassword(16, $addsympols),
                'disclose'  => $row['whois_protected'] ? 1 : 0,
            ], $this->getFilterCallback()), [
                'epp_id'        => 'id',
                'created_date'  => 'crDate',
            ]);
        } catch (Throwable $e) {
            if (strpos($e->getMessage(), self::NON_ALPHANUMERIC_EXCEPTION) !== false) {
                return $this->contactCreate($row, true);
            }
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @param array $row
     * @param array|null $info
     * @return array
     */
    public function contactUpdate(array $row, array $info): array
    {
        if (!$this->isAvailable()) {
            return $row;
        }

        $row = $this->prepareDataForContactUpdate($row, $info);

        return $this->tool->commonRequest("{$this->object}:update", array_filter([
            'id'        => $row['epp_id'],
            'chg'       => $row['chg'],
        ]), [], [
            'epp_id'    => $this->fixContactID($row['epp_id']),
        ]);
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
    private function prepareDataForContactUpdate(array $local, array $remote): array
    {
        return [
            'epp_id' => $local['epp_id'],
            'chg' => array_filter([
                'name'      => $local['name'],
                'org'       => $local['organization'] ?? null,
                'email'     => $local['email'],
                'fax'       => $local['fax_phone'] ?? null,
                'voice'     => $local['voice_phone'],
                'cc'        => $local['country'],
                'city'      => $local['city'],
                'pc'        => $local['postal_code'],
                'street1'   => $local['street1'],
                'street2'   => $local['street2'] ?? null,
                'street3'   => $local['street3'] ?? null,
                'sp'        => $local['province'] ?? null,
                'pw'        => $local['password'] ?? null,
                'disclose'  => strval((int) (!$local['whois_protected'])),
            ]),
        ];
    }

    private function parseEPPInfo(array $info, array $map): array
    {
        foreach (['int', 'loc'] as $type) {
            if (empty($info[$type])) {
                continue;
            }

            if (isset($info[$type]['name']) && empty($first_name)) {
                [$first_name, $last_name] = explode(" ", $info[$type]['name'], 2);
            }

            $org = $org ?? ($info[$type]['org'] ?? null);
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
}
