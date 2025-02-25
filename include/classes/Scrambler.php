<?php

namespace Eddiekidiw\YakproPo\classes;

//========================================================================
// Author:  Pascal KISSIAN
// Resume:  http://pascal.kissian.net
//
// Copyright (c) 2015-2020 Pascal KISSIAN
//
// Published under the MIT License
//          Consider it as a proof of concept!
//          No warranty of any kind.
//          Use and abuse at your own risks.
//========================================================================
// case-sensitive:      variable names, constant name, array keys, class properties, labels
// case-insensitive:    function names, class names, class method names, namespaces, keywords and constructs
// classes, interfaces, and traits share the same internal naming_space! only a single Scrambler instance for all of them!
use Eddiekidiw\YakproPo\get_default_defined_objects;
/**
* @class Scrambler
* @new Scrambler();
*/
class Scrambler
{
    const SCRAMBLER_CONTEXT_VERSION = '1.1';
    private $t_first_chars = null;
    // allowed first char of a generated identifier
    private $t_chars = null;
    // allowed all except first char of a generated identifier
    private $l1 = null;
    // length of $t_first_chars string
    private $l2 = null;
    // length of $t_chars       string
    private $r = null;
    // seed and salt for random char generation, modified at each iteration.
    private $scramble_type = null;
    // type on which scrambling is done (i.e. variable, function, etc.)
    private $case_sensitive = null;
    // self explanatory
    private $scramble_mode = null;
    // allowed modes are 'identifier', 'hexa', 'numeric'
    private $scramble_length = null;
    // current length of scrambled names
    private $scramble_length_min = null;
    // min     length of scrambled names
    private $scramble_length_max = null;
    // max     length of scrambled names
    private $t_ignore = null;
    // array where keys are names to ignore.
    private $t_ignore_prefix = null;
    // array where keys are prefix of names to ignore.
    private $t_scramble = null;
    // array of scrambled items (key = source name , value = scrambled name)
    private $t_rscramble = null;
    // array of reversed scrambled items (key = scrambled name, value = source name)
    private $context_directory = null;
    // where to save/restore context
    private $silent = null;
    // display or not Information level messages.
    private $label_counter = 0;
    // internal label counter.
    private $t_reserved_variable_names = array(
        'this',
        'GLOBALS',
        '_SERVER',
        '_GET',
        '_POST',
        '_FILES',
        '_COOKIE',
        '_SESSION',
        '_ENV',
        '_REQUEST',
        'php_errormsg',
        'HTTP_RAW_POST_DATA',
        'http_response_header',
        'argc',
        'argv',
    );
    private $t_reserved_function_names = array(
        '__halt_compiler',
        '__autoload',
        'abstract',
        'and',
        'array',
        'as',
        'bool',
        'break',
        'callable',
        'case',
        'catch',
        'class',
        'clone',
        'const',
        'continue',
        'declare',
        'default',
        'die',
        'do',
        'echo',
        'else',
        'elseif',
        'empty',
        'enddeclare',
        'endfor',
        'endforeach',
        'endif',
        'endswitch',
        'endwhile',
        'eval',
        'exit',
        'extends',
        'false',
        'final',
        'finally',
        'float',
        'for',
        'foreach',
        'function',
        'global',
        'goto',
        'if',
        'fn',
        'implements',
        'include',
        'include_once',
        'instanceof',
        'insteadof',
        'int',
        'interface',
        'isset',
        'list',
        'namespace',
        'new',
        'null',
        'or',
        'print',
        'private',
        'protected',
        'public',
        'require',
        'require_once',
        'return',
        'static',
        'string',
        'switch',
        'throw',
        'trait',
        'true',
        'try',
        'unset',
        'use',
        'var',
        'while',
        'xor',
        'yield',
        'apache_request_headers',
    );
    private $t_reserved_class_names = array(
        'parent',
        'self',
        'static',
        // same reserved names for classes, interfaces  and traits...
        'int',
        'float',
        'bool',
        'string',
        'true',
        'false',
        'null',
        'void',
        'iterable',
        'object',
        'resource',
        'scalar',
        'mixed',
        'numeric',
        'fn',
    );
    private $t_reserved_method_names = array(
        '__construct',
        '__destruct',
        '__call',
        '__callstatic',
        '__get',
        '__set',
        '__isset',
        '__unset',
        '__sleep',
        '__wakeup',
        '__tostring',
        '__invoke',
        '__set_state',
        '__clone',
        '__debuginfo',
    );
    /**
    * @function __construct
    * @param $type
    * @param $conf
    * @param $target_directory
    * @return ?
    */
    function __construct($type, $conf, $target_directory)
    {
        /*
                global $t_pre_defined_classes,$t_pre_defined_class_methods,$t_pre_defined_class_properties,$t_pre_defined_class_constants;
                global $t_pre_defined_class_methods_by_class,$t_pre_defined_class_properties_by_class,$t_pre_defined_class_constants_by_class;
        */
        $this->scramble_type = $type;
        $this->t_first_chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $this->t_chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_';
        $this->r = md5(microtime(true));
        // random seed
        $this->t_scramble = array();
        $this->silent = $conf->conf->silent;
        if (isset($conf->conf->scramble_mode)) {
            switch ($conf->conf->scramble_mode) {
                case 'numeric':
                    $this->scramble_length_max = 32;
                    $this->scramble_mode = $conf->conf->scramble_mode;
                    $this->t_first_chars = 'O';
                    $this->t_chars = '0123456789';
                    break;
                case 'hexa':
                    $this->scramble_length_max = 32;
                    $this->scramble_mode = $conf->conf->scramble_mode;
                    $this->t_first_chars = 'abcdefABCDEF';
                    break;
                case 'identifier':
                default:
                    $this->scramble_length_max = 16;
                    $this->scramble_mode = 'identifier';
            }
        }
        $this->l1 = strlen($this->t_first_chars) - 1;
        $this->l2 = strlen($this->t_chars) - 1;
        $this->scramble_length_min = 2;
        $this->scramble_length = 5;
        if (isset($conf->conf->scramble_length)) {
            $conf->conf->scramble_length += 0;
            if ($conf->conf->scramble_length >= $this->scramble_length_min && $conf->conf->scramble_length <= $this->scramble_length_max) {
                $this->scramble_length = $conf->conf->scramble_length;
            }
        }
        switch ($type) {
            case 'constant':
                $this->case_sensitive = true;
                $this->t_ignore = array_flip($this->t_reserved_function_names);
                $this->t_ignore = array_merge($this->t_ignore, get_defined_constants(false));
                if (isset($conf->conf->t_ignore_constants)) {
                    $t = $conf->conf->t_ignore_constants;
                    $t = array_flip($t);
                    $this->t_ignore = array_merge($this->t_ignore, $t);
                }
                if (isset($conf->conf->t_ignore_constants_prefix)) {
                    $t = $conf->conf->t_ignore_constants_prefix;
                    $t = array_flip($t);
                    $this->t_ignore_prefix = $t;
                }
                break;
            case 'class_constant':
                $this->case_sensitive = true;
                $this->t_ignore = array_flip($this->t_reserved_function_names);
                $this->t_ignore = array_merge($this->t_ignore, get_defined_constants(false));
                if ($conf->conf->t_ignore_pre_defined_classes != 'none') {
                    if ($conf->conf->t_ignore_pre_defined_classes == 'all') {
                        $this->t_ignore = array_merge($this->t_ignore, get_default_defined_objects::$t_pre_defined_class_constants);
                    }
                    if (is_array($conf->conf->t_ignore_pre_defined_classes)) {
                        $t_class_names = array_map('strtolower', $conf->conf->t_ignore_pre_defined_classes);
                        foreach ($t_class_names as $class_name) {
                            if (isset(get_default_defined_objects::$t_pre_defined_class_constants_by_class[$class_name])) {
                                $this->t_ignore = array_merge($this->t_ignore, get_default_defined_objects::$t_pre_defined_class_constants_by_class[$class_name]);
                            }
                        }
                    }
                }
                if (isset($conf->conf->t_ignore_class_constants)) {
                    $t = $conf->conf->t_ignore_class_constants;
                    $t = array_flip($t);
                    $this->t_ignore = array_merge($this->t_ignore, $t);
                }
                if (isset($conf->conf->t_ignore_class_constants_prefix)) {
                    $t = $conf->conf->t_ignore_class_constants_prefix;
                    $t = array_flip($t);
                    $this->t_ignore_prefix = $t;
                }
                break;
            case 'variable':
                $this->case_sensitive = true;
                $this->t_ignore = array_flip($this->t_reserved_variable_names);
                if (isset($conf->conf->t_ignore_variables)) {
                    $t = $conf->conf->t_ignore_variables;
                    $t = array_flip($t);
                    $this->t_ignore = array_merge($this->t_ignore, $t);
                }
                if (isset($conf->conf->t_ignore_variables_prefix)) {
                    $t = $conf->conf->t_ignore_variables_prefix;
                    $t = array_flip($t);
                    $this->t_ignore_prefix = $t;
                }
                break;
            /*
                         case 'function':
            $this->case_sensitive       = false;
            $this->t_ignore             = array_flip($this->t_reserved_function_names);
            $t                          = get_defined_functions();                  $t = array_map('strtolower',$t['internal']);    $t = array_flip($t);
            $this->t_ignore             = array_merge($this->t_ignore,$t);
            if (isset($conf->conf->t_ignore_functions))
            {
                $t                      = $conf->conf->t_ignore_functions;                $t = array_map('strtolower',$t);                $t = array_flip($t);
                $this->t_ignore         = array_merge($this->t_ignore,$t);
            }
            if (isset($conf->conf->t_ignore_functions_prefix))
            {
                $t                      = $conf->conf->t_ignore_functions_prefix;         $t = array_map('strtolower',$t);                $t = array_flip($t);
                $this->t_ignore_prefix  = $t;
            }
            break;
            */
            case 'property':
                $this->case_sensitive = true;
                $this->t_ignore = array_flip($this->t_reserved_variable_names);
                if ($conf->conf->t_ignore_pre_defined_classes != 'none') {
                    if ($conf->conf->t_ignore_pre_defined_classes == 'all') {
                        $this->t_ignore = array_merge($this->t_ignore, get_default_defined_objects::$t_pre_defined_class_properties);
                    }
                    if (is_array($conf->conf->t_ignore_pre_defined_classes)) {
                        $t_class_names = array_map('strtolower', $conf->conf->t_ignore_pre_defined_classes);
                        foreach ($t_class_names as $class_name) {
                            if (isset(get_default_defined_objects::$t_pre_defined_class_properties_by_class[$class_name])) {
                                $this->t_ignore = array_merge($this->t_ignore, get_default_defined_objects::$t_pre_defined_class_properties_by_class[$class_name]);
                            }
                        }
                    }
                }
                if (isset($conf->conf->t_ignore_properties)) {
                    $t = $conf->conf->t_ignore_properties;
                    $t = array_flip($t);
                    $this->t_ignore = array_merge($this->t_ignore, $t);
                }
                if (isset($conf->conf->t_ignore_properties_prefix)) {
                    $t = $conf->conf->t_ignore_properties_prefix;
                    $t = array_flip($t);
                    $this->t_ignore_prefix = $t;
                }
                break;
            case 'function_or_class':
                // same instance is used for scrambling classes, interfaces, and traits.  and namespaces... and functions ...for aliasing
                $this->case_sensitive = false;
                $this->t_ignore = array_flip($this->t_reserved_function_names);
                $t = get_defined_functions();
                $t = array_map('strtolower', $t['internal']);
                $t = array_flip($t);
                $this->t_ignore = array_merge($this->t_ignore, $t);
                if (isset($conf->conf->t_ignore_functions)) {
                    $t = $conf->conf->t_ignore_functions;
                    $t = array_map('strtolower', $t);
                    $t = array_flip($t);
                    $this->t_ignore = array_merge($this->t_ignore, $t);
                }
                if (isset($conf->conf->t_ignore_functions_prefix)) {
                    $t = $conf->conf->t_ignore_functions_prefix;
                    $t = array_map('strtolower', $t);
                    $t = array_flip($t);
                    $this->t_ignore_prefix = $t;
                }
                $this->t_ignore = array_merge($this->t_ignore, array_flip($this->t_reserved_class_names));
                $this->t_ignore = array_merge($this->t_ignore, array_flip($this->t_reserved_variable_names));
                //                $this->t_ignore             = array_merge($this->t_ignore, array_flip($this->t_reserved_function_names));
                $t = get_defined_functions();
                $t = array_flip($t['internal']);
                $this->t_ignore = array_merge($this->t_ignore, $t);
                if ($conf->conf->t_ignore_pre_defined_classes != 'none') {
                    if ($conf->conf->t_ignore_pre_defined_classes == 'all') {
                        $this->t_ignore = array_merge($this->t_ignore, get_default_defined_objects::$t_pre_defined_classes);
                    }
                    if (is_array($conf->conf->t_ignore_pre_defined_classes)) {
                        $t_class_names = array_map('strtolower', $conf->conf->t_ignore_pre_defined_classes);
                        foreach ($t_class_names as $class_name) {
                            if (isset(get_default_defined_objects::$t_pre_defined_classes[$class_name])) {
                                $this->t_ignore[$class_name] = 1;
                            }
                        }
                    }
                }
                if (isset($conf->conf->t_ignore_classes)) {
                    $t = $conf->conf->t_ignore_classes;
                    $t = array_map('strtolower', $t);
                    $t = array_flip($t);
                    $this->t_ignore = array_merge($this->t_ignore, $t);
                }
                if (isset($conf->conf->t_ignore_interfaces)) {
                    $t = $conf->conf->t_ignore_interfaces;
                    $t = array_map('strtolower', $t);
                    $t = array_flip($t);
                    $this->t_ignore = array_merge($this->t_ignore, $t);
                }
                if (isset($conf->conf->t_ignore_traits)) {
                    $t = $conf->conf->t_ignore_traits;
                    $t = array_map('strtolower', $t);
                    $t = array_flip($t);
                    $this->t_ignore = array_merge($this->t_ignore, $t);
                }
                if (isset($conf->conf->t_ignore_namespaces)) {
                    $t = $conf->conf->t_ignore_namespaces;
                    $t = array_map('strtolower', $t);
                    $t = array_flip($t);
                    $this->t_ignore = array_merge($this->t_ignore, $t);
                }
                if (isset($conf->conf->t_ignore_classes_prefix)) {
                    $t = $conf->conf->t_ignore_classes_prefix;
                    $t = array_map('strtolower', $t);
                    $t = array_flip($t);
                    $this->t_ignore_prefix = array_merge($this->t_ignore_prefix, $t);
                }
                if (isset($conf->conf->t_ignore_interfaces_prefix)) {
                    $t = $conf->conf->t_ignore_interfaces_prefix;
                    $t = array_map('strtolower', $t);
                    $t = array_flip($t);
                    $this->t_ignore_prefix = array_merge($this->t_ignore_prefix, $t);
                }
                if (isset($conf->conf->t_ignore_traits_prefix)) {
                    $t = $conf->conf->t_ignore_traits_prefix;
                    $t = array_map('strtolower', $t);
                    $t = array_flip($t);
                    $this->t_ignore_prefix = array_merge($this->t_ignore_prefix, $t);
                }
                if (isset($conf->conf->t_ignore_namespaces_prefix)) {
                    $t = $conf->conf->t_ignore_namespaces_prefix;
                    $t = array_map('strtolower', $t);
                    $t = array_flip($t);
                    $this->t_ignore_prefix = array_merge($this->t_ignore_prefix, $t);
                }
                break;
            case 'method':
                $this->case_sensitive = false;
                if ($conf->conf->parser_mode == 'ONLY_PHP7') {
                    $this->t_ignore = array();
                } else {
                    $this->t_ignore = array_flip($this->t_reserved_function_names);
                }
                $t = array_flip($this->t_reserved_method_names);
                $this->t_ignore = array_merge($this->t_ignore, $t);
                $t = get_defined_functions();
                $t = array_map('strtolower', $t['internal']);
                $t = array_flip($t);
                $this->t_ignore = array_merge($this->t_ignore, $t);
                if ($conf->conf->t_ignore_pre_defined_classes != 'none') {
                    if ($conf->conf->t_ignore_pre_defined_classes == 'all') {
                        $this->t_ignore = array_merge($this->t_ignore, get_default_defined_objects::$t_pre_defined_class_methods);
                    }
                    if (is_array($conf->conf->t_ignore_pre_defined_classes)) {
                        $t_class_names = array_map('strtolower', $conf->conf->t_ignore_pre_defined_classes);
                        foreach ($t_class_names as $class_name) {
                            if (isset(get_default_defined_objects::$t_pre_defined_class_methods_by_class[$class_name])) {
                                $this->t_ignore = array_merge($this->t_ignore, get_default_defined_objects::$t_pre_defined_class_methods_by_class[$class_name]);
                            }
                        }
                    }
                }
                if (isset($conf->conf->t_ignore_methods)) {
                    $t = $conf->conf->t_ignore_methods;
                    $t = array_map('strtolower', $t);
                    $t = array_flip($t);
                    $this->t_ignore = array_merge($this->t_ignore, $t);
                }
                if (isset($conf->conf->t_ignore_methods_prefix)) {
                    $t = $conf->conf->t_ignore_methods_prefix;
                    $t = array_map('strtolower', $t);
                    $t = array_flip($t);
                    $this->t_ignore_prefix = $t;
                }
                break;
            case 'label':
                $this->case_sensitive = true;
                $this->t_ignore = array_flip($this->t_reserved_function_names);
                if (isset($conf->conf->t_ignore_labels)) {
                    $t = $conf->conf->t_ignore_labels;
                    $t = array_flip($t);
                    $this->t_ignore = array_merge($this->t_ignore, $t);
                }
                if (isset($conf->conf->t_ignore_labels_prefix)) {
                    $t = $conf->conf->t_ignore_labels_prefix;
                    $t = array_flip($t);
                    $this->t_ignore_prefix = $t;
                }
                break;
        }
        if (isset($target_directory)) {
            // the constructor will restore previous saved context if exists
            $this->context_directory = $target_directory;
            if (file_exists("{$this->context_directory}/yakpro-po/context/{$this->scramble_type}")) {
                $t = unserialize(file_get_contents("{$this->context_directory}/yakpro-po/context/{$this->scramble_type}"));
                if ($t[0] !== self::SCRAMBLER_CONTEXT_VERSION) {
                    fprintf(STDERR, "Error:\tContext format has changed! run with --clean option!" . PHP_EOL);
                    $this->context_directory = null;
                    // do not overwrite incoherent values when exiting
                    exit(1);
                }
                $this->t_scramble = $t[1];
                $this->t_rscramble = $t[2];
                $this->scramble_length = $t[3];
                $this->label_counter = $t[4];
            }
        }
    }
    /**
    * @function __destruct
    * @return ?
    */
    function __destruct()
    {
        //print_r($this->t_scramble);
        if (!$this->silent) {
            fprintf(STDERR, "Info:\t[%-17s] scrambled \t: %8d%s", $this->scramble_type, count($this->t_scramble), PHP_EOL);
        }
        if (isset($this->context_directory)) {
            // the destructor will save the current context
            $t = array();
            $t[0] = self::SCRAMBLER_CONTEXT_VERSION;
            $t[1] = $this->t_scramble;
            $t[2] = $this->t_rscramble;
            $t[3] = $this->scramble_length;
            $t[4] = $this->label_counter;
            file_put_contents("{$this->context_directory}/yakpro-po/context/{$this->scramble_type}", serialize($t));
        }
    }
    /**
    * @function str_scramble
    * @param $s
    * @return ?
    */
    private function str_scramble($s)
    {
        $c1 = $this->t_first_chars[mt_rand(0, $this->l1)];
        // first char of the identifier
        $c2 = $this->t_chars[mt_rand(0, $this->l2)];
        // prepending salt for md5
        $this->r = str_shuffle(md5($c2 . $s . md5($this->r)));
        // 32 chars random hex number derived from $s and lot of pepper and salt
        $s = $c1;
        switch ($this->scramble_mode) {
            case 'numeric':
                for ($i = 0, $l = $this->scramble_length - 1; $i < $l; ++$i) {
                    $s .= $this->t_chars[base_convert(substr($this->r, $i, 2), 16, 10) % ($this->l2 + 1)];
                }
                break;
            case 'hexa':
                for ($i = 0, $l = $this->scramble_length - 1; $i < $l; ++$i) {
                    $s .= substr($this->r, $i, 1);
                }
                break;
            case 'identifier':
            default:
                for ($i = 0, $l = $this->scramble_length - 1; $i < $l; ++$i) {
                    $s .= $this->t_chars[base_convert(substr($this->r, 2 * $i, 2), 16, 10) % ($this->l2 + 1)];
                }
        }
        return $s;
    }
    /**
    * @function case_shuffle
    * @param $s
    * @return ?
    */
    private function case_shuffle($s)
    {
        for ($i = 0; $i < strlen($s); ++$i) {
            $s[$i] = mt_rand(0, 1) ? strtoupper($s[$i]) : strtolower($s[$i]);
        }
        return $s;
    }
    /**
    * @function scramble
    * @param $s
    * @return ?
    */
    public function scramble($s)
    {
        $r = $this->case_sensitive ? $s : strtolower($s);
        if (array_key_exists($r, $this->t_ignore)) {
            return $s;
        }
        if (isset($this->t_ignore_prefix)) {
            foreach ($this->t_ignore_prefix as $key => $dummy) {
                if (substr($r, 0, strlen($key)) === $key) {
                    return $s;
                }
            }
        }
        if (!isset($this->t_scramble[$r])) {
            // if not already scrambled:
            for ($i = 0; $i < 50; ++$i) {
                // try at max 50 times if the random generated scrambled string has already beeen generated!
                $x = $this->str_scramble($s);
                $z = strtolower($x);
                $y = $this->case_sensitive ? $x : $z;
                if (isset($this->t_rscramble[$y]) || isset($this->t_ignore[$z])) {
                    // this random value is either already used or a reserved name
                    if ($i == 5 && $this->scramble_length < $this->scramble_length_max) {
                        ++$this->scramble_length;
                    }
                    // if not found after 5 attempts, increase the length...
                    continue;
                    // the next attempt will always be successfull, unless we already are maxlength
                }
                $this->t_scramble[$r] = $y;
                $this->t_rscramble[$y] = $r;
                break;
            }
            if (!isset($this->t_scramble[$r])) {
                fprintf(STDERR, "Scramble Error: Identifier not found after 50 iterations!%sAborting...%s", PHP_EOL, PHP_EOL);
                // should statistically never occur!
                exit(2);
            }
        }
        return $this->case_sensitive ? $this->t_scramble[$r] : $this->case_shuffle($this->t_scramble[$r]);
    }
    /**
    * @function unscramble
    * @param $s
    * @return ?
    */
    public function unscramble($s)
    {
        if (!$this->case_sensitive) {
            $s = strtolower($s);
        }
        return isset($this->t_rscramble[$s]) ? $this->t_rscramble[$s] : '';
    }
    /**
    * @function generate_label_name
    * @param $prefix
    * @return ?
    */
    public function generate_label_name($prefix = "!label")
    {
        return $prefix . $this->label_counter++;
    }
}