<?php
/**
 * Created by PhpStorm.
 * User: stevenh
 * Date: 2019-03-18
 * Time: 15:39
 */

namespace Ezad\Smali\Device;

/**
 * Device model & sdk version.
 *
 * @package Ezad\Smali\Device
 */
class DeviceVersion
{
    public $model;
    public $sdk;

    /**
     * DeviceVersion constructor.
     * @param string $model
     * @param int $sdk
     */
    public function __construct($model, $sdk)
    {
        $this->model = $model;
        $this->sdk = $sdk;
    }
}