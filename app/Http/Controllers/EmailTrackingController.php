<?php

namespace App\Http\Controllers;

use App\Models\UserNotification;
use App\Services\NotificationDispatcher;
use Illuminate\Http\Response;

class EmailTrackingController extends Controller
{
    /**
     * A 1x1 transparent pixel embedded in notification emails. Loading it marks
     * the notification as opened and, where configured, tells the applicant that
     * an employer has viewed their application.
     */
    public function track(string $token, NotificationDispatcher $dispatcher): Response
    {
        $notification = UserNotification::where('tracking_token', $token)->first();

        if ($notification && $notification->opened_at === null) {
            $notification->markOpened();

            $data = $notification->data ?? [];

            if (! empty($data['notify_applicant_on_open']) && ! empty($data['applicant_id'])) {
                $applicant = \App\Models\User::find($data['applicant_id']);

                if ($applicant) {
                    $dispatcher->notify(
                        $applicant,
                        'job_application_viewed',
                        'Your job application was viewed',
                        'An employer has reviewed your application'
                            .(! empty($data['job_title']) ? ' for '.$data['job_title'] : '')
                            .'. Keep an eye on your applications for updates.',
                        route('jobs.applications'),
                        ['job_id' => $data['job_id'] ?? null, 'job_application_id' => $data['job_application_id'] ?? null],
                        false
                    );
                }
            }
        }

        return response($this->pixel(), 200, [
            'Content-Type' => 'image/gif',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }

    private function pixel(): string
    {
        return base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
    }
}
