<?php

namespace hiapi\heppy\modules;

use err;

class ContactModule extends AbstractModule
{
    /**
     * @param array $row
     * @return array
     */
    public function contactSet(array $row): array
    {
        $info = $this->tool->contactInfo($row);

        if (err::is($info) || empty($info['epp_id'])) {
            $res = $this->contactCreate($row);
        } else {
            $row = $this->prepareDataForUpdate($row, $info, [
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
            ]);
            $res = $this->contactUpdate($row);
        }

        return $res;
    }

    /**
     * @param array $row
     * @return array
     */
    public function contactInfo(array $row): array
    {
        return $this->tool->commonRequest('contact:info', array_filter([
            'id'        => $row['epp_id'],
            'pw'        => $row['password'],
        ]), [
            'epp_id'        => 'id',
            'name'          => 'name',
            'organization'  => 'org',
            'password'      => 'pw',
            'email'         => 'email',
            'fax_phone'     => 'fax',
            'voice_phone'   => 'voice',
            'country'       => 'cc',
            'city'          => 'city',
            'org'           => 'org',
            'roid'          => 'roid',
            'postal_code'   => 'pc',
            'street1'       => 'street',
            'province'      => 'sp',
            'statuses'      => function ($data) {
                implode(',', array_keys($data['statuses']));
            },
        ]);
    }

    /**
     * @param array $row
     * @return array
     */
    public function contactCreate(array $row): array
    {
        return $this->tool->commonRequest('contact:create', [
            'id'        => $row['epp_id'],
            'name'      => $row['name'],
            'email'     => $row['email'],
            'voice'     => $row['voice_phone'],
            'fax'       => $row['fax_phone'],
            'org'       => $row['org'],
            'cc'        => $row['country'],
            'city'      => $row['city'],
            'street1'   => $row['street1'],
            'street2'   => $row['street2'],
            'street3'   => $row['street3'],
            'pc'        => $row['postal_code'],
            'pw'        => $row['password'] ?: $this->generatePassword(),
        ], [
            'id'            => 'id',
            'created_date'  => 'crDate',
        ]);
    }

    /**
     * @param array $row
     * @return array
     */
    public function contactUpdate(array $row): array
    {
        return $this->tool->commonRequest('contact:update', array_filter([
            'id'        => $row['epp_id'],
            'add'       => $row['add'],
            'rem'       => $row['rem'],
            'chg'       => $row['chg'],
        ]));
    }

    /**
     * @param $local
     * @param $remote
     * @param $map
     * @return array
     */
    private function prepareDataForUpdate(array $local, array $remote, array $map): array
    {
        $add = [];
        $chg = [];
        $rem = [];

        foreach ($map as $apiName => $eppName) {
            if (key_exists($apiName, $local)
                && !key_exists($apiName, $remote)
                && !is_null($local[$apiName])) {
                $add[$eppName] = $local[$apiName];
            } else if (key_exists($apiName, $local)
                && key_exists($apiName, $remote)
                && !is_null($local[$apiName])
                && $local[$apiName] !== $remote[$apiName]) {
                $chg[$eppName] = $local[$apiName];
            } else if (key_exists($apiName, $remote)
                && !key_exists($apiName, $local)
                && !is_null($remote[$apiName])) {
                $rem[$eppName] = $remote[$apiName];
            }
        }
        empty($add) ?: $local['add'] = $add;
        empty($chg) ?: $local['chg'] = $chg;
        empty($rem) ?: $local['rem'] = $rem;

        return $local;
    }

    /**
     * @param array $row
     * @return array
     */
    public function contactDelete(array $row): array
    {
        return $this->tool->commonRequest('contact:delete', [
            'id'    => $row['epp_id'],
        ]);
    }
}
