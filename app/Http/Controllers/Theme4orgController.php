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

    public function setTheme4org(Request $request, $theme_id, $org_id){
        $org = Organization::find($org_id);
        $theme4org = Theme4orgWallet::where('themeID',$theme_id)->where('OrganizationID',$org_id)->first();
        $user = Auth::user();
        if($theme4org && $user->id == $org->hostID){
            $org->themeID = $theme4org->themeID;
            $org->save();
            return response()->json(["themeID"=>$theme4org->themeID],200);
        } else {
            return response()->json(["error"=>'error'],500);
        }
    }
}

