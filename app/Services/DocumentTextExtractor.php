<?php

namespace App\Services;

use PhpOffice\PhpWord\IOFactory;
use Smalot\PdfParser\Parser;

class DocumentTextExtractor {
    public function extract(string $path,string $ext):string {
        $ext=strtolower($ext);
        if($ext==='pdf') return trim((new Parser())->parseFile($path)->getText());
        if(in_array($ext,['doc','docx'],true)){
            $doc=IOFactory::load($path); $out=[];
            foreach($doc->getSections() as $section) foreach($section->getElements() as $element)
                if(method_exists($element,'getText') && is_string($element->getText())) $out[]=$element->getText();
            return trim(implode("\n",$out));
        }
        throw new \RuntimeException('Unsupported document format.');
    }
}
