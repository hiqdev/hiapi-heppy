<?php

namespace hiapi\heppy\modules;

use err;

class ContactModule extends AbstractModule
{
    public function contactSet(array $row): void
    {
        $info = $this->tool->contactInfo($row);
        $row['id'] = $info['id'] ?? null;

        if (err::is($info) || empty($info['id'])) {
            $this->contactCreate($row);
        }
        else {
            $this->contactUpdate($row);
        }
    }

    public function contactInfo(array $row)
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
            'cc'            => $data['cc'],
            'city'          => $data['city'],
            'email'         => $data['email'],
            'fax'           => $data['fax'],
            'voice'         => $data['voice'],
            'org'           => $data['org'],
            'roid'          => $data['roid'],
            'pc'            => $data['pc'],
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
}
