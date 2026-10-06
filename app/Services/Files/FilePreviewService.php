<?php

namespace App\Services\Files;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory as SpreadsheetIOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use PhpOffice\PhpWord\Settings as WordSettings;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Renders in-browser previews of stored files.
 *
 * Controllers keep their own authorisation and call respond() when the
 * request carries ?preview=1 (preview page) or ?preview=raw (inline bytes).
 */
class FilePreviewService
{
    public const IMAGE = ['png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif', 'webp' => 'image/webp', 'bmp' => 'image/bmp'];
    public const SPREADSHEET = ['xlsx', 'xlsm', 'xls', 'ods', 'csv'];
    public const WORD = ['docx' => 'Word2007', 'odt' => 'ODText', 'rtf' => 'RTF'];
    public const TEXT = ['txt', 'md', 'log', 'json', 'xml'];
    public const VIDEO = ['mp4' => 'video/mp4', 'm4v' => 'video/mp4', 'webm' => 'video/webm', 'mov' => 'video/quicktime', 'ogv' => 'video/ogg'];
    public const AUDIO = ['mp3' => 'audio/mpeg', 'm4a' => 'audio/mp4', 'aac' => 'audio/aac', 'wav' => 'audio/wav', 'ogg' => 'audio/ogg', 'oga' => 'audio/ogg'];

    public const MAX_BYTES = 15 * 1024 * 1024;
    public const MAX_ROWS = 500;
    public const MAX_COLUMNS = 50;
    public const MAX_SHEETS = 10;
    public const MAX_TEXT_BYTES = 200 * 1024;

    public function wantsPreview(Request $request): bool
    {
        return in_array($request->query('preview'), ['1', 'raw'], true);
    }

    public function kind(string $name): string
    {
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        return match (true) {
            $extension === 'pdf' => 'pdf',
            isset(self::IMAGE[$extension]) => 'image',
            in_array($extension, self::SPREADSHEET, true) => 'spreadsheet',
            isset(self::WORD[$extension]) => 'word',
            in_array($extension, self::TEXT, true) => 'text',
            isset(self::VIDEO[$extension]) => 'video',
            isset(self::AUDIO[$extension]) => 'audio',
            default => 'unsupported',
        };
    }

    /**
     * Preview a file stored on a filesystem disk.
     * $allowDownload = false renders a view-only preview: no download links and no caching.
     */
    public function respond(Request $request, string $disk, string $path, ?string $name = null, bool $allowDownload = true): Response
    {
        $storage = Storage::disk($disk);
        abort_unless($storage->exists($path), 404);

        $temporary = null;

        try {
            $absolute = $storage->path($path);
        } catch (Throwable) {
            $absolute = null;
        }

        if (! $absolute || ! is_file($absolute)) {
            $temporary = tempnam(sys_get_temp_dir(), 'preview');
            file_put_contents($temporary, $storage->get($path));
            $absolute = $temporary;
        }

        try {
            return $this->respondForPath($request, $absolute, $name ?: basename($path), null, $allowDownload);
        } finally {
            if ($temporary) {
                @unlink($temporary);
            }
        }
    }

    /** Preview a file at an absolute local path (e.g. a freshly generated export). */
    public function respondForPath(Request $request, string $absolute, string $name, ?string $downloadUrl = null, bool $allowDownload = true): Response
    {
        $kind = $this->kind($name);
        $size = (int) @filesize($absolute);
        $downloadUrl = $allowDownload ? ($downloadUrl ?? $request->fullUrlWithoutQuery(['preview', 'embed'])) : null;

        if ($request->query('preview') === 'raw') {
            return $this->raw($absolute, $name, $kind, $allowDownload);
        }

        $data = [
            'name' => $name,
            'kind' => $kind,
            'size' => $size,
            'downloadUrl' => $downloadUrl,
            'rawUrl' => $request->fullUrlWithQuery(['preview' => 'raw']),
            'tooLarge' => $size > self::MAX_BYTES && ! in_array($kind, ['video', 'audio'], true),
            'sheets' => [],
            'text' => null,
            'truncated' => false,
            'error' => null,
            'embedded' => $request->boolean('embed'),
            'allowDownload' => $allowDownload,
            'mediaType' => self::VIDEO[strtolower(pathinfo($name, PATHINFO_EXTENSION))]
                ?? self::AUDIO[strtolower(pathinfo($name, PATHINFO_EXTENSION))]
                ?? null,
        ];

        if (! $data['tooLarge']) {
            try {
                if ($kind === 'spreadsheet') {
                    [$data['sheets'], $data['truncated']] = $this->sheets($absolute);
                } elseif ($kind === 'text') {
                    $data['text'] = (string) file_get_contents($absolute, false, null, 0, self::MAX_TEXT_BYTES);
                    $data['truncated'] = $size > self::MAX_TEXT_BYTES;
                }
            } catch (Throwable $e) {
                report($e);
                $data['error'] = $allowDownload
                    ? 'This file could not be read for preview. Download it to open it.'
                    : 'This file could not be read for preview.';
            }
        }

        return response()
            ->view('files.preview', $data)
            ->header('X-Content-Type-Options', 'nosniff')
            ->header('Cache-Control', $allowDownload ? 'private, max-age=0, must-revalidate' : 'private, no-store, max-age=0');
    }

