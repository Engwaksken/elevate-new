<?php

namespace App\Support;

/**
 * Shared instructions so AI writing reads like a real person and suits participants
 * with limited IT experience. Pair with AiPlainText::clean() on the output.
 */
class AiWritingStyle
{
    /** Tone and format for documents she will send (resume text, cover letters). */
    public const DOCUMENT = 'Write the way a thoughtful person writes, not like a template: natural sentences, everyday words, varied sentence length, '
        .'and specific details from the facts supplied. Avoid buzzwords and cliches such as "passionate", "dynamic", "results-driven", '
        .'"leverage", "synergy" or "I am writing to express my interest". '
        .'Write plain text only: no Markdown, no asterisks, no bold, no headings, no bullet symbols, no emojis and no long dashes; '
        .'use commas or full stops instead. Never invent facts, numbers, employers, qualifications or skills that were not supplied.';

    /** Tone and format for advice shown to her (reviews, checks, comparisons). */
    public const ADVICE = 'Explain things to her in simple, friendly language as if sitting beside her, assuming she is new to computers and job applications. '
        .'Avoid jargon; if you must use a term like ATS, explain it in a few words. '
        .'Give at most five short numbered points, each saying exactly what to change and, where useful, an example sentence she can copy. '
        .'Write plain text only: no Markdown, no asterisks, no bold, no headings, no bullet symbols and no long dashes. '
        .'Never invent facts, numbers, employers, qualifications or skills.';
}
