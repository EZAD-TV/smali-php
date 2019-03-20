<?php
/**
 * Created by PhpStorm.
 * User: stevenh
 * Date: 2019-03-19
 * Time: 19:54
 */

namespace Ezad\Smali\Patch;


use Ezad\Smali\Device\DeviceMatcher;
use Ezad\Smali\Device\DeviceVersion;

class PatchFileSet
{
    /**
     * @var PatchFile[]
     */
    private $patches = [];

    /**
     * PatchFileSet constructor.
     * @param PatchFile[] $patches
     */
    public function __construct(array $patches)
    {
        $this->patches = $patches;
    }

    static public function fromFolder($folder)
    {
        $patches = [];
        foreach ( glob("$folder/*.patch") as $file ) {
            $patches[basename($file)] = PatchFile::parseFile($file);
        }

        asort($patches, function(PatchFile $a, PatchFile $b) {
            $priorityDiff = $b->priority - $a->priority;
            if ( $priorityDiff !== 0 ) {
                return $priorityDiff;
            }
            return strcmp($b->name, $a->name);
        });

        return new PatchFileSet($patches);
    }

    /**
     * Returns a set of patches that should run for the given device.
     *
     * @param DeviceVersion $device
     * @return PatchFileSet
     */
    public function filterApplicable(DeviceVersion $device)
    {
        $patches = array_filter($this->patches, function(PatchFile $patch) use ($device) {
            return null !== DeviceMatcher::findMatch($patch->specList, $device);
        });

        return new PatchFileSet($patches);
    }

    /**
     * Get all the jar files that need to be modified.
     *
     * @return array
     */
    public function getJarFiles()
    {
        $files = [];
        foreach ( $this->patches as $patch ) {
            $files[$patch->jarFile] = true;
        }
        return array_keys($files);
    }

    /**
     * Get a hash of all the patches in this set. Used to determine if any changes happened between sets.
     *
     * @param string $algo
     * @return string
     */
    public function getPatchSum($algo = 'sha1')
    {
        $hash = hash_init($algo);
        foreach ( $this->patches as $patchFile ) {
            hash_update($hash, serialize($patchFile));
        }
        return hash_final($hash);
    }
}