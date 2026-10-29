<?php

namespace App\Support;

/**
 * Turns model output into plain conversational text for chat bubbles: removes Markdown
 * markers (*, **, __, #, `) and replaces typographic dashes, which read as machine-written.
 */
class AiPlainText
{
    public static function clean(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        // Non-breaking and other Unicode hyphens become a normal hyphen ("sub‑field" -> "sub-field").
        $text = str_replace(["\u{2010}", "\u{2011}", "\u{2012}", "\u{00AD}"], ['-', '-', '-', ''], $text);

        // Number ranges such as "2–3" read as "2 to 3".
        $text = preg_replace('/(\d)\s*[\x{2013}\x{2014}]\s*(\d)/u', '$1 to $2', $text);
        // A dash before a capital letter starts a new thought ("skills – Your diploma"), so end the sentence.
        $text = preg_replace('/\s*[\x{2013}\x{2014}\x{2015}]\s*(?=\p{Lu})/u', '. ', $text);
        // Any other en/em dash or horizontal bar becomes a comma pause.
        $text = preg_replace('/\s*[\x{2013}\x{2014}\x{2015}]\s*/u', ', ', $text);

        // Markdown emphasis, headings, code and links.
        $text = preg_replace('/(\*\*|__)(.+?)\1/su', '$2', $text);
        $text = preg_replace('/(?<![\w*])\*(?!\s)(.+?)(?<!\s)\*(?![\w*])/u', '$1', $text);
        $text = preg_replace('/^\s{0,3}#{1,6}\s*/mu', '', $text);
        $text = preg_replace('/`{1,3}([^`]*)`{1,3}/u', '$1', $text);
        $text = preg_replace('/\[([^\]]+)\]\((https?:[^)]+)\)/u', '$1 ($2)', $text);

        // Bullet markers ("- ", "* ", "+ ") become a simple dot; numbered lists are kept.
        $text = preg_replace('/^[ \t]*[-*+•][ \t]+/mu', '• ', $text);
        $text = str_replace('*', '', $text);

        // Tidy punctuation left behind by the replacements.
        $text = preg_replace('/ ,/u', ',', $text);
        $text = preg_replace('/,\s*([,.!?;:])/u', '$1', $text);
        $text = preg_replace('/^, /mu', '', $text);
        $text = preg_replace('/[ \t]+$/mu', '', $text);
        $text = preg_replace("/\n{3,}/", "\n\n", $text);

        return trim($text);
    }
}
