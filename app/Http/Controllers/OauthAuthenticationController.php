<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Socialite;

class OauthAuthenticationController extends Controller
{
    public function auth_redirect(Request $request, $provider){
        return Socialite::driver($provider)->redirect();
    }

    public function auth_callback(Request $request, $provider){
        $user = Socialite::driver($provider)->user();

        $new_user = User::updateOrCreate(
            [
                "provider_id" => $user->id,
                "provider_name" => $provider
            ],[
                'email'=>$user->email,
                'name'=>$user->name,
            ]
            );

        Auth::login($new_user);
    }
}
