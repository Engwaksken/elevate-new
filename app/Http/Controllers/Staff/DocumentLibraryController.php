<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\DocumentFile;
use App\Models\DocumentFileVersion;
use App\Models\DocumentFolder;
use App\Models\DocumentShare;
use App\Models\Employee;
use App\Models\User;
use App\Services\DocumentAccessService;
use App\Services\DocumentLibraryService;
use App\Services\UserNotificationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Department document libraries ("Documents"), modelled on SharePoint:
 * department sites → folders → files with version history, and sharing at
 * site, folder or file level with Can view / Can edit / Full control.
 */
class DocumentLibraryController extends Controller
{
    /** Per-file upload limit in KB (50 MB). */
    public const MAX_UPLOAD_KB = 51200;

    /** Never stored: things a web server might execute or a browser might run. */
    private const BLOCKED_EXTENSIONS = ['php', 'php3', 'php4', 'php5', 'phtml', 'phar', 'exe', 'msi', 'bat', 'cmd', 'com', 'sh', 'ps1', 'vbs', 'js', 'jar', 'html', 'htm', 'svg', 'xhtml', 'htaccess', 'dll', 'scr'];

    private const DISK = 'local';

    public function __construct(private UserNotificationService $notifications, private DocumentLibraryService $library)
    {
    }

    /* ------------------------------------------------------------------ */
    /* Pages                                                              */
    /* ------------------------------------------------------------------ */

    /** Home: the department sites you can open, recent files and what's shared with you. */
    public function index(Request $request): View
    {
        $user = $request->user();
        $sites = $this->access()->accessibleSites($user);
        $siteIds = $sites->pluck('id');

        $counts = DocumentFile::whereIn('department_id', $siteIds)
            ->selectRaw('department_id, count(*) as files, coalesce(sum(size),0) as bytes, max(updated_at) as last_change')
            ->groupBy('department_id')->get()->keyBy('department_id');

        $recent = DocumentFile::with(['department:id,name', 'folder:id,name', 'editor:id,name'])
            ->whereIn('department_id', $siteIds)
            ->latest('updated_at')->limit(10)->get();

        return view('staff.documents.index', [
            'sites' => $sites,
            'counts' => $counts,
            'recent' => $recent,
            'sharedCount' => $this->access()->sharedWithMe($user)->count(),
            'mySiteIds' => $this->memberSiteIds($user),
        ]);
    }

    public function shared(Request $request): View
    {
        $user = $request->user();

        return view('staff.documents.shared', [
            'items' => $this->access()->sharedWithMe($user),
            'sites' => $this->access()->accessibleSites($user),
        ]);
    }

    /** A department's document library at the root or inside a folder. */
    public function library(Request $request, Department $department): View
    {
        $user = $request->user();
        $folder = $request->filled('folder')
            ? DocumentFolder::where('department_id', $department->id)->findOrFail($request->integer('folder'))
            : null;

        $role = $this->access()->locationRole($user, $department, $folder);
        abort_unless($role, 403, 'You do not have access to this location.');

        $view = $request->get('view') === 'tiles' ? 'tiles' : 'list';
        $sort = in_array($request->get('sort'), ['name', 'modified', 'size'], true) ? $request->get('sort') : 'name';
        $dir = $request->get('dir') === 'desc' ? 'desc' : 'asc';
        $search = trim((string) $request->get('q'));

        if ($search !== '') {
            // Search the current folder and everything below it.
            $scope = $folder ? $folder->descendantIds() : null;
            $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $search).'%';
            $folders = DocumentFolder::where('department_id', $department->id)
                ->when($scope, fn ($q) => $q->whereIn('parent_id', $scope))
                ->where('name', 'like', $like)->with(['editor:id,name', 'parent:id,name'])->get();
            $files = DocumentFile::where('department_id', $department->id)
                ->when($scope, fn ($q) => $q->whereIn('folder_id', $scope))
                ->where('name', 'like', $like)->with(['editor:id,name', 'folder:id,name'])->get();
        } else {
            $folders = DocumentFolder::where('department_id', $department->id)->where('parent_id', $folder?->id)
                ->with('editor:id,name')->withCount(['children', 'files'])->get();
            $files = DocumentFile::where('department_id', $department->id)->where('folder_id', $folder?->id)
                ->with('editor:id,name')->get();
        }

        $folders = $this->sortItems($folders, $sort === 'size' ? 'name' : $sort, $sort === 'size' ? 'asc' : $dir);
        $files = $this->sortItems($files, $sort, $dir);

