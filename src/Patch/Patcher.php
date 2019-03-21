<?php
/**
 * Created by PhpStorm.
 * User: stevenh
 * Date: 2019-03-20
 * Time: 14:39
 */

namespace Ezad\Smali\Patch;

use Ezad\Smali\Patch\Exception\MethodNotFoundException;

/**
 * Applies a PatchFile onto a given .smali file.
 *
 * @package Ezad\Smali\Patch
 */
class Patcher
{
    public function apply($smaliFile, PatchFile $patch)
    {
        // just use file(), don't get fancy. even 100K lines will probably use <20mb ram.
        $smaliLines = file($smaliFile, FILE_IGNORE_NEW_LINES);
        $methodOffset = -1;
        $lines = $this->extractMethodLines($smaliLines, $patch->method, $methodOffset);
        if ( !$lines ) {
            throw new MethodNotFoundException("Method \"{$patch->method}\" not found in \"$smaliFile\"");
        }

        $oldLineCount = count($lines);

        $processor = new Processor($lines);
        $lines = $processor->process($patch);

        // splice in the new method lines
        array_splice($smaliLines, $methodOffset, $oldLineCount, $lines);

        // add newline to end of file. it's what baksmali does.
        file_put_contents($smaliFile, implode("\n", $smaliLines) . "\n");
    }

    /**
     * Extracts the lines of the given method and saves the first line index of the method in $methodOffset.
     *
     * @param array $smaliLines
     * @param $method
     * @param $methodOffset
     * @return array
     */
    private function extractMethodLines(array $smaliLines, $method, &$methodOffset)
    {
        $search = ".method $method";
        $searchEnd = '.end method';

        // search the smali file for a line matching ".method $method"
        $methodLines = [];
        $inMethod = false;

        foreach ( $smaliLines as $i => $line ) {
            // found the .method line, next iteration start collecting
            if ( $line === $search ) {
                $inMethod = true;
                $methodOffset = $i + 1;
                continue;
            }

            if ( $inMethod ) {
                // collect lines until ".end method" is reached
                if ( $line === $searchEnd ) {
                    break;
                }
                $methodLines[] = $line;
            }
        }

        return $methodLines;
    }
}