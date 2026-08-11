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
            'isHost' => $organization !== null && $organization->hostID === Auth::id(),
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
            'isApplied' => $organization !== null && (int) $organization->themeID === $theme->id,
            'isHost' => $organization !== null && $organization->hostID === Auth::id(),
        ]);
    }

    /**
     * Áp dụng một chủ đề mà tổ chức đã sở hữu. Chỉ chủ sở hữu tổ chức được phép.
     */
    public function apply_theme(Request $request, $organizationID, $themeID)
    {
        $organization = Organization::query()->find($organizationID);
        if (! $organization) {
            abort(404);
        }

        $theme = Theme4org::query()->find($themeID);
        if (! $theme) {
            abort(404);
        }

        if ($organization->hostID !== Auth::id()) {
            return redirect()->route('organization', $organization->id)
                ->with('error', 'Chỉ chủ sở hữu tổ chức mới có thể đổi chủ đề.');
        }

        // Cột trong theme4org_wallets là `theme4ID` / `organizationID`.
        $owned = Theme4orgWallet::query()
            ->where('organizationID', $organization->id)
            ->where('theme4ID', $theme->id)
            ->exists();

        if (! $owned) {
            return redirect()->route('themes.org.show', ['id' => $theme->id, 'organizationID' => $organization->id])
                ->with('error', 'Tổ chức cần mua chủ đề này trước khi áp dụng.');
        }

        $organization->themeID = $theme->id;
        $organization->save();

        return redirect()->route('organization.settings', $organization->id)
            ->with('success', 'Đã áp dụng chủ đề "'.$theme->name.'" cho tổ chức.');
    }

    /**
     * Gỡ chủ đề của tổ chức, quay về giao diện mặc định.
     */
    public function reset_theme(Request $request, $organizationID)
    {
        $organization = Organization::query()->find($organizationID);
        if (! $organization) {
            abort(404);
        }

        if ($organization->hostID !== Auth::id()) {
            return redirect()->route('organization', $organization->id)
                ->with('error', 'Chỉ chủ sở hữu tổ chức mới có thể đổi chủ đề.');
        }

        $organization->themeID = null;
        $organization->save();

        return redirect()->route('organization.settings', $organization->id)
            ->with('success', 'Tổ chức đã quay lại giao diện mặc định.');
    }
}
