<?php

require_once 'vendor/autoload.php';

$patch =
"@devices p212:21- rk3399:21-23 model:old-new
@jar /system/framework/services.jar
@dex /classes.dex
@file /com/android/server/am/ActivityManagerService.smali
@method updateConfigurationLocked(Landroid/content/res/Configuration;Lcom/android/server/am/ActivityRecord;ZZ)Z

# initialize context with @find to look for the first instance of these 2 consecutive lines.
# current line pointer will reference the .local v23 line.
# end the @find block with an empty @. this way we can handle searching empty lines without it being ambiguous.
@find
.local v23, \"configCopy\":Landroid/content/res/Configuration;
invoke-static/range {v30 .. v30}, Lcom/android/server/am/ActivityManagerService;->shouldShowDialogs(Landroid/content/res/Configuration;)Z
@

# remove the line referenced at line pointer + 1, the invoke-static line.
@remove +1

# find the first instance of \"move-result v3\" starting at line pointer within 5 lines of current.
# set line pointer to this line.
@findNear 5
move-result v3
@

# overwrite lines at pointer to the following
@replace
const/16 v3, 0x0
@";

var_dump(\Ezad\Smali\Patch\PatchFile::parse($patch));