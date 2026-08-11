<?php

namespace App\Http\Controllers;

use App\Models\MarkAsDone;
use App\Models\Note;
use App\Models\Organization;
use App\Models\OrganizationsMember;
use App\Models\PivotForNote;
use App\Models\Replynote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NoteController extends Controller
{
    public function home(Request $request)
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        $userId = Auth::id();

        $noteFilters = [
            'all' => 'Tất cả ghi chú',
            'not-done' => 'Chưa hoàn thành',
            'done' => 'Hoàn tất',
            'by-me' => 'Do tôi tạo',
            'shared' => 'Được chia sẻ với tôi',
        ];

        $filter = $request->query('filter', 'all');
        if (! array_key_exists($filter, $noteFilters)) {
            $filter = 'all';
        }

        $ownNotes = Note::query()
            ->where('creater_id', $userId)
            ->latest()
            ->take(20)
            ->get();

        $sharedNotes = Note::query()
            ->whereIn('id', PivotForNote::query()->where('shared_with', $userId)->select('note_id'))
            ->latest()
            ->take(20)
            ->get();

        // Note cũ đứng trước, note mới xếp sau (hàng đợi): khi note phía trên bị
        // skip / đánh dấu hoàn thành thì note mới hơn trồi lên thay thế vị trí.
        $allNotes = $ownNotes->merge($sharedNotes)->unique('id')->sortBy('created_at')->values();

        $doneNoteIds = MarkAsDone::query()
            ->where('userID', $userId)
            ->where('status', true)
            ->pluck('noteID')
            ->all();

        $notes = match ($filter) {
            'done' => $allNotes->filter(fn ($note) => in_array($note->id, $doneNoteIds)),
            'not-done' => $allNotes->filter(fn ($note) => ! in_array($note->id, $doneNoteIds)),
            'by-me' => $ownNotes,
            'shared' => $sharedNotes,
            default => $allNotes,
        };
        $notes = $notes->sortBy('created_at')->values();

        return view('home', [
            'notes' => $notes,
            'doneNoteIds' => $doneNoteIds,
            'noteFilter' => $filter,
            'noteFilters' => $noteFilters,
        ]);
    }

    public function show(Request $request, $id)
    {
        $note = Note::query()->find($id);
        if (! $note) {
            abort(404);
        }

        // Creator can always see their own note; otherwise only shared recipients (BE-15 / E6).
        $canView = $note->creater_id === Auth::id()
            || PivotForNote::query()
                ->where('note_id', $note->id)
                ->where('shared_with', Auth::id())
                ->exists();

        if (! $canView) {
            abort(403, 'You are not authorized to view this note');
        }

        $isDone = MarkAsDone::query()
            ->where('noteID', $note->id)
            ->where('userID', Auth::id())
            ->where('status', true)
            ->exists();

        $isCreator = $note->creater_id === Auth::id();

        $replies = Replynote::query()->where('noteID', $note->id)->with('user')->latest()->get();

        $shares = PivotForNote::query()->where('note_id', $note->id)->with('user')->get();

        return view('note', compact('note', 'isDone', 'isCreator', 'replies', 'shares'));
    }

    public function create_note(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
        ]);

        $note = Note::create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'creater_id' => Auth::id(),
        ]);

        MarkAsDone::create([
            'noteID' => $note->id,
            'userID' => Auth::id(),
            'status' => false,
        ]);

        return redirect()->route('note', $note->id)->with('success', 'Note created successfully');
    }

    public function create_note_in_organization(Request $request, $organizationID)
    {
        $organization = Organization::query()->find($organizationID);
        if (! $organization) {
            return redirect()->route('organizations.index')->with('error', 'Organization not found');
        }

        // Must be host or an accepted member to create notes (BE-23).
        $isMember = OrganizationsMember::query()
            ->where('organizationID', $organization->id)
            ->where('userID', Auth::id())
            ->where('status', true)
            ->exists();

        if (! $isMember && $organization->hostID !== Auth::id()) {
            return redirect()->route('organization', $organization->id)->with('error', 'You are not a member of this organization');
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
        ]);

        $note = Note::create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'creater_id' => Auth::id(),
            'organizationID' => $organization->id,
        ]);

        MarkAsDone::create([
            'noteID' => $note->id,
            'userID' => Auth::id(),
            'status' => false,
        ]);

        return redirect()->route('note', $note->id)->with('success', 'Note created successfully');
    }

    public function delete_note(Request $request, $id)
    {
        $note = Note::query()->find($id);
        if (! $note) {
            return redirect()->route('home')->with('error', 'Note not found');
        }

        if ($note->creater_id !== Auth::id()) {
            return redirect()->route('note', $note->id)->with('error', 'Only the creator can delete this note');
        }

        DB::transaction(function () use ($note) {
            $note->shares()->delete();
            MarkAsDone::query()->where('noteID', $note->id)->delete();
            Replynote::query()->where('noteID', $note->id)->delete();
            $note->delete();
        });

        return redirect()->route('home')->with('success', 'Note deleted successfully');
    }

    public function edit_note(Request $request, $id)
    {
        $user = Auth::user();
        $note = Note::query()->find($id);

        if (! $note) {
            return redirect()->route('home')->with('error', 'Note not found');
        }

        $canEdit = $note->creater_id === $user->id
            || PivotForNote::query()
                ->where('note_id', $note->id)
                ->where('shared_with', $user->id)
                ->exists();

        if (! $canEdit) {
            return redirect()->route('note', $id)->with('error', 'You cannot edit this note');
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
        ]);

        $note->update($validated);

        return redirect()->route('note', $id)->with('success', 'Note updated successfully');
    }
}
