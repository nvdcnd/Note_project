<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Mail\Password_change;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use App\Models\Password_change_request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class authentication extends Controller
{
    public function login(Request $request)
    {
        $data = $request->all();
        $email = $data['email'];
        $password = $data['password'];
        $remember = $request->boolean('remember');

        if(Auth::attempt(['email' => $email, 'password' => $password], $remember)){
            $request->session()->regenerate();
            return redirect()->intended('home')->with('success', 'User logged in successfully');
        }else{
            return redirect('login')->with('error', 'Invalid username or password');
        }
    }

    public function signup(Request $request){
        $name = $request->name;
        $email = $request->email;
        $password = $request->password;
        $remember = $request->boolean('remember');

        $user = new User();
        $user->name = $name;
        $user->email = $email;
        $user->password = Hash::make($password);
        $user->save();

        Auth::login($user, $remember);
        $request->session()->regenerate();

        return redirect('home')->with('success', 'User logged in successfully');
    }

    public function signup40acc_note(Request $request, $shareid){
        $pivot = pivot_for_note::where('id', $shareid)->first();
        if(!$pivot){
            return redirect('login')->with('error', 'Invalid share id');
        }
        //$data = $request->all();
        $email = $pivot->shared_with;
        $password = $request->password;
        $name = $request->name;
        $remember = $request->boolean('remember');

        $user = new User();
        $user->email = $email;
        $user->password = Hash::make($password);
        $user->name = $name;
        $user->save();

        Auth::login($user, $remember);
        $request->session()->regenerate();

        return redirect('note',$pivot->noteID)->with('success', 'You have accepted the invitation');
    }

    public function signup40acc_host_org(Request $request, $shareid){
        $pivot = pivot_change_host_organization::where('id', $shareid)->first();
        if(!$pivot){
            return redirect('login')->with('error', 'Invalid share id');
        }
        //$data = $request->all();
        $email = $pivot->shared_with;
        $password = $request->password;
        $name = $request->name;
        $remember = $request->boolean('remember');

        $user = new User();
        $user->email = $email;
        $user->password = Hash::make($password);
        $user->name = $name;
        $user->save();

        Auth::login($user);
        $request->session()->regenerate();

        return redirect('change_host_organzation_request', $pivot->id)->with('success', 'You have accepted the invitation');
    }
     
    public function signup40acc_member_org(Request $request, $shareid){
        $pivot = organizations_member::where('id', $shareid)->first();
        if(!$pivot){
            return redirect('login')->with('error', 'Invalid share id');
        }
        //$data = $request->all();
        $email = $pivot->email;
        $password = $request->password;
        $name = $request->name;
        $remember = $request->boolean('remember');

        $user = new User();
        $user->email = $email;
        $user->password = Hash::make($password);
        $user->name = $name;
        $user->save();

        Auth::login($user, $remember);
        $request->session()->regenerate();

        return redirect('organization_acceptance_request', $pivot->id)->with('success', 'You have accepted the invitation');
    }

}
