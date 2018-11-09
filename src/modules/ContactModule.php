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

        if (err::is($info) || empty($info['id'])) {
            $info = $this->contactCreate($row);
        }
        return $info;
    }

    /**
     * @param array $row
     * @return array
     */
    public function contactInfo(array $row): array
    {
        $data = $this->tool->request(array_filter([
            'command'   => 'contact:info',
            'id'        => $row['epp_id'],
            'pw'        => $row['password'],
        ]));

        return array_filter([
            'id'            => $data['id'],
            'name'          => $data['name'],
            'password'      => $data['pw'],
            'email'         => $data['email'],
            'fax_phone'     => $data['fax'],
            'voice_phone'   => $data['voice'],
            'country'       => $data['cc'],
            'city'          => $data['city'],
            'org'           => $data['org'],
            'roid'          => $data['roid'],
            'postal_code'   => $data['pc'],
            'street'        => $data['street'],
            'sp'            => $data['sp'],
            'statuses'      => implode(',', array_keys($data['statuses'])),
            'created_by'    => $data['crID'],
            'created_date'  => $data['crDate'],
            'result_msg'    => $data['result_msg'],
            'result_reason' => $data['reasons'],
            'result_code'   => $data['result_code'],
            'result_lang'   => $data['result_lang'],
            'client_trid'   => $data['clTRID'],
            'epp_client_id' => $data['clID'],
            'server_trid'   => $data['svTRID'],
        ]);
    }

    /**
     * @param array $row
     * @return array
     */
    public function contactCreate(array $row): array
    {
        $data = $this->tool->request([
            'command'   => 'contact:create',
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
        ]);

        return array_filter([
            'id'            => $data['id'],
            'result_msg'    => $data['result_msg'],
            'client_trid'   => $data['clTRID'],
            'created_date'  => $data['crDate'],
            'result_code'   => $data['result_code'],
            'result_lang'   => $data['result_lang'],
            'server_trid'   => $data['svTRID'],
        ]);
    }
}
