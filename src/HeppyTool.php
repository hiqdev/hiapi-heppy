<?php
/**
 * hiAPI hEPPy plugin
 *
 * @link      https://github.com/hiqdev/hiapi-heppy
 * @package   hiapi-heppy
 * @license   BSD-3-Clause
 * @copyright Copyright (c) 2017, HiQDev (http://hiqdev.com/)
 */

namespace hiapi\heppy;

/**
 * hEPPy tool.
 */
class HeppyTool extends \hiapi\components\AbstractTool
{
    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    public function domainInfo($row)
    {
        $data = $this->client->request([
            'command'   => 'domain:info',
            'name'      => $row['name'],
        ]);
        var_dump($data);die;
    }
}
