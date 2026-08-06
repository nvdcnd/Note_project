<?php

namespace App\Http\Controllers;

use App\Models\OrganizationsMember;
use App\Models\PivotChangeHostOrganization;
use App\Models\PivotForNote;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthenticationController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required',
            'password' => 'required',
            'remember' => 'required',
        ]);
        $data = $request->all();
        $email = $data['email'];
        $password = $data['password'];
        $remember = $request->boolean('remember');

        if (Auth::attempt(['email' => $email, 'password' => $password], $remember)) {
            $request->session()->regenerate();

            return redirect()->route('home')->with('success', 'User logged in successfully');
        } else {
            return redirect('login')->with('error', 'Invalid username or password');
        }
    }

    public function signup(Request $request)
    {
        $request->validate([
            'email' => 'required',
            'password' => 'required',
            'remember' => 'required',
        ]);
        $name = $request->name;
        $email = $request->email;
        $password = $request->password;
        $remember = $request->boolean('remember');

        $user = new User;
        $user->name = $name;
        $user->email = $email;
        $user->password = Hash::make($password);
        $user->save();

        Auth::login($user, $remember);
        $request->session()->regenerate();

        return redirect()->route('home')->with('success', 'User logged in successfully');
    }

    public function signup40acc_note(Request $request, $shareid)
    {
        $pivot = PivotForNote::where('id', $shareid)->first();
        if (! $pivot) {
            return redirect('login')->with('error', 'Invalid share id');
        }
        // $data = $request->all();
        $request->validate([
            'email' => 'required',
            'password' => 'required',
            'remember' => 'required',
        ]);
        $email = $pivot->shared_with;
        $password = $request->password;
        $name = $request->name;
        $remember = $request->boolean('remember');

        $user = new User;
        $user->email = $email;
        $user->password = Hash::make($password);
        $user->name = $name;
        $user->save();

        Auth::login($user, $remember);
        $request->session()->regenerate();

        return redirect()->route('note', $pivot->noteID)->with('success', 'You have accepted the invitation');
    }

    public function signup40acc_host_org(Request $request, $shareid)
    {
        $pivot = PivotChangeHostOrganization::where('id', $shareid)->first();
        if (! $pivot) {
            return redirect('login')->with('error', 'Invalid share id');
        }
        // $data = $request->all();
        $request->validate([
            'email' => 'required',
            'password' => 'required',
            'remember' => 'required',
        ]);
        $email = $pivot->shared_with;
        $password = $request->password;
        $name = $request->name;
        $remember = $request->boolean('remember');

        $user = new User;
        $user->email = $email;
        $user->password = Hash::make($password);
        $user->name = $name;
        $user->save();

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('home')->with('success', 'You have accepted the invitation');
    }

    public function signup40acc_member_org(Request $request, $shareid)
    {
        $pivot = OrganizationsMember::where('id', $shareid)->first();
        if (! $pivot) {
            return redirect('login')->with('error', 'Invalid share id');
        }
        $request->validate([
            'email' => 'required',
            'password' => 'required',
            'remember' => 'required',
        ]);
        // $data = $request->all();
        $email = $pivot->email;
        $password = $request->password;
        $name = $request->name;
        $remember = $request->boolean('remember');

        $user = new User;
        $user->email = $email;
        $user->password = Hash::make($password);
        $user->name = $name;
        $user->save();

        Auth::login($user, $remember);
        $request->session()->regenerate();

        return redirect()->route('home')->with('success', 'You have accepted the invitation');
    }
}
