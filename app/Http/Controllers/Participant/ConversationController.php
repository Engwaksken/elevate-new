<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Models\ParticipantConversation;
use App\Models\ParticipantMessage;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class ConversationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $conversations = ParticipantConversation::query()
            ->whereHas('members', fn ($query) => $query->where('users.id', $user->id))
            ->with(['members:id,name', 'latestMessage.sender:id,name'])
            ->withCount('messages')
            ->orderByDesc('updated_at')
            ->paginate(20);

        return view('participant.chat.index', [
            'conversations' => $conversations,
            'participants' => User::query()->where('user_type', 'participant')->where('status', 'active')
                ->whereKeyNot($user->id)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:direct,group'],
            'participant_id' => ['required_if:type,direct', 'nullable', 'integer'],
            'participant_ids' => ['required_if:type,group', 'array', 'min:1', 'max:50'],
            'participant_ids.*' => ['integer', 'distinct'],
            'title' => ['required_if:type,group', 'nullable', 'string', 'max:120'],
        ]);

        $user = $request->user();
        $ids = collect($data['type'] === 'direct' ? [$data['participant_id']] : ($data['participant_ids'] ?? []))
            ->map(fn ($id) => (int) $id)->filter()->unique()->values();
        abort_if($ids->contains($user->id), 422, 'You cannot invite yourself.');

        $validIds = User::query()->where('user_type', 'participant')->where('status', 'active')
            ->whereIn('id', $ids)->pluck('id');
        if ($validIds->count() !== $ids->count() || ($data['type'] === 'direct' && $ids->count() !== 1)) {
            return back()->withErrors(['participant_id' => 'Choose active participant accounts only.'])->withInput();
        }

        if ($data['type'] === 'direct') {
            $conversation = ParticipantConversation::query()
                ->where('type', 'direct')
                ->whereHas('members', fn ($query) => $query->where('users.id', $user->id))
                ->whereHas('members', fn ($query) => $query->where('users.id', $ids->first()))
                ->whereDoesntHave('members', fn ($query) => $query->whereNotIn('users.id', [$user->id, $ids->first()]))
                ->first();

            if ($conversation) {
                return redirect()->route('participant.chat.show', $conversation);
            }
        }

        $conversation = ParticipantConversation::create([
            'type' => $data['type'],
            'title' => $data['type'] === 'group' ? trim($data['title']) : null,
            'created_by' => $user->id,
        ]);
        $conversation->members()->attach([$user->id, ...$validIds->all()], ['joined_at' => now()]);

        return redirect()->route('participant.chat.show', $conversation)->with('success', 'Conversation created.');
    }

    public function show(Request $request, ParticipantConversation $conversation): View
    {
        $this->authorizeMember($request, $conversation);
        $conversation->load(['members:id,name', 'messages' => fn ($query) => $query->with('sender:id,name')->latest('id')->limit(300)]);
        $conversation->setRelation('messages', $conversation->messages->reverse()->values());

        return view('participant.chat.show', [
            'conversation' => $conversation,
            'participants' => $conversation->type === 'group'
                ? User::query()->where('user_type', 'participant')->where('status', 'active')
                    ->whereNotIn('id', $conversation->members->pluck('id'))->orderBy('name')->get(['id', 'name'])
                : collect(),
        ]);
    }

    public function send(Request $request, ParticipantConversation $conversation): RedirectResponse
    {
        $this->authorizeMember($request, $conversation);
        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);
        $conversation->messages()->create(['sender_id' => $request->user()->id, 'body' => trim($data['body'])]);
        $conversation->touch();

        return redirect()->to(route('participant.chat.show', $conversation).'#latest');
    }

    public function updateMessage(Request $request, ParticipantConversation $conversation, ParticipantMessage $message): RedirectResponse
    {
        $this->authorizeMember($request, $conversation);
        abort_unless($message->conversation_id === $conversation->id && $message->sender_id === $request->user()->id, 403);
        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);
        $message->update(['body' => trim($data['body'])]);

        return redirect()->to(route('participant.chat.show', $conversation).'#latest')->with('success', 'Message updated.');
    }

    public function destroyMessage(Request $request, ParticipantConversation $conversation, ParticipantMessage $message): RedirectResponse
    {
        $this->authorizeMember($request, $conversation);
        abort_unless($message->conversation_id === $conversation->id && $message->sender_id === $request->user()->id, 403);
        $message->delete();

        return redirect()->to(route('participant.chat.show', $conversation).'#latest')->with('success', 'Message deleted.');
    }

    public function messages(Request $request, ParticipantConversation $conversation): JsonResponse
    {
        $this->authorizeMember($request, $conversation);
        $after = max(0, (int) $request->query('after', 0));
        $messages = $conversation->messages()->where('id', '>', $after)
            ->with('sender:id,name')->orderBy('id')->limit(100)->get()
            ->map(fn ($message) => [
                'id' => $message->id,
                'sender_id' => $message->sender_id,
                'sender' => $message->sender?->name,
                'body' => $message->body,
                'created_at' => $message->created_at?->format('d M Y · H:i'),
            ]);

        return response()->json(['messages' => $messages]);
    }

    public function invite(Request $request, ParticipantConversation $conversation): RedirectResponse
    {
        $this->authorizeMember($request, $conversation);
        abort_unless($conversation->type === 'group', 404);
        $data = $request->validate(['participant_ids' => ['required', 'array', 'min:1', 'max:50'], 'participant_ids.*' => ['integer', 'distinct']]);
        $ids = collect($data['participant_ids'])->map(fn ($id) => (int) $id)->unique();
        abort_if($ids->contains($request->user()->id), 422, 'You cannot invite yourself.');
        $already = $conversation->members()->pluck('users.id');
        abort_if($ids->intersect($already)->isNotEmpty(), 422, 'One or more selected participants are already in this group.');
        $valid = User::query()->where('user_type', 'participant')->where('status', 'active')->whereIn('id', $ids)->pluck('id');
        if ($valid->count() !== $ids->count()) {
            return back()->withErrors(['participant_ids' => 'Choose active participants only.']);
        }
        $conversation->members()->attach($valid->mapWithKeys(fn ($id) => [$id => ['joined_at' => now()]]));

        return back()->with('success', 'Participant(s) added to the group.');
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
        $conversation->delete();

        return redirect()->route('participant.chat.index')->with('success', 'Conversation deleted.');
    }

    private function authorizeMember(Request $request, ParticipantConversation $conversation): void
    {
        abort_unless($conversation->members()->where('users.id', $request->user()->id)->exists(), 403);
    }
}
