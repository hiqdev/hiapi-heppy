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
    protected $_client;

    public function domainInfo($row)
    {
        return $this->request([
            'command'   => 'domain:info',
            'name'      => $row['domain'],
        ]);
    }

    protected function addNamestoreExt(array $data, string $zone = null): array
    {
        $zone = strtoupper($zone ?: $this->findZone($data));
        if (in_array($zone, ['COM', 'NET'])) {
            $data['extensions']['namestoreExt:subProduct'] = 'namestoreExt:subProduct';
            $data['subProduct'] = $zone;
        }

        return $data;
    }

    protected function findZone(array $data, string $name = null): ?string
    {
        if (isset($data['zone'])) {
            return $data['zone'];
        }
        if (!$name) {
            $name = $name ?: $data['name'] ?? null;
        }

        return array_pop(explode('.', $name));
    }

    protected function request(array $data): array
    {
        $data = $this->addNamestoreExt($data);
        return $this->getClient()->request($data);
    }

    protected function getClient(): ClientInterface
    {
        if ($this->_client === null) {
            $this->_client = new RabbitMQClient([
                [
                    'host'      => $this->data['url']       ?? null,
                    'port'      => $this->data['port']      ?? 5672,
                    'user'      => $this->data['login']     ?? 'guest',
                    'password'  => $this->data['password']  ?? 'guest',
                    'vhost'     => $this->data['vhost']     ?? '/',
                ],
            ], $this->data['queue'] ?? null);
        }

        return $this->_client;
    }
}