    private function raw(string $absolute, string $name, string $kind, bool $allowDownload = true): Response
    {
        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => $allowDownload ? 'private, max-age=300' : 'private, no-store, max-age=0',
        ];
        $inlineName = str_replace(['"', '/', '\\'], '', $name);
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        if ($kind === 'video' || $kind === 'audio') {
            // BinaryFileResponse honours Range requests so media can be streamed and seeked.
            return response()->file($absolute, $headers + [
                'Content-Type' => self::VIDEO[$extension] ?? self::AUDIO[$extension],
                'Content-Disposition' => 'inline; filename="'.$inlineName.'"',
            ]);
        }

        abort_if((int) @filesize($absolute) > self::MAX_BYTES, 413, 'File is too large to preview.');

        if ($kind === 'pdf') {
            return response((string) file_get_contents($absolute), 200, $headers + [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$inlineName.'"',
            ]);
        }

        if ($kind === 'image') {
            return response((string) file_get_contents($absolute), 200, $headers + [
                'Content-Type' => self::IMAGE[$extension],
                'Content-Disposition' => 'inline; filename="'.$inlineName.'"',
                'Content-Security-Policy' => "default-src 'none'; sandbox",
            ]);
        }

        if ($kind === 'word') {
            return response()->file($this->wordToPdf($absolute, $name), $headers + [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.pathinfo($inlineName, PATHINFO_FILENAME).'.pdf"',
            ]);
        }

        abort(404);
    }

    /** Convert a Word/ODT/RTF document to PDF via PhpWord + Dompdf, cached by content hash. */
    public function wordToPdf(string $absolute, string $name): string
    {
        $directory = storage_path('app/previews');
        File::ensureDirectoryExists($directory);

        $target = $directory.'/'.sha1_file($absolute).'.pdf';

        if (is_file($target)) {
            return $target;
        }

        $reader = self::WORD[strtolower(pathinfo($name, PATHINFO_EXTENSION))];

        WordSettings::setPdfRendererName(WordSettings::PDF_RENDERER_DOMPDF);
        WordSettings::setPdfRendererPath(base_path('vendor/dompdf/dompdf'));

        $document = WordIOFactory::load($absolute, $reader);
        WordIOFactory::createWriter($document, 'PDF')->save($target);

        return $target;
    }

    /** @return array{0: array<int, array{title: string, rows: array}>, 1: bool} */
    private function sheets(string $absolute): array
    {
        $reader = SpreadsheetIOFactory::createReaderForFile($absolute);
        $reader->setReadDataOnly(true);

        if (method_exists($reader, 'setReadEmptyCells')) {
            $reader->setReadEmptyCells(false);
        }

        $reader->setReadFilter(new class(self::MAX_ROWS + 1, self::MAX_COLUMNS) implements IReadFilter {
            public function __construct(private int $rows, private int $columns) {}

            public function readCell($columnAddress, $row, $worksheetName = ''): bool
            {
                return $row <= $this->rows
                    && \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($columnAddress) <= $this->columns;
            }
        });

        $spreadsheet = $reader->load($absolute);
        $sheets = [];
        $truncated = $spreadsheet->getSheetCount() > self::MAX_SHEETS;

        foreach (array_slice($spreadsheet->getAllSheets(), 0, self::MAX_SHEETS) as $sheet) {
            $highestRow = $sheet->getHighestDataRow();
            $highestColumn = $sheet->getHighestDataColumn();

            $truncated = $truncated
                || $highestRow > self::MAX_ROWS
                || \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn) > self::MAX_COLUMNS;

            $lastRow = min($highestRow, self::MAX_ROWS);
            $lastColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(
                min(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn), self::MAX_COLUMNS)
            );
            $range = 'A1:'.$lastColumn.$lastRow;

            try {
                $rows = $sheet->rangeToArray($range, null, true, true, false);
            } catch (Throwable) {
                $rows = $sheet->rangeToArray($range, null, false, true, false);
            }

            $sheets[] = ['title' => $sheet->getTitle(), 'rows' => $rows];
        }

        $spreadsheet->disconnectWorksheets();

        return [$sheets, $truncated];
    }
}
