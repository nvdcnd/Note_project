<?php

namespace App\Http\Controllers;

use App\Models\Theme4user;
use App\Models\Theme4userWallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Theme4userController extends Controller
{
    public function index()
    {
        $themes = Theme4user::query()->latest()->get();

        $ownedThemeIds = Theme4userWallet::query()
            ->where('userID', Auth::id())
            ->pluck('theme4ID')
            ->all();

        return view('themes.index', [
            'themes' => $themes,
            'ownedThemeIds' => $ownedThemeIds,
        ]);
    }

    public function show(Request $request, $id)
    {
        $theme = Theme4user::query()->find($id);
        if (! $theme) {
            abort(404);
        }

        $owned = Theme4userWallet::query()
            ->where('userID', Auth::id())
            ->where('theme4ID', $theme->id)
            ->exists();

        return view('themes.show', [
            'theme' => $theme,
            'owned' => $owned,
        ]);
    }
}
