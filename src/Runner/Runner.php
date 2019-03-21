<?php
/**
 * Created by PhpStorm.
 * User: stevenh
 * Date: 2019-03-20
 * Time: 16:09
 */

namespace Ezad\Smali\Runner;

use Ezad\Smali\Device\DeviceVersion;
use Ezad\Smali\Patch\Patcher;
use Ezad\Smali\Patch\PatchFile;
use Ezad\Smali\Patch\PatchFileSet;
use Ezad\Smali\Patch\Processor;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Executes the patching process on a device.
 *
 * @package Ezad\Smali\Runner
 */
class Runner
{
    /**
     * @var RunnerConfig
     */
    private $config;

    /**
     * Runner constructor.
     * @param RunnerConfig $config
     */
    public function __construct(RunnerConfig $config)
    {
        $this->config = $config;
    }

    public function run()
    {
        $serial = $this->config->deviceSerial;
        $adb = new ADB($this->config->adbPath);

        $deviceModel = $adb->getprop($serial, 'ro.product.model');
        $sdkVersion = $adb->getprop($serial, 'ro.build.version.sdk');
        $device = new DeviceVersion($deviceModel, $sdkVersion);

        // first we want a list of patch files that apply to our device
        $patchSet = PatchFileSet::fromFolder($this->config->patchPath)->filterByDevice($device);
        $jarFiles = $patchSet->getJarFiles();
        $patchSum = $patchSet->getPatchSum();

        $patcher = new Patcher();
        $registry = new JarRegistry($this->config->registryPath);
        $fs = new Filesystem();

        foreach ($jarFiles as $jarFile) {
            $tmp = $this->config->tmpRoot . '/tmp' . uniqid();
            $local = $tmp . '/' . basename($jarFile);

            // try getting sum from device, failing that then pull it.
            $jarSum = $adb->sha1sum($serial, $jarFile);
            if (!$jarSum) {
                $adb->pull($serial, $jarFile, $local);
                $jarSum = hash_file('sha1', $local);
            }

            // $patchSum is global and not based on the patches for the jar. just in case there's some patches
            // across multiple jars that require all of them to be applied.
            $sum = "$jarSum.$patchSum";

            // if we have a modified .jar and patch set linked to $sum already, upload that jar
            $modifiedJar = $registry->find($jarFile, $sum);
            if (!$modifiedJar) {
                if ( !is_file($local) ) {
                    $adb->pull($serial, $jarFile, $local);
                }

                JarCommands::extract($local);
                $patchesForJar = $patchSet->filterByJar($jarFile);
                $dexFiles = $patchesForJar->getDexFiles();
                foreach ( $dexFiles as $dexFile ) {
                    $dexFilePath = $tmp . '/' . ltrim($dexFile, '/');
                    $dexOut = "$tmp/out_" . basename($dexFilePath);
                    SmaliCommands::disassemble($dexFilePath, $dexOut);

                    $patchesForDex = $patchesForJar->filterByDex($dexFile);
                    /** @var PatchFile $patch */
                    foreach ( $patchesForDex as $patch ) {
                        $smaliFile = $dexOut . '/' . ltrim($patch->smaliFile, '/');
                        $patcher->apply($smaliFile, $patch);
                    }

                    unlink($dexFilePath);
                    SmaliCommands::assemble($dexFilePath, $dexOut);
                    $fs->remove($dexOut);
                }

                $modifiedJar = $local;
                $fs->rename($local, "$local.orig");
                $local = $local . '.orig';
                JarCommands::compress($modifiedJar);
                $registry->register($jarFile, $sum, $local, $modifiedJar);

                // we need to jar xf the $local file (it's in its own folder already)
                // get all patches that apply to the jar file, loop
                //   organize patches by dex file, loop through dex files.
                //     then run "baksmali dis $dex -o $tmp/out_$dex"
                //     loop through patches for the dex file
                //       then parse $tmp/out_$dex/$file and extract the lines from $method
                //       then run those lines through the Processor
                //       then put the .smali file together with {before method} . {altered method} . {after method}
                //     end loop
                //     unlink $dex
                //     recompile dex with "smali ass -o $dex $tmp/out_$dex"
                //     remove $tmp/out_$dex folder
                //   end loop
                //   set $modifiedJar to $local
                //   move $local to $local.orig
                //   repackage jar file with "jar cf $modifiedJar {everything but .orig}" in the $tmp folder
                //   $registry->register($jarFile, $sum, $local, $modifiedJar);
                // end loop
            }
            $adb->push($serial, $modifiedJar, $jarFile);
        }
    }
}