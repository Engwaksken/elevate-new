<?php

namespace App\Services;

use PhpOffice\PhpWord\IOFactory;
use Smalot\PdfParser\Parser;

class DocumentTextExtractor {
    public function extract(string $path,string $ext):string {
        $ext=strtolower($ext);
        if($ext==='pdf') return trim((new Parser())->parseFile($path)->getText());
        if(in_array($ext,['doc','docx'],true)){
            $doc=IOFactory::load($path, $ext === 'doc' ? 'MsDoc' : 'Word2007'); $out=[];
            foreach($doc->getSections() as $section) $this->collectText($section, $out);
            return trim(implode("\n",$out));
        }
        throw new \RuntimeException('Unsupported document format.');
    }

    private function collectText(object $element, array &$out): void
    {
        if (method_exists($element, 'getText') && is_string($text = $element->getText())) $out[] = $text;
        foreach (['getElements', 'getRows', 'getCells'] as $method) {
            if (method_exists($element, $method)) {
                foreach ($element->{$method}() as $child) {
                    if (is_object($child)) $this->collectText($child, $out);
                }
            }
        }
    }
}
