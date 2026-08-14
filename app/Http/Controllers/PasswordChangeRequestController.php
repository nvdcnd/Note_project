<?php

namespace App\Http\Controllers;

use App\Mail\Password_change;
use App\Models\PasswordChangeRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class PasswordChangeRequestController extends Controller
{
    public const MAX_ATTEMPTS = 5;

    public function one_time_password_generator($length = 6)
    {
        // Passkey chỉ được so với hash của chính yêu cầu đó (tra theo id trên
        // URL) lúc đổi mật khẩu, nên không cần unique toàn cục. Vòng quét
        // Hash::check mọi yêu cầu chưa dùng trước đây chậm dần theo dữ liệu.
        return (string) random_int(10 ** ($length - 1), (10 ** $length) - 1);
    }

    public function forgot_password_view(Request $request)
    {
        return view('password.forgot');
    }

    public function forgot_password(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::query()->where('email', $validated['email'])->first();

        // Generic message in both cases to avoid user enumeration (E19).
        if (! $user) {
            return redirect()->route('password.forgot')->with('status', 'If that email exists, a reset link has been sent');
        }

        $passkey = $this->one_time_password_generator();
        $password_change_request = new PasswordChangeRequest;
        $password_change_request->user_id = $user->id;
        $password_change_request->token = Hash::make($passkey);
        $password_change_request->expires_at = now()->addMinutes(10);
        $password_change_request->attempts = 0;
        $password_change_request->save();

        // Không ai ngồi chờ response này (thông báo chung chống dò email), nên
        // mail đi qua queue để hưởng retry thay vì chặn request bằng SMTP.
        Mail::to($user->email)->queue(new Password_change($passkey));

        return redirect()->route('password.forgot')->with('status', 'If that email exists, a reset link has been sent');
    }

    public function change_password_view(Request $request, $id)
    {
        $change_password_request = PasswordChangeRequest::query()->find($id);
        if (! $change_password_request || $change_password_request->used) {
            return redirect()->route('password.forgot')->with('error', 'Liên kết đặt lại mật khẩu không hợp lệ hoặc đã được dùng.');
        }

        return view('password.reset', [
            'change_password_request' => $change_password_request,
        ]);
    }

    public function change_password(Request $request, $id)
    {
        $validated = $request->validate([
            'passkey' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        /** @var PasswordChangeRequest|null $change_password_request */
        $change_password_request = PasswordChangeRequest::query()->find($id);

        if (! $change_password_request || $change_password_request->used) {
            return redirect()->route('password.forgot')->with('error', 'Yêu cầu đặt lại mật khẩu không hợp lệ.');
        }

        if ($change_password_request->attempts >= self::MAX_ATTEMPTS) {
            $change_password_request->delete();

            return redirect()->route('password.forgot')->with('error', 'Bạn đã thử quá nhiều lần. Vui lòng yêu cầu liên kết đặt lại mới.');
        }

        $user = User::query()->find($change_password_request->user_id);
        if (! $user) {
            $change_password_request->delete();

            return redirect()->route('password.forgot')->with('error', 'Không tìm thấy người dùng.');
        }

        if (now()->greaterThan($change_password_request->expires_at)) {
            $change_password_request->delete();

            return redirect()->route('password.forgot')->with('error', 'Liên kết đặt lại mật khẩu đã hết hạn. Vui lòng yêu cầu liên kết mới.');
        }

        if (! Hash::check($validated['passkey'], $change_password_request->token)) {
            // Keep the request alive so the user can retry (BE-26 / E18).
            PasswordChangeRequest::whereKey($change_password_request->id)->increment('attempts');
            $change_password_request->refresh();

            return redirect()->route('password.reset.view', $change_password_request->id)
                ->with('error', 'Mã OTP không đúng. '.($change_password_request->attempts).' lần nhập sai.');
        }

        $user->password = Hash::make($validated['password']);
        $user->save();
        $change_password_request->used = true;
        $change_password_request->save();

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('home')->with('success', 'Đã đổi mật khẩu.');
    }
}
