<?php

namespace App\Http\Controllers;

use App\Models\Theme4user;
use App\Models\Theme4userWallet;
use App\Models\User;
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
            'appliedThemeId' => $this->appliedThemeId(),
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
            'isApplied' => $this->appliedThemeId() === $theme->id,
        ]);
    }

    /**
     * Id chủ đề người dùng đang áp dụng, null nếu đang dùng giao diện mặc định.
     */
    private function appliedThemeId(): ?int
    {
        $themeId = User::query()->where('id', Auth::id())->value('theme4_id');

        return $themeId === null ? null : (int) $themeId;
    }

    /**
     * Áp dụng một chủ đề mà người dùng đã sở hữu cho tài khoản của mình.
     */
    public function apply_theme(Request $request, $id)
    {
        $theme = Theme4user::query()->find($id);
        if (! $theme) {
            abort(404);
        }

        // Cột trong theme4user_wallets là `theme4ID` (không phải `themeID`).
        $owned = Theme4userWallet::query()
            ->where('userID', Auth::id())
            ->where('theme4ID', $theme->id)
            ->exists();

        if (! $owned) {
            return redirect()->route('themes.show', $theme->id)
                ->with('error', 'Bạn cần mua chủ đề này trước khi áp dụng.');
        }

        // Lấy model User thật thay vì Auth::user() (trả về interface Authenticatable,
        // không đảm bảo có save()).
        $user = User::query()->findOrFail(Auth::id());
        $user->theme4_id = $theme->id;
        $user->save();

        return redirect()->route('themes.show', $theme->id)
            ->with('success', 'Đã áp dụng chủ đề "'.$theme->name.'".');
    }

    /**
     * Gỡ chủ đề đang áp dụng, quay về giao diện mặc định của Noteket.
     */
    public function reset_theme(Request $request)
    {
        $user = User::query()->findOrFail(Auth::id());
        $user->theme4_id = null;
        $user->save();

        return redirect()->route('themes.index')
            ->with('success', 'Đã quay lại giao diện mặc định.');
    }
}
