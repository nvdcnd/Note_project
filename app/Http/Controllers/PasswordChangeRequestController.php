<?php

namespace App\Http\Controllers;

use App\Mail\Password_change;
use App\Models\Password_change_request;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class PasswordChangeRequestController extends Controller
{
    public function one_time_password_generator($length = 6)
    {
        $chars = '0123456789';
        $pass = '';
        for ($i = 0; $i < $length; $i++) {
            $pass .= $chars[rand(0, strlen($chars) - 1)];
        }
        $check = Password_change_request::where('token', $pass)->first();
        if ($check) {
            return $this->one_time_password_generator();
        } else {
            return $pass;
        }
    }

    public function forgot_password(Request $request)
    {
        $data = $request->all();
        $email = $data['email'] ?? '';
        $user = User::where('email', $email)->first();
        if ($user) {
            $passkey = $this->one_time_password_generator();
            $password_change_request = new Password_change_request;
            $password_change_request->user_id = $user->id;
            $password_change_request->token = $passkey;
            $password_change_request->expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            $password_change_request->save();
            Mail::to($email)->send(new Password_change($passkey));

            return redirect('login')->with('success', 'Password reset token sent to your email');
        } else {
            return redirect('login')->with('error', 'User not found');
        }
    }

    public function change_password(Request $request)
    {
        $data = $request->all();
        $email = $data['email'] ?? '';
        $user = User::where('email', $email)->first();
        if ($user) {
            $user->password = Hash::make($data['password']);
            $user->save();

            return redirect('login')->with('success', 'Password changed successfully');
        } else {
            return redirect('login')->with('error', 'User not found');
        }
    }
}
