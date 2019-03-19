<?php
/**
 * Created by PhpStorm.
 * User: stevenh
 * Date: 2019-03-18
 * Time: 16:16
 */

namespace Ezad\Smali\Patch;

use Ezad\Smali\Device\DeviceSpec;
use Ezad\Smali\Patch\Instruction\AbstractInstruction;
use Ezad\Smali\Patch\Instruction\FindInstruction;
use Ezad\Smali\Patch\Instruction\FindNearInstruction;
use Ezad\Smali\Patch\Instruction\RemoveInstruction;
use Ezad\Smali\Patch\Instruction\ReplaceInstruction;

/**
 * .devices p212:21- rk3399:21-23 model:old-new
 * .jar /system/framework/services.jar
 * .dex classes.dex
 * .file /com/android/server/am/ActivityManagerService.smali
 * .method updateConfigurationLocked(Landroid/content/res/Configuration;Lcom/android/server/am/ActivityRecord;ZZ)Z
 */
class PatchFile
{
    public $specList = [];
    public $jarFile;
    public $dexFile;
    public $smaliFile;
    public $method;

    public $instructions = [];

    public static function parseFile($file)
    {
        return static::parse(\file_get_contents($file));
    }

    public static function parse($text)
    {
        $instructions = [
            'find' => FindInstruction::class,
            'findNear' => FindNearInstruction::class,
            'remove' => RemoveInstruction::class,
            'replace' => ReplaceInstruction::class,
        ];

        $lines = \preg_split('/\r?\n/', $text, -1, \PREG_SPLIT_NO_EMPTY);

        // remove blank lines and comment lines
        $lines = array_map('trim', $lines);
        $lines = array_filter($lines, function($line) {
            return $line && $line[0] != '#';
        });
        $lines = array_values($lines);

        $count = count($lines);

        $patch = new PatchFile();

        for ( $i = 0; $i < $count; $i++ ) {
            $line = $lines[$i];
            echo "LINE # $i | $line\n";
            if ( strpos($line, '@devices ') === 0 ) {
                $patch->specList = DeviceSpec::parseList(substr($line, 9));
            }
            if ( strpos($line, '@jar ') === 0 ) {
                $patch->jarFile = trim(substr($line, 5));
            }
            if ( strpos($line, '@dex ') === 0 ) {
                $patch->dexFile = trim(substr($line, 5));
            }
            if ( strpos($line, '@file ') === 0 ) {
                $patch->smaliFile = trim(substr($line, 6));
            }
            if ( strpos($line, '@method ') === 0 ) {
                $patch->method = trim(substr($line, 8));
            }

            foreach ( $instructions as $instr => $instrClass ) {
                // "@instr " or "@instr\n"
                if ( preg_match("/^@$instr(\s|$)/", $line) ) {
                    echo "PARSING instruction $instr\n";
                    /** @var AbstractInstruction $instruction */
                    $instruction = new $instrClass();
                    // parse will give us the index of the last line.
                    $i = $instruction->parse($lines, $i);
                    echo "AFTER PARSE i=$i\n";

                    $patch->instructions[] = $instruction;
                    break;
                }
            }
        }

        return $patch;
    }
}

/*
@devices p212:21- rk3399:21-23 model:old-new
@jar /system/framework/services.jar
@dex classes.dex
@file /com/android/server/am/ActivityManagerService.smali
@method updateConfigurationLocked(Landroid/content/res/Configuration;Lcom/android/server/am/ActivityRecord;ZZ)Z

# initialize context with @find to look for the first instance of these 2 consecutive lines.
# current line pointer will reference the .local v23 line.
# end the @find block with an empty @. this way we can handle searching empty lines without it being ambiguous.
@find
.local v23, "configCopy":Landroid/content/res/Configuration;
invoke-static/range {v30 .. v30}, Lcom/android/server/am/ActivityManagerService;->shouldShowDialogs(Landroid/content/res/Configuration;)Z
@

# remove the line referenced at line pointer + 1, the invoke-static line.
@remove +1

# find the first instance of "move-result v3" starting at line pointer within 5 lines of current.
# set line pointer to this line.
@findNear 5
move-result v3
@

# replace line at pointer to the following
@replace
const/16 v3, 0x0
 */