<?php

namespace App\Support\Export;

use App\Services\SettingsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Renders a TableExport as a streamed CSV or a dompdf PDF.
 */
class TableExporter
{
    public const FORMATS = ['csv', 'pdf'];

    /** Column count above which the PDF switches to landscape A4. */
    public const LANDSCAPE_AFTER_COLUMNS = 6;

    public function __construct(protected SettingsService $settings)
    {
    }

    public static function isSupported(?string $format): bool
    {
        return in_array($format, self::FORMATS, true);
    }

    public function download(TableExport $export, string $format): Response
    {
        return match ($format) {
            'csv' => $this->csv($export),
            'pdf' => $this->pdf($export),
            default => throw new InvalidArgumentException("Unsupported export format [{$format}]."),
        };
    }

    public function csv(TableExport $export): StreamedResponse
    {
        return response()->streamDownload(function () use ($export): void {
            $out = fopen('php://output', 'w');
            // UTF-8 BOM so Excel detects the encoding correctly.
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $export->headings());

            $i = 0;
            foreach ($export->rows() as $row) {
                fputcsv($out, array_map([static::class, 'csvSafe'], $export->mapRow($row, $i++)));
                if ($i % 500 === 0) {
                    flush();
                }
            }

            fclose($out);
        }, $export->filenameFor('csv'), [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function pdf(TableExport $export): Response
    {
        $limit = $export->getPdfLimit();
        $rows = [];
        $truncated = false;
        $i = 0;

        foreach ($export->rows() as $row) {
            if ($i >= $limit) {
                $truncated = true;
                break;
            }
            $rows[] = $export->mapRow($row, $i++);
        }

        $headings = $export->headings();
        $orientation = count($headings) > self::LANDSCAPE_AFTER_COLUMNS ? 'landscape' : 'portrait';

        return Pdf::loadView('exports.table-pdf', [
            'title' => $export->title(),
            'subtitle' => $export->getSubtitle(),
            'headings' => $headings,
            'rows' => $rows,
            'filters' => $export->appliedFilters(),
            'truncated' => $truncated,
            'limit' => $limit,
            'generatedAt' => now(),
            'generatedBy' => auth()->user()?->name,
            'orgName' => $this->organisationName(),
            'logo' => $this->logoDataUri(),
            'orientation' => $orientation,
        ])->setPaper('a4', $orientation)->download($export->filenameFor('pdf'));
    }

    /**
     * Neutralise spreadsheet formula injection (=, +, -, @, tab, CR) while
     * leaving plain numbers such as "-12.5" untouched.
     */
    public static function csvSafe(string $value): string
    {
        if ($value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true) && ! is_numeric($value)) {
            return "'".$value;
        }

        return $value;
    }

    protected function organisationName(): string
    {
        $name = $this->settings->get('branding.system_name');

        return is_string($name) && trim($name) !== '' ? trim($name) : (string) config('app.name');
    }

    protected function logoDataUri(): ?string
    {
        try {
            $path = $this->settings->get('branding.logo_path');
            if (! is_string($path) || $path === '') {
                return null;
            }

            $disk = Storage::disk('public');
            if (! $disk->exists($path)) {
                return null;
            }

            $mime = $disk->mimeType($path) ?: 'image/png';
            // dompdf cannot render SVG/WebP reliably; skip rather than break the PDF.
            if (! in_array($mime, ['image/png', 'image/jpeg', 'image/gif'], true)) {
                return null;
            }

            return 'data:'.$mime.';base64,'.base64_encode($disk->get($path));
        } catch (Throwable) {
            return null;
        }
    }
}
