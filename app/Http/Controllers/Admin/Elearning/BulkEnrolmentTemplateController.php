<?php

namespace App\Http\Controllers\Admin\Elearning;

use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BulkEnrolmentTemplateController extends Controller
{
    public function download(): StreamedResponse
    {
        $filename = 'elevateher360_bulk_enrolment_template.csv';

        return response()->streamDownload(function (): void {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Excel compatibility.
            fwrite($handle, "\xEF\xBB\xBF");

            /*
             * Only email is required by the existing bulk enrolment importer.
             * The second row is an example and should be replaced/deleted by the user.
             */
            fputcsv($handle, ['email']);
            fputcsv($handle, ['participant@example.com']);

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }
}
