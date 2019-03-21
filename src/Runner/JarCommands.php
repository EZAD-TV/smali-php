<?php
/**
 * Created by PhpStorm.
 * User: stevenh
 * Date: 2019-03-20
 * Time: 13:49
 */

namespace Ezad\Smali\Runner;


use Symfony\Component\Process\Process;

class JarCommands
{
    public static function extract($jar)
    {
        $proc = new Process(['jar', 'xf', basename($jar)]);
        $proc->setWorkingDirectory(dirname($jar));
        $proc->run();
    }

    public static function compress($jar)
    {
        // get all files in dirname($jar) which aren't .jar or .orig
        $files = [];
        foreach ( scandir(dirname($jar)) as $file ) {
            if ( $file == '.' || $file == '..' || strpos($file, '.jar') || strpos($file, '.orig') ) {
                continue;
            }
            $files[] = $file;
        }

        $proc = new Process(array_merge(['jar', 'cf', basename($jar)], $files));
        $proc->setWorkingDirectory(dirname($jar));
        $proc->run();
    }
}