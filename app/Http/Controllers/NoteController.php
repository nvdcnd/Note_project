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
    /** Số note hiển thị trên mỗi trang (trang chủ và trang tổ chức). */
    public const NOTES_PER_PAGE = 20;

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

        $sharedNoteIds = PivotForNote::query()
            ->where('shared_with', $userId)
            ->pluck('note_id');

        $doneNoteIds = MarkAsDone::query()
            ->where('userID', $userId)
            ->where('status', true)
            ->pluck('noteID')
            ->all();

        // Tập note người dùng được phép thấy: do mình tạo HOẶC được chia sẻ với mình.
        $query = Note::query()->where(function ($q) use ($userId, $sharedNoteIds) {
            $q->where('creater_id', $userId)->orWhereIn('id', $sharedNoteIds);
        });

        $query = match ($filter) {
            'done' => $query->whereIn('id', $doneNoteIds),
            'not-done' => $query->whereNotIn('id', $doneNoteIds),
            'by-me' => $query->where('creater_id', $userId),
            'shared' => $query->whereIn('id', $sharedNoteIds),
            default => $query,
        };

        // Note cũ đứng trước, note mới xếp sau (hàng đợi): khi note phía trên được
        // xử lý xong thì note kế tiếp trồi lên thay thế vị trí.
        // Phân trang thay cho take(20) để note vượt quá 20 không bị ẩn mất (E3).
        $notes = $query->oldest()->paginate(self::NOTES_PER_PAGE)->withQueryString();

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
            abort(403, 'Bạn không có quyền xem ghi chú này.');
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

        return redirect()->route('note', $note->id)->with('success', 'Đã tạo ghi chú.');
    }

    public function create_note_in_organization(Request $request, $organizationID)
    {
        $organization = Organization::query()->find($organizationID);
        if (! $organization) {
            return redirect()->route('organizations.index')->with('error', 'Không tìm thấy tổ chức.');
        }

        // Must be host or an accepted member to create notes (BE-23).
        $isMember = OrganizationsMember::query()
            ->where('organizationID', $organization->id)
            ->where('userID', Auth::id())
            ->where('status', true)
            ->exists();

        if (! $isMember && $organization->hostID !== Auth::id()) {
            return redirect()->route('organization', $organization->id)->with('error', 'Bạn không phải thành viên của tổ chức này.');
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

        return redirect()->route('note', $note->id)->with('success', 'Đã tạo ghi chú.');
    }

    public function delete_note(Request $request, $id)
    {
        $note = Note::query()->find($id);
        if (! $note) {
            return redirect()->route('home')->with('error', 'Không tìm thấy ghi chú.');
        }

        if ($note->creater_id !== Auth::id()) {
            return redirect()->route('note', $note->id)->with('error', 'Chỉ người tạo mới có thể xóa ghi chú này.');
        }

        DB::transaction(function () use ($note) {
            $note->shares()->delete();
            MarkAsDone::query()->where('noteID', $note->id)->delete();
            Replynote::query()->where('noteID', $note->id)->delete();
            $note->delete();
        });

        return redirect()->route('home')->with('success', 'Đã xóa ghi chú.');
    }

    public function edit_note(Request $request, $id)
    {
        $user = Auth::user();
        $note = Note::query()->find($id);

        if (! $note) {
            return redirect()->route('home')->with('error', 'Không tìm thấy ghi chú.');
        }

        $canEdit = $note->creater_id === $user->id
            || PivotForNote::query()
                ->where('note_id', $note->id)
                ->where('shared_with', $user->id)
                ->exists();

        if (! $canEdit) {
            return redirect()->route('note', $id)->with('error', 'Bạn không thể chỉnh sửa ghi chú này.');
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
        ]);

        $note->update($validated);

        return redirect()->route('note', $id)->with('success', 'Đã cập nhật ghi chú.');
    }
}
