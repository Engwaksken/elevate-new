<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Models\ParticipantConversation;
use App\Models\ParticipantMessage;
use App\Models\ParticipantMessageAttachment;
use App\Models\User;
use App\Services\ChatDirectory;
use App\Services\UserNotificationService;
use App\Support\ChatPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Chat for participants (/participant/chat) and staff (/admin/chat).
 *
 * Both portals share one set of conversations; who may be messaged or added
 * to a group comes from ChatDirectory (staff ↔ staff, instructors ↔ their
 * course participants, participants ↔ participants and their instructors).
 * Any message may carry file attachments, readable only by members.
 */
class ConversationController extends Controller
{
    public const DISK = 'local';
    public const MAX_FILES = 5;
    public const MAX_FILE_KB = 10240;
    public const ALLOWED_EXTENSIONS = 'jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,rtf,odt,ods,odp,zip,mp3,m4a,wav,mp4,mov';

    public function __construct(
        private readonly ChatDirectory $directory,
        private readonly UserNotificationService $notifications,
    ) {
    }

    public function index(Request $request): View
    {
        return view('participant.chat.index', $this->shared($request) + [
            'conversations' => $this->conversationList($request)->paginate(30),
        ]);
    }

    public function people(Request $request): JsonResponse
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'group' => ['nullable', 'string', 'max:40'],
            'exclude' => ['nullable', 'array', 'max:500'],
            'exclude.*' => ['integer'],
        ]);

        return response()->json([
            'people' => $this->directory->search($request->user(), $data['q'] ?? null, $data['group'] ?? null, $data['exclude'] ?? [])->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:direct,group'],
            'participant_id' => ['required_if:type,direct', 'nullable', 'integer'],
            'participant_ids' => ['required_if:type,group', 'array', 'min:1', 'max:200'],
            'participant_ids.*' => ['integer', 'distinct'],
            'title' => ['required_if:type,group', 'nullable', 'string', 'max:120'],
        ]);

        $user = $request->user();
        $ids = collect($data['type'] === 'direct' ? [$data['participant_id']] : ($data['participant_ids'] ?? []))
            ->map(fn ($id) => (int) $id)->filter()->unique()->values();
        abort_if($ids->contains($user->id), 422, 'You cannot invite yourself.');

        if (($data['type'] === 'direct' && $ids->count() !== 1) || ! $this->directory->allows($user, $ids)) {
            return back()->withErrors(['participant_id' => 'Choose only active people you are allowed to message.'])->withInput();
        }

        if ($data['type'] === 'direct') {
            $conversation = ParticipantConversation::query()
                ->where('type', 'direct')
                ->whereHas('members', fn ($query) => $query->where('users.id', $user->id))
                ->whereHas('members', fn ($query) => $query->where('users.id', $ids->first()))
                ->whereDoesntHave('members', fn ($query) => $query->whereNotIn('users.id', [$user->id, $ids->first()]))
                ->first();

            if ($conversation) {
                return redirect()->route($this->routeBase($request).'.show', $conversation);
            }
        }

        $conversation = ParticipantConversation::create([
            'type' => $data['type'],
            'title' => $data['type'] === 'group' ? trim($data['title']) : null,
            'created_by' => $user->id,
        ]);
        $conversation->members()->attach([$user->id, ...$ids->all()], ['joined_at' => now()]);
        if ($conversation->type === 'group') {
            $this->notifyAdded($request, $conversation, $ids);
        }

        return redirect()->route($this->routeBase($request).'.show', $conversation)->with('success', 'Conversation created.');
    }

    public function show(Request $request, ParticipantConversation $conversation): View
    {
        $this->authorizeMember($request, $conversation);
        $conversation->load(['members:id,name,user_type', 'messages' => fn ($query) => $query->with(['sender:id,name', 'attachments'])->latest('id')->limit(300)]);
        $conversation->setRelation('messages', $conversation->messages->reverse()->values());

        return view('participant.chat.show', $this->shared($request) + [
            'conversation' => $conversation,
            'conversations' => $this->conversationList($request)->limit(50)->get(),
        ]);
    }

    public function send(Request $request, ParticipantConversation $conversation): RedirectResponse|JsonResponse
    {
        $this->authorizeMember($request, $conversation);
        $data = $request->validate([
            'body' => ['nullable', 'required_without:attachments', 'string', 'max:5000'],
            'attachments' => ['nullable', 'array', 'max:'.self::MAX_FILES],
            'attachments.*' => ['file', 'max:'.self::MAX_FILE_KB, 'mimes:'.self::ALLOWED_EXTENSIONS],
        ], [
            'body.required_without' => 'Write a message or attach a file.',
            'attachments.max' => 'Attach at most '.self::MAX_FILES.' files to one message.',
            'attachments.*.max' => 'Each file must be 10 MB or smaller.',
            'attachments.*.mimes' => 'That file type is not allowed in chat.',
        ]);

        $message = $conversation->messages()->create(['sender_id' => $request->user()->id, 'body' => trim((string) ($data['body'] ?? ''))]);
        foreach ($request->file('attachments', []) as $file) {
            $this->storeAttachment($conversation, $message, $file);
        }
        $conversation->touch();

        if ($request->wantsJson()) {
            return response()->json(['message' => $this->messagePayload($request, $conversation, $message->load(['sender:id,name', 'attachments']))], 201);
        }

        return redirect()->to(route($this->routeBase($request).'.show', $conversation).'#latest');
    }

    public function updateMessage(Request $request, ParticipantConversation $conversation, ParticipantMessage $message): RedirectResponse|JsonResponse
    {
        $this->authorizeOwnMessage($request, $conversation, $message);
        $hasFiles = $message->attachments()->exists();
        $data = $request->validate(['body' => [$hasFiles ? 'nullable' : 'required', 'string', 'max:5000']]);
        $message->update(['body' => trim((string) ($data['body'] ?? ''))]);

        if ($request->wantsJson()) {
            return response()->json(['message' => $this->messagePayload($request, $conversation, $message->load(['sender:id,name', 'attachments']))]);
        }

        return redirect()->to(route($this->routeBase($request).'.show', $conversation).'#latest')->with('success', 'Message updated.');
    }

    public function destroyMessage(Request $request, ParticipantConversation $conversation, ParticipantMessage $message): RedirectResponse|JsonResponse
    {
        $this->authorizeOwnMessage($request, $conversation, $message);
        $this->deleteFiles($message->attachments()->pluck('path'));
        $message->delete();

        if ($request->wantsJson()) {
            return response()->json(['deleted' => $message->id]);
        }

        return redirect()->to(route($this->routeBase($request).'.show', $conversation).'#latest')->with('success', 'Message deleted.');
    }

    public function messages(Request $request, ParticipantConversation $conversation): JsonResponse
    {
        $this->authorizeMember($request, $conversation);
        $after = max(0, (int) $request->query('after', 0));
        $messages = $conversation->messages()->where('id', '>', $after)
            ->with(['sender:id,name', 'attachments'])->orderBy('id')->limit(100)->get()
            ->map(fn ($message) => $this->messagePayload($request, $conversation, $message));

        return response()->json(['messages' => $messages]);
    }

    public function attachment(Request $request, ParticipantConversation $conversation, ParticipantMessageAttachment $attachment): Response
    {
        $this->authorizeMember($request, $conversation);
        abort_unless((int) $attachment->message?->conversation_id === (int) $conversation->id, 404);
        abort_unless(Storage::disk(self::DISK)->exists($attachment->path), 404);

        $headers = ['X-Content-Type-Options' => 'nosniff'];
        if ($request->boolean('inline') && $attachment->isImage()) {
            return Storage::disk(self::DISK)->response($attachment->path, $attachment->original_name, $headers + ['Content-Type' => $attachment->mime_type], 'inline');
        }

        return Storage::disk(self::DISK)->download($attachment->path, $attachment->original_name, $headers);
    }

    public function invite(Request $request, ParticipantConversation $conversation): RedirectResponse
    {
        $this->authorizeMember($request, $conversation);
        abort_unless($conversation->type === 'group', 404);
        $data = $request->validate(['participant_ids' => ['required', 'array', 'min:1', 'max:200'], 'participant_ids.*' => ['integer', 'distinct']]);
        $ids = collect($data['participant_ids'])->map(fn ($id) => (int) $id)->unique();
        abort_if($ids->contains($request->user()->id), 422, 'You cannot invite yourself.');
        $already = $conversation->members()->pluck('users.id');
        abort_if($ids->intersect($already)->isNotEmpty(), 422, 'One or more selected people are already in this group.');
        if (! $this->directory->allows($request->user(), $ids)) {
            return back()->withErrors(['participant_ids' => 'Choose only active people you are allowed to message.']);
        }
        $conversation->members()->attach($ids->mapWithKeys(fn ($id) => [$id => ['joined_at' => now()]])->all());
        $conversation->touch();
        $this->notifyAdded($request, $conversation, $ids);

        return back()->with('success', $ids->count() === 1 ? 'Person added to the group.' : $ids->count().' people added to the group.');
    }

    public function leave(Request $request, ParticipantConversation $conversation): RedirectResponse
    {
        $this->authorizeMember($request, $conversation);
        abort_unless($conversation->type === 'group' && (int) $conversation->created_by !== (int) $request->user()->id, 403);
        $conversation->members()->detach($request->user()->id);

        return redirect()->route($this->routeBase($request).'.index')->with('success', 'You left the group.');
    }

    public function updateConversation(Request $request, ParticipantConversation $conversation): RedirectResponse
    {
        $this->authorizeMember($request, $conversation);
        abort_unless($conversation->type === 'group' && (int) $conversation->created_by === (int) $request->user()->id, 403);
        $data = $request->validate(['title' => ['required', 'string', 'max:120']]);
        $conversation->update(['title' => trim($data['title'])]);

        return back()->with('success', 'Group conversation updated.');
    }

    public function destroyConversation(Request $request, ParticipantConversation $conversation): RedirectResponse
    {
        $this->authorizeMember($request, $conversation);
        abort_unless((int) $conversation->created_by === (int) $request->user()->id, 403);
        $this->deleteFiles(ParticipantMessageAttachment::query()
            ->whereHas('message', fn ($query) => $query->where('conversation_id', $conversation->id))
            ->pluck('path'));
        $conversation->delete();

        return redirect()->route($this->routeBase($request).'.index')->with('success', 'Conversation deleted.');
    }

    /**
     * View data both chat pages need: which portal we are in, and the picker setup.
     */
    private function shared(Request $request): array
    {
        $base = $this->routeBase($request);

        return [
            'chatRoute' => $base,
            'chatLayout' => $base === 'admin.chat' ? 'layouts.admin' : 'layouts.app',
            'chatGroups' => $this->directory->groups($request->user()),
            'chatAccept' => collect(explode(',', self::ALLOWED_EXTENSIONS))->map(fn ($ext) => '.'.$ext)->join(','),
            'chatMaxFiles' => self::MAX_FILES,
        ];
    }

    private function routeBase(Request $request): string
    {
        return str_starts_with((string) $request->route()?->getName(), 'admin.') ? 'admin.chat' : 'participant.chat';
    }

    /**
     * The viewer's conversations, most recent activity first, for the chat sidebar.
     */
    private function conversationList(Request $request)
    {
        return ParticipantConversation::query()
            ->whereHas('members', fn ($query) => $query->where('users.id', $request->user()->id))
            ->with(['members:id,name', 'latestMessage.sender:id,name'])
            ->withCount('messages')
            ->orderByDesc('updated_at');
    }

    private function storeAttachment(ParticipantConversation $conversation, ParticipantMessage $message, UploadedFile $file): void
    {
        $name = Str::limit(trim(str_replace(['/', '\\', "\0"], '-', $file->getClientOriginalName())) ?: 'file', 250, '');

        $message->attachments()->create([
            'path' => $file->store('chat/'.$conversation->id, self::DISK),
            'original_name' => $name,
            'mime_type' => $file->getMimeType(),
            'size' => (int) $file->getSize(),
        ]);
    }

    /**
     * In-app notice (no email) to people added to a group, linking to the
     * chat in their own portal.
     */
    private function notifyAdded(Request $request, ParticipantConversation $conversation, Collection $ids): void
    {
        $actor = $request->user();
        User::query()->whereIn('id', $ids)->get()->each(function (User $member) use ($actor, $conversation) {
            $route = ChatDirectory::isStaffSide($member) ? 'admin.chat.show' : 'participant.chat.show';
            try {
                $this->notifications->send($member, 'chat_group_added', 'Added to '.$conversation->title,
                    $actor->name.' added you to the group chat "'.$conversation->title.'".',
                    route($route, $conversation), ['conversation_id' => $conversation->id, 'added_by' => $actor->id]);
            } catch (\Throwable $e) {
                report($e);
            }
        });
    }

    private function deleteFiles(Collection $paths): void
    {
        $paths = $paths->filter()->values()->all();
        if ($paths !== []) {
            Storage::disk(self::DISK)->delete($paths);
        }
    }

    /**
     * JSON shape used by the live thread (polling and inline send/edit).
     */
    private function messagePayload(Request $request, ParticipantConversation $conversation, ParticipantMessage $message): array
    {
        $base = $this->routeBase($request);
        $mine = (int) $message->sender_id === (int) $request->user()->id;

        return [
            'id' => $message->id,
            'sender_id' => $message->sender_id,
            'sender' => $message->sender?->name,
            'color' => ChatPresenter::color('u'.$message->sender_id),
            'body' => $message->body,
            'mine' => $mine,
            'edited' => $message->updated_at && $message->created_at && $message->updated_at->gt($message->created_at),
            'ts' => $message->created_at?->getTimestamp(),
            'day' => $message->created_at?->toDateString(),
            'day_label' => $message->created_at ? ChatPresenter::dayLabel($message->created_at) : null,
            'time' => $message->created_at?->format('H:i'),
            'created_at' => $message->created_at?->format('d M Y · H:i'),
            'attachments' => $message->attachments->map(fn (ParticipantMessageAttachment $file) => [
                'id' => $file->id,
                'name' => $file->original_name,
                'size' => $file->sizeLabel(),
                'is_image' => $file->isImage(),
                'url' => route($base.'.attachments.show', [$conversation, $file]),
                'preview_url' => $file->isImage() ? route($base.'.attachments.show', [$conversation, $file, 'inline' => 1]) : null,
            ])->values(),
            'update_url' => $mine ? route($base.'.messages.update', [$conversation, $message]) : null,
            'delete_url' => $mine ? route($base.'.messages.destroy', [$conversation, $message]) : null,
        ];
    }

    private function authorizeMember(Request $request, ParticipantConversation $conversation): void
    {
        abort_unless($conversation->members()->where('users.id', $request->user()->id)->exists(), 403);
    }

    private function authorizeOwnMessage(Request $request, ParticipantConversation $conversation, ParticipantMessage $message): void
    {
        $this->authorizeMember($request, $conversation);
        abort_unless((int) $message->conversation_id === (int) $conversation->id && (int) $message->sender_id === (int) $request->user()->id, 403);
    }
}
