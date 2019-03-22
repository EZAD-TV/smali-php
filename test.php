<?php

use Symfony\Component\Process\Process;

require_once 'vendor/autoload.php';

$config = new \Ezad\Smali\Runner\RunnerConfig();
$config->tmpRoot = __DIR__ . '/testfiles/tmps';
$config->patchPath = __DIR__ . '/testfiles/patches';
$config->registryPath = __DIR__ . '/testfiles/jars';
$config->adbPath = '/Users/stevenh/android-sdk-macosx/platform-tools/adb';
$config->deviceSerial = '192.168.1.19';

$runner = new \Ezad\Smali\Runner\Runner($config);
$runner->adb->makeSystemWritable($config->deviceSerial);
print_r($runner->run());

exit;

$adb = new \Ezad\Smali\Runner\ADB('/Users/stevenh/android-sdk-macosx/platform-tools/adb');
$setupui = new \Ezad\Smali\Runner\SetupUI();
$setupui->scanNetwork($adb);
exit;

print_r($adb->devices());
exit;

$patch =
"@devices p212:21- rk3399:21-23 model:old-new
@jar /system/framework/services.jar
@dex /classes.dex
@file /com/android/server/am/ActivityManagerService.smali
@method public static addressAndPortToString(Ljava/net/InetAddress;I)Ljava/lang/String;

@find
:goto_7
const/4 v1, 0x2
@

# replace one line with 2 lines (offset, length). without length, would overwrite the line after.
@replace +1 1
const/4 v1, 0x3
const/4 v1, 0x4
@

@find 6
.line 145
@

@remove";

// reverse patch
$patch2 =
"@devices p212:21- rk3399:21-23 model:old-new
@jar /system/framework/services.jar
@dex /classes.dex
@file /com/android/server/am/ActivityManagerService.smali
@method public static addressAndPortToString(Ljava/net/InetAddress;I)Ljava/lang/String;

@find
:goto_7
const/4 v1, 0x3
const/4 v1, 0x4
@

# replace 2 lines with 1 line.
@replace +1 2
const/4 v1, 0x2
@

@find 5
invoke-virtual {p0}, Ljava/net/InetAddress;->getHostAddress()Ljava/lang/String;
@

# to insert just use replace with 0 length. offset 0 will put this immediately preceding the found line.
@replace 0 0
.line 145
@";

$pf = \Ezad\Smali\Patch\PatchFile::parse($patch);
$pf2 = \Ezad\Smali\Patch\PatchFile::parse($patch2);

$patcher = new \Ezad\Smali\Patch\Patcher();
$patcher->apply('tests/data/IpUtils.smali', $pf);
$patcher->apply('tests/data/IpUtils.smali', $pf2);

echo 'Patch+reverse: ', hash_file('sha1', 'tests/data/IpUtils.smali'), "\n";
echo 'Original:      ', hash_file('sha1', 'tests/data/IpUtils.smali.orig'), "\n";