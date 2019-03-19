<?php
/**
 * Created by PhpStorm.
 * User: stevenh
 * Date: 2019-03-18
 * Time: 15:40
 */

namespace Ezad\Smali\Device;

/**
 * Handles a model and min/max version. Can be used to determine if a DeviceVersion matches.
 *
 * @package Ezad\Smali\Device
 */
class DeviceSpec
{
    public $model;
    public $versionMin;
    public $versionMax;

    /**
     * DeviceSpec constructor.
     * @param $model
     * @param $versionMin
     * @param $versionMax
     */
    public function __construct($model, $versionMin, $versionMax)
    {
        $this->model = $model;
        $this->versionMin = $versionMin;
        $this->versionMax = $versionMax;
    }

    public static function parseList($string)
    {
        $split = preg_split('/\s+/', $string);
        $specs = [];
        foreach ( $split as $str ) {
            $specs[] = static::parse($str);
        }
        return $specs;
    }

    public static function parse($string)
    {
        if ( strpos($string, ':') !== false ) {
            // handle model:x-y, model:x-, model:-y, model:z
            list($model, $range) = explode(':', $string);
            list($min, $max) = static::parseRange($range);
        } else {
            // handle model, x-y, x-, -y, z
            if ( preg_match('/^[0-9-]/$', $string) ) {
                $model = '';
                list($min, $max) = static::parseRange($string);
            } else {
                $model = $string;
                $min = 0;
                $max = 999;
            }
        }

        return new DeviceSpec($model, $min, $max);
    }

    public function matches(DeviceVersion $version)
    {
        $matches = $this->model ? ($this->model === $version->model) : true;
        return $matches && $version->sdk >= $this->versionMin && $version->sdk <= $this->versionMax;
    }

    private static function parseRange($range)
    {
        if ( strpos($range, '-') !== false ) {
            list($min, $max) = explode('-', $range);
            if ( $min ) {
                $min = (int)$min;
            } else {
                $min = 0;
            }
            if ( $max ) {
                $max = (int)$max;
            } else {
                $max = 999;
            }
        } else {
            $min = $max = (int) $range;
        }

        return [$min, $max];
    }
}