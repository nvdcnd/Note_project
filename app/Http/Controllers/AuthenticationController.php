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
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            return redirect()->route('home')->with('success', 'User logged in successfully');
        }

        return redirect()->route('login')->with('error', 'Invalid username or password');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'You have been logged out');
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

        return redirect()->route('home')->with('success', 'Account created successfully');
    }

    public function signup40acc_note(Request $request, $shareid)
    {
        $pivot = PivotForNote::where('id', $shareid)->first();
        if (! $pivot) {
            return redirect('login')->with('error', 'Invalid share id');
        }
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $pivot->shared_with,
            'password' => $validated['password'],
        ]);

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->route('note', $pivot->noteID)->with('success', 'You have accepted the invitation');
    }

    public function signup40acc_host_org(Request $request, $shareid)
    {
        $pivot = PivotChangeHostOrganization::where('id', $shareid)->first();
        if (! $pivot) {
            return redirect('login')->with('error', 'Invalid share id');
        }
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $pivot->shared_with,
            'password' => $validated['password'],
        ]);

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->route('home')->with('success', 'You have accepted the invitation');
    }

    public function signup40acc_member_org(Request $request, $shareid)
    {
        $pivot = OrganizationsMember::where('id', $shareid)->first();
        if (! $pivot) {
            return redirect('login')->with('error', 'Invalid share id');
        }
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $pivot->email,
            'password' => $validated['password'],
        ]);

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->route('home')->with('success', 'You have accepted the invitation');
    }
}
