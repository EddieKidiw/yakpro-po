<?php

namespace Eddiekidiw\YakproPo;

//========================================================================
// Author:  Pascal KISSIAN
// Resume:  http://pascal.kissian.net
//
// Copyright (c) 2015 Pascal KISSIAN
//
// Published under the MIT License
//          Consider it as a proof of concept!
//          No warranty of any kind.
//          Use and abuse at your own risks.
//========================================================================

/**
* @class get_default_defined_objects
* @new get_default_defined_objects();
*/
class get_default_defined_objects
{
    public static $t_pre_defined_classes = array();
    public static $t_pre_defined_interfaces = array();
    public static $t_pre_defined_traits = array();
    public static $t_pre_defined_class_methods = array();
    public static $t_pre_defined_class_methods_by_class = array();
    public static $t_pre_defined_class_properties = array();
    public static $t_pre_defined_class_properties_by_class = array();
    public static $t_pre_defined_class_constants = array();
    public static $t_pre_defined_class_constants_by_class = array();
    /**
    * @function __construct
    * @return ?
    */
    public function __construct()
    {
        self::$t_pre_defined_classes = array_flip(array_map('strtolower', get_declared_classes()));
        self::$t_pre_defined_interfaces = array_flip(array_map('strtolower', get_declared_interfaces()));
        self::$t_pre_defined_traits = function_exists('get_declared_traits') ? array_flip(array_map('strtolower', get_declared_traits())) : array();
        self::$t_pre_defined_classes = array_merge(self::$t_pre_defined_classes, self::$t_pre_defined_interfaces, self::$t_pre_defined_traits);
        foreach (self::$t_pre_defined_classes as $pre_defined_class_name => $dummy) {
            $t = array_flip(array_map('strtolower', get_class_methods($pre_defined_class_name)));
            if (count($t)) {
                self::$t_pre_defined_class_methods_by_class[$pre_defined_class_name] = $t;
            }
            self::$t_pre_defined_class_methods = array_merge(self::$t_pre_defined_class_methods, $t);
            $t = get_class_vars($pre_defined_class_name);
            if (count($t)) {
                self::$t_pre_defined_class_properties_by_class[$pre_defined_class_name] = $t;
            }
            self::$t_pre_defined_class_properties = array_merge(self::$t_pre_defined_class_properties, $t);
            $r = new \ReflectionClass($pre_defined_class_name);
            $t = $r->getConstants();
            if (count($t)) {
                self::$t_pre_defined_class_constants_by_class[$pre_defined_class_name] = $t;
            }
            self::$t_pre_defined_class_constants = array_merge(self::$t_pre_defined_class_constants, $t);
        }
    }
}