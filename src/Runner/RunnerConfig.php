<?php
/**
 * Created by PhpStorm.
 * User: stevenh
 * Date: 2019-03-20
 * Time: 16:10
 */

namespace Ezad\Smali\Runner;


use Symfony\Component\Console\Output\OutputInterface;

class RunnerConfig
{
    /**
     * Path to the adb binary.
     *
     * @var string
     */
    public $adbPath;

    /**
     * Path to the folder for jars.
     *
     * @var string
     */
    public $registryPath;

    /**
     * Path to the folder for patches.
     *
     * @var string
     */
    public $patchPath;

    /**
     * Device serial to use as the -s argument in adb.
     *
     * @var string
     */
    public $deviceSerial;

    /**
     * Root path where tmp folders are created.
     *
     * @var string
     */
    public $tmpRoot = '.';

    /**
     * List of patch files (sans extension) that ONLY should run. If empty, runs all available.
     *
     * @var array
     */
    public $patchFilter = [];

    /**
     * @var null|OutputInterface
     */
    public $output = null;
}