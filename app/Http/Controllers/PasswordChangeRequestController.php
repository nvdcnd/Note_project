<?php

namespace App\Http\Controllers;

use App\Mail\Password_change;
use App\Models\PasswordChangeRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Carbon;

class PasswordChangeRequestController extends Controller
{
    public function one_time_password_generator($length = 6)
    {
        $chars = '0123456789';
        $pass = '';
        for ($i = 0; $i < $length; $i++) {
            $pass .= $chars[rand(0, strlen($chars) - 1)];
        }
        $check = PasswordChangeRequest::where('token', $pass)->first();
        if ($check->used == false || now()->greaterThan(Carbon::parse($check->expires_at)) || !$check) {
            return $this->one_time_password_generator();
        } else {
            return $pass;
        }
    }

    public function forgot_password(Request $request)
    {
        $request->validate([
            "email"=>'required',
        ]);
        $data = $request->all();
        $email = $data['email'] ?? '';
        $user = User::where('email', $email)->first();
        if ($user) {
            $passkey = $this->one_time_password_generator();
            $password_change_request = new PasswordChangeRequest;
            $password_change_request->user_id = $user->id;
            $password_change_request->token = Hash::make($passkey);
            $password_change_request->expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            $password_change_request->save();
            Mail::to($email)->send(new Password_change($passkey));

            return redirect('change_password_view',$password->change_request->id)->with('success', 'Password reset token sent to your email');
        } else {
            return redirect('login_view')->with('error', 'User not found');
        }
    }

    public function change_password(Request $request, $id)
    {
        $request->validate([
            'passkey'=>'required',
            'password'=>'required'
        ]);
        $data = $request->all();
        $change_password_request = PasswordChangeRequest::find($id);
        //$email = $data['email'] ?? '';
        $user = User::find($change_password_request->user_id)->first();
        $passkey_input = $data['passkey'];
        $time = Carbon::parse($change_password_request->expires_at);
        if ($change_password_request && Hash::check($passkey_input, $change_password_request->passkey) && !(now()->greaterThan($time))) {
            if (now()->greaterThan($time)) {
                $change_password_request->delete();
                return redirect()->route('login_view')->with('error',"The OTP was expire. Please make the request again");
            } else {
                $user->password = Hash::make($data['password']);
                $user->save();
                $change_password_request->used = true;
                Auth::login($user);
                return redirect('home')->with('success', 'Password changed successfully');
            }
        } else {
            $change_password_request->delete();
            return redirect('login_view')->with('error', 'User not found');
        }
    }
}
