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
use PhpParser\Error;
use PhpParser\Node;
use PhpParser\Node\Stmt;
/**
* @class functions
* @new functions();
*/
class functions
{
    public $conf;
    public $_parser;
    public $_traverser;
    public $_prettyPrinter;
    public $debug_mode;
    public $t_scrambler;
    /**
    * @function init
    * @param $conf
    * @param $parser
    * @param $traverser
    * @param $prettyPrinter
    * @param $debug_mode
    * @param $t_scrambler
    * @return ?
    */
    public function init($conf = null, $parser = null, $traverser = null, $prettyPrinter = null, $debug_mode = null, $t_scrambler = null)
    {
        $this->conf = $conf;
        $this->_parser = $parser;
        $this->_traverser = $traverser;
        $this->_prettyPrinter = $prettyPrinter;
        $this->debug_mode = $debug_mode;
        $this->t_scrambler = $t_scrambler;
    }
    /**
    * @function obfuscate
    * @param $filename
    * @return ?
    */
    public function obfuscate($filename)
    {
        //global $conf;
        //global $parser,$traverser,$prettyPrinter;
        //global $debug_mode;
        $src_filename = $filename;
        $tmp_filename = $first_line = '';
        $t_source = file($filename);
        if (substr($t_source[0], 0, 2) == '#!') {
            $first_line = array_shift($t_source);
            $tmp_filename = tempnam(sys_get_temp_dir(), 'po-');
            file_put_contents($tmp_filename, implode(PHP_EOL, $t_source));
            $filename = $tmp_filename;
            // override
        }
        try {
            $source = php_strip_whitespace($filename);
            fprintf(STDERR, "Obfuscating %s%s", $src_filename, PHP_EOL);
            //var_dump( token_get_all($source));    exit;
            if ($source === '') {
                if ($this->conf->conf->allow_and_overwrite_empty_files) {
                    return $source;
                }
                throw new \Exception("Error obfuscating [{$src_filename}]: php_strip_whitespace returned an empty string!");
            }
            try {
                $stmts = $this->_parser->parse($source);
                // PHP-Parser returns the syntax tree
            } catch (PhpParser\Error $e) {
                // if an error occurs, then redo it without php_strip_whitespace, in order to display the right line number with error!
                $source = file_get_contents($filename);
                $stmts = $this->_parser->parse($source);
            }
            if ($this->conf->debug_mode === 2) {
                //  == 2 is true when debug_mode is true!
                $source = file_get_contents($filename);
                $stmts = $this->_parser->parse($source);
            }
            if ($this->conf->debug_mode) {
                var_dump($stmts);
            }
            $stmts = $this->_traverser->traverse($stmts);
            //  Use PHP-Parser function to traverse the syntax tree and obfuscate names
            if ($this->conf->conf->shuffle_stmts && count($stmts) > 2) {
                $last_inst = array_pop($stmts);
                $last_use_stmt_pos = -1;
                foreach ($stmts as $i => $stmt) {
                    // if a use statement exists, do not shuffle before the last use statement
                    //TODO: enhancement: keep all use statements at their position, and shuffle all sub-parts
                    if ($stmt instanceof PhpParser\Node\Stmt\Use_) {
                        $last_use_stmt_pos = $i;
                    }
                }
                if ($last_use_stmt_pos < 0) {
                    $stmts_to_shuffle = $stmts;
                    $stmts = array();
                } else {
                    $stmts_to_shuffle = array_slice($stmts, $last_use_stmt_pos + 1);
                    $stmts = array_slice($stmts, 0, $last_use_stmt_pos + 1);
                }
                $stmts = array_merge($stmts, $this->shuffle_statements($stmts_to_shuffle));
                $stmts[] = $last_inst;
            }
            // if ($this->conf->debug_mode) var_dump($stmts);
            $code = trim($this->_prettyPrinter->prettyPrintFile($stmts));
            //  Use PHP-Parser function to output the obfuscated source, taking the modified obfuscated syntax tree as input
            if (isset($this->conf->conf->strip_indentation) && $this->conf->conf->strip_indentation) {
                // self-explanatory
                $code = $this->remove_whitespaces($code);
            }
            $endcode = substr($code, 6);
            $code = '<?php' . PHP_EOL;
            $code .= $this->conf->conf->get_comment();
            // comment obfuscated source
            if (isset($this->conf->conf->extract_comment_from_line) && isset($this->conf->conf->extract_comment_to_line)) {
                $t_source = file($filename);
                for ($i = $this->conf->conf->extract_comment_from_line - 1; $i < $this->conf->conf->extract_comment_to_line; ++$i) {
                    $code .= $t_source[$i];
                }
            }
            if (isset($this->conf->conf->user_comment)) {
                $code .= '/*' . PHP_EOL . $this->conf->conf->user_comment . PHP_EOL . '*/' . PHP_EOL;
            }
            $code .= $endcode;
            if ($tmp_filename != '' && $first_line != '') {
                $code = $first_line . $code;
                unlink($tmp_filename);
            }
            return trim($code);
        } catch (Exception $e) {
            fprintf(STDERR, "Obfuscator Parse Error [%s]:%s\t%s%s", $filename, PHP_EOL, $e->getMessage(), PHP_EOL);
            return null;
        }
    }
    /**
    * @function check_preload_file
    * @param $filename
    * @return ?
    */
    public function check_preload_file($filename)
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
            if ($line != '// YAK Pro - Php Obfuscator: Preload File') {
                fclose($fp);
                break;
            }
            fclose($fp);
            $ok = true;
            break;
        }
        if (!$ok) {
            fprintf(STDERR, "Warning:[%s] is not a valid yakpro-po preload file!%s\tCheck if file is php, and if magic line is present!%s", $filename, PHP_EOL, PHP_EOL);
        }
        return $ok;
    }
    /**
    * @function create_context_directories
    * @param $target_directory
    * @return ?
    */
    public static function create_context_directories($target_directory)
    {
        foreach (array("{$target_directory}/yakpro-po", "{$target_directory}/yakpro-po/obfuscated", "{$target_directory}/yakpro-po/context") as $dummy => $dir) {
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }
            if (!file_exists($dir)) {
                fprintf(STDERR, "Error:\tCannot create directory [%s]%s", $dir, PHP_EOL);
                exit(51);
            }
        }
        $target_directory = realpath($target_directory);
        if (!file_exists("{$target_directory}/yakpro-po/.yakpro-po-directory")) {
            touch("{$target_directory}/yakpro-po/.yakpro-po-directory");
        }
    }
    /**
    * @function remove_directory
    * @param $path
    * @return ?
    */
    public function remove_directory($path)
    {
        if ($dp = opendir($path)) {
            while (($entry = readdir($dp)) !== false) {
                if ($entry == ".") {
                    continue;
                }
                if ($entry == "..") {
                    continue;
                }
                if (is_link("{$path}/{$entry}")) {
                    unlink("{$path}/{$entry}");
                } elseif (is_dir("{$path}/{$entry}")) {
                    $this->remove_directory("{$path}/{$entry}");
                } else {
                    unlink("{$path}/{$entry}");
                }
            }
            closedir($dp);
            rmdir($path);
        }
    }
    /**
    * @function confirm
    * @param $str
    * @return ?
    */
    public function confirm($str)
    {
        //global $conf;
        if (!$this->conf->conf->confirm) {
            return true;
        }
        for (;;) {
            fprintf(STDERR, "%s [y/n] : ", $str);
            $r = strtolower(trim(fgets(STDIN)));
            if ($r == 'y') {
                return true;
            }
            if ($r == 'n') {
                return false;
            }
        }
    }
    /**
    * @function obfuscate_directory
    * @param $source_dir
    * @param $target_dir
    * @param $keep_mode
    * @return ?
    */
    public function obfuscate_directory($source_dir, $target_dir, $keep_mode = false)
    {
        //global $conf;
        static $recursion_level = 0;
        if (++$recursion_level > $this->conf->conf->max_nested_directory) {
            if ($this->conf->conf->follow_symlinks) {
                fprintf(STDERR, "Error:\t [%s] nested directories have been created!\nloop detected when follow_symlinks option is set to true!%s", $this->conf->conf->max_nested_directory, PHP_EOL);
                exit(52);
            }
        }
        if (!($dp = opendir($source_dir))) {
            fprintf(STDERR, "Error:\t [%s] directory does not exists!%s", $source_dir, PHP_EOL);
            exit(53);
        }
        $t_dir = array();
        $t_file = array();
        while (($entry = readdir($dp)) !== false) {
            if ($entry == "." || $entry == "..") {
                continue;
            }
            $new_keep_mode = $keep_mode;
            $source_path = "{$source_dir}/{$entry}";
            $source_stat = @lstat($source_path);
            $target_path = "{$target_dir}/{$entry}";
            $target_stat = @lstat($target_path);
            if ($source_stat === false) {
                fprintf(STDERR, "Error:\t cannot stat [%s] !%s", $source_path, PHP_EOL);
                exit(54);
            }
            if (isset($this->conf->conf->t_skip) && is_array($this->conf->conf->t_skip) && in_array($source_path, $this->conf->conf->t_skip)) {
                continue;
            }
            if (!$this->conf->conf->follow_symlinks && is_link($source_path)) {
                if ($target_stat !== false && is_link($target_path) && $source_stat['mtime'] <= $target_stat['mtime']) {
                    continue;
                }
                if ($target_stat !== false) {
                    if (is_dir($target_path)) {
                        $this->remove_directory($target_path);
                    } else {
                        if (unlink($target_path) === false) {
                            fprintf(STDERR, "Error:\t cannot unlink [%s] !%s", $target_path, PHP_EOL);
                            exit(55);
                        }
                    }
                }
                @symlink(readlink($source_path), $target_path);
                // Do not warn on non existing symbolinc link target!
                if (strtolower(PHP_OS) == 'linux') {
                    $x = `touch '{$target_path}' --no-dereference --reference='{$source_path}' `;
                }
                continue;
            }
            if (is_dir($source_path)) {
                if ($target_stat !== false) {
                    if (!is_dir($target_path)) {
                        if (unlink($target_path) === false) {
                            fprintf(STDERR, "Error:\t cannot unlink [%s] !%s", $target_path, PHP_EOL);
                            exit(56);
                        }
                    }
                }
                if (!file_exists($target_path)) {
                    mkdir($target_path, 0777, true);
                }
                if (isset($this->conf->conf->t_keep) && is_array($this->conf->conf->t_keep) && in_array($source_path, $this->conf->conf->t_keep)) {
                    $new_keep_mode = true;
                }
                $this->obfuscate_directory($source_path, $target_path, $new_keep_mode);
                continue;
            }
            if (is_file($source_path)) {
                if ($target_stat !== false && is_dir($target_path)) {
                    $this->remove_directory($target_path);
                }
                if ($target_stat !== false && $source_stat['mtime'] <= $target_stat['mtime']) {
                    continue;
                }
                // do not process if source timestamp is not greater than target
                $extension = pathinfo($source_path, 4);
                $keep = $keep_mode;
                if (isset($this->conf->conf->t_keep) && is_array($this->conf->conf->t_keep) && in_array($source_path, $this->conf->conf->t_keep)) {
                    $keep = true;
                }
                if (!in_array($extension, $this->conf->conf->t_obfuscate_php_extension)) {
                    $keep = true;
                }
                if ($keep) {
                    file_put_contents($target_path, file_get_contents($source_path));
                } else {
                    $obfuscated_str = $this->obfuscate($source_path);
                    if ($obfuscated_str === null) {
                        if (isset($this->conf->conf->abort_on_error)) {
                            fprintf(STDERR, "Aborting...%s", PHP_EOL);
                            exit(57);
                        }
                    }
                    file_put_contents($target_path, $obfuscated_str . PHP_EOL);
                }
                touch($target_path, $source_stat['mtime']);
                chmod($target_path, $source_stat['mode']);
                chgrp($target_path, $source_stat['gid']);
                chown($target_path, $source_stat['uid']);
                continue;
            }
        }
        closedir($dp);
        --$recursion_level;
    }
    /**
    * @function shuffle_get_chunk_size
    * @param $stmts
    * @return ?
    */
    public function shuffle_get_chunk_size(&$stmts)
    {
        //global $conf;
        $n = count($stmts);
        switch ($this->conf->conf->shuffle_stmts_chunk_mode) {
            case 'ratio':
                $chunk_size = sprintf("%d", $n / $this->conf->conf->shuffle_stmts_chunk_ratio) + 0;
                if ($chunk_size < $this->conf->conf->shuffle_stmts_min_chunk_size) {
                    $chunk_size = $this->conf->conf->shuffle_stmts_min_chunk_size;
                }
                break;
            case 'fixed':
                $chunk_size = $this->conf->conf->shuffle_stmts_min_chunk_size;
                break;
            default:
                $chunk_size = 1;
        }
        return $chunk_size;
    }
    /**
    * @function shuffle_statements
    * @param $stmts
    * @return ?
    */
    public function shuffle_statements($stmts)
    {
        //global $conf;
        //global $t_scrambler;
        if (!$this->conf->conf->shuffle_stmts) {
            return $stmts;
        }
        $chunk_size = $this->shuffle_get_chunk_size($stmts);
        if ($chunk_size <= 0) {
            return $stmts;
        }
        // should never occur!
        $n = count($stmts);
        if ($n < 2 * $chunk_size) {
            return $stmts;
        }
        $scrambler = $this->t_scrambler['label'];
        $label_name_prev = $scrambler->scramble($scrambler->generate_label_name());
        $first_goto = new Stmt\Goto_($label_name_prev);
        $t = array();
        $t_chunk = array();
        for ($i = 0; $i < $n; ++$i) {
            $t_chunk[] = $stmts[$i];
            if (count($t_chunk) >= $chunk_size) {
                $label = array(new Stmt\Label($label_name_prev));
                $label_name = $scrambler->scramble($scrambler->generate_label_name());
                $goto = array(new Stmt\Goto_($label_name));
                $t[] = array_merge($label, $t_chunk, $goto);
                $label_name_prev = $label_name;
                $t_chunk = array();
            }
        }
        if (count($t_chunk) > 0) {
            $label = array(new Stmt\Label($label_name_prev));
            $label_name = $scrambler->scramble($scrambler->generate_label_name());
            $goto = array(new Stmt\Goto_($label_name));
            $t[] = array_merge($label, $t_chunk, $goto);
            $label_name_prev = $label_name;
            $t_chunk = array();
        }
        $last_label = new Stmt\Label($label_name);
        shuffle($t);
        $stmts = array();
        $stmts[] = $first_goto;
        foreach ($t as $dummy => $stmt) {
            foreach ($stmt as $dummy => $inst) {
                $stmts[] = $inst;
            }
        }
        $stmts[] = $last_label;
        return $stmts;
    }
    /**
    * @function remove_whitespaces
    * @param $str
    * @return ?
    */
    public function remove_whitespaces($str)
    {
        $tmp_filename = @tempnam(sys_get_temp_dir(), 'po-');
        file_put_contents($tmp_filename, $str);
        $str = php_strip_whitespace($tmp_filename);
        // can remove more whitespaces
        @unlink($tmp_filename);
        return $str;
    }
}