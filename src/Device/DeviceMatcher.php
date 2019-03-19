<?php
/**
 * Created by PhpStorm.
 * User: stevenh
 * Date: 2019-03-18
 * Time: 15:38
 */

namespace Ezad\Smali\Device;

/**
 * Matching utility.
 *
 * @package Ezad\Smali\Device
 */
class DeviceMatcher
{
    /**
     * @param DeviceSpec[] $specList
     * @param DeviceVersion $version
     * @return DeviceSpec|null
     */
    public static function findMatch(array $specList, DeviceVersion $version)
    {
        foreach ( $specList as $spec ) {
            if ( $spec->matches($version) ) {
                return $spec;
            }
        }
        return null;
    }
}