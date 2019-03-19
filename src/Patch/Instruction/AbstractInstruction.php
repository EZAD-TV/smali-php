<?php
/**
 * Created by PhpStorm.
 * User: stevenh
 * Date: 2019-03-18
 * Time: 18:31
 */

namespace Ezad\Smali\Patch\Instruction;


use Ezad\Smali\Patch\Processor;

abstract class AbstractInstruction
{
    /**
     * The data on the same line as the instruction.
     *
     * @var array
     */
    public $arguments = [];

    /**
     * The line after the instruction, or all the lines in the block for isBlock() = true.
     *
     * @var array
     */
    public $content = [];

    public function getName()
    {
        // Ezad\Smali\Patch\Instruction\FindNearInstruction
        $className = get_class($this);
        // FindNearInstruction
        $simple = substr($className, strrpos($className, '\\') + 1);
        // FindNear
        $simple = str_replace('Instruction', '', $simple);
        // findNear
        $simple[0] = strtolower($simple[0]);

        return $simple;
    }

    /**
     * Executes this instruction, optionally modifying the lines and/or moving the line pointer.
     *
     * @param Processor $processor
     */
    abstract public function execute(Processor $processor);

    /**
     * Return true if there are 1 or more lines after the instruction that should be read.
     *
     * @return bool
     */
    public function hasContent()
    {
        return true;
    }

    /**
     * Return true if the content block ends with a lone @. Otherwise will have one line of content.
     * Pointless if hasContent is false.
     *
     * @return bool
     */
    public function isBlock()
    {
        return false;
    }

    /**
     * @param array $lines
     * @param $index
     * @return int The index of the last line, the parse loop will increment past it.
     */
    public function parse(array $lines, $index)
    {
        $line = $lines[$index];
        $args = substr($line, strlen($this->getName()) + 1); // @ + name
        $args = trim($args);
        if ( $args ) {
            $this->arguments = preg_split('/\s+/', $args);
        }

        if ( $this->hasContent() ) {
            if ($this->isBlock()) {
                while ($index < count($lines) - 1) {
                    // go to next line
                    $index++;

                    $line = trim($lines[$index]);
                    if ( $line === '@' ) {
                        // if line is @ then we're done parsing
                        break;
                    }
                    $this->content[] = $line;
                }
                return $index;
            } else {
                $lines = array_slice($lines, $index + 1, 1);
                $this->content = [trim($lines[0])];
                return $index + 1;
            }
        }

        return $index;
    }
}