        // Role per row (a child can carry a stronger direct share) and whether it's shared.
        $sharedKeys = DocumentShare::where(function ($q) use ($folders, $files) {
            $q->where(fn ($q) => $q->where('shareable_type', DocumentShare::FOLDER)->whereIn('shareable_id', $folders->pluck('id')->all() ?: [0]))
                ->orWhere(fn ($q) => $q->where('shareable_type', DocumentShare::FILE)->whereIn('shareable_id', $files->pluck('id')->all() ?: [0]));
        })->get(['shareable_type', 'shareable_id'])->map(fn ($s) => $s->shareable_type.':'.$s->shareable_id)->flip();

        foreach ($folders as $f) {
            $f->setAttribute('row_role', $search !== '' ? $this->access()->folderRole($user, $f) : $this->access()->childRole($user, $role, DocumentShare::FOLDER, $f->id));
            $f->setAttribute('is_shared', $sharedKeys->has(DocumentShare::FOLDER.':'.$f->id));
        }
        foreach ($files as $f) {
            $f->setAttribute('row_role', $search !== '' ? $this->access()->fileRole($user, $f) : $this->access()->childRole($user, $role, DocumentShare::FILE, $f->id));
            $f->setAttribute('is_shared', $sharedKeys->has(DocumentShare::FILE.':'.$f->id));
        }

        // Breadcrumb: crumbs above the user's reach are shown but not linked.
        $crumbs = [];
        $siteRole = $this->access()->siteRole($user, $department);
        $crumbs[] = ['label' => 'Documents', 'folder' => null, 'linked' => (bool) $siteRole];
        if ($folder) {
            foreach ($folder->ancestry() as $node) {
                $crumbs[] = ['label' => $node->name, 'folder' => $node->id, 'linked' => (bool) $this->access()->folderRole($user, $node)];
            }
        }

        $panel = $this->panelData($request, $user, $department);

