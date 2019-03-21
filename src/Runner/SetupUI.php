<?php
/**
 * Created by PhpStorm.
 * User: stevenh
 * Date: 2019-03-19
 * Time: 19:46
 */

namespace Ezad\Smali\Runner;

use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Process\Process;

/**
 * Console UI for setting ADB path, and picking device to set up.
 *
 * @package Ezad\Smali\Setup
 */
class SetupUI
{
    public function askAdbPath()
    {
        // see if android_home is set already
        $path = getenv('ANDROID_HOME');
        if ( $path ) {
            return rtrim($path, '/') . '/platform-tools/adb';
        }

        return $path;
    }

    public function scanNetwork(ADB $adb)
    {
        $procs = [];
        for ( $i = 1; $i < 255; $i++ ) {
            $host = "192.168.1.$i";
            $procs[$i] = new Process([$adb->binary, 'connect', $host]);
            $procs[$i]->start(function($type, $data) use ($host) {
                if ( $type == Process::ERR ) {
                    echo "[$host] [ERR] $data\n";
                } else {
                    echo "[$host] [OUT] $data\n";
                }
            });
        }

        for ( $i = 1; $i < 255; $i++ ) {
            $procs[$i]->wait();
        }
    }

    public function pickDevice(ADB $adb)
    {
        $console = new ConsoleOutput();

        $devices = $adb->devices();
        foreach ( $devices['online'] as $device ) {

        }
        foreach ( $devices['offline'] as $device ) {

        }
    }
}