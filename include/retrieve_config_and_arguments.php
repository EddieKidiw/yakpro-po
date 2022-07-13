<?php

namespace Eddiekidiw\YakproPo;

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
use Eddiekidiw\YakproPo\functions;
use Eddiekidiw\YakproPo\classes\Config;
/**
* @class retrieve_config_and_arguments
* @new retrieve_config_and_arguments();
*/
class retrieve_config_and_arguments
{
    public $t_args;
    public $t_yakpro_po_pathinfo;
    public $yakpro_po_dirname;
    public $config_filename = '';
    public $process_mode = '';
    // can be: 'file' or 'directory'
    public $pos;
    public $source_directory = '';
    public $argument_config_filename = false;
    public $clean_mode;
    public $whatis;
    public $debug_mode = false;
    public $conf = array();
    public $source_file = '';
    public $target = '';
    public $target_file = '';
    public $target_directory = '';
    public $parser_mode = '';
    private $t_where = array();
    private $config_file_namepart = '';
    private $force_conf_silent = false;
    /**
    * @function __construct
    * @param $argv
    * @param $yakpro_po_version
    * @param $config_file_namepart
    * @return ?
    */
    public function __construct($argv, $yakpro_po_version = '0.1', $config_file_namepart = 'yakpro-po.cnf')
    {
        $this->conf = new Config($yakpro_po_version);
        $this->t_args = $argv;
        $this->t_yakpro_po_pathinfo = pathinfo(realpath(array_shift($this->t_args)));
        $this->yakpro_po_dirname = $this->t_yakpro_po_pathinfo['dirname'];
        $this->pos = array_search('-h', $this->t_args);
        if (!isset($this->pos) || $this->pos === false) {
            $this->pos = array_search('--help', $this->t_args);
        }
        if (isset($this->pos) && $this->pos !== false) {
            fprintf(STDERR, "Info:\tyakpro-po version = %s%s", $yakpro_po_version, PHP_EOL . PHP_EOL);
            $lang = '';
            if (($x = getenv('LANG')) !== false) {
                $s = strtolower($x);
            }
            $x = explode('_', $x);
            $x = $x[0];
            if (file_exists("{$this->yakpro_po_dirname}/locale/{$x}/README.md")) {
                $help = file_get_contents("{$this->yakpro_po_dirname}/locale/{$x}/README.md");
            } elseif (file_exists("{$this->yakpro_po_dirname}/README.md")) {
                $help = file_get_contents("{$this->yakpro_po_dirname}/README.md");
            } else {
                $help = "Help File not found!";
            }
            $this->pos = stripos($help, '####');
            if ($this->pos !== false) {
                $help = substr($help, $this->pos + strlen('####'));
            }
            $this->pos = stripos($help, '####');
            if ($this->pos !== false) {
                $help = substr($help, 0, $this->pos);
            }
            $help = trim(str_replace(array('## ', '`'), array('', ''), $help));
            echo "{$help}" . PHP_EOL;
            exit(11);
        }
        $this->pos = array_search('--config-file', $this->t_args);
        if (isset($this->pos) && $this->pos !== false && isset($this->t_args[$this->pos + 1])) {
            $this->argument_config_filename = $this->t_args[$this->pos + 1];
            array_splice($this->t_args, $this->pos, 2);
            // remove the 2 args and reorder
        } else {
            $this->argument_config_filename = '';
        }
        $this->pos = array_search('-o', $this->t_args);
        if (!isset($this->pos) || $this->pos === false) {
            $this->pos = array_search('--output-file', $this->t_args);
        }
        if (isset($this->pos) && $this->pos !== false && isset($this->t_args[$this->pos + 1])) {
            $this->target = $this->t_args[$this->pos + 1];
            array_splice($this->t_args, $this->pos, 2);
            // remove the 2 args and reorder
        } else {
            $this->target = '';
        }
        $this->pos = array_search('--clean', $this->t_args);
        if (isset($this->pos) && $this->pos !== false) {
            $this->clean_mode = true;
            array_splice($this->t_args, $this->pos, 1);
            // remove the arg and reorder
        } else {
            $this->clean_mode = false;
        }
        $this->pos = array_search('--silent', $this->t_args);
        if (isset($this->pos) && $this->pos !== false) {
            $this->force_conf_silent = true;
            array_splice($this->t_args, $this->pos, 1);
            // remove the arg and reorder
        } else {
            $this->force_conf_silent = false;
        }
        $this->pos = array_search('--whatis', $this->t_args);
        if (isset($this->pos) && $this->pos !== false && isset($this->t_args[$this->pos + 1])) {
            $this->whatis = $this->t_args[$this->pos + 1];
            array_splice($this->t_args, $this->pos, 2);
            // remove the 2 args and reorder
            $this->force_conf_silent = true;
        } else {
            $this->whatis = '';
        }
        $this->pos = array_search('--debug', $this->t_args);
        if (isset($this->pos) && $this->pos !== false) {
            $this->debug_mode = true;
            array_splice($this->t_args, $this->pos, 1);
            // remove the arg and reorder
        } else {
            $this->debug_mode = false;
        }
        $this->pos = array_search('--debug', $this->t_args);
        // repeated --debug
        if (isset($this->pos) && $this->pos !== false) {
            $this->debug_mode = 2;
            array_splice($this->t_args, $this->pos, 1);
            // remove the arg and reorder
        }
        // $this->t_args now containes remaining parameters.
        // We will first look for config file, and then we will analyze $this->t_args accordingly
        $this->config_file_namepart = $config_file_namepart;
        if (($x = getenv('YAKPRO_PO_CONFIG_FILENAME')) !== false) {
            $this->config_file_namepart = $x;
        }
        if ($this->argument_config_filename != '') {
            $this->t_where[] = $this->argument_config_filename;
        }
        // --config-file argument
        if (($x = getenv('YAKPRO_PO_CONFIG_FILE')) !== false) {
            $this->t_where[] = $x;
        }
        // YAKPRO_PO_CONFIG_FILE
        if (($x = getenv('YAKPRO_PO_CONFIG_DIRECTORY')) !== false) {
            $this->t_where[] = "{$x}/{$this->config_file_namepart}";
        }
        // YAKPRO_PO_CONFIG_DIRECTORY
        $this->t_where[] = $this->config_file_namepart;
        // current_working_directory
        $this->t_where[] = "config/{$this->config_file_namepart}";
        // current_working_directory/config
        if (($x = getenv('HOME')) !== false) {
            $this->t_where[] = "{$x}/{$this->config_file_namepart}";
        }
        // HOME
        if ($x !== false) {
            $this->t_where[] = "{$x}/config/{$this->config_file_namepart}";
        }
        // HOME/config
        $this->t_where[] = "/usr/local/YAK/yakpro-po/{$this->config_file_namepart}";
        // /usr/local/YAK/yakpro-po
        $this->t_where[] = "{$this->yakpro_po_dirname}/yakpro-po.cnf";
        // source_code_directory/default_conf_filename
        foreach ($this->t_where as $dummy => $where) {
            if ($this->check_config_file($where)) {
                $this->config_filename = $where;
                break;
            }
        }
        if ($this->force_conf_silent) {
            $this->conf->silent = true;
        }
        if ($this->config_filename == '') {
            fprintf(STDERR, "Warning:No config file found... using default values!%s", PHP_EOL);
        } else {
            $this->config_filename = realpath($this->config_filename);
            if (!$this->conf->silent) {
                fprintf(STDERR, "Info:\tUsing [%s] Config File...%s", $this->config_filename, PHP_EOL);
            }
            require_once $this->config_filename;
            $this->conf->validate();
            if ($this->force_conf_silent) {
                $this->conf->silent = true;
            }
        }
        //var_dump($this->conf);
        if (!$this->conf->silent) {
            fprintf(STDERR, "Info:\tyakpro-po version = %s%s", $yakpro_po_version, PHP_EOL);
        }
        $this->pos = array_search('-y', $this->t_args);
        if (isset($this->pos) && $this->pos !== false) {
            $this->conf->confirm = false;
            array_splice($this->t_args, $this->pos, 1);
            // remove the arg and reorder
        }
        $this->pos = array_search('-s', $this->t_args);
        if (isset($this->pos) && $this->pos !== false) {
            $this->conf->strip_indentation = false;
            array_splice($this->t_args, $this->pos, 1);
        }
        $this->pos = array_search('--no-strip-indentation', $this->t_args);
        if (isset($this->pos) && $this->pos !== false) {
            $this->conf->strip_indentation = false;
            array_splice($this->t_args, $this->pos, 1);
        }
        $this->pos = array_search('--strip-indentation', $this->t_args);
        if (isset($this->pos) && $this->pos !== false) {
            $this->conf->strip_indentation = true;
            array_splice($this->t_args, $this->pos, 1);
        }
        $this->pos = array_search('--no-shuffle-statements', $this->t_args);
        if (isset($this->pos) && $this->pos !== false) {
            $this->conf->shuffle_stmts = false;
            array_splice($this->t_args, $this->pos, 1);
        }
        $this->pos = array_search('--shuffle-statements', $this->t_args);
        if (isset($this->pos) && $this->pos !== false) {
            $this->conf->shuffle_stmts = true;
            array_splice($this->t_args, $this->pos, 1);
        }
        $this->pos = array_search('--no-obfuscate-string-literal', $this->t_args);
        if (isset($this->pos) && $this->pos !== false) {
            $this->conf->obfuscate_string_literal = false;
            array_splice($this->t_args, $this->pos, 1);
        }
        $this->pos = array_search('--obfuscate-string-literal', $this->t_args);
        if (isset($this->pos) && $this->pos !== false) {
            $this->conf->obfuscate_string_literal = true;
            array_splice($this->t_args, $this->pos, 1);
        }
        $this->pos = array_search('--no-obfuscate-loop-statement', $this->t_args);
        if (isset($this->pos) && $this->pos !== false) {
            $this->conf->obfuscate_loop_statement = false;
            array_splice($this->t_args, $this->pos, 1);
        }
        $this->pos = array_search('--obfuscate-loop-statement', $this->t_args);
        if (isset($this->pos) && $this->pos !== false) {
            $this->conf->obfuscate_loop_statement = true;
            array_splice($this->t_args, $this->pos, 1);
        }
        $this->pos = array_search('--no-obfuscate-if-statement', $this->t_args);
        if (isset($this->pos) && $this->pos !== false) {
            $this->conf->obfuscate_if_statement = false;
            array_splice($this->t_args, $this->pos, 1);
        }
        $this->pos = array_search('--obfuscate-if-statement', $this->t_args);
        if (isset($this->pos) && $this->pos !== false) {
            $this->conf->obfuscate_if_statement = true;
            array_splice($this->t_args, $this->pos, 1);
        }
        $this->pos = array_search('--no-obfuscate-constant-name', $this->t_args);
        if (isset($this->pos) && $this->pos !== false) {
            $this->conf->obfuscate_constant_name = false;
            array_splice($this->t_args, $this->pos, 1);
        }
        $this->pos = array_search('--obfuscate-constant-name', $this->t_args);
        if (isset($this->pos) && $this->pos !== false) {
            $this->conf->obfuscate_constant_name = true;
            array_splice($this->t_args, $this->pos, 1);
        }
        $this->pos = array_search('--no-obfuscate-variable-name', $this->t_args);
        if (isset($this->pos) && $this->pos !== false) {
            $this->conf->obfuscate_variable_name = false;
            array_splice($this->t_args, $this->pos, 1);
        }
        $this->pos = array_search('--obfuscate-variable-name', $this->t_args);
        if (isset($this->pos) && $this->pos !== false) {
            $this->conf->obfuscate_variable_name = true;
            array_splice($this->t_args, $this->pos, 1);
        }
        $this->pos = array_search('--no-obfuscate-function-name', $this->t_args);
        if (isset($this->pos) && $this->pos !== false) {
            $this->conf->obfuscate_function_name = false;
            array_splice($this->t_args, $this->pos, 1);
        }
        $this->pos = array_search('--obfuscate-function-name', $this->t_args);
        if (isset($this->pos) && $this->pos !== false) {
            $this->conf->obfuscate_function_name = true;
            array_splice($this->t_args, $this->pos, 1);
        }
        $this->pos = array_search('--no-obfuscate-class-name', $this->t_args);
        if (isset($this->pos) && $this->pos !== false) {
            $this->conf->obfuscate_class_name = false;
            array_splice($this->t_args, $this->pos, 1);
        }
        $this->pos = array_search('--obfuscate-class-name', $this->t_args);
        if (isset($this->pos) && $this->pos !== false) {
            $this->conf->obfuscate_class_name = true;
            array_splice($this->t_args, $this->pos, 1);
        }
        $this->pos = array_search('--no-obfuscate-class_constant-name', $this->t_args);
        if (isset($this->pos) && $this->pos !== false) {
            $this->conf->obfuscate_class_constant_name = false;
            array_splice($this->t_args, $this->pos, 1);
        }
        $this->pos = array_search('--obfuscate-class_constant-name', $this->t_args);
        if (isset($this->pos) && $this->pos !== false) {
            $this->conf->obfuscate_class_constant_name = true;
            array_splice($this->t_args, $this->pos, 1);
        }
        $this->pos = array_search('--no-obfuscate-interface-name', $this->t_args);
        if (isset($this->pos) && $this->pos !== false) {
            $this->conf->obfuscate_interface_name = false;
            array_splice($this->t_args, $this->pos, 1);
        }
        $this->pos = array_search('--obfuscate-interface-name', $this->t_args);
        if (isset($this->pos) && $this->pos !== false) {
            $this->conf->obfuscate_interface_name = true;
            array_splice($this->t_args, $this->pos, 1);
        }
        $this->pos = array_search('--no-obfuscate-trait-name', $this->t_args);
        if (isset($this->pos) && $this->pos !== false) {
            $this->conf->obfuscate_trait_name = false;
            array_splice($this->t_args, $this->pos, 1);
        }
        $this->pos = array_search('--obfuscate-trait-name', $this->t_args);
        if (isset($this->pos) && $this->pos !== false) {
            $this->conf->obfuscate_trait_name = true;
            array_splice($this->t_args, $this->pos, 1);
        }
        $this->pos = array_search('--no-obfuscate-property-name', $this->t_args);
        if (isset($this->pos) && $this->pos !== false) {
            $this->conf->obfuscate_property_name = false;
            array_splice($this->t_args, $this->pos, 1);
        }
        $this->pos = array_search('--obfuscate-property-name', $this->t_args);
        if (isset($this->pos) && $this->pos !== false) {
            $this->conf->obfuscate_property_name = true;
            array_splice($this->t_args, $this->pos, 1);
        }
        $this->pos = array_search('--no-obfuscate-method-name', $this->t_args);
        if (isset($this->pos) && $this->pos !== false) {
            $this->conf->obfuscate_method_name = false;
            array_splice($this->t_args, $this->pos, 1);
        }
        $this->pos = array_search('--obfuscate-method-name', $this->t_args);
        if (isset($this->pos) && $this->pos !== false) {
            $this->conf->obfuscate_method_name = true;
            array_splice($this->t_args, $this->pos, 1);
        }
        $this->pos = array_search('--no-obfuscate-namespace-name', $this->t_args);
        if (isset($this->pos) && $this->pos !== false) {
            $this->conf->obfuscate_namespace_name = false;
            array_splice($this->t_args, $this->pos, 1);
        }
        $this->pos = array_search('--obfuscate-namespace-name', $this->t_args);
        if (isset($this->pos) && $this->pos !== false) {
            $this->conf->obfuscate_namespace_name = true;
            array_splice($this->t_args, $this->pos, 1);
        }
        $this->pos = array_search('--no-obfuscate-label-name', $this->t_args);
        if (isset($this->pos) && $this->pos !== false) {
            $this->conf->obfuscate_label_name = false;
            array_splice($this->t_args, $this->pos, 1);
        }
        $this->pos = array_search('--obfuscate-label-name', $this->t_args);
        if (isset($this->pos) && $this->pos !== false) {
            $this->conf->obfuscate_label_name = true;
            array_splice($this->t_args, $this->pos, 1);
        }
        $this->pos = array_search('--scramble-mode', $this->t_args);
        if (isset($this->pos) && $this->pos !== false && isset($this->t_args[$this->pos + 1])) {
            $this->conf->scramble_mode = $this->t_args[$this->pos + 1];
            array_splice($this->t_args, $this->pos, 2);
            // remove the 2 args and reorder
        }
        $this->pos = array_search('--scramble-length', $this->t_args);
        if (isset($this->pos) && $this->pos !== false && isset($this->t_args[$this->pos + 1])) {
            $this->conf->scramble_length = $this->t_args[$this->pos + 1] + 0;
            array_splice($this->t_args, $this->pos, 2);
            // remove the 2 args and reorder
        }
        switch (count($this->t_args)) {
            case 0:
                if (isset($this->conf->source_directory) && isset($this->conf->target_directory)) {
                    $this->process_mode = 'directory';
                    $this->source_directory = $this->conf->source_directory;
                    $this->target_directory = $this->conf->target_directory;
                    functions::create_context_directories($this->target_directory);
                    break;
                }
                fprintf(STDERR, "Error:\tsource_directory and target_directory not specified!%s\tneither within command line parameter,%s\tnor in config file!%s", PHP_EOL, PHP_EOL, PHP_EOL);
                exit(12);
            case 1:
                $this->source_file = realpath($this->t_args[0]);
                if ($this->source_file !== false && file_exists($this->source_file)) {
                    if (is_file($this->source_file) && is_readable($this->source_file)) {
                        $this->process_mode = 'file';
                        $this->target_file = $this->target;
                        if ($this->target_file !== '' && file_exists($this->target_file)) {
                            $x = realpath($this->target_file);
                            if (is_dir($x)) {
                                fprintf(STDERR, "Error:\tTarget file [%s] is a directory!%s", $x !== false ? $x : $this->target_file, PHP_EOL);
                                exit(13);
                            }
                            if (is_readable($x) && is_writable($x) && is_file($x) && file_get_contents($x) !== '') {
                                $fp = fopen($this->target_file, "r");
                                $y = fgets($fp);
                                $y = fgets($fp) . fgets($fp) . fgets($fp) . fgets($fp) . fgets($fp);
                                if (strpos($y, '    |  Obfuscated by YAK Pro - Php Obfuscator ') === false) {
                                    // comment is a magic string, used to not overwrite wrong files!!!
                                    $x = realpath($this->target_file);
                                    fprintf(STDERR, "Error:\tTarget file [%s] exists and is not an obfuscated file!%s", $x !== false ? $x : $this->target_file, PHP_EOL);
                                    exit(14);
                                }
                                fclose($fp);
                            }
                        }
                        break;
                    }
                    if (is_dir($this->source_file)) {
                        $this->process_mode = 'directory';
                        $this->source_directory = $this->source_file;
                        $this->target_directory = $this->target;
                        if ($this->target_directory == '' && isset($this->conf->target_directory)) {
                            $this->target_directory = $this->conf->target_directory;
                        }
                        if ($this->target_directory == '') {
                            fprintf(STDERR, "Error:\tTarget directory is not specified!%s", PHP_EOL);
                            exit(15);
                        }
                        functions::create_context_directories($this->target_directory);
                        break;
                    }
                }
                fprintf(STDERR, "Error:\tSource file [%s] is not readable!%s", $this->source_file !== false ? $this->source_file : $this->t_args[0], PHP_EOL);
                exit(16);
            default:
                fprintf(STDERR, "Error:\tToo much parameters are specified, I do not know how to deal with that!!!%s", PHP_EOL);
                exit(17);
        }
        //print_r($this->t_args);
        if (!$this->conf->silent) {
            fprintf(STDERR, "Info:\tProcess Mode\t\t= %s%s", $this->process_mode, PHP_EOL);
        }
        switch ($this->process_mode) {
            case 'file':
                if (!$this->conf->silent) {
                    fprintf(STDERR, "Info:\tsource_file\t\t= [%s]%s", $this->source_file, PHP_EOL);
                }
                if (!$this->conf->silent) {
                    fprintf(STDERR, "Info:\ttarget_file\t\t= [%s]%s", $this->target_file !== '' ? $this->target_file : 'stdout', PHP_EOL);
                }
                break;
            case 'directory':
                if (!$this->conf->silent) {
                    fprintf(STDERR, "Info:\tsource_directory\t= [%s]%s", $this->source_directory, PHP_EOL);
                }
                if (!$this->conf->silent) {
                    fprintf(STDERR, "Info:\ttarget_directory\t= [%s]%s", $this->target_directory, PHP_EOL);
                }
                break;
        }
        //print_r($this->conf);
        //die;
    }
    /**
    * @function check_config_file
    * @param $filename
    * @return ?
    */
    private function check_config_file($filename)
    {
        for ($ok = false;;) {
            if (!file_exists($filename)) {
                return false;
            }
            if (!is_readable($filename)) {
                fprintf(STDERR, "Warning:[%s] is not readable!%s", $filename, PHP_EOL);
                return false;
            }
            $fp = fopen($filename, "r");
            if ($fp === false) {
                break;
            }
            $line = trim(fgets($fp));
            if ($line != '<?php') {
                fclose($fp);
                break;
            }
            $line = trim(fgets($fp));
            if ($line != '// YAK Pro - Php Obfuscator: Config File') {
                fclose($fp);
                break;
            }
            fclose($fp);
            $ok = true;
            break;
        }
        if (!$ok) {
            fprintf(STDERR, "Warning:[%s] is not a valid yakpro-po config file!%s\tCheck if file is php, and if magic line is present!%s", $filename, PHP_EOL, PHP_EOL);
        }
        return $ok;
    }
}