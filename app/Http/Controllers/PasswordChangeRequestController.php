<?php

namespace App\Http\Controllers;

use App\Mail\Password_change;
use App\Models\PasswordChangeRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
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

        $existingRequests = PasswordChangeRequest::query()->where('used', false)->get();

        foreach ($existingRequests as $requestModel) {
            if (Hash::check($pass, $requestModel->token)) {
                return $this->one_time_password_generator();
            }
        }

        return $pass;
    }

    public function forgot_password(Request $request)
    {
        $request->validate([
            'email' => 'required',
        ]);
        $data = $request->all();
        $email = $data['email'] ?? '';
        $user = User::query()->where('email', $email)->first();
        if ($user) {
            $passkey = $this->one_time_password_generator();
            $password_change_request = new PasswordChangeRequest;
            $password_change_request->user_id = $user->id;
            $password_change_request->token = Hash::make($passkey);
            $password_change_request->expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            $password_change_request->save();
            Mail::to($email)->send(new Password_change($passkey));

            return redirect('/login')->with('success', 'Password reset token sent to your email');
        } else {
            return redirect('login_view')->with('error', 'User not found');
        }
    }

    public function change_password(Request $request, $id)
    {
        $request->validate([
            'passkey' => 'required',
            'password' => 'required',
        ]);
        $data = $request->all();
        /** @var PasswordChangeRequest|null $change_password_request */
        $change_password_request = PasswordChangeRequest::query()->find($id);
        if (! $change_password_request) {
            return redirect('/login')->with('error', 'Invalid password reset request');
        }

        $user = User::query()->find($change_password_request->user_id);
        if (! $user) {
            PasswordChangeRequest::destroy($change_password_request->id);

            return redirect('/login')->with('error', 'User not found');
        }

        $passkey_input = $data['passkey'];
        $time = Carbon::parse($change_password_request->expires_at);

        if (Hash::check($passkey_input, $change_password_request->token) && ! (now()->greaterThan($time))) {
            $user->password = Hash::make($data['password']);
            $user->save();
            $change_password_request->used = true;
            $change_password_request->save();
            Auth::login($user);

            return redirect('home')->with('success', 'Password changed successfully');
        }

        PasswordChangeRequest::destroy($change_password_request->id);

        return redirect('/login')->with('error', 'Invalid or expired OTP');
    }
}
