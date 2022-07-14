#!/usr/bin/env php
<?php
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
if (isset($_SERVER['SERVER_SOFTWARE']) && $_SERVER['SERVER_SOFTWARE'] != '') {
    echo "<h1>Comand Line Interface Only!</h1>";
    die;
}
include 'version.php';
include 'include/check_version.php';
include 'vendor/autoload.php';
use PhpParser\Error;
use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;
use PhpParser\PrettyPrinter;
use PhpParser\PrettyPrinter\Standard;
use Eddiekidiw\YakproPo\functions;
use Eddiekidiw\YakproPo\classes\Scrambler;
use Eddiekidiw\YakproPo\retrieve_config_and_arguments;
use Eddiekidiw\YakproPo\classes\parser_extensions\my_node_visitor;
use Eddiekidiw\YakproPo\classes\parser_extensions\my_pretty_printer;

$Config = new retrieve_config_and_arguments($argv, $yakpro_po_version);
if ($Config->clean_mode && file_exists("{$Config->target_directory}/yakpro-po/.yakpro-po-directory")) {
    if (!$Config->conf->silent) {
        fprintf(STDERR, "Info:\tRemoving directory\t= [%s]%s", "{$Config->target_directory}/yakpro-po", PHP_EOL);
    }
    (new functions())->remove_directory("{$Config->target_directory}/yakpro-po");
    exit(31);
}
switch ($Config->parser_mode) {
    case 'PREFER_PHP7':
        $parser_mode = ParserFactory::PREFER_PHP7;
        break;
    case 'PREFER_PHP5':
        $parser_mode = ParserFactory::PREFER_PHP5;
        break;
    case 'ONLY_PHP7':
        $parser_mode = ParserFactory::ONLY_PHP7;
        break;
    case 'ONLY_PHP5':
        $parser_mode = ParserFactory::ONLY_PHP5;
        break;
    default:
        $parser_mode = ParserFactory::PREFER_PHP5;
        break;
}
$parser = (new ParserFactory())->create($parser_mode);
$traverser = new NodeTraverser();
if ($Config->conf->obfuscate_string_literal) {
    $prettyPrinter = new my_pretty_printer();
} else {
    $prettyPrinter = new Standard();
}
$t_scrambler = array();
//foreach(array('variable','function','method','property','class','class_constant','constant','label') as $scramble_what)
foreach (array('variable', 'function_or_class', 'method', 'property', 'class_constant', 'constant', 'label') as $scramble_what) {
    $t_scrambler[$scramble_what] = new Scrambler($scramble_what, $Config, $Config->process_mode == 'directory' ? $Config->target_directory : null);
}
if ($Config->whatis !== '') {
    if ($Config->whatis[0] == '$') {
        $Config->whatis = substr($Config->whatis, 1);
    }
    //    foreach(array('variable','function','method','property','class','class_constant','constant','label') as $scramble_what)
    foreach (array('variable', 'function_or_class', 'method', 'property', 'class_constant', 'constant', 'label') as $scramble_what) {
        if (($s = $t_scrambler[$scramble_what]->unscramble($Config->whatis)) !== '') {
            switch ($scramble_what) {
                case 'variable':
                case 'property':
                    $prefix = '$';
                    break;
                default:
                    $prefix = '';
            }
            echo "{$scramble_what}: {$prefix}{$s}" . PHP_EOL;
        }
    }
    exit(32);
}
$traverser->addVisitor(new my_node_visitor($Config, $t_scrambler));
$Obfuscator = new functions();
$Obfuscator->init($Config, $parser, $traverser, $prettyPrinter, $Config->debug_mode, $t_scrambler);
switch ($Config->process_mode) {
    case 'file':
        $obfuscated_str = $Obfuscator->obfuscate($Config->source_file);
        if ($obfuscated_str === null) {
            exit(33);
        }
        if ($Config->target_file === '') {
            echo $obfuscated_str . PHP_EOL . PHP_EOL;
            exit(34);
        }
        file_put_contents($Config->target_file, $obfuscated_str);
        exit(0);
    case 'directory':
        if (isset($Config->conf->t_skip) && is_array($Config->conf->t_skip)) {
            foreach ($Config->conf->t_skip as $key => $val) {
                $Config->conf->t_skip[$key] = "{$Config->source_directory}/{$val}";
            }
        }
        if (isset($Config->conf->t_keep) && is_array($Config->conf->t_keep)) {
            foreach ($Config->conf->t_keep as $key => $val) {
                $Config->conf->t_keep[$key] = "{$Config->source_directory}/{$val}";
            }
        }
        $Obfuscator->obfuscate_directory($Config->source_directory, "{$Config->target_directory}/yakpro-po/obfuscated");
        exit(0);
}
?>

