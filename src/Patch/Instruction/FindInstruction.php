<?php
/**
 * Created by PhpStorm.
 * User: stevenh
 * Date: 2019-03-18
 * Time: 18:29
 */

namespace Ezad\Smali\Patch\Instruction;


use Ezad\Smali\Patch\Exception\FindFailedException;
use Ezad\Smali\Patch\Processor;

class FindInstruction extends AbstractInstruction
{
    public function execute(Processor $processor)
    {
        $consecutive = 0;
        $toMatch = count($this->content);
        $lineCount = count($processor->lines);

        if ( count($this->arguments) > 0 ) {
            $within = (int)$this->arguments[0];
            $maxPointer = min($lineCount, $processor->pointer + $within + 1);
        } else {
            $maxPointer = $lineCount;
        }

        while ( $processor->pointer < $maxPointer ) {
            $line = $processor->lines[$processor->pointer];
            if ( trim($line) === $this->content[$consecutive] ) {
                $consecutive++;
                if ( $consecutive >= $toMatch ) {
                    // reset pointer to start of match area.
                    // if toMatch is 1, we are already at the start so nothing happens.
                    $processor->pointer -= $toMatch - 1;
                    return;
                }
            } else {
                $consecutive = 0;
            }

            $processor->pointer++;
        }

        throw new FindFailedException("Failed to find:\n" . implode("\n", $this->content));
    }

    public function hasContent()
    {
        return true;
    }

    public function isBlock()
    {
        return true;
    }
}