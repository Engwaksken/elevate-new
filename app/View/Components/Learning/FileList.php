<?php

namespace App\View\Components\Learning;

use App\Services\Learning\LearningFileService;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * Lists lesson / assignment / submission files with View, Download (only
 * when the viewer may download) and optional Remove buttons.
 *
 * <x-learning.file-list :files="$lesson->files" :delete-url="fn ($f) => route(...)" />
 */
class FileList extends Component
{
    /** @var array<int,array> */
    public array $items;

    public function __construct(
        iterable $files = [],
        public ?Closure $deleteUrl = null,
        ?bool $canDownload = null,
        public string $empty = '',
        public bool $compact = false,
        public string $title = '',
    ) {
        $service = app(LearningFileService::class);
        $viewer = auth()->user();

        $this->items = collect($files)
            ->filter(fn ($file) => $file && $file->existsOnDisk())
            ->map(function ($file) use ($service, $viewer, $canDownload) {
                $item = $service->present($file, $viewer, $canDownload);
                $item['delete_url'] = $this->deleteUrl ? ($this->deleteUrl)($file) : null;
                $item['icon'] = self::icon($item['kind'], $item['extension']);

                return $item;
            })
            ->values()
            ->all();
    }

    public static function icon(string $kind, string $extension): string
    {
        return match (true) {
            $kind === 'pdf' => 'fa-file-pdf',
            $kind === 'image' => 'fa-file-image',
            $kind === 'video' => 'fa-file-video',
            $kind === 'audio' => 'fa-file-audio',
            $kind === 'spreadsheet' => 'fa-file-excel',
            $kind === 'word' || in_array($extension, ['doc', 'docx'], true) => 'fa-file-word',
            in_array($extension, ['ppt', 'pptx'], true) => 'fa-file-powerpoint',
            in_array($extension, ['zip', 'rar', '7z'], true) => 'fa-file-zipper',
            $kind === 'text' => 'fa-file-lines',
            default => 'fa-file',
        };
    }

    public function render(): View
    {
        return view('components.learning.file-list');
    }
}
