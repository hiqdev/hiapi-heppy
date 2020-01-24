<?php
/**
 * hiAPI hEPPy plugin
 *
 * @link      https://github.com/hiqdev/hiapi-heppy
 * @package   hiapi-heppy
 * @license   BSD-3-Clause
 * @copyright Copyright (c) 2017, HiQDev (http://hiqdev.com/)
 */

namespace hiapi\heppy\extensions;

/**
 * Abstract class of EPP extension
 */
abstract class AbstractExtension
{
    /** @var string */
    public $urlns;

    /** @var string */
    public $version;

    /** @var array */
    public $availableCommands = [];

    public function __construct(array $data)
    {
        $this->version = $data['version'] ?? '';
        $this->urlns = $data['urlns'] ?? array_shift($data);
    }

    /** {@inheritdoc} */
    public function apply(string $command, array $data): array
    {
        if ($this->isApplicable($command, $data)) {
            return $this->addExtension($command, $data);
        }

        return $data;
    }

    /** {@inheritdoc} */
    public function isApplicable(string $command, array $data): bool
    {
        [$object, $command] = explode(":", $command, 2);
        $object = $this->availableCommands['*'] ? '*' : $object;
        $command = $this->availableCommands[$object]['*'] ? '*' : $command;

        if (!empty($this->availableCommands[$object][$command]['*'])) {
            return true;
        }

        if (!empty($this->availableCommands[$object][$command][$data['op']])) {
            return true;
        }

        return false;
    }

    /** {@inheritdoc} */
    public function  addExtension(string $command, array $data): array
    {
        return $data;
    }

    /**
     * @param array $data
     * @param string|null $name
     * @return null|string
     */
    protected function findZone(string $command, array $data, string $name = null): ?string
    {
        if (isset($data['zone'])) {
            return $data['zone'];
        }
        if (!$name && isset($data['name'])) {
            $name = $data['name'];
        }
        if (!$name && isset($data['names'])) {
            $name = reset($data['names']);
        }

        return array_pop(explode('.', $name));
    }
}
