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
use Symfony\Component\Console\Output\ConsoleOutput;
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
     * @var ADBInterface
     */
    public $adb;

    /**
     * Runner constructor.
     * @param RunnerConfig $config
     * @param ADBInterface|null $adb
     */
    public function __construct(RunnerConfig $config, ADBInterface $adb = null)
    {
        $this->config = $config;
        $this->adb = $adb ?: new ADB($this->config->adbPath);
    }

    /**
     * Downloads, patches, and uploads jar files from the device.
     * Returns a map of "/sdcard/xxx" file paths to *actual* file paths, so that you can cp -f the files into
     * place while logged in as root.
     *
     * @return array
     */
    public function run()
    {
        $out = new ConsoleOutput();

        // track a list of /sdcard/xxx file paths pointing to their final destination on the unit
        $fileCopyOps = [];

        $serial = $this->config->deviceSerial;
        $adb = $this->adb;

        $deviceModel = $adb->getprop($serial, ADBInterface::PROP_MODEL);
        $deviceModel = str_replace(' ', '_', $deviceModel);
        $sdkVersion = $adb->getprop($serial, ADBInterface::PROP_SDK);
        $device = new DeviceVersion($deviceModel, $sdkVersion);

        // first we want a list of patch files that apply to our device
        $patchSet = PatchFileSet::fromFolder($this->config->patchPath)->filterByDevice($device);
        if ( $this->config->patchFilter ) {
            $patchSet = $patchSet->filter(function(PatchFile $pf) {
                $sansExt = str_replace('.patch', '', $pf->name);
                return in_array($sansExt, $this->config->patchFilter);
            });
        }

        $jarFiles = $patchSet->getJarFiles();
        $patchSum = $patchSet->getPatchSum();

        $patcher = new Patcher();
        $registry = new JarRegistry($this->config->registryPath);
        $fs = new Filesystem();

        foreach ($jarFiles as $jarFile) {
            $out->writeln("<info>Jar: $jarFile</info>");

            $tmp = $this->config->tmpRoot . '/tmp' . uniqid();
            $out->writeln("- Creating stage in $tmp");
            $fs->mkdir($tmp);
            $local = $tmp . '/' . basename($jarFile);

            // try getting sum from device, failing that then pull it.
            $jarSum = $adb->sha1sum($serial, $jarFile);
            if (!$jarSum) {
                $out->writeln("- Pulling $jarFile from device");
                $adb->pull($serial, $jarFile, $local);
                $jarSum = hash_file('sha1', $local);
            }
            $out->writeln("- Jar sum: $jarSum");

            // $patchSum is global and not based on the patches for the jar. just in case there's some patches
            // across multiple jars that require all of them to be applied.
            $sum = "$jarSum.$patchSum";
            $out->writeln("- Full sum: $sum");

            // if we have a modified .jar and patch set linked to $sum already, upload that jar
            $modifiedJar = $registry->find($jarFile, $sum);
            if ( $modifiedJar ) {
                $out->writeln("- Found jar in registry");
            } else {
                $out->writeln("- Modified jar not in registry, building");
                if ( !is_file($local) ) {
                    $out->writeln("- Pulling $jarFile from device");
                    $adb->pull($serial, $jarFile, $local);
                }

                $out->writeln("- Extracting $local");
                JarCommands::extract($local);
                $patchesForJar = $patchSet->filterByJar($jarFile);
                $dexFiles = $patchesForJar->getDexFiles();
                foreach ( $dexFiles as $dexFile ) {
                    $out->writeln("<info>- Dex: $dexFile</info>");
                    $dexFilePath = $tmp . '/' . ltrim($dexFile, '/');
                    $dexOut = "$tmp/out_" . basename($dexFilePath);

                    $out->writeln("  - Disassembling $dexFilePath into $dexOut");
                    SmaliCommands::disassemble($dexFilePath, $dexOut);

                    $patchesForDex = $patchesForJar->filterByDex($dexFile);
                    /** @var PatchFile $patch */
                    foreach ( $patchesForDex as $patch ) {
                        $smaliFile = $dexOut . '/' . ltrim($patch->smaliFile, '/');
                        $out->writeln("  - Patching $smaliFile");
                        $patcher->apply($smaliFile, $patch);
                    }

                    $out->writeln("  - Re-assembling $dexFilePath");
                    unlink($dexFilePath);
                    SmaliCommands::assemble($dexFilePath, $dexOut);
                    $fs->remove($dexOut);
                }

                $modifiedJar = $local;
                $fs->rename($local, "$local.orig");
                $local = $local . '.orig';

                $out->writeln("- Compressing $modifiedJar");
                JarCommands::compress($modifiedJar);

                $sumshort = substr($jarSum, 0, 8) . '...' . substr($patchSum, 0, 8);
                $out->writeln("- Registering $jarFile:$sumshort to $modifiedJar");
                $registry->register($jarFile, $sum, $local, $modifiedJar, $device);
            }

            $out->writeln("- Pushing modified jar $modifiedJar to device $jarFile");
            // needs to push to /sdcard and a superuser would cp -f it into place

            $sdcardPath = '/sdcard/' . ltrim(str_replace('/', '_', $jarFile), '_');
            $adb->push($serial, $modifiedJar, $sdcardPath);
            $fileCopyOps[$sdcardPath] = $jarFile;

            $out->writeln("- Cleaning up $tmp");
            $fs->remove($tmp);
        }

        return $fileCopyOps;
    }
}