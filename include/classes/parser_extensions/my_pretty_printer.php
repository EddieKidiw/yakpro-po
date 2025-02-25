<?php

namespace Eddiekidiw\YakproPo\classes\parser_extensions;

//========================================================================
// Author:  Pascal KISSIAN
// Resume:  http://pascal.kissian.net
//
// Copyright (c) 2015-2021 Pascal KISSIAN
//
// Published under the MIT License
//          Consider it as a proof of concept!
//          No warranty of any kind.
//          Use and abuse at your own risks.
//========================================================================
use PhpParser;
use PhpParser\Node;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Scalar\Encapsed;
use PhpParser\Node\Scalar\EncapsedStringPart;
use PhpParser\PrettyPrinter\Standard;
/**
* @class my_pretty_printer
* @new my_pretty_printer();
*/
class my_pretty_printer extends Standard
{
    /**
    * @function obfuscate_string
    * @param $str
    * @return ?
    */
    private function obfuscate_string($str)
    {
        $l = strlen($str);
        $result = '';
        for ($i = 0; $i < $l; ++$i) {
            $result .= mt_rand(0, 1) ? "\\x" . dechex(ord($str[$i])) : "\\" . decoct(ord($str[$i]));
        }
        return $result;
    }
    /**
    * @function pScalar_String
    * @param String_ $node
    * @return ?
    */
    public function pScalar_String(String_ $node)
    {
        $result = $this->obfuscate_string($node->value);
        if (!strlen($result)) {
            return "''";
        }
        return '"' . $this->obfuscate_string($node->value) . '"';
    }
    /**
    * @function pScalar_Encapsed
    * @param Encapsed $node
    * @return ?
    */
    protected function pScalar_Encapsed(Encapsed $node)
    {
        /*
        if ($node->getAttribute('kind') === String_::KIND_HEREDOC) 
        {
            $label = $node->getAttribute('docLabel');
            if ($label && !$this->encapsedContainsEndLabel($node->parts, $label)) 
            {
                if (count($node->parts) === 1
                    && $node->parts[0] instanceof EncapsedStringPart
                    && $node->parts[0]->value === ''
                )
                {
                    return "<<<$label\n$label" . $this->docStringEndToken;
                }
        
                return "<<<$label\n" . $this->pEncapsList($node->parts, null) . "\n$label"
                     . $this->docStringEndToken;
            }
        }
        */
        $result = '';
        foreach ($node->parts as $element) {
            if ($element instanceof EncapsedStringPart) {
                $result .= $this->obfuscate_string($element->value);
            } else {
                $result .= '{' . $this->p($element) . '}';
            }
        }
        return '"' . $result . '"';
    }
}