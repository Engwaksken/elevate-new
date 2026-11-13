<?php

namespace App\Support;

use App\Models\ParticipantConversation;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;

/**
 * Display helpers for the participant chat views (titles, avatars, timestamps).
 */
class ChatPresenter
{
    private const COLORS = ['#800000', '#1d4ed8', '#047857', '#b45309', '#7c3aed', '#be185d', '#0e7490', '#4d7c0f'];

    public static function title(ParticipantConversation $conversation, int $viewerId): string
    {
        if ($conversation->type === 'group') {
            return $conversation->title ?: 'Group conversation';
        }

        return $conversation->members->firstWhere('id', '!=', $viewerId)?->name ?? 'Direct conversation';
    }

    public static function initials(?string $name): string
    {
        $parts = preg_split('/\s+/', trim((string) $name)) ?: [];
        $letters = collect($parts)->filter()->take(2)->map(fn ($part) => Str::upper(Str::substr($part, 0, 1)))->join('');

        return $letters !== '' ? $letters : '?';
    }

    public static function color(int|string|null $seed): string
    {
        return self::COLORS[abs(crc32((string) $seed)) % count(self::COLORS)];
    }

    /**
     * Avatar seed for a conversation: the other person in a direct chat, the group itself otherwise.
     */
    public static function avatarSeed(ParticipantConversation $conversation, int $viewerId): string
    {
        return $conversation->type === 'group'
            ? 'g'.$conversation->id
            : 'u'.($conversation->members->firstWhere('id', '!=', $viewerId)?->id ?? $conversation->id);
    }

    public static function preview(ParticipantConversation $conversation, int $viewerId): string
    {
        $latest = $conversation->latestMessage;
        if (! $latest) {
            return $conversation->type === 'group' ? 'Group created. Say hello!' : 'No messages yet';
        }

        $prefix = (int) $latest->sender_id === $viewerId
            ? 'You: '
            : ($conversation->type === 'group' && $latest->sender ? Str::before($latest->sender->name, ' ').': ' : '');

        return $prefix.Str::limit(preg_replace('/\s+/', ' ', $latest->body), 80);
    }

    /**
     * Short list timestamp: time today, weekday this week, date otherwise.
     */
    public static function listTime(?CarbonInterface $at): string
    {
        if (! $at) {
            return '';
        }
        if ($at->isToday()) {
            return $at->format('H:i');
        }
        if ($at->isYesterday()) {
            return 'Yesterday';
        }
        if ($at->greaterThan(now()->subDays(6)->startOfDay())) {
            return $at->format('D');
        }

        return $at->format($at->isCurrentYear() ? 'd M' : 'd M Y');
    }

    public static function dayLabel(CarbonInterface $at): string
    {
        if ($at->isToday()) {
            return 'Today';
        }
        if ($at->isYesterday()) {
            return 'Yesterday';
        }

        return $at->format($at->isCurrentYear() ? 'l, d M' : 'd M Y');
    }
}
