<?php

namespace App\Support;

/** Display helpers for the document libraries (site tiles, avatars). */
class DocumentUi
{
    private const COLOURS = ['#800000', '#0f6cbd', '#107c41', '#8764b8', '#c43e1c', '#038387', '#5c2e91', '#986f0b', '#c239b3', '#4f6bed'];

    public static function colour(int|string $seed): string
    {
        $n = is_int($seed) ? $seed : crc32($seed);

        return self::COLOURS[abs($n) % count(self::COLOURS)];
    }

    public static function initials(?string $name): string
    {
        $words = preg_split('/[\s&\-]+/u', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY) ?: ['?'];
        $letters = mb_substr($words[0], 0, 1).(isset($words[1]) ? mb_substr($words[1], 0, 1) : mb_substr($words[0], 1, 1));

        return mb_strtoupper($letters);
    }
}
