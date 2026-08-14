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
        return Socialite::driver($provider)->redirect();
    }

    public function auth_callback(Request $request, $provider){
        $user = Socialite::driver($provider)->user();

        $new_user = User::updateOrCreate(
            [
                "provider_id" => $user->getId(),
                "provider_name" => $provider
            ],[
                'email'=>$user->getEmail(),
                'name'=>$user->getName(),
            ]
            );

        Auth::login($new_user);
    }
}
