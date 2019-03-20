<?php
/**
 * Created by PhpStorm.
 * User: stevenh
 * Date: 2019-03-19
 * Time: 19:39
 */

namespace Ezad\Smali\Setup;


use Symfony\Component\Process\Process;

class ADB
{
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
}