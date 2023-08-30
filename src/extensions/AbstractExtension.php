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

use hiapi\heppy\HeppyTool;

/**
 * Abstract class of EPP extension
 */
abstract class AbstractExtension
{
    /** @var string */
    public ?string $urlns = null;

    /** @var string */
    public ?string $version = null;

    /** @var array */
    public array $availableCommands = [];

    public ?HeppyTool $tool = null;

    public function __construct(array $data, HeppyTool $tool)
    {
        $this->version = $data['version'] ?? '';
        $this->urlns = $data['urlns'] ?? array_shift($data);
        $this->tool = $tool;
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
        $object = !empty($this->availableCommands['*']) ? '*' : $object;
        $command = !empty($this->availableCommands[$object]['*']) ? '*' : $command;

        if (!empty($this->availableCommands[$object][$command]['*'])) {
            return true;
        }

        if (isset($data['op']) && !empty($this->availableCommands[$object][$command][$data['op']])) {
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

        $parts = $this->getNamesParts($data, $name);

        return !empty($parts) ? array_pop($parts) : null;
    }

    /**
     * @param array $data
     * @param string|null $name
     * @return null|string
     */
    protected function findFullZone(string $command, array $data, string $name = null): ?string
    {
        if (isset($data['zone'])) {
            return $data['zone'];
        }

        $parts = $this->getNamesParts($data, $name);
        array_shift($parts);

        return implode(".", $parts);
    }

    /**
     * @param string $command
     * @param array $data
     * @param string $name
     * @return null|string
     */
    protected function findName(string $command, array $data, string $name = null): ?string
    {
        $parts = $this->getNamesParts($data, $name);

        return array_shift($parts);
    }

    /**
     * @param array $data
     * @param string $name
     * @return array
     */
    protected function getNamesParts(array $data, string $name = null): ?array
    {
        if (!$name && isset($data['domain'])) {
            $name = $data['domain'];
        }

        if (!$name && isset($data['name'])) {
            $name = $data['name'];
        }

        if (!$name && isset($data['names'])) {
            $name = reset($data['names']);
        }

        return !empty($name) ? explode('.', $name) : null;
    }
}
