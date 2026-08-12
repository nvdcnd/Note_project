<?php

namespace App\Http\Controllers;

use App\Mail\Mail40account;
use App\Mail\UserEmail;
use App\Models\Invitation;
use App\Models\Note;
use App\Models\PivotForNote;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class PivotForNoteController extends Controller
{
    public const MAX_RECIPIENTS_PER_REQUEST = 20;

    /**
     * Gửi lời mời cho các email chưa có tài khoản.
     *
     * Mỗi email được cấp một lời mời có token riêng, nhờ đó email chứa link
     * đăng ký thật sự dùng được — trước đây email được gửi đi mà không kèm
     * đường dẫn nào nên người nhận không có cách nào vào ứng dụng.
     *
     * @param  array<int, string>  $users
     */
    public function mail_for_no_account($users, Note $noteid)
    {
        foreach ($users as $email) {
            $issued = Invitation::issueFor($noteid, $email, Auth::id());

            Mail::to($email)->queue(new Mail40account($email, $noteid, $issued['token']));
        }
    }

    public function share_note(Request $request, $noteid)
    {
        $request->validate([
            // Mỗi email là một bản ghi invitation cộng một job mail — chặn trần
            // để một request không thể tỏa ra hàng trăm email.
            'shared_with' => ['required', 'array', 'max:'.self::MAX_RECIPIENTS_PER_REQUEST],
            'shared_with.*' => ['email'],
        ]);

        $noteModel = Note::find($noteid);
        if (! $noteModel) {
            return redirect()->route('home')->with('error', 'Không tìm thấy ghi chú.');
        }

        if ($noteModel->creater_id !== Auth::id()) {
            return redirect()->route('note', $noteModel->id)->with('error', 'Chỉ người tạo mới có thể chia sẻ ghi chú này.');
        }

        $sharedwith = collect($request->input('shared_with', []))
            ->filter(fn ($value) => is_string($value) && trim($value) !== '')
            ->map(fn ($value) => strtolower(trim($value)))
            ->unique()
            ->values()
            ->all();

        $newShares = [];
        $unregisteredEmails = [];
        $skippedExistingShares = 0;

        foreach ($sharedwith as $userEmail) {
            $userModel = User::where('email', $userEmail)->first();

            if ($userModel) {
                $alreadyShared = PivotForNote::query()
                    ->where('note_id', $noteModel->id)
                    ->where('shared_with', $userModel->id)
                    ->exists();

                if ($alreadyShared) {
                    $skippedExistingShares++;

                    continue;
                }

                PivotForNote::create([
                    'note_id' => $noteModel->id,
                    'shared_with' => $userModel->id,
                ]);
                $newShares[] = $userModel;
            } else {
                $unregisteredEmails[] = $userEmail;
            }
        }

        foreach ($newShares as $userModel) {
            Mail::to($userModel->email)->queue(new UserEmail($userModel, $noteModel));
        }

        if (count($unregisteredEmails) > 0) {
            $this->mail_for_no_account($unregisteredEmails, $noteModel);
        }

        if (count($newShares) === 0 && count($unregisteredEmails) === 0) {
            return redirect()->route('note', $noteModel->id)->with('warning', 'Bạn chưa nhập người nhận hợp lệ nào.');
        }

        $message = 'Đã chia sẻ ghi chú.';
        if ($skippedExistingShares > 0) {
            $message = 'Đã chia sẻ ghi chú. Bỏ qua '.$skippedExistingShares.' người nhận đã được chia sẻ trước đó.';
        }

        return redirect()->route('note', $noteModel->id)->with('success', $message);
    }

    public function undo_shared_note(Request $request, $id)
    {
        $pivot = PivotForNote::find($id);
        if (! $pivot) {
            return redirect()->route('home')->with('error', 'Không tìm thấy bản ghi chia sẻ.');
        }
        $note = Note::find($pivot->note_id);
        if (! $note || $note->creater_id !== Auth::user()->id) {
            return redirect()->route('home')->with('error', 'Bạn không có quyền hủy chia sẻ ghi chú này.');
        }
        $pivot->delete();

        return redirect()->route('note', $note->id)->with('success', 'Đã hủy chia sẻ ghi chú.');
    }
}
