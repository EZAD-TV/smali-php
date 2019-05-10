<?php


namespace Ezad\Smali\Runner;

/**
 * Fake ADB that pre-defines a device, and local filesystem paths for push/pull/sha1.
 *
 * @package Ezad\Smali\Runner
 */
class FakeADB implements ADBInterface
{
    private $deviceModel = '';
    private $deviceSdk = 0;
    private $pushPath = '';
    private $pullPath = '';
    private $shaPath = '';

    public function __construct($deviceModel, $deviceSdk, $pushPath, $pullPath, $shaPath)
    {
        $this->deviceModel = $deviceModel;
        $this->deviceSdk = $deviceSdk;
        $this->pushPath = $pushPath;
        $this->pullPath = $pullPath;
        $this->shaPath = $shaPath;
    }

    public function getprop($serial, $prop)
    {
        if ( $prop == ADBInterface::PROP_SDK ) {
            return $this->deviceSdk;
        } else if ( $prop == ADBInterface::PROP_MODEL ) {
            return $this->deviceModel;
        }

        return '';
    }

    public function push($serial, $from, $to)
    {
        copy($from, $this->pushPath . '/' . basename($to));
    }

    public function pull($serial, $from, $to)
    {
        copy($this->pullPath . '/' . basename($from), $to);
    }

    public function sha1sum($serial, $file)
    {
        return hash_file('sha1', $this->shaPath . '/' . basename($file));
    }
}