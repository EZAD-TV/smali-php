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
use Traversable;

class PatchFileSet implements \IteratorAggregate
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

        uasort($patches, function(PatchFile $a, PatchFile $b) {
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
    public function filterByDevice(DeviceVersion $device)
    {
        return $this->filter(function(PatchFile $patch) use ($device) {
            return null !== DeviceMatcher::findMatch($patch->specList, $device);
        });
    }

    /**
     * @param string $jarFile
     * @return PatchFileSet
     */
    public function filterByJar($jarFile)
    {
        return $this->filter(function(PatchFile $patch) use ($jarFile) {
            return $patch->jarFile === $jarFile;
        });
    }

    /**
     * @param $dexFile
     * @return PatchFileSet
     */
    public function filterByDex($dexFile)
    {
        return $this->filter(function(PatchFile $patch) use ($dexFile) {
            return $patch->dexFile === $dexFile;
        });
    }

    public function filter(callable $predicate)
    {
        $patches = array_filter($this->patches, $predicate);
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
     * @return array
     */
    public function getDexFiles()
    {
        $files = [];
        foreach ( $this->patches as $patch ) {
            $files[$patch->dexFile] = true;
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

    /**
     * Retrieve an external iterator
     * @link https://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     * @since 5.0.0
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->patches);
    }
}