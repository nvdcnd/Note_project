<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@400..700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link href="{{ asset('css/noteket.css') }}" rel="stylesheet">
    <title>@yield('title', 'Noteket')</title>
    @stack('styles')
</head>
<body>
    <div class="toast-container"></div>

    @if (session('success'))
        <div data-toast="success" data-message="{{ session('success') }}" style="display: none;"></div>
    @endif
    @if (session('error'))
        <div data-toast="error" data-message="{{ session('error') }}" style="display: none;"></div>
    @endif
    @if (session('status'))
        <div data-toast="success" data-message="{{ session('status') }}" style="display: none;"></div>
    @endif
    @if ($errors->any())
        <div data-toast="error" data-message="{{ $errors->first() }}" style="display: none;"></div>
    @endif

    <div class="auth-body">
        <div class="card auth-card mx-auto rounded shadow">
            <div class="card-header" style="background-color: var(--nk-yellow);">
                <h3 class="text-center" style="color: transparent;">Noteket</h3>
            </div>
            <div class="card-body rounded p-5" style="background-color: var(--nk-sticky);">
                @yield('content')
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.min.js" crossorigin="anonymous"></script>
    <script src="{{ asset('js/noteket.js') }}"></script>
    @stack('scripts')
</body>
</html>
