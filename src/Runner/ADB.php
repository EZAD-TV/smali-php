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

    private $binary;

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
        $proc = new Process([$this->binary, 'devices', '-l']);
        $proc->run();
        $lines = explode("\n", $proc->getOutput());
    }

    public function getprop($serial, $prop)
    {
        return $this->run($serial, ['shell', 'getprop', $prop]);
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