        return view('staff.documents.library', [
            'department' => $department,
            'folder' => $folder,
            'role' => $role,
            'siteRole' => $siteRole,
            'folders' => $folders,
            'files' => $files,
            'crumbs' => $crumbs,
            'view' => $view,
            'sort' => $sort,
            'dir' => $dir,
            'search' => $search,
            'panel' => $panel,
            'sites' => $this->access()->accessibleSites($user),
            'memberCount' => Employee::where('department_id', $department->id)->count(),
            'maxUploadMb' => (int) (self::MAX_UPLOAD_KB / 1024),
            'moveTargets' => $this->moveTargets($user, $department, $siteRole),
        ]);
    }

    /* ------------------------------------------------------------------ */
    /* Folders & uploads                                                  */
    /* ------------------------------------------------------------------ */

    public function storeFolder(Request $request, Department $department): RedirectResponse
    {
        $parent = $this->parentFolder($request, $department);
        $this->authorizeRole($this->access()->locationRole($request->user(), $department, $parent), DocumentShare::EDIT);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', 'not_regex:/[\\\\\/:*?"<>|]/'],
            'description' => ['nullable', 'string', 'max:500'],
        ], ['name.not_regex' => 'A folder name cannot contain \\ / : * ? " < > |']);

        $name = trim($data['name']);
        if ($this->nameTaken(DocumentFolder::class, $department, $parent?->id, $name)) {
            throw ValidationException::withMessages(['name' => "A folder named \"{$name}\" already exists here."]);
        }

        DocumentFolder::create([
            'department_id' => $department->id,
            'parent_id' => $parent?->id,
            'name' => $name,
            'description' => $data['description'] ?? null,
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return $this->backToLibrary($department, $parent)->with('success', "Folder \"{$name}\" created.");
    }

    /**
     * Upload one or more files. A file with the same name as one already in
     * the folder becomes its next version, as SharePoint does.
     */
    public function upload(Request $request, Department $department): RedirectResponse
    {
        $parent = $this->parentFolder($request, $department);
        $user = $request->user();
        $this->authorizeRole($this->access()->locationRole($user, $department, $parent), DocumentShare::EDIT);

        $request->validate([
            'files' => ['required', 'array', 'max:20'],
            'files.*' => ['required', 'file', 'max:'.self::MAX_UPLOAD_KB],
        ], [
            'files.required' => 'Choose at least one file to upload.',
            'files.*.max' => 'Each file must be '.(self::MAX_UPLOAD_KB / 1024).' MB or smaller.',
            'files.*.uploaded' => 'A file failed to upload. It may be larger than the server allows.',
        ]);

        // Check every name first so a blocked file doesn't leave a half-done batch.
        $uploads = array_map(fn (UploadedFile $u) => [$u, $this->cleanFileName($u)], $request->file('files'));

        $added = $versioned = 0;
        foreach ($uploads as [$upload, $name]) {
            $existing = DocumentFile::where('department_id', $department->id)->where('folder_id', $parent?->id)
                ->whereRaw('lower(name) = ?', [mb_strtolower($name)])->first();

            if ($existing) {
                $this->authorizeRole($this->access()->fileRole($user, $existing), DocumentShare::EDIT);
                $this->addVersion($existing, $upload, $user, null);
                $versioned++;
                continue;
            }

            DB::transaction(function () use ($department, $parent, $upload, $name, $user) {
                $path = $this->store($upload, $department);
                $file = DocumentFile::create([
                    'department_id' => $department->id,
                    'folder_id' => $parent?->id,
                    'name' => $name,
                    'extension' => strtolower(pathinfo($name, PATHINFO_EXTENSION)) ?: null,
                    'mime_type' => $upload->getClientMimeType(),
                    'size' => $upload->getSize(),
                    'path' => $path,
                    'version' => 1,
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ]);
                $file->versions()->create([
                    'version' => 1, 'path' => $path, 'size' => $file->size,
                    'mime_type' => $file->mime_type, 'uploaded_by' => $user->id,
                ]);
            });
            $added++;
        }

        $this->touchFolders($parent, $user);

        $parts = array_filter([
            $added ? $added.' '.Str::plural('file', $added).' uploaded' : null,
            $versioned ? $versioned.' existing '.Str::plural('file', $versioned).' updated to a new version' : null,
        ]);

        return $this->backToLibrary($department, $parent)->with('success', ucfirst(implode(', ', $parts)).'.');
    }

    public function rename(Request $request, string $type, int $id): RedirectResponse
    {
        [$item, $role] = $this->resolveItem($request->user(), $type, $id);
        $this->authorizeRole($role, DocumentShare::EDIT);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:180', 'not_regex:/[\\\\\/:*?"<>|]/'],
        ], ['name.not_regex' => 'A name cannot contain \\ / : * ? " < > |']);
        $name = trim($data['name']);

        if ($item instanceof DocumentFile) {
            // Keep the extension so the file still opens in the right app.
            $ext = $item->extension;
            if ($ext && ! str_ends_with(strtolower($name), '.'.$ext)) $name .= '.'.$ext;
            $parentId = $item->folder_id;
        } else {
            $parentId = $item->parent_id;
        }

        if ($this->nameTaken($item::class, $item->department, $parentId, $name, $item->id)) {
            throw ValidationException::withMessages(['name' => "\"{$name}\" already exists here."]);
        }

        $item->update(['name' => $name, 'updated_by' => $request->user()->id]);

        return back()->with('success', 'Renamed to "'.$name.'".');
    }

    /** Send a file or folder (with everything inside) to the site's recycle bin. */
    public function destroy(Request $request, string $type, int $id): RedirectResponse
    {
        [$item, $role] = $this->resolveItem($request->user(), $type, $id);
        $this->authorizeRole($role, DocumentShare::FULL);

        $department = $item->department;
        $parent = $item instanceof DocumentFile ? $item->folder : $item->parent;
        $name = $item->name;

        $this->library->trash($item, $request->user());

        $message = "\"{$name}\" moved to the recycle bin.";
        // A parent the user can no longer see would 403; fall back to the root.
        $target = $parent && $this->access()->folderRole($request->user(), $parent) ? $parent : null;
        if (! $target && ! $this->access()->siteRole($request->user(), $department)) {
            return redirect()->route('staff.documents.shared')->with('success', $message);
        }

        return $this->backToLibrary($department, $target)->with('success', $message);
    }

    /**
     * Act on several selected items at once: download as a zip, move to a
     * folder or send to the recycle bin. Items come as "folder:12" / "file:7".
     */
    public function bulk(Request $request, Department $department): Response
    {
        $user = $request->user();
        $data = $request->validate([
            'action' => ['required', Rule::in(['download', 'move', 'delete'])],
            'items' => ['required', 'array', 'min:1', 'max:200'],
            'items.*' => ['string', 'regex:/^(folder|file):\d+$/'],
            'destination' => ['nullable', 'string', 'regex:/^(root|\d+)$/'],
        ], ['items.required' => 'Select at least one item.']);

        $needed = ['download' => DocumentShare::VIEW, 'move' => DocumentShare::EDIT, 'delete' => DocumentShare::FULL][$data['action']];
        $items = collect($data['items'])->unique()->map(function (string $key) use ($user, $department, $needed) {
            [$type, $id] = explode(':', $key);
            [$item, $role] = $this->resolveItem($user, $type, (int) $id);
            abort_unless((int) $item->department_id === $department->id, 404);
            $this->authorizeRole($role, $needed);

            return $item;
        });
        // Drop items inside a selected folder; the folder already covers them.
        $inside = $items->filter(fn ($i) => $i instanceof DocumentFolder)
            ->flatMap(fn ($f) => array_slice($f->descendantIds(), 1))->flip();
        $items = $items->reject(fn ($i) => $i instanceof DocumentFolder ? $inside->has($i->id) : $inside->has($i->folder_id) || $items->contains(fn ($o) => $o instanceof DocumentFolder && $o->id === $i->folder_id))->values();

        if ($data['action'] === 'download') {
            if ($items->count() === 1 && $items->first() instanceof DocumentFile) {
                return $this->download($request, $items->first());
            }
            $label = $items->count() === 1 ? $items->first()->name : $department->name.' files';
            $zip = $this->library->zip($items, $label);

            return response()->download($zip, Str::slug($label).'.zip')->deleteFileAfterSend();
        }

        if ($data['action'] === 'move') {
            $destination = ($data['destination'] ?? 'root') === 'root' ? null
                : DocumentFolder::where('department_id', $department->id)->findOrFail((int) $data['destination']);
            $this->authorizeRole($this->access()->locationRole($user, $department, $destination), DocumentShare::EDIT);
            $this->library->move($items, $department->id, $destination, $user);
            $this->touchFolders($destination, $user);

            return $this->backToLibrary($department, $destination)
                ->with('success', $items->count().' '.Str::plural('item', $items->count()).' moved to '.($destination?->name ?? 'Documents').'.');
        }

        foreach ($items as $item) $this->library->trash($item, $user);

        return back()->with('success', $items->count().' '.Str::plural('item', $items->count()).' moved to the recycle bin.');
    }

    /* ------------------------------------------------------------------ */
    /* Recycle bin                                                        */
    /* ------------------------------------------------------------------ */

    public function recycleBin(Request $request, Department $department): View
    {
        $user = $request->user();
        $siteRole = $this->access()->siteRole($user, $department);
        $isOwner = DocumentAccessService::allows($siteRole, DocumentShare::FULL);

        $scope = fn ($q) => $q->where('department_id', $department->id)->where('is_delete_root', true)
            ->when(! $isOwner, fn ($q) => $q->where('deleted_by', $user->id));
        $folders = DocumentFolder::onlyTrashed()->where($scope)->with(['deleter:id,name', 'parent' => fn ($q) => $q->withTrashed()])->get();
        $files = DocumentFile::onlyTrashed()->where($scope)->with(['deleter:id,name', 'folder' => fn ($q) => $q->withTrashed()])->get();
        abort_unless($siteRole || $folders->isNotEmpty() || $files->isNotEmpty(), 403, 'You do not have access to this recycle bin.');

        $batches = $folders->pluck('delete_batch')->merge($files->pluck('delete_batch'))->filter()->values()->all();
        $batchFiles = DocumentFile::onlyTrashed()->whereIn('delete_batch', $batches ?: [''])
            ->selectRaw('delete_batch, count(*) as n, coalesce(sum(size),0) as bytes')->groupBy('delete_batch')->get()->keyBy('delete_batch');

        $items = $folders->map(fn ($f) => (object) ['type' => 'folder', 'item' => $f, 'location' => $f->parent?->name])
            ->concat($files->map(fn ($f) => (object) ['type' => 'file', 'item' => $f, 'location' => $f->folder?->name]))
            ->each(function ($row) use ($batchFiles) {
                $b = $batchFiles->get($row->item->delete_batch);
                $row->files = (int) ($b->n ?? 0);
                $row->bytes = (int) ($b->bytes ?? 0);
                $row->days_left = max(0, DocumentFolder::RETENTION_DAYS - (int) $row->item->deleted_at->diffInDays(now()));
            })
            ->sortByDesc(fn ($r) => $r->item->deleted_at->timestamp)->values();

        return view('staff.documents.recycle-bin', [
            'department' => $department,
            'items' => $items,
            'isOwner' => $isOwner,
            'siteRole' => $siteRole,
            'sites' => $this->access()->accessibleSites($user),
        ]);
    }

    public function restore(Request $request, string $type, int $id): RedirectResponse
    {
        $item = $this->trashedRoot($request->user(), $type, $id);
        $this->library->restore($item, $request->user());

        return back()->with('success', '"'.$item->fresh()->name.'" restored.');
    }

    public function purge(Request $request, string $type, int $id): RedirectResponse
    {
        $item = $this->trashedRoot($request->user(), $type, $id);
        $name = $item->name;
        $this->library->purge($item);

        return back()->with('success', "\"{$name}\" permanently deleted.");
    }

    public function emptyRecycleBin(Request $request, Department $department): RedirectResponse
    {
        $this->authorizeRole($this->access()->siteRole($request->user(), $department), DocumentShare::FULL);

        $count = 0;
        foreach ([DocumentFile::class, DocumentFolder::class] as $class) {
            $class::onlyTrashed()->where('department_id', $department->id)->where('is_delete_root', true)->get()
                ->each(function ($item) use (&$count) { $this->library->purge($item); $count++; });
        }

        return back()->with('success', $count ? "Recycle bin emptied ({$count} ".Str::plural('item', $count).').' : 'The recycle bin was already empty.');
    }

    /** A recycle-bin item the user may restore or purge: site owners, or whoever deleted it. */
    private function trashedRoot(User $user, string $type, int $id): DocumentFolder|DocumentFile
    {
        $class = $type === 'folder' ? DocumentFolder::class : DocumentFile::class;
        $item = $class::onlyTrashed()->where('is_delete_root', true)->findOrFail($id);
        $isOwner = DocumentAccessService::allows($this->access()->siteRole($user, $item->department), DocumentShare::FULL);
        abort_unless($isOwner || (int) $item->deleted_by === $user->id, 403, 'Only site owners or the person who deleted it can do that.');

        return $item;
    }

    /**
     * Folders the user may move items into, as an indented list (root first).
     * Roles are worked out from the in-memory tree instead of a query per folder.
     *
     * @return array<int, array{id: ?int, name: string, depth: int}>
     */
    private function moveTargets(User $user, Department $department, ?string $siteRole): array
    {
        $folders = DocumentFolder::where('department_id', $department->id)->orderBy('name')->get(['id', 'parent_id', 'name']);
        $byParent = $folders->groupBy(fn ($f) => (string) $f->parent_id);
        $targets = [];
        if (DocumentAccessService::allows($siteRole, DocumentShare::EDIT)) {
            $targets[] = ['id' => null, 'name' => 'Documents', 'depth' => 0];
        }

        $walk = function (string $parentKey, int $depth, ?string $inherited) use (&$walk, &$targets, $byParent, $user) {
            foreach ($byParent->get($parentKey, collect()) as $folder) {
                $role = DocumentAccessService::strongest($inherited, $this->access()->directShare($user, DocumentShare::FOLDER, $folder->id));
                if (DocumentAccessService::allows($role, DocumentShare::EDIT)) {
                    $targets[] = ['id' => $folder->id, 'name' => $folder->name, 'depth' => $depth];
                }
                if ($depth < 30) $walk((string) $folder->id, $depth + 1, $role);
            }
        };
        $walk('', 1, $siteRole);

        return $targets;
    }

    /* ------------------------------------------------------------------ */
    /* Files & versions                                                   */
    /* ------------------------------------------------------------------ */

    public function open(Request $request, DocumentFile $file): Response
    {
        $this->authorizeRole($this->access()->fileRole($request->user(), $file), DocumentShare::VIEW);
        abort_unless(Storage::disk(self::DISK)->exists($file->path), 404);

        if (! $file->canOpenInline()) {
            return Storage::disk(self::DISK)->download($file->path, $file->name);
        }

        return Storage::disk(self::DISK)->response($file->path, $file->name, [
            'Content-Type' => $this->inlineMime($file->extension),
            'X-Content-Type-Options' => 'nosniff',
        ], 'inline');
    }

    public function download(Request $request, DocumentFile $file): Response
    {
        $this->authorizeRole($this->access()->fileRole($request->user(), $file), DocumentShare::VIEW);
        abort_unless(Storage::disk(self::DISK)->exists($file->path), 404);

        return Storage::disk(self::DISK)->download($file->path, $file->name);
    }

    public function uploadVersion(Request $request, DocumentFile $file): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeRole($this->access()->fileRole($user, $file), DocumentShare::EDIT);

        $data = $request->validate([
            'file' => ['required', 'file', 'max:'.self::MAX_UPLOAD_KB],
            'comment' => ['nullable', 'string', 'max:255'],
        ]);
        $this->cleanFileName($request->file('file')); // rejects blocked types

        $this->addVersion($file, $request->file('file'), $user, $data['comment'] ?? null);
        $this->touchFolders($file->folder, $user);

        return back()->with('success', "\"{$file->name}\" updated to version {$file->version}.");
    }

    public function downloadVersion(Request $request, DocumentFileVersion $version): Response
    {
        $file = $version->file;
        $this->authorizeRole($this->access()->fileRole($request->user(), $file), DocumentShare::VIEW);
        abort_unless(Storage::disk(self::DISK)->exists($version->path), 404);

        $base = pathinfo($file->name, PATHINFO_FILENAME);
        $ext = $file->extension ? '.'.$file->extension : '';

        return Storage::disk(self::DISK)->download($version->path, "{$base} (v{$version->version}){$ext}");
    }

    /** Make an older version current again by copying it forward as a new version. */
    public function restoreVersion(Request $request, DocumentFileVersion $version): RedirectResponse
    {
        $file = $version->file;
        $user = $request->user();
        $this->authorizeRole($this->access()->fileRole($user, $file), DocumentShare::EDIT);
        abort_unless(Storage::disk(self::DISK)->exists($version->path), 404);

        DB::transaction(function () use ($file, $version, $user) {
            $path = 'documents/'.$file->department_id.'/'.Str::uuid().($file->extension ? '.'.$file->extension : '');
            Storage::disk(self::DISK)->copy($version->path, $path);
            $next = $file->version + 1;
            $file->versions()->create([
                'version' => $next, 'path' => $path, 'size' => $version->size, 'mime_type' => $version->mime_type,
                'comment' => 'Restored from version '.$version->version, 'uploaded_by' => $user->id,
            ]);
            $file->update(['path' => $path, 'size' => $version->size, 'mime_type' => $version->mime_type, 'version' => $next, 'updated_by' => $user->id]);
        });

        return back()->with('success', "Version {$version->version} restored as version {$file->version}.");
    }

    /* ------------------------------------------------------------------ */
    /* Sharing                                                            */
    /* ------------------------------------------------------------------ */

    public function share(Request $request, string $type, int $id): RedirectResponse
    {
        $actor = $request->user();
        [$item, $role] = $this->resolveItem($actor, $type, $id);
        $this->authorizeRole($role, DocumentShare::FULL);

        $data = $request->validate([
            'user_ids' => ['required', 'array', 'min:1', 'max:50'],
            'user_ids.*' => ['integer', Rule::exists('users', 'id')->where('user_type', 'staff')],
            'role' => ['required', Rule::in(array_keys(DocumentShare::RANKS))],
            'message' => ['nullable', 'string', 'max:500'],
            'notify' => ['nullable', 'boolean'],
        ], ['user_ids.required' => 'Choose at least one person to share with.']);

        $shareType = $this->shareType($type);
        $users = User::whereIn('id', $data['user_ids'])->where('id', '!=', $actor->id)->get();

        foreach ($users as $person) {
            DocumentShare::updateOrCreate(
                ['shareable_type' => $shareType, 'shareable_id' => $item->id, 'user_id' => $person->id],
                ['role' => $data['role'], 'granted_by' => $actor->id]
            );
        }

        if ($request->boolean('notify', true) && $users->isNotEmpty()) {
            $label = $item instanceof Department ? $item->name.' documents' : $item->name;
            $what = match ($shareType) { DocumentShare::SITE => 'site', DocumentShare::FOLDER => 'folder', default => 'file' };
            $message = $actor->name.' shared the '.$what.' "'.$label.'" with you ('.mb_strtolower(DocumentShare::LABELS[$data['role']]).').'
                .(! empty($data['message']) ? ' Message: '.$data['message'] : '');
            $this->notifications->sendToManySafely($users, 'document_share', 'Shared with you: '.$label, $message, $this->itemUrl($item));
        }

        return back()->with('success', 'Shared with '.$users->pluck('name')->join(', ', ' and ').'.');
    }

    public function updateShare(Request $request, DocumentShare $share): RedirectResponse
    {
        $this->authorizeShare($request->user(), $share);
        $data = $request->validate(['role' => ['required', Rule::in(array_keys(DocumentShare::RANKS))]]);
        $share->update(['role' => $data['role'], 'granted_by' => $request->user()->id]);

        return back()->with('success', $share->user?->name.' now '.mb_strtolower(DocumentShare::LABELS[$data['role']]).'.');
    }

    public function removeShare(Request $request, DocumentShare $share): RedirectResponse
    {
        $this->authorizeShare($request->user(), $share);
        $name = $share->user?->name;
        $share->delete();

        return back()->with('success', "Access removed for {$name}.");
    }

    /* ------------------------------------------------------------------ */
    /* Helpers                                                            */
    /* ------------------------------------------------------------------ */

    /**
     * One access service per request: the router reuses controller instances,
     * so a constructor-injected service would keep stale cached grants.
     */
    private function access(): DocumentAccessService
    {
        $attributes = request()->attributes;
        if (! $attributes->has('document_access')) {
            $attributes->set('document_access', new DocumentAccessService());
        }

        return $attributes->get('document_access');
    }

    /** Details pane on the right of the library: Manage access or Version history. */
    private function panelData(Request $request, User $user, Department $department): ?array
    {
        $panel = $request->get('panel');
        if (! in_array($panel, ['access', 'versions', 'details'], true)) return null;

        $itemKey = (string) $request->get('item', 'site:'.$department->id);
        [$type, $id] = array_pad(explode(':', $itemKey, 2), 2, 0);
        try {
            [$item, $role] = $this->resolveItem($user, $type, (int) $id);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException) {
            return null;
        }
        if ((int) ($item instanceof Department ? $item->id : $item->department_id) !== $department->id) return null;
        if ($panel === 'versions' && ! $item instanceof DocumentFile) $panel = 'details';

        $data = ['type' => $panel, 'item' => $item, 'itemType' => $type, 'itemKey' => $itemKey, 'role' => $role];

        if ($panel === 'versions') {
            $data['versions'] = $item->versions()->with('uploader:id,name')->get();
        }

        if ($panel === 'access' || $panel === 'details') {
            $data += $this->accessList($item, $department);
            if ($panel === 'access' && DocumentAccessService::allows($role, DocumentShare::FULL)) {
                $data['staff'] = User::where('user_type', 'staff')->where('status', 'active')
                    ->where('id', '!=', $user->id)->orderBy('name')->get(['id', 'name', 'email']);
            }
        }

        return $data;
    }

    /** Everyone with access to an item: owners, members, inherited and direct shares. */
    private function accessList(Model $item, Department $department): array
    {
        $direct = collect();
        $inherited = collect();
        $shareType = $item instanceof Department ? DocumentShare::SITE : ($item instanceof DocumentFolder ? DocumentShare::FOLDER : DocumentShare::FILE);

        $direct = DocumentShare::with(['user:id,name,email', 'grantor:id,name'])
            ->where('shareable_type', $shareType)->where('shareable_id', $item->id)->get()
            ->sortBy(fn ($s) => [-DocumentAccessService::rank($s->role), $s->user?->name])->values();

        if (! $item instanceof Department) {
            // Shares on the site and on folders above carry down to this item.
            $folders = $item instanceof DocumentFolder
                ? array_slice($item->ancestry(), 0, -1)
                : ($item->folder ? $item->folder->ancestry() : []);
            $sources = [[DocumentShare::SITE, $department->id, 'the whole site']];
            foreach ($folders as $f) $sources[] = [DocumentShare::FOLDER, $f->id, $f->name];

            foreach ($sources as [$t, $sid, $label]) {
                foreach (DocumentShare::with('user:id,name,email')->where('shareable_type', $t)->where('shareable_id', $sid)->get() as $s) {
                    $s->setAttribute('source_label', $label);
                    $s->setAttribute('source_folder', $t === DocumentShare::FOLDER ? $sid : null);
                    $inherited->push($s);
                }
            }
        }

        $members = User::whereIn('id', Employee::where('department_id', $department->id)->pluck('user_id'))
            ->where('status', 'active')->orderBy('name')->get(['id', 'name', 'email']);

        return [
            'owner' => $department->head,
            'members' => $members,
            'direct' => $direct,
            'inherited' => $inherited,
        ];
    }

    /** @return array{0: Model, 1: ?string} the item and the user's role on it */
    private function resolveItem(User $user, string $type, int $id): array
    {
        $result = match ($type) {
            'site' => [$d = Department::findOrFail($id), $this->access()->siteRole($user, $d)],
            'folder' => [$f = DocumentFolder::with('department')->findOrFail($id), $this->access()->folderRole($user, $f)],
            'file' => [$f = DocumentFile::with(['department', 'folder'])->findOrFail($id), $this->access()->fileRole($user, $f)],
            default => abort(404),
        };
        abort_unless($result[1], 403, 'You do not have access to this item.');

        return $result;
    }

    private function shareType(string $type): string
    {
        return match ($type) { 'site' => DocumentShare::SITE, 'folder' => DocumentShare::FOLDER, default => DocumentShare::FILE };
    }

    private function authorizeShare(User $user, DocumentShare $share): void
    {
        $type = match ($share->shareable_type) { DocumentShare::SITE => 'site', DocumentShare::FOLDER => 'folder', default => 'file' };
        [, $role] = $this->resolveItem($user, $type, $share->shareable_id);
        $this->authorizeRole($role, DocumentShare::FULL);
    }

    private function authorizeRole(?string $role, string $needed): void
    {
        $messages = [
            DocumentShare::VIEW => 'You do not have access to this item.',
            DocumentShare::EDIT => 'You can only view here. Ask an owner for edit access.',
            DocumentShare::FULL => 'Only people with full control can do that.',
        ];
        abort_unless(DocumentAccessService::allows($role, $needed), 403, $messages[$needed]);
    }

    private function parentFolder(Request $request, Department $department): ?DocumentFolder
    {
        return $request->filled('folder_id')
            ? DocumentFolder::where('department_id', $department->id)->findOrFail($request->integer('folder_id'))
            : null;
    }

    private function nameTaken(string $class, Department $department, ?int $parentId, string $name, ?int $ignoreId = null): bool
    {
        $column = $class === DocumentFolder::class ? 'parent_id' : 'folder_id';

        return $class::where('department_id', $department->id)->where($column, $parentId)
            ->whereRaw('lower(name) = ?', [mb_strtolower($name)])
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();
    }

    private function cleanFileName(UploadedFile $upload): string
    {
        $name = trim(str_replace(['\\', '/', ':', '*', '?', '"', '<', '>', '|'], '-', $upload->getClientOriginalName()));
        $name = Str::limit($name !== '' ? $name : 'file', 180, '');
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        if (in_array($ext, self::BLOCKED_EXTENSIONS, true) || str_starts_with($name, '.')) {
            throw ValidationException::withMessages(['files' => "\"{$name}\" can't be uploaded: .{$ext} files are blocked for security."]);
        }

        return $name;
    }

    private function store(UploadedFile $upload, Department $department): string
    {
        $ext = strtolower($upload->getClientOriginalExtension());
        $path = $upload->storeAs('documents/'.$department->id, Str::uuid().($ext ? '.'.$ext : ''), self::DISK);
        if (! $path) {
            throw ValidationException::withMessages(['files' => 'The file could not be saved. Please try again.']);
        }

        return $path;
    }

    private function addVersion(DocumentFile $file, UploadedFile $upload, User $user, ?string $comment): void
    {
        DB::transaction(function () use ($file, $upload, $user, $comment) {
            $path = $this->store($upload, $file->department);
            $next = $file->version + 1;
            $file->versions()->create([
                'version' => $next, 'path' => $path, 'size' => $upload->getSize(),
                'mime_type' => $upload->getClientMimeType(), 'comment' => $comment, 'uploaded_by' => $user->id,
            ]);
            $file->update([
                'path' => $path, 'size' => $upload->getSize(), 'mime_type' => $upload->getClientMimeType(),
                'version' => $next, 'updated_by' => $user->id,
            ]);
        });
    }

    /** Bump "Modified" on the folder chain, as SharePoint does when content changes. */
    private function touchFolders(?DocumentFolder $folder, User $user): void
    {
        if (! $folder) return;
        $ids = array_map(fn ($f) => $f->id, $folder->ancestry());
        DocumentFolder::whereIn('id', $ids)->update(['updated_at' => now(), 'updated_by' => $user->id]);
    }

    private function sortItems(Collection $items, string $sort, string $dir): Collection
    {
        $sorted = match ($sort) {
            'modified' => $items->sortBy(fn ($i) => $i->updated_at?->timestamp ?? 0),
            'size' => $items->sortBy(fn ($i) => $i->size ?? 0),
            default => $items->sortBy(fn ($i) => mb_strtolower($i->name), SORT_NATURAL),
        };

        return ($dir === 'desc' ? $sorted->reverse() : $sorted)->values();
    }

    private function inlineMime(?string $ext): string
    {
        return match (strtolower((string) $ext)) {
            'pdf' => 'application/pdf',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'text/plain; charset=UTF-8',
        };
    }

    private function backToLibrary(Department $department, ?DocumentFolder $folder): RedirectResponse
    {
        return redirect()->route('staff.documents.library', array_filter(['department' => $department->id, 'folder' => $folder?->id]));
    }

    private function itemUrl(Model $item): string
    {
        return match (true) {
            $item instanceof Department => route('staff.documents.library', $item),
            $item instanceof DocumentFolder => route('staff.documents.library', ['department' => $item->department_id, 'folder' => $item->id]),
            default => route('staff.documents.open', $item),
        };
    }

    /** Sites the user belongs to as member or head (for the "Your department" badge). */
    private function memberSiteIds(User $user): array
    {
        return Employee::where('user_id', $user->id)->whereNotNull('department_id')->pluck('department_id')
            ->merge(Department::where('head_user_id', $user->id)->pluck('id'))->unique()->all();
    }
}
