<?php
/**
 * hiAPI hEPPy plugin
 *
 * @link      https://github.com/hiqdev/hiapi-heppy
 * @package   hiapi-heppy
 * @license   BSD-3-Clause
 * @copyright Copyright (c) 2017, HiQDev (http://hiqdev.com/)
 */


namespace hiapi\heppy\modules;

use Exception;

class PollModule extends AbstractModule
{
    const POLL_QUEUE_EMPTY = 1300;
    const POLL_QUEUE_FULL = 1301;
    const M024_MAINTENANCE = "M024: The maintenance window Registry Scheduled Maintenance";
    const M027_MAINTENANCE = "M027: The domain";
    const DOMAIN_RENEWAL_SUCCESSFUL = 'DOMAIN_RENEWAL_SUCCESSFUL';
    const DOMAIN_AUTO_RENEW_NOTICE ='Auto Renew Notice';
    const DOMAIN_DELETE_COMPLETED = 'Delete Completed';
    const REGISTRY_INITIATED_UPDATE_DOMAIN = 'Registry initiated update of domain';

    /** @var array */
    protected array $unusedPolls = [
        'Unused Objects Policy',
        'Unused objects policy',
    ];

    /** @var array */
    protected array $MXYZ_MAINTENANCE = [
        'M024' => self::M024_MAINTENANCE,
        'M027' => self::M027_MAINTENANCE,
        'DRS' => self::DOMAIN_RENEWAL_SUCCESSFUL,
        'DARN' => self::DOMAIN_AUTO_RENEW_NOTICE,
        'RIUD' => self::REGISTRY_INITIATED_UPDATE_DOMAIN,
    ];

    /**
     * @param array $row
     * @return array
     */
    public function pollAck(array $row = []): array
    {
        if (empty($row)) {
            throw new Exception('Array is empty');
        }

        $id = $row['id'] ?? null;
        if (empty($id)) {
            throw new Exception("msgID could not be empty");
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
        if (isset($row['request_client'])) {
            $row['outgoing'] = (string) $row['request_client'] !== (string) $this->tool->getRegistrar();
        }

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
