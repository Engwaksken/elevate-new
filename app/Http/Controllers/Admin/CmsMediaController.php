<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CmsContentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Uploads images and videos for CMS sections (backgrounds, grids, logos) and
 * serves them through a route, so the public site does not depend on the
 * storage symlink existing on the host.
 */
class CmsMediaController extends Controller
{
    /** Largest upload PHP on this server accepts (min of upload_max_filesize and post_max_size), in bytes. */
    public static function serverUploadLimit(): int
    {
        $toBytes = function ($value): int {
            $value = trim((string) $value);
            if ($value === '' || $value === '0' || $value === '-1') return PHP_INT_MAX;
            $unit = strtolower(substr($value, -1));
            $n = (float) $value;
            return (int) match ($unit) { 'g' => $n * 1024 ** 3, 'm' => $n * 1024 ** 2, 'k' => $n * 1024, default => $n };
        };
        return min($toBytes(ini_get('upload_max_filesize')), $toBytes(ini_get('post_max_size')), 50 * 1024 * 1024);
    }

    public function store(Request $request, CmsContentService $cms): JsonResponse
    {
        // PHP drops files over the server limit before Laravel sees them; explain that instead of "file required".
        $limitMb = round(self::serverUploadLimit() / 1048576, 1);
        $uploaded = $_FILES['file'] ?? null;
        if (! $request->hasFile('file') && (in_array($uploaded['error'] ?? null, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)
            || ($uploaded === null && (int) $request->server('CONTENT_LENGTH') > self::serverUploadLimit()))) {
            return response()->json(['message' => "The file is larger than this server allows ({$limitMb} MB). Use a smaller file, or ask your host to raise upload_max_filesize and post_max_size."], 422);
        }
        if (($uploaded['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK && ! $request->hasFile('file')) {
            return response()->json(['message' => 'The server could not receive the file (PHP upload error '.(int) $uploaded['error'].').'], 422);
        }

        $request->validate([
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,gif,mp4,webm', 'max:51200'],
        ], ['file.max' => 'Files must be 50 MB or smaller.', 'file.mimes' => 'Upload a JPG, PNG, WEBP, GIF, MP4 or WEBM file.']);

        $file = $request->file('file');
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension());
        $ext = $ext === 'jpeg' ? 'jpg' : $ext;
        abort_unless(array_key_exists($ext, CmsContentService::MEDIA_TYPES), 422, 'Unsupported file type.');

        $isImage = str_starts_with(CmsContentService::MEDIA_TYPES[$ext], 'image/');
        if ($isImage && @getimagesize($file->getRealPath()) === false) {
            return response()->json(['message' => 'The file is not a valid image.'], 422);
        }
        if ($isImage && $file->getSize() > 8 * 1024 * 1024) {
            return response()->json(['message' => 'Images must be 8 MB or smaller.'], 422);
        }

        $path = $file->storeAs('cms/media/'.now()->format('Y/m'), Str::random(24).'.'.$ext, 'public');

        return response()->json([
            'path' => $path,
            'url' => $cms->mediaUrl($path),
            'kind' => $isImage ? 'image' : 'video',
        ]);
    }

    public function show(string $path, CmsContentService $cms)
    {
        abort_unless($cms->isMediaPath($path), 404);
        $disk = Storage::disk('public');
        abort_unless($disk->exists($path), 404);

        $mime = CmsContentService::MEDIA_TYPES[strtolower(pathinfo($path, PATHINFO_EXTENSION))];

        // BinaryFileResponse honours Range requests, which browsers need to stream video.
        return response()->file($disk->path($path), [
            'Content-Type' => $mime,
            'Cache-Control' => 'public, max-age=604800',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
