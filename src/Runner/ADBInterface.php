<?php


namespace Ezad\Smali\Runner;


interface ADBInterface
{
    const PROP_MODEL = 'ro.product.model';
    const PROP_SDK = 'ro.build.version.sdk';

    public function getprop($serial, $prop);

    public function push($serial, $from, $to);

    public function pull($serial, $from, $to);

    public function sha1sum($serial, $file);
}