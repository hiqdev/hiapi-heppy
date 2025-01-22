<?php

namespace hiapi\heppy\modules;

use hiapi\heppy\HeppyTool;

class AbstractModule
{
    const OBJECT_DOES_NOT_EXIST = 'Object does not exist';
    /** Object statuses */
    const CLIENT_TRANSFER_PROHIBITED = 'clientTransferProhibited';
    const CLIENT_UPDATE_PROHIBITED = 'clientUpdateProhibited';
    const CLIENT_DELETE_PROHIBITED = 'clientDeleteProhibited';
    const CLIENT_HOLD = 'clientHold';

    const UNIMPLEMENTED_OBJECT_FOR_THE_SUB_PRODUCT = 'Unimplemented command Unimplemented object for the sub product';
    const UNIMPLEMENTED_COMMAND = 'Unimplemented command';

    public $tool;
    public $base;

    /** @var array of [object => uri] */
    public array $uris = [];

    /** @var string $object */
    protected ?string $object = null;

    /** @var string $extension */
    protected ?string $extension = null;

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
                break;
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
    public function generatePassword(int $length = 16, ?bool $notalphanumeric = false): string
    {
        $charsets = [
            'abcdefghijklmnopqrstuvwxyz',
            'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
            '0123456789',
            '.,?!<>&^()[]%$#+_=-/\|',
        ];

        $result = '';
        for ($i = 0; $i < $length; $i++) {
            $n = $i % ($notalphanumeric === false ? 4 : 3);
            $max = strlen($charsets[$n]) - 1;
            $index = rand(0, $max);
            $result .= substr($charsets[$n], $index, 1);
        }

        return $result;
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
     * Fix contact ID
     *
     * @param string
     * @return string
     */
    public function fixContactID(string $epp_id, ?bool $sensative = true, ?bool $dashonly = false) : string
    {
        return strtolower(str_replace("_", "-", $epp_id));
    }

    /**
     * Check is NameStore Extension enabled
     *
     * @return bool
     */
    public function isNamestoreExtensionEnabled() : bool
    {
        return $this->isExtensionEnabled('namestoreExt');
    }

    /**
     * Check is KeySYS Extension enabled
     *
     * @return bool
     */
    public function isKeySysExtensionEnabled() : bool
    {
        return $this->isExtensionEnabled('keysys');
    }

    public function isNeulevelExtensionEnabled() : bool
    {
        return $this->isExtensionEnabled('neulevel')
            || $this->isExtensionEnabled('neulevel10');
    }

    /**
     * Check is Extension enabled
     *
     * @param string $extension
     * @return bool
     */
    public function isExtensionEnabled(string $extension) : bool
    {
        $extensions = $this->tool->getExtensions();

        return !empty($extensions[$extension]);
    }

    public function isPremiumExtensionAvailable(): bool
    {
        $extensions = $this->tool->getExtensions();
        foreach ($extensions as $name => $ext) {
            if (strpos($name, 'fee') !== false) {
                return true;
            }

            if (strpos($name, 'price') !== false) {
                return true;
            }
        }
        return false;
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
                $remote[$apiName] = $remote[$apiName] ?? $remote[$eppName] ?? [];
                $remote[$apiName] = is_array($remote[$apiName]) ? $remote[$apiName] : explode(",", $remote[$apiName]);

                if ($add = array_diff($local[$apiName], $remote[$apiName])) {
                    $res['add'][][$eppName] = array_values($add);
                }
                if ($rem = array_diff($remote[$apiName], $local[$apiName])) {
                    $res['rem'][][$eppName] = array_values($rem);
                }
            } else if (key_exists($apiName, $local) &&
                strcasecmp((string)($local[$apiName] ?? ''), (string)($remote[$apiName] ?? ''))) {
                $res['chg'][$eppName] = $local[$apiName];
            }
        }

        return array_merge($local, array_filter($res));
    }

    protected function fixStatuses(array $info): array
    {
        if (empty($info['statuses'])) {
            return $info;
        }

        $statuses = is_array($info['statuses']) ? $info['statuses'] : explode(',', $info['statuses']);

        foreach ($statuses as $k => $v) {
            if ($v === 'Spam') {
                $statuses[$k] = is_string($k) ? $k : 'serverHold';
            } else {
                $statuses[$k] = $k;
                $statuses[$v] = $v;
            }
        }

        $info['statuses'] = $statuses;

        return $info;
    }

    protected function getZone(array $row, ?bool $main = false): ?string
    {
        if (isset($row['zone']) && !empty($row['zone'])) {
            return $row['zone'];
        }

        $domain = $row['domain'] ?? $row['name'] ?? $row['host'] ?? null;

        if (empty($domain)) {
            return null;
        }

        if ($main !== true) {
            return substr($domain,strpos($domain,'.')+1);
        }

        $parts = explode('.', $domain);

        return array_pop($parts);
    }
}
