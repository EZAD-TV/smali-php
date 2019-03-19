# Goal

The goal of this project is to be able to unpack a jar, decompile the .dex
file(s) inside, apply one or more patches, recompile the .dex files, and
re-pack the jar file.

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