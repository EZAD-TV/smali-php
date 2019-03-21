<?php
/**
 * Created by PhpStorm.
 * User: stevenh
 * Date: 2019-03-20
 * Time: 12:55
 */

namespace Ezad\Smali\Runner;


use Symfony\Component\Process\Process;

class SmaliCommands
{
    const BAKSMALI_PATH = __DIR__ . '/../../bin/baksmali';
    const SMALI_PATH    = __DIR__ . '/../../bin/smali';


    public static function assemble($dexFile, $folder)
    {
        $proc = new Process([self::SMALI_PATH, 'ass', '-o', $dexFile, $folder]);
        $proc->run();
        return is_file($dexFile);
    }

    public static function disassemble($dexFile, $folder)
    {
        $proc = new Process([self::BAKSMALI_PATH, 'dis', $dexFile, '-o', $folder]);
        $proc->run();

        return count(scandir($folder)) > 2; // . and .. are given
    }
}