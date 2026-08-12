<?php

namespace App\Providers;


use App\Models\Organization;
use App\Models\User;
use App\Support\ThemeStyle;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use ImageKit\ImageKit;
use Illuminate\View\View as ViewInstance;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Imagekit::class, function ($app) {
            return new Imagekit(
                publicKey: config('services.imagekit.public_key'),
                privateKey: config('services.imagekit.private_key'),
                urlEndpoint: config('services.imagekit.url_endpoint'),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Giao diện dùng Bootstrap 5, không phải Tailwind (mặc định của Laravel),
        // nên phải khai báo để link phân trang render đúng theme.
        Paginator::useBootstrapFive();

        // Chủ đề đang áp dụng được tính một lần cho layout chính, nên không
        // controller nào phải tự truyền xuống. Nếu view đang render trong phạm vi
        // một tổ chức thì chủ đề của tổ chức được ưu tiên hơn chủ đề cá nhân.
        View::composer('layouts.app', function (ViewInstance $view) {
            $organization = $view->getData()['organization'] ?? null;
            $user = Auth::user();

            $theme = ThemeStyle::resolveFor(
                $user instanceof User ? $user : null,
                $organization instanceof Organization ? $organization : null,
            );

            $view->with('nkThemeCss', ThemeStyle::toCssVariables($theme['style']))
                ->with('nkThemeDragType', $theme['drag_type'])
                ->with('nkThemeName', $theme['name']);
        });
    }
}
