<?php

namespace App\Http\Controllers\Api\V1\Participant;

use App\Http\Controllers\Controller;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;

/**
 * Help & Support details for the mobile app. These are the same values the
 * web page at /participant/help shows, managed in Admin > Support Settings.
 */
class SupportController extends Controller
{
    private const FIELDS = [
        'email', 'alternate_email', 'phone', 'whatsapp', 'branch',
        'address', 'hours', 'introduction', 'technical',
    ];

    public function show(SettingsService $settings): JsonResponse
    {
        $support = [];

        foreach (self::FIELDS as $field) {
            $value = trim((string) $settings->get('support.'.$field, ''));
            $support[$field] = $value === '' ? null : $value;
        }

        $support['introduction'] ??= 'Contact the ElevateHer360 support team if you need assistance.';
        $support['email'] ??= config('legal.support_email') ?: null;

        $whatsappDigits = preg_replace('/\D+/', '', (string) $support['whatsapp']);
        $support['whatsapp_url'] = $whatsappDigits !== '' ? 'https://wa.me/'.$whatsappDigits : null;
        $support['help_page_url'] = route('participant.help');
        $support['privacy_policy_url'] = route('legal.privacy');
        $support['terms_url'] = route('legal.terms');

        return response()->json(['support' => $support]);
    }
}
