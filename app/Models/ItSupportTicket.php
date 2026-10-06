<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItSupportTicket extends Model
{
    use HasFactory;

    /**
     * Ticket categories (value => label). The original participant/API values come first;
     * the IT-specific ones were added for the staff Help & Support page. Every validator
     * and filter reads this list so the IT queue can filter on any of them.
     */
    public const CATEGORIES = [
        'general' => 'General help',
        'access' => 'Account access',
        'learning' => 'Learning platform',
        'procurement' => 'Procurement',
        'hardware' => 'Laptop / hardware',
        'network' => 'Network / internet',
        'email' => 'Email / accounts',
        'software' => 'Software / system access',
        'printer' => 'Printer / scanner',
        'other_it' => 'Other IT issue',
        'other' => 'Other',
    ];

    public const PRIORITIES = [
        'low' => 'Low',
        'normal' => 'Normal',
        'high' => 'High',
        'urgent' => 'Urgent',
    ];

    public const STATUSES = [
        'open' => 'Open',
        'in_progress' => 'In progress',
        'awaiting_requester' => 'Awaiting your reply',
        'resolved' => 'Resolved',
    ];

    public static function categoryLabel(?string $category): string
    {
        return self::CATEGORIES[$category] ?? ucwords(str_replace('_', ' ', (string) $category));
    }

    protected $fillable = [
        'subject',
        'description',
        'category',
        'priority',
        'status',
        'requester_id',
        'assignee_id',
        'status_updated_at',
    ];

    protected $casts = [
        'status_updated_at' => 'datetime',
    ];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }
}
