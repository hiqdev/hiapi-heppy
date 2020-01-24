<?php
/**
 * hiAPI hEPPy plugin
 *
 * @link      https://github.com/hiqdev/hiapi-heppy
 * @package   hiapi-heppy
 * @license   BSD-3-Clause
 * @copyright Copyright (c) 2017, HiQDev (http://hiqdev.com/)
 */

namespace hiapi\heppy\interfaces;

/**
 * Interface of EPP Extension
 */
interface ExtensionInterface
{
    /**
     * @param string $command
     * @param array $data
     *
     * @return $array
     */
    public function apply(string $command, array $data) : array;

    /**
     * @param string $command
     * @param array $data
     *
     * @return $array
     */
    public function isApplicable(string $command, array $data) : bool;

    /**
     * @param string $command
     * @param array $data
     *
     * @return $array
     */
    public function addExtension(string $command, array $data) : array;
}
