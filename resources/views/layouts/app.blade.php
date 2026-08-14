<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@400..700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link href="{{ asset('css/noteket.css') }}" rel="stylesheet">
    {{-- Chủ đề đang áp dụng: ghi đè các biến --nk-* của noteket.css.
         Phải đặt SAU thẻ link noteket.css thì mới thắng được khối :root mặc định.
         Giá trị đã được ThemeStyle::sanitize() lọc chỉ còn mã màu hex hợp lệ. --}}
    <style>{{ $nkThemeCss }}</style>
    <title>@yield('title', 'Noteket')</title>
    @stack('styles')
</head>
<body data-drag-type="{{ $nkThemeDragType }}">
    <!-- Toast container -->
    <div class="toast-container"></div>

    {{-- Flash messages rendered as toasts --}}
    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
        <div data-toast="success" data-message="{{ session('success') }}" style="display: none;"></div>
    @endif
    @if (session('error'))
        <div data-toast="error" data-message="{{ session('error') }}" style="display: none;"></div>
    @endif
    @if (session('warning'))
        <div data-toast="warning" data-message="{{ session('warning') }}" style="display: none;"></div>
    @endif

    {{-- Desktop shell --}}
    <section class="widescreen-only">
        <div class="row g-0">
            <div class="col-2">
                <div class="sidebar">
                    <div class="sidebar-header">
                        <a href="{{ route('home') }}" style="text-decoration: none;">
                            <h2 class="text-center" style="color: #000; font-size: 2rem; font-weight: bold;">Noteket</h2>
                        </a>
                    </div>
                    <div class="sidebar-container">
                        <ul>
                            <li><a href="{{ route('home') }}" class="{{ request()->routeIs('home') || request()->routeIs('note*') ? 'active' : '' }}"><i class="fas fa-home"></i> Trang chủ</a></li>
                            <li><a href="{{ route('organizations.index') }}" class="{{ request()->routeIs('organizations*') || request()->routeIs('organization*') ? 'active' : '' }}"><i class="fas fa-info-circle"></i> Tổ chức</a></li>
                            <li><a href="{{ route('themes.index') }}" class="{{ request()->routeIs('themes*') ? 'active' : '' }}"><i class="fas fa-cogs"></i> Chủ đề</a></li>
                            <li><a href="{{ route('balance') }}" class="{{ request()->routeIs('balance') ? 'active' : '' }}"><i class="fas fa-envelope"></i> Ví</a></li>
                            <li><a href="{{ route('settings') }}" class="{{ request()->routeIs('settings') ? 'active' : '' }}"><i class="fas fa-cog"></i> Cài đặt</a></li>
                        </ul>
                        <div class="sidebar-footer">
                            <p class="text-center" style="color: #000;">&copy; 2026 Noteket</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                @include('layouts.partials.topbar')
                <section class="content">
                    @yield('content')
                </section>
            </div>
        </div>
    </section>

    {{-- Mobile shell --}}
    <section class="mobile-view">
        @include('layouts.partials.topbar')
        <div class="view">
            <div class="note-container">
                @yield('content-mobile', '')
            </div>
            @yield('mobile-fab')
        </div>
        <div class="bottom-navbar">
            <div class="bottom-navbar-content">
                <a href="{{ route('home') }}" title="Trang chủ"><i class="fas fa-home"></i></a>
                <a href="{{ route('organizations.index') }}" title="Tổ chức"><i class="fas fa-info-circle"></i></a>
                <a href="{{ route('themes.index') }}" title="Chủ đề"><i class="fas fa-cogs"></i></a>
                <a href="{{ route('balance') }}" title="Ví"><i class="fas fa-envelope"></i></a>
                <a href="{{ route('settings') }}" title="Cài đặt"><i class="fas fa-cog"></i></a>
            </div>
        </div>
    </section>

    @yield('modals')

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.min.js" crossorigin="anonymous"></script>
    <script src="{{ asset('js/noteket.js') }}"></script>
    @stack('scripts')
</body>
</html>
