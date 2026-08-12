<?php

namespace App\Providers;

use App\Models\Organization;
use App\Models\User;
use App\Support\ThemeStyle;
use Illuminate\Pagination\Paginator;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as ViewInstance;
use ImageKit\ImageKit;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ImageKit::class, function ($app) {
            return new ImageKit(
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

        // Job hết số lần thử chỉ nằm im trong bảng failed_jobs, không ai thấy
        // nếu không ghi log. Với mail queue, tên job chính là tên mailable.
        Queue::failing(function (JobFailed $event) {
            Log::error('Queue job failed: '.$event->job->resolveName(), [
                'connection' => $event->connectionName,
                'exception' => $event->exception->getMessage(),
            ]);
        });

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
