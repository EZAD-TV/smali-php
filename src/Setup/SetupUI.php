<?php
/**
 * Created by PhpStorm.
 * User: stevenh
 * Date: 2019-03-19
 * Time: 19:46
 */

namespace Ezad\Smali\Setup;

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
}