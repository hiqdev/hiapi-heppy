<?php

namespace hiapi\heppy\modules;

use hiapi\legacy\lib\deps\err;
use hiapi\legacy\lib\deps\check;

class PollModule extends AbstractModule
{
    const POLL_QUEUE_EMPTY = 1300;
    const POLL_QUEUE_FULL = 1301;
    const M024_MAINTENANCE = "M024: The maintenance window Registry Scheduled Maintenance";
    const M027_MAINTENANCE = "M027: The domain";

    /** @var array */
    protected $unusedPolls = [
        'Unused Objects Policy',
        'Unused objects policy',
    ];

    /** @var array */
    protected $MXYZ_MAINTENANCE = [
        'M024' => self::M024_MAINTENANCE,
        'M027' => self::M027_MAINTENANCE,
    ];

    /**
     * @param array $row
     * @return array
     */
    public function pollAck(array $row = []): array
    {
        if (empty($row)) {
            return err::set($row, 'array is empty');
        }

        $id = $row['id'];
        if (err::is($id)) {
            return err::set($row, err::get($id));
        }

        $res = $this->tool->commonRequest('epp:poll', [
            'op' => 'ack',
            'msgID' => (string) $id,
        ]);

        return $res;
    }

    public function pollReq(array $row = []) : array
    {
        return $this->tool->commonRequest('epp:poll', [
            'op' => 'req',
        ], [
            'count' => 'msgCount',
            'request_date' => 'reDate',
            'type' => 'trStatus',
            'name' => 'name',
            'id' => 'msgID',
            'request_client' => 'reID',
            'action_date' => 'acDate',
            'action_client' => 'acID',
            'message' => 'msg',
            'time' => 'exDate',
        ]);
    }

    /**
     * @params array $row
     * @return array
     */
    public function pollsGetNew(array $jrow = []) : array
    {
        $polls = [];
        $rc = $this->pollReq();
        $i = 1;
        while ((int) $rc['result_code'] === self::POLL_QUEUE_FULL) {
            $poll = $rc;
            $this->pollAck($rc);
            $rc = $this->pollReq();
            $polls[$poll['id']] = $this->_pollPostEvent($poll);
            $i++;
            if ($i > 10) {
                break;
            }
        }

        return $polls;
    }

    /**
     * @params array $row
     * @return array
     */
    public function pollsDeleteUnsupported(array $jrow = [])
    {
        $polls = [];
        $rc = $this->pollReq();
        $i = 1;
        while ((int) $rc['result_code'] === self::POLL_QUEUE_FULL) {
            $poll = $this->_pollPostEvent($rc);
            if ($this->_pollCheckUnsupported($poll['message']) === false) {
                break;
            }

            $this->pollAck($poll);
            $rc = $this->pollReq();
            $i++;
            $polls[] = $poll;
            if ($i > 10) {
                break;
            }
        }

        return $polls;
    }

    /**
     * @param array row
     * @return array
     */
    protected function _pollPostEvent(array $row) : array
    {
        foreach (['action_date', 'request_date', 'time'] as $key) {
            if (empty($row[$key])) {
                continue;
            }

            $row[$key] = date("Y-m-d H:i:s", strtotime($row[$key]));
        }

        $row['class'] = 'domain';
        return $row;
    }

    protected function _pollCheckUnsupported(string $message) : bool
    {
        if (in_array($message,  $this->unusedPolls, true)) {
            return true;
        }

        foreach ($this->MXYZ_MAINTENANCE as $name => $str) {
            if (strpos($message, $str) !== false) {
                return true;
            }
        }

        return false;
    }
}
