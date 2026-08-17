<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class OauthAuthenticationController extends Controller
{
    public function auth_redirect(Request $request, $provider){
        $providers = ['google','facebook','apple','github'];
        if(in_array($provider, $providers)){
            return Socialite::driver($provider)->redirect();
        } else {
            return redirect()->route('home')->with('error','Can not create account with'.$provider.', please try agian with normal login');
        }
    }

    public function auth_callback(Request $request, $provider){
        $providers = ['google','facebook','apple','github'];
        if(in_array($provider, $providers)){
            $user = Socialite::driver($provider)->user();
            $check = User::where('email', $user->getEmail())->first();
            if($check){
                $check->update([
                    "provider_id" => $user->getId(),
                    "provider_name" => $provider
                ]);
                // Email đã có tài khoản: nối provider vào tài khoản cũ và đăng nhập
                // bằng chính nó — không được rơi xuống Auth::login($new_user) khi
                // biến đó chưa tồn tại.
                $new_user = $check;
            } else {

                $new_user = User::updateOrCreate(
                    [
                        "provider_id" => $user->getId(),
                        "provider_name" => $provider
                    ],[
                        'email'=>$user->getEmail(),
                        'name'=>$user->getName(),
                    ]
                    );
            }

            Auth::login($new_user);
            $request->session()->regenerate();
            return redirect()->route('home')->with('success','Create account successfull');
        } else {
            return redirect()->route('home')->with('error','Can not create account with'.$provider.', please try agian with normal login');
        }
    }
}
