<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\Note;
use App\Models\Organization;
use App\Models\OrganizationsMember;
use App\Models\PivotForNote;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Nhận lời mời bằng link trong email và tạo tài khoản.
 *
 * Đây là mảnh còn thiếu của luồng "chia sẻ cho người chưa đăng ký": trước đây
 * chỉ có email được gửi đi mà không có link nào, và các method signup40acc_*
 * thì không có route.
 */
class InvitationController extends Controller
{
    /**
     * Hiện form đăng ký ứng với lời mời.
     */
    public function show(Request $request, string $token)
    {
        $invitation = Invitation::findByToken($token);

        if (! $invitation || ! $invitation->isPending()) {
            return redirect()->route('login')
                ->with('error', 'Lời mời không hợp lệ hoặc đã hết hạn.');
        }

        // Email đã có tài khoản: không tạo tài khoản mới, mời họ đăng nhập.
        if (User::query()->where('email', $invitation->email)->exists()) {
            return redirect()->route('login')
                ->with('warning', 'Email này đã có tài khoản. Hãy đăng nhập để xem nội dung được chia sẻ.');
        }

        return view('invitations.accept', [
            'invitation' => $invitation,
            'token' => $token,
            'targetLabel' => $this->describeTarget($invitation),
        ]);
    }

    /**
     * Tạo tài khoản từ lời mời rồi gắn người dùng vào note / tổ chức tương ứng.
     */
    public function accept(Request $request, string $token)
    {
        $invitation = Invitation::findByToken($token);

        if (! $invitation || ! $invitation->isPending()) {
            return redirect()->route('login')
                ->with('error', 'Lời mời không hợp lệ hoặc đã hết hạn.');
        }

        if (User::query()->where('email', $invitation->email)->exists()) {
            return redirect()->route('login')
                ->with('warning', 'Email này đã có tài khoản. Hãy đăng nhập để xem nội dung được chia sẻ.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = DB::transaction(function () use ($invitation, $validated) {
            $user = User::create([
                'name' => $validated['name'],
                // Email lấy từ lời mời, KHÔNG lấy từ form — người dùng không thể
                // đổi sang email khác để chiếm quyền truy cập nội dung.
                'email' => $invitation->email,
                'password' => $validated['password'],
            ]);

            // Nhận luôn mọi lời mời khác đang chờ cùng email này, để người dùng
            // không phải mở lại từng email một.
            foreach (Invitation::pendingIdsFor($invitation->email) as $pendingId) {
                $item = Invitation::findById($pendingId);
                if ($item === null) {
                    continue;
                }

                $this->grantAccess($item, $user);
                $item->markAccepted();
            }

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return $this->redirectToTarget($invitation)
            ->with('success', 'Tài khoản đã được tạo. Chào mừng bạn đến với Noteket!');
    }

    /**
     * Gắn người dùng vừa tạo vào đối tượng được mời.
     */
    private function grantAccess(Invitation $invitation, User $user): void
    {
        $target = $invitation->invitable;

        if ($target instanceof Note) {
            $alreadyShared = PivotForNote::query()
                ->where('note_id', $target->id)
                ->where('shared_with', $user->id)
                ->exists();

            if (! $alreadyShared) {
                PivotForNote::create([
                    'note_id' => $target->id,
                    'shared_with' => $user->id,
                ]);
            }

            return;
        }

        if ($target instanceof Organization) {
            $alreadyMember = OrganizationsMember::query()
                ->where('organizationID', $target->id)
                ->where('userID', $user->id)
                ->exists();

            if (! $alreadyMember) {
                // status = true: bấm vào link mời rồi đăng ký chính là hành động
                // chấp nhận, không cần bắt xác nhận thêm một lần nữa.
                OrganizationsMember::create([
                    'organizationID' => $target->id,
                    'userID' => $user->id,
                    'status' => true,
                ]);
            }
        }
    }

    private function redirectToTarget(Invitation $invitation)
    {
        $target = $invitation->invitable;

        if ($target instanceof Note) {
            return redirect()->route('note', $target->id);
        }

        if ($target instanceof Organization) {
            return redirect()->route('organization', $target->id);
        }

        return redirect()->route('home');
    }

    private function describeTarget(Invitation $invitation): string
    {
        $target = $invitation->invitable;

        if ($target instanceof Note) {
            return 'ghi chú "'.$target->title.'"';
        }

        if ($target instanceof Organization) {
            return 'tổ chức "'.$target->name.'"';
        }

        return 'Noteket';
    }
}
