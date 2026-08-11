<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticationController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            return redirect()->route('home')->with('success', 'Đăng nhập thành công.');
        }

        return redirect()->route('login')->with('error', 'Email hoặc mật khẩu không đúng.');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Bạn đã đăng xuất.');
    }

    public function signup(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'unique:users,email', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->route('home')->with('success', 'Tạo tài khoản thành công.');
    }

    // Đăng ký qua lời mời được xử lý ở App\Http\Controllers\InvitationController.
    //
    // Ba method signup40acc_note / signup40acc_host_org / signup40acc_member_org
    // trước đây nằm ở đây đã được gỡ: chúng không có route, và đều đọc email từ
    // một cột không chứa email (`pivot_for_note.shared_with` là khóa ngoại tới
    // `users.id`), nên không thể chạy đúng. Cơ chế thay thế là bảng `invitations`
    // với token dùng một lần, xem InvitationController.
}
