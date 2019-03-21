<?php
/**
 * Created by PhpStorm.
 * User: stevenh
 * Date: 2019-03-19
 * Time: 19:39
 */

namespace Ezad\Smali\Runner;


use Symfony\Component\Process\Process;

class ADB
{
    const PROP_MODEL = 'ro.product.model';
    const PROP_SDK = 'ro.build.version.sdk';

    public $binary;

    /**
     * ADB constructor.
     * @param $binary
     */
    public function __construct($binary)
    {
        $this->binary = $binary;
    }

    public function devices()
    {
        // 192.168.1.14:5555      device product:p212 model:x96 device:p212 transport_id:2
        $proc = new Process([$this->binary, 'devices', '-l']);
        $proc->run();
        $lines = explode("\n", $proc->getOutput());
        $devices = ['online' => [], 'offline' => []];

        foreach ( $lines as $line ) {
            if ( strpos($line, 'List of devices') !== false || !$line ) {
                continue;
            }

            $parts = preg_split('/\s+/', $line);
            if ( $parts[1] == 'device' || $parts[1] == 'offline' ) {
                $device = ['serial' => $parts[0]];
                foreach ( array_slice($parts, 2) as $part ) {
                    list($k, $v) = explode(':', $part);
                    $device[$k] = $v;
                }

                $k = $parts[1] == 'device' ? 'online' : 'offline';
                $devices[$k][] = $device;
            }
        }

        return $devices;
    }

    public function getprop($serial, $prop)
    {
        return $this->run($serial, ['shell', 'getprop', $prop]);
    }

    public function makeSystemWritable($serial)
    {
        $this->run($serial, ['shell', "su -c 'mount -o rw,remount /system'"]);
    }

    public function push($serial, $from, $to)
    {
        $this->run($serial, ['push', $from, $to]);
    }

    public function pull($serial, $from, $to)
    {
        $this->run($serial, ['pull', $from, $to]);
    }

    public function sha1sum($serial, $file)
    {
        $output = $this->run($serial, ['shell', 'sha1sum', $file]);
        if ( preg_match('/^[A-Fa-f0-9]{40}/', $output) ) {
            return substr($output, 0, 40);
        }
        return false;
    }

    private function run($serial, array $parts)
    {
        $cmd = array_merge([$this->binary, '-s', $serial], $parts);
        $proc = new Process($cmd);
        $proc->run();
        return trim($proc->getOutput());
    }
}