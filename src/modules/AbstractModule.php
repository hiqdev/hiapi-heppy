<?php

namespace hiapi\heppy\modules;

use hiapi\heppy\HeppyTool;

class AbstractModule
{
    public $tool;
    public $base;

    /** @var array of [object => uri] */
    public $uris = [];

    /** @var string $object */
    protected $object = null;

    /** @var string $extension */
    protected $extension = null;

    public function __construct(HeppyTool $tool)
    {
        $this->tool = $tool;
        $this->base = $tool->getBase();
        $this->init();
    }

    /**
     * Initiate module with object and required ext
     *
     * @param void
     * @return self
     */
    public function init() : self
    {
        $uris = $this->tool->getObjects();
        foreach ($this->uris as $object => $uri) {
            if (in_array($uri, $uris, true)) {
                $this->object = $object;
                return $this;
            }
        }

        if (empty($this->extURIs)) {
            return $this;
        }

        $exts = $this->tool->getExtensions();
        foreach ($this->extURIs as $obj => $uri) {
            if (!empty($exts[$obj])) {
                $this->extension = $obj;
                return $this;
            }
        }

        return $this;
    }

    /**
     * Check if module is available
     *
     * @param void
     * @return bool
     */
    public function isAvailable() : bool
    {
        return true;
    }

    /**
     * @param int $length
     * @return string
     */
    public function generatePassword(int $length = 10): string
    {
        $charsets = [
            'abcdefghijklmnopqrstuvwxyz',
            'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
            '0123456789',
            '!@#$%^&*',
        ];

        $result = '';
        for ($i = 0; $i < $length; $i++) {
            $n = $i % 4;
            $max = strlen($charsets[$n]) - 1;
            $index = rand(0, $max);
            $result .= substr($charsets[$n], $index, 1);
        }

        return $result;
    }

    /**
     * @return \Closure
     */
    protected function getFilterCallback(): \Closure
    {
        return function ($value) {
            return !is_null($value);
        };
    }

    /**
     * @param array $local
     * @param array $remote
     * @param array $map
     * @return array
     */
    protected function prepareDataForUpdate(array $local, array $remote, array $map): array
    {
        $res = [
            'add' => [],
            'chg' => [],
            'rem' => [],
        ];

        foreach ($map as $apiName => $eppName) {
            if (is_array($local[$apiName])) {
                $remote[$apiName] = $remote[$apiName] ?? [];
                if ($add = array_diff($local[$apiName], $remote[$apiName])) {
                    $res['add'][$eppName] = $add;
                }
                if ($rem = array_diff($remote[$apiName], $local[$apiName])) {
                    $res['rem'][$eppName] = $rem;
                }
            } else if (key_exists($apiName, $local) &&
                strcasecmp((string)$local[$apiName], (string)$remote[$apiName])) {
                $res['chg'][$eppName] = $local[$apiName];
            }
        }

        return array_merge($local, array_filter($res));
    }

    /**
     * This method is for testing purpose only
     *
     * @param HeppyTool $tool
     */
    public function setTool(HeppyTool $tool): void
    {
        $this->tool = $tool;
    }

    /**
     * Check is NameStore Extension enabled
     *
     * @return bool
     */
    public function isNamestoreExtensionEnabled()
    {
        $extensions = $this->tool->getExtensions();

       return !empty($extensions['namestore']);
    }
}
