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
        $res = $this->tool->contactInfo($row);

        if (err::is($res) || empty($res['id'])) {
            $res = $this->contactCreate($row);
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
            'id'            => 'id',
            'name'          => 'name',
            'password'      => 'pw',
            'email'         => 'email',
            'fax_phone'     => 'fax',
            'voice_phone'   => 'voice',
            'country'       => 'cc',
            'city'          => 'city',
            'org'           => 'org',
            'roid'          => 'roid',
            'postal_code'   => 'pc',
            'street'        => 'street',
            'sp'            => 'sp',
            'created_by'    => 'crID',
            'created_date'  => 'crDate',
            'epp_client_id' => 'clID',
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
            'name'      => $row['host'],
            'add'       => $row['add'],
            'rem'       => $row['rem'],
            'chg'       => $row['chg'],
        ]));
    }
}
