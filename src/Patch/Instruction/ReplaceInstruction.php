<?php
/**
 * Created by PhpStorm.
 * User: stevenh
 * Date: 2019-03-18
 * Time: 18:29
 */

namespace Ezad\Smali\Patch\Instruction;


use Ezad\Smali\Patch\Processor;

class ReplaceInstruction extends AbstractInstruction
{
    public function execute(Processor $processor)
    {
        if ( count($this->arguments) > 0 ) {
            $offset = (int)$this->arguments[0];
        } else {
            $offset = 0;
        }

        $position = $processor->pointer + $offset;
        if ( $position < 0 || $position >= count($processor->lines) ) {
            return;
        }

        if ( count($this->arguments) > 1 ) {
            $length = (int) $this->arguments[1];
        } else {
            $length = count($this->content);
        }

        // replace the next X lines with the content in the block.
        $processor->lines = array_splice($processor->lines, $offset, $length, $this->content);
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