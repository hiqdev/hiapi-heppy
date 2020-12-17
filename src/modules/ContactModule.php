<?php

namespace hiapi\heppy\modules;

use err;

class ContactModule extends AbstractModule
{
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
            'id'        => $this->fixContactID($row['epp_id']),
            'pw'        => $row['password'],
        ]), array_merge([
            'epp_id'        => 'id',
            'password'      => 'password',
            'fax_phone'     => 'fax',
            'voice_phone'   => 'voice',
            'statuses'      => 'statuses',
            'loc'           => 'loc',
            'int'           => 'int',
            'disclose'      => 'disclose',
        ], $map));

        return $res;
    }

    /**
     * @param array $row
     * @return array
     */
    public function contactCreate(array $row): array
    {
        if (!$this->isAvailable()) {
            return $row;
        }

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
            'pw'        => $row['password'] ?: $this->generatePassword(16),
            'disclose'  => $row['whois_protected'] ? 1 : 0,
        ], $this->getFilterCallback()), [
            'epp_id'        => 'id',
            'created_date'  => 'crDate',
        ]);
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
            'add'       => $row['add'] ?? null,
            'rem'       => $row['rem'] ?? null,
            'chg'       => $row['chg'] ?? null,
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
        $local['password'] = $local['password'] ?? $this->generatePassword();
        return $this->prepareDataForUpdate($local, $remote, [
            'name'          => 'name',
            'organization'  => 'org',
            'email'         => 'email',
            'fax_phone'     => 'fax',
            'voice_phone'   => 'voice',
            'country'       => 'cc',
            'city'          => 'city',
            'postal_code'   => 'pc',
            'street1'       => 'street1',
            'street2'       => 'street2',
            'street3'       => 'street3',
            'province'      => 'sp',
            'password'      => 'pw',
            'disclose'      => 'disclose',
        ]);
    }
}
