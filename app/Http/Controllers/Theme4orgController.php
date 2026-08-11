<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\Theme4org;
use App\Models\Theme4orgWallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Theme4orgController extends Controller
{
    public function index(Request $request)
    {
        $organization = Organization::query()->find($request->integer('organizationID'));

        $themes = Theme4org::query()->latest()->get();

        $ownedThemeIds = collect();
        if ($organization) {
            $ownedThemeIds = Theme4orgWallet::query()
                ->where('organizationID', $organization->id)
                ->pluck('theme4ID');
        }

        return view('themes.org.index', [
            'themes' => $themes,
            'organization' => $organization,
            'ownedThemeIds' => $ownedThemeIds,
        ]);
    }

    public function show(Request $request, $id)
    {
        $theme = Theme4org::query()->find($id);
        if (! $theme) {
            abort(404);
        }

        $organization = Organization::query()->find($request->integer('organizationID'));

        $owned = $organization
            ? Theme4orgWallet::query()
                ->where('organizationID', $organization->id)
                ->where('theme4ID', $theme->id)
                ->exists()
            : false;

        return view('themes.org.show', [
            'theme' => $theme,
            'organization' => $organization,
            'owned' => $owned,
        ]);
    }
}
