<?php
/**
 * Created by PhpStorm.
 * User: stevenh
 * Date: 2019-03-18
 * Time: 20:36
 */

namespace Ezad\Smali\Patch;

/**
 * Processes instructions in a PatchFile on the lines of a smali method.
 *
 * @package Ezad\Smali\Patch
 */
class Processor
{
    public $lines = [];
    public $pointer = 0;
}