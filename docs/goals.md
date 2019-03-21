# Goal

The goal of this project is to be able to unpack a jar, decompile the .dex
file(s) inside, apply one or more patches, recompile the .dex files, and
re-pack the jar file.

# Execution

There should be a directory of patch files. Possibly recursive, but for now 
don't worry since there's only like 1-2 patches to apply.

```
$adb = new ADB($adbPath);
$deviceModel = $adb->getprop($serial, 'ro.product.model');
$sdkVersion  = $adb->getprop($serial, 'ro.build.version.sdk');
$device = new DeviceVersion($deviceModel, $sdkVersion);

// first we want a list of patch files that apply to our device
$patchSet = PatchFileSet::fromFolder($patchPath)->filterApplicable($device);
$jarFiles = $patchSet->getJarFiles();
$patchSum = $patchSet->getPatchSum();

$registry = JarRegistry($registryPath);

foreach ( $jarFiles as $jarFile ) {
    $tmp = 'tmp' . uniqid();
    $local = $tmp . '/' . basename($jarFile);
    
    $jarSum = $adb->sha1sum($serial, $jarFile);
    if ( !$jarSum ) {
        $adb->pull($serial, $jarFile, $local);
        $jarSum = hash_file('sha1', $local);
    }
    
    // $patchSum is global and not based on the patches for the jar. just in case there's some patches
    // across multiple jars that require all of them to be applied.
    $sum = "$jarSum.$patchSum";
    
    // if we have a modified .jar and patch set linked to $sum already, upload that jar
    $modifiedJar = $registry->find($jarFile, $sum);
    if ( !$modifiedJar ) {
        $adb->pull($serial, $jarFile, $local);
        // we need to jar xf the $local file (it's in its own folder already)
        // get all patches that apply to the jar file, loop
        //   organize patches by dex file, loop through dex files.
        //     then run "baksmali dis $dex -o $tmp/out_$dex"
        //     loop through patches for the dex file
        //       then parse $tmp/out_$dex/$file and extract the lines from $method
        //       then run those lines through the Processor
        //       then put the .smali file together with {before method} . {altered method} . {after method}
        //     end loop
        //     unlink $dex
        //     recompile dex with "smali ass -o $dex $tmp/out_$dex"
        //     remove $tmp/out_$dex folder
        //   end loop
        //   set $modifiedJar to $local
        //   move $local to $local.orig
        //   repackage jar file with "jar cf $modifiedJar {everything but .orig}" in the $tmp folder
        //   $registry->register($jarFile, $sum, $local, $modifiedJar);
        // end loop
    }
    $adb->push($serial, $modifiedJar, $jarFile);
} 

```

Because of shit like the same device/sdk having different firmware,
we will need to copy every .jar file that needs to be patched, compare sha1 sums, and
either re-use the existing modified jar or make a new one and register it w/ the sha sum.
With each registered jar/sum, we also need to track what patches ran against it. So if
the patches change, it also will make a new one.

Note: patches may still be restricted to ONLY run for certain device/sdk combos. So a
patch that hides an ANR dialog is great for TVs but not necessary for tablets. This
can also be used to have different patches for different versions if the internal
class format changes (like ClassName$2 is moved to ClassName$3).

# Patch format

The patch needs to know:

- If it should apply to the given device model & sdk version
- What .jar file to patch
- The filename of the .dex file to unpack
- The filename of the .smali file to patch
- The function name in the .smali file to patch

Example format:

```
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
```

Original patch:

```
--- old.smali	2019-01-08 22:19:12.000000000 -0500
+++ new.smali	2019-01-08 22:18:44.000000000 -0500
@@ -207,9 +207,11 @@

     .line 17452
     .local v23, "configCopy":Landroid/content/res/Configuration;
-    invoke-static/range {v30 .. v30}, Lcom/android/server/am/ActivityManagerService;->shouldShowDialogs(Landroid/content/res/Configuration;)Z
+    #invoke-static/range {v30 .. v30}, Lcom/android/server/am/ActivityManagerService;->shouldShowDialogs(Landroid/content/res/Configuration;)Z
+    #move-result v3

-    move-result v3
+    # v3 = false instead of shouldShowDialogs()
+    const/16 v3, 0x0

     move-object/from16 v0, p0

```