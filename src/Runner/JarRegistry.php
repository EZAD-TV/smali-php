<?php
/**
 * Created by PhpStorm.
 * User: stevenh
 * Date: 2019-03-20
 * Time: 11:58
 */

namespace Ezad\Smali\Runner;


class JarRegistry
{
    private $path;

    /**
     * JarRegistry constructor.
     * @param $path
     */
    public function __construct($path)
    {
        $this->path = rtrim($path, '/');
    }

    /**
     * Tries finding the path to a modified version of the given jar file that matches the sum.
     *
     * @param string $jarFile Full jar file path on device. ie. "/system/framework/services.jar"
     * @param string $sum "sha1 of jar" "." "sha1 of patch set"
     * @return bool|string
     */
    public function find($jarFile, $sum)
    {
        // $registryPath/$sum/system/framework/services.jar
        $sum = strtolower($sum);

        $dir = $this->path . '/' . $sum;
        $jarFile = ltrim($jarFile, '/');
        $modifiedJar = $dir . '/' . $jarFile;
        if ( is_file($modifiedJar) ) {
            return $modifiedJar;
        }
        return false;
    }

    /**
     * Registers the original and modified jar file for a given jar path and sha1 sum pair.
     *
     * @param $jarFile
     * @param $sum "sha1 of original jar" "." "sha1 of patch set"
     * @param $originalJarFile
     * @param $modifiedJarFile
     */
    public function register($jarFile, $sum, $originalJarFile, $modifiedJarFile)
    {
        $sum = strtolower($sum);

        $dir = $this->path . '/' . $sum;
        $jarFile = ltrim($jarFile, '/');
        $savePath = $dir . '/' . $jarFile;

        if ( !is_dir(dirname($savePath)) ) {
            mkdir(dirname($savePath), 0755, true);
        }

        copy($originalJarFile, $savePath . '.orig');
        copy($modifiedJarFile, $savePath);
    }
}