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
            $res = $this->contactUpdate($row, $info);
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
        $row = $this->prepareDataForContactUpdate($row, $info);

        return $this->tool->commonRequest('contact:update', array_filter([
            'id'        => $row['epp_id'],
            'add'       => $row['add'],
            'rem'       => $row['rem'],
            'chg'       => $row['chg'],
        ]), [], [
            'epp_id'    => $row['epp_id']
        ]);
    }

    /**
     * @param array $local
     * @param array $remote
     * @return array
     */
    private function prepareDataForContactUpdate(array $local, array $remote): array
    {
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
        ]);
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
